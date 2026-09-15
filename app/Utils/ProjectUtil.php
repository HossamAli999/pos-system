<?php

namespace App\Utils;

use App\Project;
use App\ProjectTask;
use App\ProjectTaskComment;
use App\ProjectTimeLog;

class ProjectUtil extends Util
{
    public function createProject(array $input, $business_id, $user_id)
    {
        return Project::create([
            'business_id' => $business_id,
            'name' => $input['name'],
            'description' => $input['description'] ?? null,
            'contact_id' => $input['contact_id'] ?? null,
            'status' => 'active',
            'start_date' => $input['start_date'] ?? null,
            'end_date' => $input['end_date'] ?? null,
            'created_by' => $user_id,
        ]);
    }

    public function updateProject($project_id, array $input, $business_id)
    {
        $project = Project::where('business_id', $business_id)->findOrFail($project_id);
        $project->fill([
            'name' => $input['name'],
            'description' => $input['description'] ?? null,
            'contact_id' => $input['contact_id'] ?? null,
            'status' => $input['status'] ?? $project->status,
            'start_date' => $input['start_date'] ?? null,
            'end_date' => $input['end_date'] ?? null,
        ]);
        $project->save();

        return $project;
    }

    public function createTask(array $input, $project_id, $user_id)
    {
        return ProjectTask::create([
            'project_id' => $project_id,
            'parent_task_id' => $input['parent_task_id'] ?? null,
            'title' => $input['title'],
            'description' => $input['description'] ?? null,
            'status' => 'todo',
            'assigned_to' => $input['assigned_to'] ?? null,
            'due_date' => $input['due_date'] ?? null,
            'created_by' => $user_id,
        ]);
    }

    public function updateTaskStatus($task_id, $status)
    {
        $task = ProjectTask::findOrFail($task_id);
        $task->status = $status;
        $task->save();

        return $task;
    }

    public function addComment($task_id, $comment, $user_id)
    {
        return ProjectTaskComment::create([
            'project_task_id' => $task_id,
            'comment' => $comment,
            'created_by' => $user_id,
        ]);
    }

    public function logTime($task_id, $user_id, $log_date, $hours, $notes = null)
    {
        return ProjectTimeLog::create([
            'project_task_id' => $task_id,
            'user_id' => $user_id,
            'log_date' => $log_date,
            'hours' => $hours,
            'notes' => $notes,
        ]);
    }
}
