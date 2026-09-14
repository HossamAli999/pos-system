<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VanSalesVehicle extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The location that acts as this vehicle's stock/cash location.
     */
    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    /**
     * Trips made with this vehicle.
     */
    public function trips()
    {
        return $this->hasMany(\App\VanSalesTrip::class);
    }

    /**
     * Scope a query to only active vehicles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Return list of vehicles for a business, keyed by id, for dropdowns.
     *
     * @param  int  $business_id
     * @return array
     */
    public static function forDropdown($business_id)
    {
        return VanSalesVehicle::where('business_id', $business_id)
                    ->active()
                    ->pluck('name', 'id')
                    ->toArray();
    }
}
