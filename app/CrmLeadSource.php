<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmLeadSource extends Model
{
    protected $guarded = ['id'];

    public static function forDropdown($business_id)
    {
        return self::where('business_id', $business_id)->pluck('name', 'id');
    }
}
