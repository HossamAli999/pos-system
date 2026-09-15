@extends('layouts.app')
@section('title', __('hr.payroll_components'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.payroll_components')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal"
                    data-href="{{action([\App\Http\Controllers\Hr\PayrollComponentController::class, 'create'])}}"
                    data-container=".payroll_component_modal">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </button>
            </div>
        @endslot

        <table class="table table-bordered table-striped" id="hr_payroll_component_table">
            <thead>
                <tr>
                    <th>@lang('hr.component_name')</th>
                    <th>@lang('hr.component_type')</th>
                    <th>@lang('hr.is_taxable')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent

    <div class="modal fade payroll_component_modal" tabindex="-1" role="dialog"></div>
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var hr_payroll_component_table = $('#hr_payroll_component_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Hr\PayrollComponentController::class, "index"])}}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'type', name: 'type' },
                { data: 'is_taxable', name: 'is_taxable', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $(document).on('submit', 'form#payroll_component_add_form, form#payroll_component_edit_form', function(e) {
            e.preventDefault();
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: $(this).serialize(),
                success: function(result) {
                    if (result.success === true) {
                        $('div.payroll_component_modal').modal('hide');
                        toastr.success(result.msg);
                        hr_payroll_component_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', 'button.delete_payroll_component_button', function() {
            swal({
                title: LANG.sure,
                text: "{{__('hr.confirm_delete_payroll_component')}}",
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
                                hr_payroll_component_table.ajax.reload();
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
