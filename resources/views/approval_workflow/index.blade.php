@extends('layouts.app')
@section('title', __('approval.approval_workflows'))

@section('content')

<section class="content-header">
    <h1>@lang('approval.approval_workflows')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{action([\App\Http\Controllers\ApprovalWorkflowController::class, 'create'])}}">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </a>
            </div>
        @endslot

        <table class="table table-bordered table-striped" id="approval_workflow_table" style="width: 100%;">
            <thead>
                <tr>
                    <th>@lang('approval.name')</th>
                    <th>@lang('approval.module')</th>
                    <th>@lang('approval.steps_count')</th>
                    <th>@lang('approval.is_active')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var approval_workflow_table = $('#approval_workflow_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\ApprovalWorkflowController::class, "index"])}}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'module', name: 'module' },
                { data: 'steps_count', name: 'steps_count', orderable: false, searchable: false },
                { data: 'is_active', name: 'is_active', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $(document).on('click', 'button.delete_approval_workflow_button', function() {
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    var href = $(this).data('href');
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                approval_workflow_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                }
            });
        });
    });
</script>
@endsection
