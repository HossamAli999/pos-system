<?php

namespace App\Http\Controllers;

use App\Project;
use App\ProjectTask;
use App\Utils\ProjectUtil;
use App\Utils\Util;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    protected $projectUtil;

    protected $commonUtil;

    public function __construct(ProjectUtil $projectUtil, Util $commonUtil)
    {
        $this->projectUtil = $projectUtil;
        $this->commonUtil = $commonUtil;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $project_id)
    {
        if (! auth()->user()->can('project.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            //Ensure the project belongs to this business before adding a task to it.
            Project::where('business_id', $business_id)->findOrFail($project_id);

            $this->projectUtil->createTask($request->only(['title', 'description', 'assigned_to', 'due_date', 'parent_task_id']), $project_id, $user_id);

            $output = ['success' => true, 'msg' => __('project.task_added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\ProjectController::class, 'show'], [$project_id])->with('status', $output);
    }

    /**
     * Moves a task to a different status column (used by the task board's drag & drop).
     */
    public function moveStatus(Request $request, $id)
    {
        if (! auth()->user()->can('project.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $task = $this->projectUtil->updateTaskStatus($id, $request->input('status'));

            $output = ['success' => true, 'msg' => __('project.task_status_updated_success')];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Adds a comment to a task.
     */
    public function addComment(Request $request, $id)
    {
        if (! (auth()->user()->can('project.view_all') || auth()->user()->can('project.view_own'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $user_id = $request->session()->get('user.id');
            $task = ProjectTask::findOrFail($id);

            $this->projectUtil->addComment($id, $request->input('comment'), $user_id);

            $output = ['success' => true, 'msg' => __('project.comment_added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Logs time spent on a task.
     */
    public function logTime(Request $request, $id)
    {
        if (! (auth()->user()->can('project.view_all') || auth()->user()->can('project.view_own'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $user_id = $request->session()->get('user.id');

            $this->projectUtil->logTime($id, $user_id, $request->input('log_date'), $this->commonUtil->num_uf($request->input('hours')), $request->input('notes'));

            $output = ['success' => true, 'msg' => __('project.time_logged_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('project.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $task = ProjectTask::findOrFail($id);
            $project_id = $task->project_id;
            $task->delete();

            $output = ['success' => true, 'msg' => __('project.task_deleted_success')];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}
