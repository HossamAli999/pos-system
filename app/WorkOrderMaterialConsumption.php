<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkOrderMaterialConsumption extends Model
{
    protected $guarded = ['id'];

    public function work_order()
    {
        return $this->belongsTo(\App\WorkOrder::class, 'work_order_id');
    }

    public function raw_material_product()
    {
        return $this->belongsTo(\App\Product::class, 'raw_material_product_id');
    }

    public function raw_material_variation()
    {
        return $this->belongsTo(\App\Variation::class, 'raw_material_variation_id');
    }
}
