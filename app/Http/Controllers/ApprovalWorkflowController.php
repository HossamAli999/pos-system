<?php

namespace App\Http\Controllers;

use App\ApprovalWorkflow;
use App\User;
use App\Utils\Util;
use DB;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class ApprovalWorkflowController extends Controller
{
    protected $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('approval_workflow.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $workflows = ApprovalWorkflow::where('business_id', $business_id)
                ->withCount('steps')
                ->select(['id', 'name', 'module', 'is_active', 'steps_count']);

            return DataTables::of($workflows)
                ->addColumn('action', function ($row) {
                    $html = '<a href="'.action([\App\Http\Controllers\ApprovalWorkflowController::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</a>
                        &nbsp;
                        <button data-href="'.action([\App\Http\Controllers\ApprovalWorkflowController::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_approval_workflow_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';

                    return $html;
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active ? '<span class="label bg-green">'.__('lang_v1.active').'</span>' : '<span class="label bg-red">'.__('lang_v1.inactive').'</span>';
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'is_active'])
                ->make(true);
        }

        return view('approval_workflow.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('approval_workflow.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $users = User::forDropdown($business_id, false);
        $roles = $this->commonUtil->getDropdownForRoles($business_id);

        return view('approval_workflow.create')->with(compact('users', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('approval_workflow.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            DB::beginTransaction();

            $workflow = ApprovalWorkflow::create([
                'business_id' => $business_id,
                'module' => $request->input('module'),
                'name' => $request->input('name'),
                'min_amount' => $request->input('min_amount') !== '' ? $this->commonUtil->num_uf($request->input('min_amount')) : null,
                'max_amount' => $request->input('max_amount') !== '' ? $this->commonUtil->num_uf($request->input('max_amount')) : null,
                'is_active' => ! empty($request->input('is_active')) ? 1 : 0,
                'created_by' => $request->session()->get('user.id'),
            ]);

            $this->saveSteps($workflow, $request->input('steps', []));

            DB::commit();

            $output = ['success' => true,
                'msg' => __('approval.workflow_added_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->action([\App\Http\Controllers\ApprovalWorkflowController::class, 'index'])->with('status', $output);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('approval_workflow.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $workflow = ApprovalWorkflow::where('business_id', $business_id)
            ->with(['steps'])
            ->findOrFail($id);

        $users = User::forDropdown($business_id, false);
        $roles = $this->commonUtil->getDropdownForRoles($business_id);

        return view('approval_workflow.edit')->with(compact('workflow', 'users', 'roles'));
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
        if (! auth()->user()->can('approval_workflow.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            DB::beginTransaction();

            $workflow = ApprovalWorkflow::where('business_id', $business_id)->findOrFail($id);
            $workflow->module = $request->input('module');
            $workflow->name = $request->input('name');
            $workflow->min_amount = $request->input('min_amount') !== '' ? $this->commonUtil->num_uf($request->input('min_amount')) : null;
            $workflow->max_amount = $request->input('max_amount') !== '' ? $this->commonUtil->num_uf($request->input('max_amount')) : null;
            $workflow->is_active = ! empty($request->input('is_active')) ? 1 : 0;
            $workflow->save();

            //Steps are always replaced wholesale — simpler and safe because
            //in-flight approval_requests reference approval_steps.id directly,
            //not by position, and existing steps get deleted via the FK cascade
            //only if this workflow's in-flight requests are also resolved first.
            $workflow->steps()->delete();
            $this->saveSteps($workflow, $request->input('steps', []));

            DB::commit();

            $output = ['success' => true,
                'msg' => __('approval.workflow_updated_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->action([\App\Http\Controllers\ApprovalWorkflowController::class, 'index'])->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('approval_workflow.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $workflow = ApprovalWorkflow::where('business_id', $business_id)->findOrFail($id);
            $workflow->delete();

            $output = ['success' => true,
                'msg' => __('approval.workflow_deleted_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Creates the ApprovalStep rows for a workflow from the posted steps array,
     * auto-numbering step_order by array position and always marking the last
     * step as final.
     */
    protected function saveSteps(ApprovalWorkflow $workflow, array $steps)
    {
        $step_count = count($steps);

        foreach (array_values($steps) as $index => $step) {
            if (empty($step['approver_type'])) {
                continue;
            }

            $approver_id = null;
            $permission_name = null;

            if ($step['approver_type'] == 'user') {
                $approver_id = $step['approver_user_id'] ?? null;
            } elseif ($step['approver_type'] == 'role') {
                $approver_id = $step['approver_role_id'] ?? null;
            } elseif ($step['approver_type'] == 'permission') {
                $permission_name = $step['permission_name'] ?? null;
            }

            $workflow->steps()->create([
                'step_order' => $index + 1,
                'approver_type' => $step['approver_type'],
                'approver_id' => $approver_id,
                'permission_name' => $permission_name,
                'is_final' => ($index == $step_count - 1) ? 1 : 0,
            ]);
        }
    }
}
