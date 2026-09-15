<?php

namespace App\Contracts;

use App\ApprovalRequest;

interface ApprovalDecisionHandler
{
    /**
     * Called after an approval request tied to this module has been fully
     * approved or rejected (never for an intermediate step in a multi-step
     * workflow). Inspect $approvalRequest->status and $approvalRequest->approvable
     * to react.
     */
    public function handle(ApprovalRequest $approvalRequest);
}
