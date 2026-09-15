<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BomItem extends Model
{
    protected $guarded = ['id'];

    public function bill_of_material()
    {
        return $this->belongsTo(\App\BillOfMaterial::class, 'bill_of_material_id');
    }

    public function raw_material_product()
    {
        return $this->belongsTo(\App\Product::class, 'raw_material_product_id');
    }

    public function raw_material_variation()
    {
        return $this->belongsTo(\App\Variation::class, 'raw_material_variation_id');
    }

    /**
     * Quantity required for the given number of BOM output batches, including wastage.
     */
    public function requiredQuantityFor($batches)
    {
        $base = $this->quantity_required * $batches;

        return $base + ($base * ($this->wastage_percent / 100));
    }
}
