<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use BusinessAuditable;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Employee::class, 'employee_id');
    }

    public function leave_type()
    {
        return $this->belongsTo(\App\LeaveType::class, 'leave_type_id');
    }

    public function approval_request()
    {
        return $this->belongsTo(\App\ApprovalRequest::class, 'approval_request_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function decided_by_user()
    {
        return $this->belongsTo(\App\User::class, 'decided_by');
    }
}
