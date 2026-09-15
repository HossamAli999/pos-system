{{--
    Reusable approval-status badge + timeline, meant to be dropped onto any
    record's "show" page once that domain starts using App\Utils\ApprovalUtil.

    Usage:
        @include('components.approval_status', ['request' => $approvalRequest])

    where $approvalRequest is the latest App\ApprovalRequest for that record
    (e.g. $model->approvalRequests()->latest()->first()), or null if the
    record was never routed through an approval workflow.
--}}
@if(! empty($request))
    @php
        $status_labels = [
            'pending' => ['class' => 'bg-yellow', 'text' => __('approval.status_pending')],
            'approved' => ['class' => 'bg-green', 'text' => __('approval.status_approved')],
            'rejected' => ['class' => 'bg-red', 'text' => __('approval.status_rejected')],
            'cancelled' => ['class' => 'bg-gray', 'text' => __('approval.status_cancelled')],
        ];
        $status = $status_labels[$request->status] ?? $status_labels['pending'];
    @endphp
    <div class="approval-status-widget">
        <span class="label {{ $status['class'] }}">{{ $status['text'] }}</span>
        @if($request->status == 'pending' && ! empty($request->current_step))
            <span class="text-muted">— {{ __('approval.step') }} #{{ $request->current_step->step_order }}</span>
        @endif

        @if($request->actions->count() > 0)
            <ul class="list-unstyled" style="margin-top:10px;">
                @foreach($request->actions as $action)
                    <li>
                        <small class="text-muted">
                            {{ @format_datetime($action->acted_at) }} —
                            {{ trim(($action->user->first_name ?? '').' '.($action->user->last_name ?? '')) }}:
                            {{ __('approval.status_'.($action->action == 'approved' ? 'approved' : 'rejected')) }}
                            @if(! empty($action->comment))
                                ({{ $action->comment }})
                            @endif
                        </small>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
