<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;

class PayrollComponent extends Model
{
    use BusinessAuditable;

    protected $guarded = ['id'];

    protected $casts = [
        'is_taxable' => 'boolean',
    ];

    public static function forDropdown($business_id, $type = null)
    {
        $query = self::where('business_id', $business_id);

        if (! empty($type)) {
            $query->where('type', $type);
        }

        return $query->pluck('name', 'id');
    }
}
