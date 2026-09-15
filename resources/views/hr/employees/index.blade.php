@extends('layouts.app')
@section('title', __('hr.employees'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.employees')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @can('employee.create')
            @slot('tool')
                <div class="box-tools">
                    <a href="{{action([\App\Http\Controllers\Hr\EmployeeController::class, 'create'])}}" class="btn btn-block btn-primary">
                        <i class="fa fa-plus"></i> @lang('messages.add')
                    </a>
                </div>
            @endslot
        @endcan

        <table class="table table-bordered table-striped" id="hr_employee_table" style="width: 100%;">
            <thead>
                <tr>
                    <th>@lang('hr.employee_code')</th>
                    <th>@lang('hr.employee')</th>
                    <th>@lang('hr.department')</th>
                    <th>@lang('hr.designation')</th>
                    <th>@lang('hr.status')</th>
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
        var hr_employee_table = $('#hr_employee_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Hr\EmployeeController::class, "index"])}}',
            columns: [
                { data: 'employee_code', name: 'employee_code' },
                { data: 'full_name', name: 'full_name' },
                { data: 'department_name', name: 'department_name' },
                { data: 'designation_name', name: 'designation_name' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $(document).on('click', 'button.delete_employee_button', function() {
            swal({
                title: LANG.sure,
                text: "{{__('hr.confirm_delete_employee')}}",
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
                                hr_employee_table.ajax.reload();
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
