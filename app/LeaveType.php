<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use BusinessAuditable;

    protected $guarded = ['id'];

    protected $casts = [
        'is_paid' => 'boolean',
        'carry_forward' => 'boolean',
    ];

    public static function forDropdown($business_id)
    {
        return self::where('business_id', $business_id)->pluck('name', 'id');
    }
}
