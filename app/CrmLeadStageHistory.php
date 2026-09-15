<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmLeadStageHistory extends Model
{
    protected $table = 'crm_lead_stage_history';

    protected $guarded = ['id'];

    public function lead()
    {
        return $this->belongsTo(\App\CrmLead::class, 'crm_lead_id');
    }

    public function from_stage()
    {
        return $this->belongsTo(\App\CrmPipelineStage::class, 'from_stage_id');
    }

    public function to_stage()
    {
        return $this->belongsTo(\App\CrmPipelineStage::class, 'to_stage_id');
    }

    public function changed_by_user()
    {
        return $this->belongsTo(\App\User::class, 'changed_by');
    }
}
