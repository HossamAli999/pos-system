<?php

namespace App\Utils;

use App\AttendanceLog;
use App\Employee;
use App\LeaveRequest;
use Excel;

class AttendanceUtil extends Util
{
    public function clockIn($employee_id, $business_id, $location_id = null)
    {
        $today = \Carbon::now()->toDateString();

        $log = AttendanceLog::where('employee_id', $employee_id)
            ->where('attendance_date', $today)
            ->first();

        if (! empty($log) && ! empty($log->clock_in)) {
            throw new \Exception(__('hr.already_clocked_in'));
        }

        if (empty($log)) {
            $log = new AttendanceLog([
                'business_id' => $business_id,
                'employee_id' => $employee_id,
                'attendance_date' => $today,
            ]);
        }

        $log->location_id = $location_id;
        $log->clock_in = \Carbon::now();
        $log->source = 'web';
        $log->status = 'present';
        $log->save();

        return $log;
    }

    public function clockOut($employee_id)
    {
        $today = \Carbon::now()->toDateString();

        $log = AttendanceLog::where('employee_id', $employee_id)
            ->where('attendance_date', $today)
            ->first();

        if (empty($log) || empty($log->clock_in)) {
            throw new \Exception(__('hr.not_clocked_in'));
        }

        if (! empty($log->clock_out)) {
            throw new \Exception(__('hr.already_clocked_out'));
        }

        $clock_in = \Carbon::parse($log->clock_in);
        $log->clock_out = \Carbon::now();
        $log->total_hours = round($clock_in->diffInMinutes($log->clock_out) / 60, 2);
        $log->save();

        return $log;
    }

    public function getMonthlyAttendanceSummary($business_id, $month, $year, $employee_id = null)
    {
        $query = AttendanceLog::where('business_id', $business_id)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year);

        if (! empty($employee_id)) {
            $query->where('employee_id', $employee_id);
        }

        return $query->selectRaw('employee_id, status, count(*) as total_days, sum(total_hours) as total_hours')
            ->groupBy('employee_id', 'status')
            ->get();
    }

    /**
     * Counts unpaid days for an employee within a date range — attendance logs
     * explicitly marked 'absent', plus approved leave requests against an unpaid
     * leave type that overlap the range. Used by PayrollUtil to pro-rate pay.
     *
     * Simplification: a day covered by both an 'absent' log and an unpaid leave
     * request is counted twice; in practice these shouldn't overlap since an
     * approved leave request is the reason an employee wouldn't be marked absent.
     */
    public function getUnpaidDaysInPeriod($employee_id, $start_date, $end_date)
    {
        $absent_days = AttendanceLog::where('employee_id', $employee_id)
            ->where('status', 'absent')
            ->whereBetween('attendance_date', [$start_date, $end_date])
            ->count();

        $period_start = \Carbon::parse($start_date);
        $period_end = \Carbon::parse($end_date);

        $unpaid_leave_days = LeaveRequest::where('employee_id', $employee_id)
            ->where('status', 'approved')
            ->whereHas('leave_type', function ($q) {
                $q->where('is_paid', 0);
            })
            ->where('start_date', '<=', $end_date)
            ->where('end_date', '>=', $start_date)
            ->get()
            ->sum(function ($leave) use ($period_start, $period_end) {
                $overlap_start = $leave->start_date->greaterThan($period_start) ? $leave->start_date : $period_start;
                $overlap_end = $leave->end_date->lessThan($period_end) ? $leave->end_date : $period_end;

                if ($overlap_end->lessThan($overlap_start)) {
                    return 0;
                }

                return $overlap_start->diffInDays($overlap_end) + 1;
            });

        return $absent_days + $unpaid_leave_days;
    }

    /**
     * Imports attendance from a CSV/Excel file with columns:
     * employee_code, date (YYYY-MM-DD), clock_in (HH:MM, optional),
     * clock_out (HH:MM, optional), status (optional, defaults to 'present').
     */
    public function bulkImportAttendance($file, $business_id)
    {
        $parsed_array = Excel::toArray([], $file);
        $rows = array_splice($parsed_array[0], 1);

        $imported = 0;
        $errors = [];

        foreach ($rows as $key => $row) {
            $row_no = $key + 2;

            if (empty($row[0])) {
                continue;
            }

            $employee = Employee::where('business_id', $business_id)->where('employee_code', $row[0])->first();
            if (empty($employee)) {
                $errors[] = __('hr.import_row_employee_not_found', ['row' => $row_no]);

                continue;
            }

            if (empty($row[1])) {
                $errors[] = __('hr.import_row_date_required', ['row' => $row_no]);

                continue;
            }

            try {
                $date = \Carbon::parse($row[1])->toDateString();
            } catch (\Exception $e) {
                $errors[] = __('hr.import_row_invalid_date', ['row' => $row_no]);

                continue;
            }

            $clock_in = ! empty($row[2]) ? \Carbon::parse($date.' '.$row[2]) : null;
            $clock_out = ! empty($row[3]) ? \Carbon::parse($date.' '.$row[3]) : null;
            $total_hours = (! empty($clock_in) && ! empty($clock_out)) ? round($clock_in->diffInMinutes($clock_out) / 60, 2) : null;

            AttendanceLog::updateOrCreate(
                ['employee_id' => $employee->id, 'attendance_date' => $date],
                [
                    'business_id' => $business_id,
                    'location_id' => $employee->location_id,
                    'clock_in' => $clock_in,
                    'clock_out' => $clock_out,
                    'total_hours' => $total_hours,
                    'source' => 'import',
                    'status' => ! empty($row[4]) ? $row[4] : 'present',
                ]
            );
            $imported++;
        }

        return ['imported' => $imported, 'errors' => $errors];
    }
}
