@extends('layouts.app')
@section('title', __('hr.departments'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.departments')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @can('employee.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal"
                        data-href="{{action([\App\Http\Controllers\Hr\DepartmentController::class, 'create'])}}"
                        data-container=".department_modal">
                        <i class="fa fa-plus"></i> @lang('messages.add')
                    </button>
                </div>
            @endslot
        @endcan

        <table class="table table-bordered table-striped" id="hr_department_table">
            <thead>
                <tr>
                    <th>@lang('hr.department_name')</th>
                    <th>@lang('hr.parent_department')</th>
                    <th>@lang('hr.manager')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent

    <div class="modal fade department_modal" tabindex="-1" role="dialog"></div>
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var hr_department_table = $('#hr_department_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Hr\DepartmentController::class, "index"])}}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'parent_name', name: 'parent_name' },
                { data: 'manager_name', name: 'manager_name' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $(document).on('submit', 'form#department_add_form, form#department_edit_form', function(e) {
            e.preventDefault();
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: $(this).serialize(),
                success: function(result) {
                    if (result.success === true) {
                        $('div.department_modal').modal('hide');
                        toastr.success(result.msg);
                        hr_department_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', 'button.delete_department_button', function() {
            swal({
                title: LANG.sure,
                text: "{{__('hr.confirm_delete_department')}}",
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
                                hr_department_table.ajax.reload();
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
