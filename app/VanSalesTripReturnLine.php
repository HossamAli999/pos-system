<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VanSalesTripReturnLine extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function trip()
    {
        return $this->belongsTo(\App\VanSalesTrip::class, 'van_sales_trip_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class, 'variation_id');
    }
}
