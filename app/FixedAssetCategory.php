<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FixedAssetCategory extends Model
{
    protected $guarded = ['id'];

    public function assets()
    {
        return $this->hasMany(\App\FixedAsset::class, 'fixed_asset_category_id');
    }

    public static function forDropdown($business_id)
    {
        return self::where('business_id', $business_id)->pluck('name', 'id');
    }
}
