<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmLead extends Model
{
    use SoftDeletes;
    use BusinessAuditable;

    protected $guarded = ['id'];

    protected $casts = [
        'expected_close_date' => 'date',
        'converted_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    public function converted_contact()
    {
        return $this->belongsTo(\App\Contact::class, 'converted_contact_id');
    }

    public function source()
    {
        return $this->belongsTo(\App\CrmLeadSource::class, 'source_id');
    }

    public function assigned_to_user()
    {
        return $this->belongsTo(\App\User::class, 'assigned_to');
    }

    public function pipeline()
    {
        return $this->belongsTo(\App\CrmPipeline::class, 'pipeline_id');
    }

    public function stage()
    {
        return $this->belongsTo(\App\CrmPipelineStage::class, 'stage_id');
    }

    public function stage_history()
    {
        return $this->hasMany(\App\CrmLeadStageHistory::class, 'crm_lead_id')->orderBy('changed_at', 'desc');
    }

    public function activities()
    {
        return $this->hasMany(\App\CrmActivity::class, 'crm_lead_id')->orderBy('due_date', 'asc');
    }

    public function lead_products()
    {
        return $this->hasMany(\App\CrmLeadProduct::class, 'crm_lead_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
