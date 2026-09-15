<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProjectTimeLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'log_date' => 'date',
    ];

    public function task()
    {
        return $this->belongsTo(\App\ProjectTask::class, 'project_task_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }
}
