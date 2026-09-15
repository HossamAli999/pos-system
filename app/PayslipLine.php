<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PayslipLine extends Model
{
    protected $guarded = ['id'];

    public function payslip()
    {
        return $this->belongsTo(\App\Payslip::class, 'payslip_id');
    }

    public function payroll_component()
    {
        return $this->belongsTo(\App\PayrollComponent::class, 'payroll_component_id');
    }
}
