@extends('layouts.app')
@section('title', __('van_sales.vehicles'))

@section('content')

<section class="content-header">
    <h1>@lang('van_sales.vehicles')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('van_sales.all_vehicles')])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal"
                    data-href="{{action([\App\Http\Controllers\VanSalesVehicleController::class, 'create'])}}"
                    data-container=".view_modal">
                    <i class="fa fa-plus"></i> @lang('messages.add')</button>
            </div>
        @endslot
        <table class="table table-bordered table-striped" id="van_sales_vehicle_table">
            <thead>
                <tr>
                    <th>@lang('van_sales.name')</th>
                    <th>@lang('van_sales.plate_number')</th>
                    <th>@lang('van_sales.notes')</th>
                    <th>@lang('sale.status')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        var van_sales_vehicle_table = $('#van_sales_vehicle_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{action([\App\Http\Controllers\VanSalesVehicleController::class, 'index'])}}",
            columnDefs: [{
                "targets": 4,
                "orderable": false,
                "searchable": false
            }],
            columns: [
                { data: 'name', name: 'name' },
                { data: 'plate_number', name: 'plate_number' },
                { data: 'notes', name: 'notes' },
                { data: 'is_active', name: 'is_active' },
                { data: 'action', name: 'action' },
            ]
        });

        $(document).on('submit', 'form#van_sales_vehicle_form', function (e) {
            e.preventDefault();
            $(this).find('button[type="submit"]').attr('disabled', true);
            var data = $(this).serialize();

            $.ajax({
                method: $(this).attr('method'),
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                success: function (result) {
                    if (result.success == true) {
                        $('div.view_modal').modal('hide');
                        toastr.success(result.msg);
                        van_sales_vehicle_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });
    });
</script>
@endsection
