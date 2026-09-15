<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    protected $guarded = ['id'];

    public function payroll_run()
    {
        return $this->belongsTo(\App\PayrollRun::class, 'payroll_run_id');
    }

    public function employee()
    {
        return $this->belongsTo(\App\Employee::class, 'employee_id');
    }

    public function lines()
    {
        return $this->hasMany(\App\PayslipLine::class, 'payslip_id');
    }

    public function payment_account()
    {
        return $this->belongsTo(\App\Account::class, 'payment_account_id');
    }
}
