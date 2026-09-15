<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\LeaveType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LeaveTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('leave_type.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $leave_types = LeaveType::where('business_id', $business_id)
                ->select(['id', 'name', 'days_allowed_per_year', 'is_paid', 'carry_forward']);

            return DataTables::of($leave_types)
                ->editColumn('is_paid', function ($row) {
                    return $row->is_paid ? __('lang_v1.yes') : __('lang_v1.no');
                })
                ->editColumn('carry_forward', function ($row) {
                    return $row->carry_forward ? __('lang_v1.yes') : __('lang_v1.no');
                })
                ->addColumn('action', function ($row) {
                    return '<button data-href="'.action([\App\Http\Controllers\Hr\LeaveTypeController::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary btn-modal" data-container=".leave_type_modal"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</button>
                        &nbsp;
                        <button data-href="'.action([\App\Http\Controllers\Hr\LeaveTypeController::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_leave_type_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('hr.leave_types.index');
    }

    public function create()
    {
        if (! auth()->user()->can('leave_type.manage')) {
            abort(403, 'Unauthorized action.');
        }

        return view('hr.leave_types.create');
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('leave_type.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'days_allowed_per_year', 'max_carry_forward_days']);
            $input['business_id'] = $request->session()->get('user.business_id');
            $input['is_paid'] = $request->boolean('is_paid');
            $input['carry_forward'] = $request->boolean('carry_forward');

            LeaveType::create($input);

            $output = ['success' => true, 'msg' => __('hr.leave_type_added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function edit($id)
    {
        if (! auth()->user()->can('leave_type.manage')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $leave_type = LeaveType::where('business_id', $business_id)->findOrFail($id);

            return view('hr.leave_types.edit')->with(compact('leave_type'));
        }
    }

    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('leave_type.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $leave_type = LeaveType::where('business_id', $business_id)->findOrFail($id);
            $leave_type->fill($request->only(['name', 'days_allowed_per_year', 'max_carry_forward_days']));
            $leave_type->is_paid = $request->boolean('is_paid');
            $leave_type->carry_forward = $request->boolean('carry_forward');
            $leave_type->save();

            $output = ['success' => true, 'msg' => __('hr.leave_type_updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function destroy($id)
    {
        if (! auth()->user()->can('leave_type.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            LeaveType::where('business_id', $business_id)->findOrFail($id)->delete();

            $output = ['success' => true, 'msg' => __('hr.leave_type_deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}
