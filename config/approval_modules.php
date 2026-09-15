<?php

/*
 * Maps an approval "module" key (ApprovalWorkflow.module) to a handler class that
 * gets notified after App\Utils\ApprovalUtil::recordDecision() finalizes a request
 * (status becomes 'approved' or 'rejected'). The handler class must implement
 * App\Contracts\ApprovalDecisionHandler.
 *
 * This lets a domain (e.g. HR leave requests in a later phase) react to approval
 * decisions without App\Utils\ApprovalUtil ever needing to know that domain exists.
 *
 * Example: 'leave_request' => \App\Listeners\Approval\LeaveRequestApprovalHandler::class,
 */
return [
    'leave_request' => \App\Listeners\Approval\LeaveRequestApprovalHandler::class,
];
