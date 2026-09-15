<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmActivity extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'due_date' => 'datetime',
        'done_at' => 'datetime',
        'is_done' => 'boolean',
    ];

    public function lead()
    {
        return $this->belongsTo(\App\CrmLead::class, 'crm_lead_id');
    }

    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    public function assigned_to_user()
    {
        return $this->belongsTo(\App\User::class, 'assigned_to');
    }

    public function created_by_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
