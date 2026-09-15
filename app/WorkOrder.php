<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    use BusinessAuditable;

    protected $guarded = ['id'];

    protected $casts = [
        'planned_date' => 'date',
    ];

    public function bill_of_material()
    {
        return $this->belongsTo(\App\BillOfMaterial::class, 'bill_of_material_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function material_consumptions()
    {
        return $this->hasMany(\App\WorkOrderMaterialConsumption::class, 'work_order_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
