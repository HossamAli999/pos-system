<?php

namespace App\Http\Controllers;

use App\Utils\ApprovalUtil;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    protected $approvalUtil;

    public function __construct(ApprovalUtil $approvalUtil)
    {
        $this->approvalUtil = $approvalUtil;
    }

    /**
     * "My Approvals" inbox — pending approval requests the logged-in user
     * can currently act on, across every module (leave requests, purchase
     * requisitions, ...) that has an active workflow configured.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        $pending_approvals = $this->approvalUtil->getPendingApprovalsForUser(auth()->user(), $business_id);

        return view('approval.index')->with(compact('pending_approvals'));
    }

    /**
     * Records an approve/reject decision from the current user on the given
     * approval request's current step.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function decide(Request $request, $id)
    {
        $action = $request->input('action');

        try {
            $this->approvalUtil->recordDecision($id, auth()->user()->id, $action, $request->input('comment'));

            $output = ['success' => true,
                'msg' => $action == 'approved' ? __('approval.request_approved') : __('approval.request_rejected'),
            ];
        } catch (\Exception $e) {
            $output = ['success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        if ($request->ajax()) {
            return $output;
        }

        return redirect()->action([\App\Http\Controllers\ApprovalController::class, 'index'])->with('status', $output);
    }
}
