<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends Model
{
    use SoftDeletes;
    use BusinessAuditable;

    protected $guarded = ['id'];

    protected $casts = [
        'purchase_date' => 'date',
        'disposed_date' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(\App\FixedAssetCategory::class, 'fixed_asset_category_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function depreciation_entries()
    {
        return $this->hasMany(\App\FixedAssetDepreciationEntry::class, 'fixed_asset_id')->orderBy('depreciation_date', 'asc');
    }

    public function getAssetAccountIdEffectiveAttribute()
    {
        return $this->attributes['asset_account_id'] ?? $this->category->default_asset_account_id ?? null;
    }

    public function getDepreciationAccountIdEffectiveAttribute()
    {
        return $this->attributes['depreciation_account_id'] ?? $this->category->default_depreciation_account_id ?? null;
    }

    public function getAccumulatedDepreciationAccountIdEffectiveAttribute()
    {
        return $this->attributes['accumulated_depreciation_account_id'] ?? $this->category->default_accumulated_depreciation_account_id ?? null;
    }

    /**
     * Monthly straight-line depreciation amount: (cost - salvage) / useful life,
     * capped so accumulated depreciation never exceeds the depreciable base.
     */
    public function monthlyDepreciationAmount()
    {
        if ($this->useful_life_months <= 0) {
            return 0;
        }

        $depreciable_base = $this->purchase_cost - $this->salvage_value;
        $monthly = round($depreciable_base / $this->useful_life_months, 4);

        $remaining = $depreciable_base - $this->accumulated_depreciation;

        return max(0, min($monthly, $remaining));
    }

    public function getNetBookValueAttribute()
    {
        return $this->purchase_cost - $this->accumulated_depreciation;
    }
}
