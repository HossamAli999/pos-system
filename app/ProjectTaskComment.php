<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProjectTaskComment extends Model
{
    protected $guarded = ['id'];

    public function task()
    {
        return $this->belongsTo(\App\ProjectTask::class, 'project_task_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
