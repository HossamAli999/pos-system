<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\HrDepartment;
use App\User;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DepartmentController extends Controller
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
            $departments = HrDepartment::where('hr_departments.business_id', $business_id)
                ->leftjoin('hr_departments as parent', 'hr_departments.parent_id', '=', 'parent.id')
                ->leftjoin('users as u', 'hr_departments.manager_id', '=', 'u.id')
                ->select([
                    'hr_departments.id', 'hr_departments.name',
                    'parent.name as parent_name',
                    DB::raw("CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as manager_name"),
                ]);

            return DataTables::of($departments)
                ->addColumn('action', function ($row) {
                    return '<button data-href="'.action([\App\Http\Controllers\Hr\DepartmentController::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary btn-modal" data-container=".department_modal"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</button>
                        &nbsp;
                        <button data-href="'.action([\App\Http\Controllers\Hr\DepartmentController::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_department_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('hr.departments.index');
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
        $users = User::forDropdown($business_id, true);

        return view('hr.departments.create')->with(compact('departments', 'users'));
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
            $input = $request->only(['name', 'parent_id', 'manager_id']);
            $input['business_id'] = $request->session()->get('user.business_id');

            HrDepartment::create($input);

            $output = ['success' => true, 'msg' => __('hr.department_added_success')];
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

            $department = HrDepartment::where('business_id', $business_id)->findOrFail($id);
            $departments = HrDepartment::where('business_id', $business_id)->where('id', '!=', $id)->pluck('name', 'id');
            $users = User::forDropdown($business_id, true);

            return view('hr.departments.edit')->with(compact('department', 'departments', 'users'));
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

            $department = HrDepartment::where('business_id', $business_id)->findOrFail($id);
            $department->fill($request->only(['name', 'parent_id', 'manager_id']));
            $department->save();

            $output = ['success' => true, 'msg' => __('hr.department_updated_success')];
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

            HrDepartment::where('business_id', $business_id)->findOrFail($id)->delete();

            $output = ['success' => true, 'msg' => __('hr.department_deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}
