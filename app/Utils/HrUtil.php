<?php

namespace App\Utils;

use App\Employee;
use App\EmployeeSalaryStructure;
use App\SalaryStructureComponent;

class HrUtil extends Util
{
    public function generateEmployeeCode($business_id)
    {
        $ref_count = $this->setAndGetReferenceCount('employee', $business_id);

        return $this->generateReferenceNumber('employee', $ref_count, $business_id, 'EMP');
    }

    public function createEmployee(array $input, $business_id, $user_id)
    {
        $input['business_id'] = $business_id;
        $input['created_by'] = $user_id;
        $input['status'] = $input['status'] ?? 'active';

        if (empty($input['employee_code'])) {
            $input['employee_code'] = $this->generateEmployeeCode($business_id);
        }

        return Employee::create($input);
    }

    public function updateEmployee($employee_id, array $input, $business_id)
    {
        $employee = Employee::where('business_id', $business_id)->findOrFail($employee_id);
        $employee->fill($input);
        $employee->save();

        return $employee;
    }

    public function terminateEmployee($employee_id, $business_id, $termination_date, $reason, $status = 'terminated')
    {
        $employee = Employee::where('business_id', $business_id)->findOrFail($employee_id);
        $employee->status = $status;
        $employee->termination_date = $termination_date;
        $employee->termination_reason = $reason;
        $employee->save();

        return $employee;
    }

    /**
     * Always inserts a new versioned row rather than editing the employee's current
     * structure in place, so past payroll runs keep pointing at the structure that
     * was actually in effect when they were generated.
     */
    public function saveSalaryStructure($employee_id, $effective_from, $basic_salary, $pay_frequency, array $components, $user_id)
    {
        $structure = EmployeeSalaryStructure::create([
            'employee_id' => $employee_id,
            'effective_from' => $effective_from,
            'basic_salary' => $basic_salary,
            'pay_frequency' => $pay_frequency,
            'created_by' => $user_id,
        ]);

        foreach ($components as $component) {
            if (empty($component['payroll_component_id'])) {
                continue;
            }

            $calc_type = $component['calc_type'] ?? 'fixed';

            SalaryStructureComponent::create([
                'salary_structure_id' => $structure->id,
                'payroll_component_id' => $component['payroll_component_id'],
                'calc_type' => $calc_type,
                'amount' => $calc_type == 'percentage_of_basic' ? null : ($component['amount'] ?? 0),
                'percentage' => $calc_type == 'percentage_of_basic' ? ($component['percentage'] ?? 0) : null,
            ]);
        }

        return $structure->fresh('components.payroll_component');
    }
}
