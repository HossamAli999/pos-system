<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProjectTask extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(\App\Project::class, 'project_id');
    }

    public function parent_task()
    {
        return $this->belongsTo(\App\ProjectTask::class, 'parent_task_id');
    }

    public function sub_tasks()
    {
        return $this->hasMany(\App\ProjectTask::class, 'parent_task_id');
    }

    public function assigned_to_user()
    {
        return $this->belongsTo(\App\User::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(\App\ProjectTaskComment::class, 'project_task_id')->orderBy('created_at', 'asc');
    }

    public function time_logs()
    {
        return $this->hasMany(\App\ProjectTimeLog::class, 'project_task_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
