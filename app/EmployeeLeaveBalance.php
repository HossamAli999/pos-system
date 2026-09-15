<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveBalance extends Model
{
    protected $guarded = ['id'];

    public function employee()
    {
        return $this->belongsTo(\App\Employee::class, 'employee_id');
    }

    public function leave_type()
    {
        return $this->belongsTo(\App\LeaveType::class, 'leave_type_id');
    }

    public function getRemainingDaysAttribute()
    {
        return $this->allocated_days + $this->carried_forward_days - $this->used_days;
    }
}
