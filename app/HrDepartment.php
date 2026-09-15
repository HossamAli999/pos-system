<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;

class HrDepartment extends Model
{
    use BusinessAuditable;

    protected $guarded = ['id'];

    public function parent()
    {
        return $this->belongsTo(\App\HrDepartment::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(\App\HrDepartment::class, 'parent_id');
    }

    public function manager()
    {
        return $this->belongsTo(\App\User::class, 'manager_id');
    }

    public function designations()
    {
        return $this->hasMany(\App\HrDesignation::class, 'department_id');
    }

    public static function forDropdown($business_id)
    {
        return self::where('business_id', $business_id)->pluck('name', 'id');
    }
}
