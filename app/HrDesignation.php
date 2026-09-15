<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;

class HrDesignation extends Model
{
    use BusinessAuditable;

    protected $guarded = ['id'];

    public function department()
    {
        return $this->belongsTo(\App\HrDepartment::class, 'department_id');
    }

    public static function forDropdown($business_id, $department_id = null)
    {
        $query = self::where('business_id', $business_id);

        if (! empty($department_id)) {
            $query->where('department_id', $department_id);
        }

        return $query->pluck('name', 'id');
    }
}
