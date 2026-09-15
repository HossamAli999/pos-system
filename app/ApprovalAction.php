<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApprovalAction extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function request()
    {
        return $this->belongsTo(\App\ApprovalRequest::class, 'approval_request_id');
    }

    public function step()
    {
        return $this->belongsTo(\App\ApprovalStep::class, 'approval_step_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }
}
