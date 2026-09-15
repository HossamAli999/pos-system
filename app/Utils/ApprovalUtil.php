<?php

namespace App\Utils;

use App\ApprovalAction;
use App\ApprovalRequest;
use App\ApprovalStep;
use App\ApprovalWorkflow;
use App\User;
use DB;

class ApprovalUtil extends Util
{
    /**
     * Starts an approval request for the given approvable model if the business has
     * an active workflow configured for $module; otherwise returns null so the caller
     * can fall back to its existing status-flag behavior (zero breaking change for
     * businesses that haven't configured an approval workflow).
     *
     * @param  string  $module  key matching ApprovalWorkflow.module, e.g. 'leave_request'
     * @param  \Illuminate\Database\Eloquent\Model  $approvable  must have a business_id and id
     * @param  int  $requested_by  user id
     * @param  float|null  $amount  used to match workflows scoped to an amount range
     * @return ApprovalRequest|null
     */
    public function startApproval($module, $approvable, $requested_by, $amount = null)
    {
        $business_id = $approvable->business_id;

        $workflow = ApprovalWorkflow::findForModule($business_id, $module, $amount);

        if (empty($workflow)) {
            return null;
        }

        $first_step = $workflow->steps()->first();

        if (empty($first_step)) {
            return null;
        }

        return ApprovalRequest::create([
            'business_id' => $business_id,
            'approval_workflow_id' => $workflow->id,
            'approvable_type' => get_class($approvable),
            'approvable_id' => $approvable->id,
            'current_step_id' => $first_step->id,
            'status' => 'pending',
            'requested_by' => $requested_by,
            'requested_at' => \Carbon::now()->toDateTimeString(),
        ]);
    }

    /**
     * Records an approve/reject decision on the request's current step, advances to the
     * next step (or finalizes the request if the step was final), and notifies the owning
     * module's handler (config/approval_modules.php) once the request is fully decided.
     *
     * @return ApprovalRequest
     *
     * @throws \Exception
     */
    public function recordDecision($approval_request_id, $user_id, $action, $comment = null)
    {
        if (! in_array($action, ['approved', 'rejected'])) {
            throw new \Exception(__('approval.invalid_action'));
        }

        $approval_request = ApprovalRequest::with(['current_step', 'workflow'])->findOrFail($approval_request_id);

        if ($approval_request->status != 'pending') {
            throw new \Exception(__('approval.request_already_completed'));
        }

        $current_step = $approval_request->current_step;
        $user = User::findOrFail($user_id);

        if (empty($current_step) || ! $current_step->canBeActedOnBy($user)) {
            throw new \Exception(__('approval.not_authorized_to_decide'));
        }

        DB::beginTransaction();
        try {
            ApprovalAction::create([
                'approval_request_id' => $approval_request->id,
                'approval_step_id' => $current_step->id,
                'user_id' => $user_id,
                'action' => $action,
                'comment' => $comment,
                'acted_at' => \Carbon::now()->toDateTimeString(),
            ]);

            if ($action == 'rejected') {
                $approval_request->status = 'rejected';
                $approval_request->completed_at = \Carbon::now()->toDateTimeString();
            } else {
                $next_step = $current_step->is_final ? null : ApprovalStep::where('approval_workflow_id', $approval_request->approval_workflow_id)
                    ->where('step_order', '>', $current_step->step_order)
                    ->orderBy('step_order', 'asc')
                    ->first();

                if (empty($next_step)) {
                    $approval_request->status = 'approved';
                    $approval_request->completed_at = \Carbon::now()->toDateTimeString();
                } else {
                    $approval_request->current_step_id = $next_step->id;
                }
            }

            $approval_request->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        if (in_array($approval_request->status, ['approved', 'rejected'])) {
            $this->notifyModule($approval_request);
        }

        return $approval_request->fresh();
    }

    /**
     * Cancels a still-pending approval request (e.g. the requester withdrew it).
     */
    public function cancel($approval_request_id)
    {
        $approval_request = ApprovalRequest::findOrFail($approval_request_id);

        if ($approval_request->status == 'pending') {
            $approval_request->status = 'cancelled';
            $approval_request->completed_at = \Carbon::now()->toDateTimeString();
            $approval_request->save();
        }

        return $approval_request;
    }

    /**
     * Invokes the handler registered for the request's module in config/approval_modules.php,
     * if any. Kept as a no-op when nothing is registered so this stays fully optional.
     */
    protected function notifyModule(ApprovalRequest $approval_request)
    {
        $handler_class = config('approval_modules.'.$approval_request->workflow->module);

        if (! empty($handler_class) && class_exists($handler_class)) {
            (new $handler_class)->handle($approval_request);
        }
    }

    /**
     * Returns pending approval requests the given user is eligible to act on right now,
     * for the "My Approvals" inbox.
     */
    public function getPendingApprovalsForUser(User $user, $business_id = null)
    {
        $business_id = $business_id ?? $user->business_id;

        $requests = ApprovalRequest::where('business_id', $business_id)
            ->where('status', 'pending')
            ->with(['workflow', 'current_step', 'requested_by_user', 'approvable'])
            ->orderBy('requested_at', 'asc')
            ->get();

        return $requests->filter(function ($request) use ($user) {
            return ! empty($request->current_step) && $request->current_step->canBeActedOnBy($user);
        })->values();
    }
}
