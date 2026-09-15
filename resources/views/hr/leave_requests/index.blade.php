@extends('layouts.app')
@section('title', __('hr.leave_requests'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.my_leave_requests')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <a href="{{action([\App\Http\Controllers\Hr\LeaveRequestController::class, 'create'])}}" class="btn btn-block btn-primary">
                    <i class="fa fa-plus"></i> @lang('hr.request_leave')
                </a>
            </div>
        @endslot

        <table class="table table-bordered table-striped" id="hr_leave_request_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('hr.employee')</th>
                    <th>@lang('hr.leave_type')</th>
                    <th>@lang('hr.start_date')</th>
                    <th>@lang('hr.end_date')</th>
                    <th>@lang('hr.days_requested')</th>
                    <th>@lang('approval.approvals')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent
</section>

<div class="modal fade" id="leave_decision_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="leave_decision_form">
                <input type="hidden" name="action" id="leave_decision_action">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="leave_decision_title"></h4>
                </div>
                <div class="modal-body">
                    <p>@lang('approval.add_comment')</p>
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
        var hr_leave_request_table = $('#hr_leave_request_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Hr\LeaveRequestController::class, "index"])}}',
            columns: [
                { data: 'employee_name', name: 'employee_name' },
                { data: 'leave_type_name', name: 'leave_type_name' },
                { data: 'start_date', name: 'start_date' },
                { data: 'end_date', name: 'end_date' },
                { data: 'days_requested', name: 'days_requested' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        var decide_url = null;

        $(document).on('click', '.btn-decide-leave', function() {
            var id = $(this).data('id');
            var action = $(this).data('action');
            decide_url = '{{ url("hr/leave-requests") }}/' + id + '/decide';
            $('#leave_decision_action').val(action);
            $('#leave_decision_title').text(action == 'approved' ? "{{__('approval.approve')}}" : "{{__('approval.reject')}}");
            $('#leave_decision_modal').modal('show');
        });

        $('#leave_decision_form').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                method: 'POST',
                url: decide_url,
                dataType: 'json',
                data: $(this).serialize(),
                success: function(result) {
                    $('#leave_decision_modal').modal('hide');
                    if (result.success === true) {
                        toastr.success(result.msg);
                        hr_leave_request_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });
    });
</script>
@endsection
