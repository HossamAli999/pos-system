<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VanSalesTrip extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $casts = [
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'submitted_for_approval_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(\App\VanSalesVehicle::class, 'van_sales_vehicle_id');
    }

    public function rep()
    {
        return $this->belongsTo(\App\User::class, 'rep_id');
    }

    public function source_location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'source_location_id');
    }

    public function cash_register()
    {
        return $this->belongsTo(\App\CashRegister::class, 'cash_register_id');
    }

    public function transfer_out()
    {
        return $this->belongsTo(\App\Transaction::class, 'transfer_out_id');
    }

    public function transfer_in()
    {
        return $this->belongsTo(\App\Transaction::class, 'transfer_in_id');
    }

    public function approver()
    {
        return $this->belongsTo(\App\User::class, 'approved_by');
    }

    public function return_lines()
    {
        return $this->hasMany(\App\VanSalesTripReturnLine::class);
    }

    /**
     * Sales made during this trip.
     */
    public function sales()
    {
        return $this->hasMany(\App\Transaction::class, 'van_sales_trip_id');
    }
}
