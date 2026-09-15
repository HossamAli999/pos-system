<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;
    use BusinessAuditable;

    protected $guarded = ['id'];

    protected $appends = ['full_name'];

    public function getFullNameAttribute()
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function department()
    {
        return $this->belongsTo(\App\HrDepartment::class, 'department_id');
    }

    public function designation()
    {
        return $this->belongsTo(\App\HrDesignation::class, 'designation_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function reporting_to_employee()
    {
        return $this->belongsTo(\App\Employee::class, 'reporting_to');
    }

    public function salary_structures()
    {
        return $this->hasMany(\App\EmployeeSalaryStructure::class, 'employee_id')->orderByDesc('effective_from');
    }

    /**
     * The salary structure in effect as of $date (default: today).
     */
    public function currentSalaryStructure($date = null)
    {
        $date = $date ?? \Carbon::now()->toDateString();

        return $this->salary_structures()
            ->where('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->with('components.payroll_component')
            ->first();
    }

    public function leave_balances()
    {
        return $this->hasMany(\App\EmployeeLeaveBalance::class, 'employee_id');
    }

    public function leave_requests()
    {
        return $this->hasMany(\App\LeaveRequest::class, 'employee_id');
    }

    public function attendance_logs()
    {
        return $this->hasMany(\App\AttendanceLog::class, 'employee_id');
    }

    public static function forDropdown($business_id, $active_only = true)
    {
        $query = self::where('business_id', $business_id);

        if ($active_only) {
            $query->where('status', 'active');
        }

        return $query->get()->pluck('full_name', 'id');
    }
}
