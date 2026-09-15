<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    use BusinessAuditable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function workflow()
    {
        return $this->belongsTo(\App\ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function current_step()
    {
        return $this->belongsTo(\App\ApprovalStep::class, 'current_step_id');
    }

    public function approvable()
    {
        return $this->morphTo();
    }

    public function actions()
    {
        return $this->hasMany(\App\ApprovalAction::class)->orderBy('acted_at', 'asc');
    }

    public function requested_by_user()
    {
        return $this->belongsTo(\App\User::class, 'requested_by');
    }
}
