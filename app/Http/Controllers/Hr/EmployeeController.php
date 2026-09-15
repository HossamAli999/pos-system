<?php

namespace App\Http\Controllers\Hr;

use App\BusinessLocation;
use App\Employee;
use App\HrDepartment;
use App\HrDesignation;
use App\PayrollComponent;
use App\Http\Controllers\Controller;
use App\Utils\HrUtil;
use App\Utils\Util;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    protected $hrUtil;

    protected $commonUtil;

    public function __construct(HrUtil $hrUtil, Util $commonUtil)
    {
        $this->hrUtil = $hrUtil;
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('employee.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $employees = Employee::where('employees.business_id', $business_id)
                ->leftjoin('hr_departments as d', 'employees.department_id', '=', 'd.id')
                ->leftjoin('hr_designations as des', 'employees.designation_id', '=', 'des.id')
                ->select([
                    'employees.id', 'employees.employee_code',
                    DB::raw("CONCAT(COALESCE(employees.first_name, ''), ' ', COALESCE(employees.last_name, '')) as full_name"),
                    'd.name as department_name', 'des.name as designation_name',
                    'employees.status',
                ]);

            return DataTables::of($employees)
                ->editColumn('status', function ($row) {
                    $labels = [
                        'active' => 'bg-green',
                        'on_leave' => 'bg-yellow',
                        'terminated' => 'bg-red',
                        'resigned' => 'bg-gray',
                    ];

                    return '<span class="label '.($labels[$row->status] ?? 'bg-gray').'">'.__('hr.status_'.$row->status).'</span>';
                })
                ->addColumn('action', function ($row) {
                    $html = '<a href="'.action([\App\Http\Controllers\Hr\EmployeeController::class, 'show'], [$row->id]).'" class="btn btn-xs btn-info"><i class="fa fa-eye"></i> '.__('messages.view').'</a>';

                    if (auth()->user()->can('employee.update')) {
                        $html .= ' <a href="'.action([\App\Http\Controllers\Hr\EmployeeController::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</a>';
                    }

                    if (auth()->user()->can('employee.delete')) {
                        $html .= ' <button data-href="'.action([\App\Http\Controllers\Hr\EmployeeController::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_employee_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';
                    }

                    return $html;
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        return view('hr.employees.index');
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

        return view('hr.employees.create')->with($this->formData($business_id));
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
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            $input = $request->only([
                'employee_code', 'first_name', 'last_name', 'department_id', 'designation_id',
                'location_id', 'reporting_to', 'date_of_joining', 'date_of_birth', 'phone',
                'personal_email', 'bank_name', 'bank_account_number', 'bank_ifsc', 'national_id_number',
            ]);

            DB::beginTransaction();
            $employee = $this->hrUtil->createEmployee($input, $business_id, $user_id);
            DB::commit();

            $output = ['success' => true, 'msg' => __('hr.employee_added_success')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Hr\EmployeeController::class, 'index'])->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('employee.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $employee = Employee::where('business_id', $business_id)
            ->with(['department', 'designation', 'location', 'reporting_to_employee', 'user'])
            ->findOrFail($id);

        $current_structure = $employee->currentSalaryStructure();
        $salary_structures = $employee->salary_structures()->with('components.payroll_component')->get();

        $year = \Carbon::now()->year;
        $leave_balances = $employee->leave_balances()->where('year', $year)->with('leave_type')->get();

        return view('hr.employees.show')->with(compact('employee', 'current_structure', 'salary_structures', 'leave_balances', 'year'));
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

        $business_id = request()->session()->get('user.business_id');
        $employee = Employee::where('business_id', $business_id)->findOrFail($id);

        return view('hr.employees.edit')->with(array_merge(['employee' => $employee], $this->formData($business_id, $id)));
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

            $input = $request->only([
                'employee_code', 'first_name', 'last_name', 'department_id', 'designation_id',
                'location_id', 'reporting_to', 'date_of_joining', 'date_of_birth', 'phone',
                'personal_email', 'bank_name', 'bank_account_number', 'bank_ifsc', 'national_id_number',
            ]);

            $this->hrUtil->updateEmployee($id, $input, $business_id);

            $output = ['success' => true, 'msg' => __('hr.employee_updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Hr\EmployeeController::class, 'show'], [$id])->with('status', $output);
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

            Employee::where('business_id', $business_id)->findOrFail($id)->delete();

            $output = ['success' => true, 'msg' => __('hr.employee_deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Marks the employee as terminated/resigned.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function terminate(Request $request, $id)
    {
        if (! auth()->user()->can('employee.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $this->hrUtil->terminateEmployee(
                $id,
                $business_id,
                $request->input('termination_date'),
                $request->input('termination_reason'),
                $request->input('status', 'terminated')
            );

            $output = ['success' => true, 'msg' => __('hr.employee_updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Hr\EmployeeController::class, 'show'], [$id])->with('status', $output);
    }

    /**
     * Adds a new (versioned) salary structure for the employee.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function storeSalaryStructure(Request $request, $id)
    {
        if (! auth()->user()->can('employee.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            //Ensure the employee belongs to this business before touching their salary.
            Employee::where('business_id', $business_id)->findOrFail($id);

            $components = [];
            foreach ($request->input('components', []) as $component) {
                if (empty($component['payroll_component_id'])) {
                    continue;
                }
                $components[] = [
                    'payroll_component_id' => $component['payroll_component_id'],
                    'calc_type' => $component['calc_type'] ?? 'fixed',
                    'amount' => ! empty($component['amount']) ? $this->commonUtil->num_uf($component['amount']) : 0,
                    'percentage' => ! empty($component['percentage']) ? $this->commonUtil->num_uf($component['percentage']) : 0,
                ];
            }

            $this->hrUtil->saveSalaryStructure(
                $id,
                $request->input('effective_from'),
                $this->commonUtil->num_uf($request->input('basic_salary')),
                $request->input('pay_frequency', 'monthly'),
                $components,
                $user_id
            );

            $output = ['success' => true, 'msg' => __('hr.salary_structure_added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Hr\EmployeeController::class, 'show'], [$id])->with('status', $output);
    }

    protected function formData($business_id, $exclude_employee_id = null)
    {
        $departments = HrDepartment::forDropdown($business_id);
        $designations = HrDesignation::forDropdown($business_id);
        $business_locations = BusinessLocation::forDropdown($business_id);

        $employees_query = Employee::where('business_id', $business_id);
        if (! empty($exclude_employee_id)) {
            $employees_query->where('id', '!=', $exclude_employee_id);
        }
        $employees = $employees_query->get()->pluck('full_name', 'id');

        $payroll_components = PayrollComponent::where('business_id', $business_id)->get();

        return compact('departments', 'designations', 'business_locations', 'employees', 'payroll_components');
    }
}
