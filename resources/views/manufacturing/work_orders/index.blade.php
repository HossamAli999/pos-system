@extends('layouts.app')
@section('title', __('manufacturing.work_orders'))

@section('content')

<section class="content-header">
    <h1>@lang('manufacturing.work_orders')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @can('work_order.create')
            @slot('tool')
                <div class="box-tools">
                    <a href="{{action([\App\Http\Controllers\Manufacturing\WorkOrderController::class, 'create'])}}" class="btn btn-primary"><i class="fa fa-plus"></i> @lang('manufacturing.add_work_order')</a>
                </div>
            @endslot
        @endcan

        <table class="table table-bordered table-striped" id="work_order_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('manufacturing.ref_no')</th>
                    <th>@lang('manufacturing.finished_product')</th>
                    <th>@lang('manufacturing.planned_quantity')</th>
                    <th>@lang('manufacturing.produced_quantity')</th>
                    <th>@lang('manufacturing.location')</th>
                    <th>@lang('manufacturing.status')</th>
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
        $('#work_order_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Manufacturing\WorkOrderController::class, "index"])}}',
            columns: [
                { data: 'ref_no', name: 'ref_no' },
                { data: 'product_name', name: 'product_name' },
                { data: 'planned_quantity', name: 'planned_quantity' },
                { data: 'produced_quantity', name: 'produced_quantity' },
                { data: 'location_name', name: 'location_name' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });
    });
</script>
@endsection
