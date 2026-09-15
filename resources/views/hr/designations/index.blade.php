@extends('layouts.app')
@section('title', __('hr.designations'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.designations')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @can('employee.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal"
                        data-href="{{action([\App\Http\Controllers\Hr\DesignationController::class, 'create'])}}"
                        data-container=".designation_modal">
                        <i class="fa fa-plus"></i> @lang('messages.add')
                    </button>
                </div>
            @endslot
        @endcan

        <table class="table table-bordered table-striped" id="hr_designation_table">
            <thead>
                <tr>
                    <th>@lang('hr.designation_name')</th>
                    <th>@lang('hr.department')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent

    <div class="modal fade designation_modal" tabindex="-1" role="dialog"></div>
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var hr_designation_table = $('#hr_designation_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Hr\DesignationController::class, "index"])}}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'department_name', name: 'department_name' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $(document).on('submit', 'form#designation_add_form, form#designation_edit_form', function(e) {
            e.preventDefault();
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: $(this).serialize(),
                success: function(result) {
                    if (result.success === true) {
                        $('div.designation_modal').modal('hide');
                        toastr.success(result.msg);
                        hr_designation_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', 'button.delete_designation_button', function() {
            swal({
                title: LANG.sure,
                text: "{{__('hr.confirm_delete_designation')}}",
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
                                hr_designation_table.ajax.reload();
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
