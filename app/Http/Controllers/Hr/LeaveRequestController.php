<?php

namespace App\Http\Controllers\Hr;

use App\Employee;
use App\Http\Controllers\Controller;
use App\LeaveRequest;
use App\LeaveType;
use App\Utils\LeaveUtil;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LeaveRequestController extends Controller
{
    protected $leaveUtil;

    public function __construct(LeaveUtil $leaveUtil)
    {
        $this->leaveUtil = $leaveUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! (auth()->user()->can('leave_request.view_all') || auth()->user()->can('leave_request.view_own'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $can_view_all = auth()->user()->can('leave_request.view_all');

        if (request()->ajax()) {
            $requests = LeaveRequest::where('leave_requests.business_id', $business_id)
                ->join('employees as e', 'leave_requests.employee_id', '=', 'e.id')
                ->join('leave_types as lt', 'leave_requests.leave_type_id', '=', 'lt.id')
                ->select([
                    'leave_requests.id',
                    DB::raw("CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) as employee_name"),
                    'lt.name as leave_type_name', 'leave_requests.start_date', 'leave_requests.end_date',
                    'leave_requests.days_requested', 'leave_requests.status',
                ]);

            if (! $can_view_all) {
                $employee_id = Employee::where('business_id', $business_id)->where('user_id', auth()->user()->id)->value('id');
                $requests->where('leave_requests.employee_id', $employee_id);
            }

            return DataTables::of($requests)
                ->editColumn('start_date', '{{@format_date($start_date)}}')
                ->editColumn('end_date', '{{@format_date($end_date)}}')
                ->editColumn('status', function ($row) {
                    $labels = ['pending' => 'bg-yellow', 'approved' => 'bg-green', 'rejected' => 'bg-red', 'cancelled' => 'bg-gray'];

                    return '<span class="label '.($labels[$row->status] ?? 'bg-gray').'">'.__('approval.status_'.$row->status).'</span>';
                })
                ->addColumn('action', function ($row) {
                    $html = '';
                    if ($row->status == 'pending' && auth()->user()->can('leave_request.approve')) {
                        $html .= '<button type="button" class="btn btn-xs btn-success btn-decide-leave" data-id="'.$row->id.'" data-action="approved"><i class="fa fa-check"></i> '.__('approval.approve').'</button> ';
                        $html .= '<button type="button" class="btn btn-xs btn-danger btn-decide-leave" data-id="'.$row->id.'" data-action="rejected"><i class="fa fa-times"></i> '.__('approval.reject').'</button>';
                    }

                    return $html;
                })
                ->removeColumn('id')
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('hr.leave_requests.index')->with(compact('can_view_all'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        $employee = Employee::where('business_id', $business_id)->where('user_id', auth()->user()->id)->first();

        if (empty($employee) && ! auth()->user()->can('leave_request.view_all')) {
            abort(403, __('hr.no_linked_employee_record'));
        }

        $employees = auth()->user()->can('leave_request.view_all') ? Employee::forDropdown($business_id) : null;
        $leave_types = LeaveType::forDropdown($business_id);

        return view('hr.leave_requests.create')->with(compact('employee', 'employees', 'leave_types'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            $employee_id = $request->input('employee_id');
            if (empty($employee_id) || ! auth()->user()->can('leave_request.view_all')) {
                $employee_id = Employee::where('business_id', $business_id)->where('user_id', $user_id)->value('id');
            }

            if (empty($employee_id)) {
                throw new \Exception(__('hr.no_linked_employee_record'));
            }

            $this->leaveUtil->requestLeave(
                $business_id,
                $employee_id,
                $request->input('leave_type_id'),
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('reason'),
                $user_id
            );

            $output = ['success' => true, 'msg' => __('hr.leave_request_added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => $e->getMessage() ?: __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Hr\LeaveRequestController::class, 'index'])->with('status', $output);
    }

    /**
     * Records an approve/reject decision on a leave request.
     */
    public function decide(Request $request, $id)
    {
        if (! auth()->user()->can('leave_request.approve')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $this->leaveUtil->decide($id, $request->input('action'), auth()->user()->id, $business_id);

            $output = ['success' => true, 'msg' => __('approval.request_'.($request->input('action') == 'approved' ? 'approved' : 'rejected'))];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => $e->getMessage()];
        }

        return $output;
    }

    /**
     * Cancels a still-pending leave request.
     */
    public function cancel(Request $request, $id)
    {
        try {
            $business_id = $request->session()->get('user.business_id');

            $this->leaveUtil->cancel($id, $business_id);

            $output = ['success' => true, 'msg' => __('hr.leave_request_cancelled_success')];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}
