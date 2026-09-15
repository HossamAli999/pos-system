<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmPipelineStage extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_won' => 'boolean',
        'is_lost' => 'boolean',
    ];

    public function pipeline()
    {
        return $this->belongsTo(\App\CrmPipeline::class, 'crm_pipeline_id');
    }

    public function leads()
    {
        return $this->hasMany(\App\CrmLead::class, 'stage_id');
    }
}
