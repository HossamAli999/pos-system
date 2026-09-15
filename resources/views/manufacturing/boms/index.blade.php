@extends('layouts.app')
@section('title', __('manufacturing.bill_of_materials'))

@section('content')

<section class="content-header">
    <h1>@lang('manufacturing.bill_of_materials')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @can('manufacturing.manage')
            @slot('tool')
                <div class="box-tools">
                    <a href="{{action([\App\Http\Controllers\Manufacturing\ManufacturingController::class, 'create'])}}" class="btn btn-primary"><i class="fa fa-plus"></i> @lang('manufacturing.add_bom')</a>
                </div>
            @endslot
        @endcan

        <table class="table table-bordered table-striped" id="bom_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('manufacturing.finished_product')</th>
                    <th>@lang('manufacturing.output_quantity')</th>
                    <th>@lang('manufacturing.unit')</th>
                    <th>@lang('manufacturing.version')</th>
                    <th>@lang('manufacturing.is_active')</th>
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
        var bom_table = $('#bom_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Manufacturing\ManufacturingController::class, "index"])}}',
            columns: [
                { data: 'product_name', name: 'product_name' },
                { data: 'output_quantity', name: 'output_quantity' },
                { data: 'unit_name', name: 'unit_name' },
                { data: 'version', name: 'version' },
                { data: 'is_active', name: 'is_active', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $(document).on('click', 'button.delete_bom_button', function() {
            swal({
                title: LANG.sure,
                text: "{{__('manufacturing.confirm_delete_bom')}}",
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
                                bom_table.ajax.reload();
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
