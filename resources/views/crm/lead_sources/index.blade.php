@extends('layouts.app')
@section('title', __('crm.lead_sources'))

@section('content')

<section class="content-header">
    <h1>@lang('crm.lead_sources')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal"
                    data-href="{{action([\App\Http\Controllers\Crm\LeadSourceController::class, 'create'])}}"
                    data-container=".lead_source_modal">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </button>
            </div>
        @endslot

        <table class="table table-bordered table-striped" id="crm_lead_source_table">
            <thead>
                <tr>
                    <th>@lang('crm.source_name')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent

    <div class="modal fade lead_source_modal" tabindex="-1" role="dialog"></div>
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var crm_lead_source_table = $('#crm_lead_source_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Crm\LeadSourceController::class, "index"])}}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $(document).on('submit', 'form#lead_source_add_form, form#lead_source_edit_form', function(e) {
            e.preventDefault();
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: $(this).serialize(),
                success: function(result) {
                    if (result.success === true) {
                        $('div.lead_source_modal').modal('hide');
                        toastr.success(result.msg);
                        crm_lead_source_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', 'button.delete_lead_source_button', function() {
            swal({
                title: LANG.sure,
                text: "{{__('crm.confirm_delete_lead_source')}}",
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
                                crm_lead_source_table.ajax.reload();
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
