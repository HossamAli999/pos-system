@extends('layouts.app')
@section('title', __('hr.leave_types'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.leave_types')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal"
                    data-href="{{action([\App\Http\Controllers\Hr\LeaveTypeController::class, 'create'])}}"
                    data-container=".leave_type_modal">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </button>
            </div>
        @endslot

        <table class="table table-bordered table-striped" id="hr_leave_type_table">
            <thead>
                <tr>
                    <th>@lang('hr.leave_type_name')</th>
                    <th>@lang('hr.days_allowed_per_year')</th>
                    <th>@lang('hr.is_paid')</th>
                    <th>@lang('hr.carry_forward')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent

    <div class="modal fade leave_type_modal" tabindex="-1" role="dialog"></div>
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var hr_leave_type_table = $('#hr_leave_type_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Hr\LeaveTypeController::class, "index"])}}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'days_allowed_per_year', name: 'days_allowed_per_year' },
                { data: 'is_paid', name: 'is_paid', orderable: false, searchable: false },
                { data: 'carry_forward', name: 'carry_forward', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $(document).on('submit', 'form#leave_type_add_form, form#leave_type_edit_form', function(e) {
            e.preventDefault();
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: $(this).serialize(),
                success: function(result) {
                    if (result.success === true) {
                        $('div.leave_type_modal').modal('hide');
                        toastr.success(result.msg);
                        hr_leave_type_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', 'button.delete_leave_type_button', function() {
            swal({
                title: LANG.sure,
                text: "{{__('hr.confirm_delete_leave_type')}}",
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: $(this).data('href'),
                        dataType: 'json',
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                hr_leave_type_table.ajax.reload();
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
