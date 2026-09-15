<?php

namespace App\Http\Controllers\Hr;

use App\AttendanceLog;
use App\AttendanceShift;
use App\BusinessLocation;
use App\Employee;
use App\Holiday;
use App\Http\Controllers\Controller;
use App\Utils\AttendanceUtil;
use App\Utils\Util;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AttendanceController extends Controller
{
    protected $attendanceUtil;

    protected $commonUtil;

    public function __construct(AttendanceUtil $attendanceUtil, Util $commonUtil)
    {
        $this->attendanceUtil = $attendanceUtil;
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of attendance logs.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('attendance.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $logs = AttendanceLog::where('attendance_logs.business_id', $business_id)
                ->join('employees as e', 'attendance_logs.employee_id', '=', 'e.id')
                ->select([
                    'attendance_logs.id',
                    DB::raw("CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) as employee_name"),
                    'attendance_logs.attendance_date', 'attendance_logs.clock_in', 'attendance_logs.clock_out',
                    'attendance_logs.total_hours', 'attendance_logs.status',
                ]);

            if (! empty(request()->employee_id)) {
                $logs->where('attendance_logs.employee_id', request()->employee_id);
            }

            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $logs->whereDate('attendance_logs.attendance_date', '>=', request()->start_date)
                    ->whereDate('attendance_logs.attendance_date', '<=', request()->end_date);
            }

            return DataTables::of($logs)
                ->editColumn('clock_in', '@if(!empty($clock_in)){{@format_datetime($clock_in)}}@endif')
                ->editColumn('clock_out', '@if(!empty($clock_out)){{@format_datetime($clock_out)}}@endif')
                ->addColumn('action', function ($row) {
                    if (! auth()->user()->can('attendance.manage')) {
                        return '';
                    }

                    return '<a href="'.action([\App\Http\Controllers\Hr\AttendanceController::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</a>';
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        $employees = Employee::forDropdown($business_id, false);

        return view('hr.attendance.index')->with(compact('employees'));
    }

    /**
     * The current user clocks themselves in for today.
     */
    public function clockIn(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $employee = Employee::where('business_id', $business_id)->where('user_id', auth()->user()->id)->firstOrFail();

            $log = $this->attendanceUtil->clockIn($employee->id, $business_id, session('user.location_id'));

            $output = ['success' => true, 'msg' => __('hr.clocked_in_success'), 'clock_in' => $this->commonUtil->format_date($log->clock_in, true)];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => $e->getMessage()];
        }

        return $output;
    }

    /**
     * The current user clocks themselves out for today.
     */
    public function clockOut(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $employee = Employee::where('business_id', $business_id)->where('user_id', auth()->user()->id)->firstOrFail();

            $log = $this->attendanceUtil->clockOut($employee->id);

            $output = ['success' => true, 'msg' => __('hr.clocked_out_success'), 'total_hours' => $log->total_hours];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => $e->getMessage()];
        }

        return $output;
    }

    /**
     * Manual attendance entry form (admin, for any employee).
     */
    public function create()
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $employees = Employee::forDropdown($business_id);

        return view('hr.attendance.create')->with(compact('employees'));
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $employee = Employee::where('business_id', $business_id)->findOrFail($request->input('employee_id'));

            $date = $request->input('attendance_date');
            $clock_in = ! empty($request->input('clock_in')) ? $date.' '.$request->input('clock_in') : null;
            $clock_out = ! empty($request->input('clock_out')) ? $date.' '.$request->input('clock_out') : null;
            $total_hours = (! empty($clock_in) && ! empty($clock_out)) ? round(\Carbon::parse($clock_in)->diffInMinutes(\Carbon::parse($clock_out)) / 60, 2) : null;

            AttendanceLog::updateOrCreate(
                ['employee_id' => $employee->id, 'attendance_date' => $date],
                [
                    'business_id' => $business_id,
                    'location_id' => $employee->location_id,
                    'clock_in' => $clock_in,
                    'clock_out' => $clock_out,
                    'total_hours' => $total_hours,
                    'source' => 'manual',
                    'status' => $request->input('status', 'present'),
                    'approved_by' => auth()->user()->id,
                ]
            );

            $output = ['success' => true, 'msg' => __('hr.attendance_saved_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Hr\AttendanceController::class, 'index'])->with('status', $output);
    }

    public function edit($id)
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $log = AttendanceLog::where('business_id', $business_id)->with('employee')->findOrFail($id);
        $employees = Employee::forDropdown($business_id);

        return view('hr.attendance.edit')->with(compact('log', 'employees'));
    }

    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $log = AttendanceLog::where('business_id', $business_id)->findOrFail($id);

            $date = $log->attendance_date->toDateString();
            $clock_in = ! empty($request->input('clock_in')) ? $date.' '.$request->input('clock_in') : null;
            $clock_out = ! empty($request->input('clock_out')) ? $date.' '.$request->input('clock_out') : null;

            $log->clock_in = $clock_in;
            $log->clock_out = $clock_out;
            $log->total_hours = (! empty($clock_in) && ! empty($clock_out)) ? round(\Carbon::parse($clock_in)->diffInMinutes(\Carbon::parse($clock_out)) / 60, 2) : null;
            $log->status = $request->input('status', $log->status);
            $log->approved_by = auth()->user()->id;
            $log->save();

            $output = ['success' => true, 'msg' => __('hr.attendance_saved_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Hr\AttendanceController::class, 'index'])->with('status', $output);
    }

    /**
     * CSV import form.
     */
    public function importForm()
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        return view('hr.attendance.import');
    }

    public function import(Request $request)
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $request->validate(['attendance_file' => 'required|file']);

            $result = $this->attendanceUtil->bulkImportAttendance($request->file('attendance_file'), $business_id);

            $msg = __('hr.import_success_count', ['count' => $result['imported']]);
            if (! empty($result['errors'])) {
                $msg .= ' '.__('hr.import_errors_count', ['count' => count($result['errors'])]);
            }

            $output = ['success' => true, 'msg' => $msg];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Hr\AttendanceController::class, 'index'])->with('status', $output);
    }

    /*
    |--------------------------------------------------------------------------
    | Shifts
    |--------------------------------------------------------------------------
    */
    public function shiftsIndex()
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $shifts = AttendanceShift::where('business_id', $business_id)
                ->select(['id', 'name', 'start_time', 'end_time', 'grace_minutes']);

            return DataTables::of($shifts)
                ->addColumn('action', function ($row) {
                    return '<button data-href="'.action([\App\Http\Controllers\Hr\AttendanceController::class, 'editShift'], [$row->id]).'" class="btn btn-xs btn-primary btn-modal" data-container=".attendance_shift_modal"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</button>
                        &nbsp;
                        <button data-href="'.action([\App\Http\Controllers\Hr\AttendanceController::class, 'destroyShift'], [$row->id]).'" class="btn btn-xs btn-danger delete_attendance_shift_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('hr.attendance.shifts.index');
    }

    public function createShift()
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        return view('hr.attendance.shifts.create');
    }

    public function storeShift(Request $request)
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'start_time', 'end_time', 'grace_minutes']);
            $input['business_id'] = $request->session()->get('user.business_id');

            AttendanceShift::create($input);

            $output = ['success' => true, 'msg' => __('hr.shift_added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function editShift($id)
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $shift = AttendanceShift::where('business_id', $business_id)->findOrFail($id);

            return view('hr.attendance.shifts.edit')->with(compact('shift'));
        }
    }

    public function updateShift(Request $request, $id)
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $shift = AttendanceShift::where('business_id', $business_id)->findOrFail($id);
            $shift->fill($request->only(['name', 'start_time', 'end_time', 'grace_minutes']));
            $shift->save();

            $output = ['success' => true, 'msg' => __('hr.shift_updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function destroyShift($id)
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            AttendanceShift::where('business_id', $business_id)->findOrFail($id)->delete();

            $output = ['success' => true, 'msg' => __('hr.shift_deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /*
    |--------------------------------------------------------------------------
    | Holidays
    |--------------------------------------------------------------------------
    */
    public function holidaysIndex()
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $holidays = Holiday::where('business_id', $business_id)
                ->select(['id', 'name', 'date', 'is_recurring_yearly']);

            return DataTables::of($holidays)
                ->editColumn('date', '{{@format_date($date)}}')
                ->editColumn('is_recurring_yearly', function ($row) {
                    return $row->is_recurring_yearly ? __('lang_v1.yes') : __('lang_v1.no');
                })
                ->addColumn('action', function ($row) {
                    return '<button data-href="'.action([\App\Http\Controllers\Hr\AttendanceController::class, 'destroyHoliday'], [$row->id]).'" class="btn btn-xs btn-danger delete_holiday_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('hr.attendance.holidays.index')->with(compact('business_locations'));
    }

    public function storeHoliday(Request $request)
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'date', 'location_id']);
            $input['business_id'] = $request->session()->get('user.business_id');
            $input['is_recurring_yearly'] = $request->boolean('is_recurring_yearly');

            Holiday::create($input);

            $output = ['success' => true, 'msg' => __('hr.holiday_added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function destroyHoliday($id)
    {
        if (! auth()->user()->can('attendance.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            Holiday::where('business_id', $business_id)->findOrFail($id)->delete();

            $output = ['success' => true, 'msg' => __('hr.holiday_deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}
