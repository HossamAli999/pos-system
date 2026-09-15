<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalaryStructureComponent extends Model
{
    protected $guarded = ['id'];

    public function salary_structure()
    {
        return $this->belongsTo(\App\EmployeeSalaryStructure::class, 'salary_structure_id');
    }

    public function payroll_component()
    {
        return $this->belongsTo(\App\PayrollComponent::class, 'payroll_component_id');
    }
}
