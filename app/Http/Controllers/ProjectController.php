<?php

namespace App\Http\Controllers;

use App\Contact;
use App\Project;
use App\User;
use App\Utils\ProjectUtil;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProjectController extends Controller
{
    protected $projectUtil;

    public function __construct(ProjectUtil $projectUtil)
    {
        $this->projectUtil = $projectUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! (auth()->user()->can('project.view_all') || auth()->user()->can('project.view_own'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $projects = Project::where('projects.business_id', $business_id)
                ->leftjoin('contacts as c', 'projects.contact_id', '=', 'c.id')
                ->select(['projects.id', 'projects.name', 'c.name as contact_name', 'projects.status', 'projects.start_date', 'projects.end_date']);

            if (! auth()->user()->can('project.view_all')) {
                $projects->where('projects.created_by', auth()->user()->id);
            }

            return DataTables::of($projects)
                ->editColumn('status', function ($row) {
                    $labels = ['active' => 'bg-green', 'on_hold' => 'bg-yellow', 'completed' => 'bg-info', 'cancelled' => 'bg-red'];

                    return '<span class="label '.($labels[$row->status] ?? 'bg-gray').'">'.__('project.status_'.$row->status).'</span>';
                })
                ->editColumn('start_date', '@if(!empty($start_date)){{@format_date($start_date)}}@endif')
                ->editColumn('end_date', '@if(!empty($end_date)){{@format_date($end_date)}}@endif')
                ->addColumn('action', function ($row) {
                    return '<a href="'.action([\App\Http\Controllers\ProjectController::class, 'show'], [$row->id]).'" class="btn btn-xs btn-info"><i class="fa fa-eye"></i> '.__('messages.view').'</a>';
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        return view('project.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('project.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $contacts = Contact::where('business_id', $business_id)->pluck('name', 'id');

        return view('project.create')->with(compact('contacts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('project.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            $project = $this->projectUtil->createProject($request->only(['name', 'description', 'contact_id', 'start_date', 'end_date']), $business_id, $user_id);

            $output = ['success' => true, 'msg' => __('project.project_added_success')];

            return redirect()->action([\App\Http\Controllers\ProjectController::class, 'show'], [$project->id])->with('status', $output);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];

            return redirect()->action([\App\Http\Controllers\ProjectController::class, 'create'])->with('status', $output);
        }
    }

    /**
     * Display the specified resource — the project's task board.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! (auth()->user()->can('project.view_all') || auth()->user()->can('project.view_own'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $project = Project::where('business_id', $business_id)
            ->with(['contact', 'tasks.assigned_to_user', 'tasks.comments', 'tasks.time_logs'])
            ->findOrFail($id);

        $tasks_by_status = $project->tasks->groupBy('status');
        $users = User::forDropdown($business_id, true);

        $statuses = ['todo', 'in_progress', 'review', 'done'];

        return view('project.show')->with(compact('project', 'tasks_by_status', 'users', 'statuses'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('project.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $project = Project::where('business_id', $business_id)->findOrFail($id);
        $contacts = Contact::where('business_id', $business_id)->pluck('name', 'id');

        return view('project.edit')->with(compact('project', 'contacts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('project.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $this->projectUtil->updateProject($id, $request->only(['name', 'description', 'contact_id', 'status', 'start_date', 'end_date']), $business_id);

            $output = ['success' => true, 'msg' => __('project.project_updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\ProjectController::class, 'show'], [$id])->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('project.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            Project::where('business_id', $business_id)->findOrFail($id)->delete();

            $output = ['success' => true, 'msg' => __('project.project_deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}
