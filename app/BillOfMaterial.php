<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;

class BillOfMaterial extends Model
{
    use BusinessAuditable;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class, 'variation_id');
    }

    public function unit()
    {
        return $this->belongsTo(\App\Unit::class, 'unit_id');
    }

    public function items()
    {
        return $this->hasMany(\App\BomItem::class, 'bill_of_material_id');
    }

    public function work_orders()
    {
        return $this->hasMany(\App\WorkOrder::class, 'bill_of_material_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public static function forDropdown($business_id, $active_only = true)
    {
        $query = self::where('business_id', $business_id)->with('product');

        if ($active_only) {
            $query->where('is_active', 1);
        }

        return $query->get()->mapWithKeys(function ($bom) {
            return [$bom->id => ($bom->product->name ?? '').' (v'.$bom->version.')'];
        });
    }
}
