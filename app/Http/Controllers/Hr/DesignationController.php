<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\HrDepartment;
use App\HrDesignation;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DesignationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! (auth()->user()->can('employee.create') || auth()->user()->can('employee.update'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $designations = HrDesignation::where('hr_designations.business_id', $business_id)
                ->leftjoin('hr_departments as d', 'hr_designations.department_id', '=', 'd.id')
                ->select(['hr_designations.id', 'hr_designations.name', 'd.name as department_name']);

            return DataTables::of($designations)
                ->addColumn('action', function ($row) {
                    return '<button data-href="'.action([\App\Http\Controllers\Hr\DesignationController::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary btn-modal" data-container=".designation_modal"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</button>
                        &nbsp;
                        <button data-href="'.action([\App\Http\Controllers\Hr\DesignationController::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_designation_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('hr.designations.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('employee.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $departments = HrDepartment::forDropdown($business_id);

        return view('hr.designations.create')->with(compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('employee.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'department_id']);
            $input['business_id'] = $request->session()->get('user.business_id');

            HrDesignation::create($input);

            $output = ['success' => true, 'msg' => __('hr.designation_added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('employee.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $designation = HrDesignation::where('business_id', $business_id)->findOrFail($id);
            $departments = HrDepartment::forDropdown($business_id);

            return view('hr.designations.edit')->with(compact('designation', 'departments'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('employee.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $designation = HrDesignation::where('business_id', $business_id)->findOrFail($id);
            $designation->fill($request->only(['name', 'department_id']));
            $designation->save();

            $output = ['success' => true, 'msg' => __('hr.designation_updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('employee.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            HrDesignation::where('business_id', $business_id)->findOrFail($id)->delete();

            $output = ['success' => true, 'msg' => __('hr.designation_deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}
