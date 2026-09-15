<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;

class CrmPipeline extends Model
{
    use BusinessAuditable;

    protected $guarded = ['id'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function stages()
    {
        return $this->hasMany(\App\CrmPipelineStage::class, 'crm_pipeline_id')->orderBy('stage_order', 'asc');
    }

    public function leads()
    {
        return $this->hasMany(\App\CrmLead::class, 'pipeline_id');
    }

    public static function forDropdown($business_id)
    {
        return self::where('business_id', $business_id)->pluck('name', 'id');
    }

    public static function defaultFor($business_id)
    {
        return self::where('business_id', $business_id)->where('is_default', 1)->first()
            ?? self::where('business_id', $business_id)->orderBy('id', 'asc')->first();
    }
}
