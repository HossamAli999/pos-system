<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryStructure extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'effective_from' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Employee::class, 'employee_id');
    }

    public function components()
    {
        return $this->hasMany(\App\SalaryStructureComponent::class, 'salary_structure_id');
    }

    /**
     * Resolves each component to a concrete amount for this structure's basic_salary
     * — fixed components return their amount as-is, percentage-of-basic components
     * are computed against $this->basic_salary.
     */
    public function resolvedComponents()
    {
        return $this->components->map(function ($component) {
            $amount = $component->calc_type == 'percentage_of_basic'
                ? round($this->basic_salary * ($component->percentage / 100), 4)
                : (float) $component->amount;

            return [
                'payroll_component_id' => $component->payroll_component_id,
                'name' => $component->payroll_component->name ?? '',
                'type' => $component->payroll_component->type ?? 'earning',
                'amount' => $amount,
            ];
        });
    }
}
