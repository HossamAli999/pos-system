@extends('layouts.app')
@section('title', __('crm.pipelines'))

@section('content')

<section class="content-header">
    <h1>@lang('crm.pipelines')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <a href="{{action([\App\Http\Controllers\Crm\PipelineController::class, 'create'])}}" class="btn btn-block btn-primary">
                    <i class="fa fa-plus"></i> @lang('crm.add_pipeline')
                </a>
            </div>
        @endslot

        <table class="table table-bordered table-striped" id="crm_pipeline_table">
            <thead>
                <tr>
                    <th>@lang('crm.pipeline_name')</th>
                    <th>@lang('crm.stages')</th>
                    <th>@lang('crm.is_default')</th>
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
        var crm_pipeline_table = $('#crm_pipeline_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Crm\PipelineController::class, "index"])}}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'stages_count', name: 'stages_count', orderable: false, searchable: false },
                { data: 'is_default', name: 'is_default', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $(document).on('click', 'button.delete_pipeline_button', function() {
            swal({
                title: LANG.sure,
                text: "{{__('crm.confirm_delete_pipeline')}}",
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
                                crm_pipeline_table.ajax.reload();
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
