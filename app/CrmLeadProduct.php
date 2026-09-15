<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmLeadProduct extends Model
{
    protected $guarded = ['id'];

    public function lead()
    {
        return $this->belongsTo(\App\CrmLead::class, 'crm_lead_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class, 'variation_id');
    }

    public function getLineTotalAttribute()
    {
        return $this->quantity * $this->unit_price;
    }
}
