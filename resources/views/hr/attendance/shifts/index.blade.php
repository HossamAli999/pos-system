@extends('layouts.app')
@section('title', __('hr.shifts'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.shifts')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal"
                    data-href="{{action([\App\Http\Controllers\Hr\AttendanceController::class, 'createShift'])}}"
                    data-container=".attendance_shift_modal">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </button>
            </div>
        @endslot

        <table class="table table-bordered table-striped" id="hr_shift_table">
            <thead>
                <tr>
                    <th>@lang('hr.shift_name')</th>
                    <th>@lang('hr.start_time')</th>
                    <th>@lang('hr.end_time')</th>
                    <th>@lang('hr.grace_minutes')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent

    <div class="modal fade attendance_shift_modal" tabindex="-1" role="dialog"></div>
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var hr_shift_table = $('#hr_shift_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Hr\AttendanceController::class, "shiftsIndex"])}}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'start_time', name: 'start_time' },
                { data: 'end_time', name: 'end_time' },
                { data: 'grace_minutes', name: 'grace_minutes' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $(document).on('submit', 'form#attendance_shift_add_form, form#attendance_shift_edit_form', function(e) {
            e.preventDefault();
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: $(this).serialize(),
                success: function(result) {
                    if (result.success === true) {
                        $('div.attendance_shift_modal').modal('hide');
                        toastr.success(result.msg);
                        hr_shift_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', 'button.delete_attendance_shift_button', function() {
            swal({
                title: LANG.sure,
                text: "{{__('hr.confirm_delete_shift')}}",
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
                                hr_shift_table.ajax.reload();
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
