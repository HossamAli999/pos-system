<?php

return [
    // Menu / general
    'approvals' => 'Approvals',
    'approval_workflows' => 'Approval Workflows',
    'my_approvals' => 'My Approvals',
    'permission_manage_workflows' => 'Manage approval workflows',

    // Workflow CRUD
    'all_approval_workflows' => 'All approval workflows',
    'add_approval_workflow' => 'Add approval workflow',
    'edit_approval_workflow' => 'Edit approval workflow',
    'name' => 'Name',
    'module' => 'Module',
    'module_help' => 'Key identifying which feature this workflow applies to, e.g. leave_request, purchase_requisition.',
    'min_amount' => 'Minimum amount',
    'max_amount' => 'Maximum amount',
    'amount_range_help' => 'Optional. Leave both blank to apply this workflow to every amount.',
    'is_active' => 'Active',
    'steps' => 'Approval steps',
    'steps_count' => 'Steps',
    'step' => 'Step',
    'add_step' => 'Add step',
    'remove_step' => 'Remove',
    'no_steps_added' => 'Add at least one approval step.',
    'approver_type' => 'Approver type',
    'approver_type_user' => 'Specific user',
    'approver_type_role' => 'Role',
    'approver_type_permission' => 'Anyone with permission',
    'select_user' => 'Select user',
    'select_role' => 'Select role',
    'permission_name' => 'Permission name',
    'permission_name_help' => "e.g. purchase_requisition.approve. Any user who has this permission can act on this step.",
    'final_step_note' => 'The last step is automatically the final approval — once it is approved the request is fully approved.',
    'workflow_added_success' => 'Approval workflow added successfully.',
    'workflow_updated_success' => 'Approval workflow updated successfully.',
    'workflow_deleted_success' => 'Approval workflow deleted successfully.',
    'confirm_delete_workflow' => 'Are you sure you want to delete this approval workflow?',

    // My Approvals inbox
    'my_approvals_desc' => 'Requests currently waiting on your decision.',
    'no_pending_approvals' => "You're all caught up — no pending approvals.",
    'requested_by' => 'Requested by',
    'requested_on' => 'Requested on',
    'current_step' => 'Current step',
    'workflow' => 'Workflow',
    'record' => 'Record',
    'approve' => 'Approve',
    'reject' => 'Reject',
    'add_comment' => 'Comment (optional)',
    'comment_placeholder' => 'Add a note for this decision...',
    'confirm_reject' => 'Reject this request?',
    'confirm_reject_text' => 'You can add a comment explaining why.',

    // Status
    'status_pending' => 'Pending',
    'status_approved' => 'Approved',
    'status_rejected' => 'Rejected',
    'status_cancelled' => 'Cancelled',
    'pending_your_approval' => 'Pending your approval',

    // ApprovalUtil exceptions (shown as flash/toast messages)
    'invalid_action' => 'Invalid approval action.',
    'request_already_completed' => 'This approval request has already been completed.',
    'not_authorized_to_decide' => 'You are not authorized to decide on this approval request.',
    'request_approved' => 'Request approved.',
    'request_rejected' => 'Request rejected.',
];
