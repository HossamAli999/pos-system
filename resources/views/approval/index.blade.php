@extends('layouts.app')
@section('title', __('approval.my_approvals'))

@section('content')

<section class="content-header">
    <h1>@lang('approval.my_approvals')
        <small>@lang('approval.my_approvals_desc')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @if($pending_approvals->count() == 0)
            <p class="text-muted text-center" style="padding: 20px 0;">
                <i class="fa fa-check-circle fa-2x" style="display:block; margin-bottom:10px;"></i>
                @lang('approval.no_pending_approvals')
            </p>
        @else
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>@lang('approval.workflow')</th>
                            <th>@lang('approval.record')</th>
                            <th>@lang('approval.requested_by')</th>
                            <th>@lang('approval.requested_on')</th>
                            <th>@lang('approval.current_step')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending_approvals as $request)
                            @php
                                $approvable = $request->approvable;
                                $record_label = '#'.$request->approvable_id;
                                if (! empty($approvable)) {
                                    $record_label = $approvable->name ?? $approvable->ref_no ?? $approvable->title ?? $record_label;
                                }
                                $requester = $request->requested_by_user;
                                $requester_name = ! empty($requester) ? trim(($requester->first_name ?? '').' '.($requester->last_name ?? '')) : '';
                            @endphp
                            <tr>
                                <td>{{ $request->workflow->name ?? '' }}</td>
                                <td>{{ $record_label }}</td>
                                <td>{{ $requester_name }}</td>
                                <td>{{ @format_datetime($request->requested_at) }}</td>
                                <td>{{ __('approval.step') }} #{{ $request->current_step->step_order ?? '' }}</td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-success btn-approve-request" data-id="{{ $request->id }}">
                                        <i class="fa fa-check"></i> @lang('approval.approve')
                                    </button>
                                    <button type="button" class="btn btn-xs btn-danger btn-reject-request" data-id="{{ $request->id }}">
                                        <i class="fa fa-times"></i> @lang('approval.reject')
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endcomponent
</section>

<div class="modal fade" id="approval_decision_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="approval_decision_form">
                <input type="hidden" name="request_id" id="approval_decision_request_id">
                <input type="hidden" name="action" id="approval_decision_action">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="approval_decision_title"></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        {!! Form::label('comment', __('approval.add_comment').':') !!}
                        {!! Form::textarea('comment', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('approval.comment_placeholder')]); !!}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        function openDecisionModal(id, action) {
            $('#approval_decision_request_id').val(id);
            $('#approval_decision_action').val(action);
            $('#approval_decision_title').text(action == 'approved' ? "{{__('approval.approve')}}" : "{{__('approval.reject')}}");
            $('#approval_decision_modal').modal('show');
        }

        $(document).on('click', '.btn-approve-request', function() {
            openDecisionModal($(this).data('id'), 'approved');
        });

        $(document).on('click', '.btn-reject-request', function() {
            openDecisionModal($(this).data('id'), 'rejected');
        });

        $('#approval_decision_form').on('submit', function(e) {
            e.preventDefault();
            var id = $('#approval_decision_request_id').val();

            $.ajax({
                method: 'POST',
                url: '{{ url("my-approvals") }}/' + id + '/decide',
                dataType: 'json',
                data: $(this).serialize(),
                success: function(result) {
                    $('#approval_decision_modal').modal('hide');
                    if (result.success === true) {
                        toastr.success(result.msg);
                        setTimeout(function() { location.reload(); }, 800);
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });
    });
</script>
@endsection
