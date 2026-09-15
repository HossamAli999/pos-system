<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    use BusinessAuditable;

    protected $guarded = ['id'];

    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'pay_date' => 'date',
    ];

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function payslips()
    {
        return $this->hasMany(\App\Payslip::class, 'payroll_run_id');
    }

    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'transaction_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function approved_by_user()
    {
        return $this->belongsTo(\App\User::class, 'approved_by');
    }
}
