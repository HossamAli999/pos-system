<?php

namespace App\Utils;

use App\Employee;
use App\EmployeeLeaveBalance;
use App\LeaveRequest;
use App\LeaveType;

class LeaveUtil extends Util
{
    protected $approvalUtil;

    public function __construct(ApprovalUtil $approvalUtil)
    {
        $this->approvalUtil = $approvalUtil;
    }

    public function getOrCreateBalance($employee_id, $leave_type_id, $year)
    {
        $balance = EmployeeLeaveBalance::where('employee_id', $employee_id)
            ->where('leave_type_id', $leave_type_id)
            ->where('year', $year)
            ->first();

        if (empty($balance)) {
            $leave_type = LeaveType::findOrFail($leave_type_id);
            $balance = EmployeeLeaveBalance::create([
                'employee_id' => $employee_id,
                'leave_type_id' => $leave_type_id,
                'year' => $year,
                'allocated_days' => $leave_type->days_allowed_per_year,
                'used_days' => 0,
                'carried_forward_days' => 0,
            ]);
        }

        return $balance;
    }

    /**
     * Creates a leave request and, if the business has an active approval workflow
     * configured for the 'leave_request' module, routes it through App\Utils\ApprovalUtil
     * — otherwise the request stays 'pending' until a leave_request.approve user decides
     * on it directly via decide().
     */
    public function requestLeave($business_id, $employee_id, $leave_type_id, $start_date, $end_date, $reason, $user_id)
    {
        $start = \Carbon::parse($start_date);
        $end = \Carbon::parse($end_date);
        $days_requested = $start->diffInDays($end) + 1;

        $leave_request = LeaveRequest::create([
            'business_id' => $business_id,
            'employee_id' => $employee_id,
            'leave_type_id' => $leave_type_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'days_requested' => $days_requested,
            'reason' => $reason,
            'status' => 'pending',
            'created_by' => $user_id,
        ]);

        $approval_request = $this->approvalUtil->startApproval('leave_request', $leave_request, $user_id, $days_requested);

        if (! empty($approval_request)) {
            $leave_request->approval_request_id = $approval_request->id;
            $leave_request->save();
        }

        return $leave_request;
    }

    /**
     * Records a direct decision on a leave request. If the request was routed through
     * the approval engine, this records the decision on the current approval step
     * (which may or may not finalize the request, depending on how many steps remain)
     * rather than deciding the leave request itself.
     */
    public function decide($leave_request_id, $decision, $user_id, $business_id)
    {
        $status = $decision == 'approved' ? 'approved' : 'rejected';

        $leave_request = LeaveRequest::where('business_id', $business_id)->findOrFail($leave_request_id);

        if ($leave_request->status != 'pending') {
            throw new \Exception(__('approval.request_already_completed'));
        }

        if (! empty($leave_request->approval_request_id)) {
            $this->approvalUtil->recordDecision($leave_request->approval_request_id, $user_id, $status);

            return $leave_request->fresh();
        }

        $this->finalizeDecision($leave_request, $status, $user_id);

        return $leave_request->fresh();
    }

    /**
     * Applies a final approve/reject decision to the leave request itself and, on
     * approval, deducts the days from the employee's leave balance for that year.
     * Called either directly from decide() (no workflow configured) or by
     * App\Listeners\Approval\LeaveRequestApprovalHandler once a multi-step
     * workflow fully resolves.
     */
    public function finalizeDecision(LeaveRequest $leave_request, $status, $user_id)
    {
        $leave_request->status = $status;
        $leave_request->decided_by = $user_id;
        $leave_request->decided_at = \Carbon::now();
        $leave_request->save();

        if ($status == 'approved') {
            $year = $leave_request->start_date->year;
            $balance = $this->getOrCreateBalance($leave_request->employee_id, $leave_request->leave_type_id, $year);
            $balance->used_days += $leave_request->days_requested;
            $balance->save();
        }

        return $leave_request;
    }

    public function cancel($leave_request_id, $business_id)
    {
        $leave_request = LeaveRequest::where('business_id', $business_id)->findOrFail($leave_request_id);

        if ($leave_request->status == 'pending') {
            $leave_request->status = 'cancelled';
            $leave_request->save();

            if (! empty($leave_request->approval_request_id)) {
                $this->approvalUtil->cancel($leave_request->approval_request_id);
            }
        }

        return $leave_request;
    }

    /**
     * Allocates each active employee's yearly leave balance for every leave type,
     * carrying forward unused days from the previous year where the leave type
     * allows it (capped at max_carry_forward_days). Idempotent — skips any
     * employee/leave-type/year combination that already has a balance row.
     */
    public function allocateYearlyLeave($business_id, $year)
    {
        $employees = Employee::where('business_id', $business_id)->where('status', 'active')->get();
        $leave_types = LeaveType::where('business_id', $business_id)->get();

        foreach ($employees as $employee) {
            foreach ($leave_types as $leave_type) {
                $exists = EmployeeLeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type_id', $leave_type->id)
                    ->where('year', $year)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $carried = 0;
                if ($leave_type->carry_forward) {
                    $prev = EmployeeLeaveBalance::where('employee_id', $employee->id)
                        ->where('leave_type_id', $leave_type->id)
                        ->where('year', $year - 1)
                        ->first();

                    if (! empty($prev)) {
                        $remaining = $prev->allocated_days + $prev->carried_forward_days - $prev->used_days;
                        $carried = max(0, $remaining);
                        if (! empty($leave_type->max_carry_forward_days)) {
                            $carried = min($carried, $leave_type->max_carry_forward_days);
                        }
                    }
                }

                EmployeeLeaveBalance::create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leave_type->id,
                    'year' => $year,
                    'allocated_days' => $leave_type->days_allowed_per_year,
                    'used_days' => 0,
                    'carried_forward_days' => $carried,
                ]);
            }
        }
    }
}
