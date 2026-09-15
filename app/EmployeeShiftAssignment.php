<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeShiftAssignment extends Model
{
    protected $guarded = ['id'];

    public function employee()
    {
        return $this->belongsTo(\App\Employee::class, 'employee_id');
    }

    public function shift()
    {
        return $this->belongsTo(\App\AttendanceShift::class, 'attendance_shift_id');
    }
}
