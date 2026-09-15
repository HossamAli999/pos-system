<?php

namespace App\Listeners\Approval;

use App\ApprovalRequest;
use App\Contracts\ApprovalDecisionHandler;
use App\LeaveRequest;
use App\Utils\LeaveUtil;

/**
 * Registered in config/approval_modules.php under the 'leave_request' key.
 * Called by App\Utils\ApprovalUtil::recordDecision() once a leave request's
 * approval workflow is fully approved/rejected (not on every intermediate step).
 */
class LeaveRequestApprovalHandler implements ApprovalDecisionHandler
{
    public function handle(ApprovalRequest $approvalRequest)
    {
        $leave_request = LeaveRequest::find($approvalRequest->approvable_id);

        if (empty($leave_request) || $leave_request->status != 'pending') {
            return;
        }

        $last_action = $approvalRequest->actions()->latest('acted_at')->first();
        $decided_by = ! empty($last_action) ? $last_action->user_id : null;

        app(LeaveUtil::class)->finalizeDecision($leave_request, $approvalRequest->status, $decided_by);
    }
}
