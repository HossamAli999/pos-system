@extends('layouts.app')
@section('title', __('manufacturing.work_orders'))

@section('content')

@php
    $status_labels = ['planned' => 'bg-yellow', 'in_progress' => 'bg-info', 'completed' => 'bg-green', 'cancelled' => 'bg-red'];
@endphp

<section class="content-header">
    <h1>{{ $work_order->ref_no }}
        <span class="label {{ $status_labels[$work_order->status] ?? 'bg-gray' }}">{{ __('manufacturing.status_'.$work_order->status) }}</span>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                @if($work_order->status == 'planned')
                    <form method="post" action="{{action([\App\Http\Controllers\Manufacturing\WorkOrderController::class, 'start'], [$work_order->id])}}" style="display:inline-block;">
                        @csrf
                        <button type="submit" class="btn btn-info"><i class="fa fa-play"></i> @lang('manufacturing.start_work_order')</button>
                    </form>
                @endif

                @if(in_array($work_order->status, ['planned', 'in_progress']))
                    @can('work_order.complete')
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target=".complete_production_modal"><i class="fa fa-check"></i> @lang('manufacturing.complete_production')</button>
                    @endcan
                    <button type="button" id="cancel_work_order_btn" class="btn btn-danger"><i class="fa fa-times"></i> @lang('messages.cancel')</button>
                @endif
            </div>
        @endslot

        <table class="table">
            <tr><th>@lang('manufacturing.finished_product')</th><td>{{ $work_order->bill_of_material->product->name ?? '' }}</td></tr>
            <tr><th>@lang('manufacturing.location')</th><td>{{ $work_order->location->name ?? '' }}</td></tr>
            <tr><th>@lang('manufacturing.planned_quantity')</th><td>{{ $work_order->planned_quantity }}</td></tr>
            <tr><th>@lang('manufacturing.produced_quantity')</th><td>{{ $work_order->produced_quantity }}</td></tr>
            <tr><th>@lang('manufacturing.planned_date')</th><td>{{ !empty($work_order->planned_date) ? $work_order->planned_date->format('Y-m-d') : '--' }}</td></tr>
            <tr><th>@lang('manufacturing.notes')</th><td>{{ $work_order->notes }}</td></tr>
        </table>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('manufacturing.raw_materials')])
        <table class="table table-bordered">
            <thead><tr><th>@lang('manufacturing.raw_material')</th><th>@lang('manufacturing.quantity_required')</th></tr></thead>
            <tbody>
            @php $batches = $work_order->planned_quantity / max($work_order->bill_of_material->output_quantity, 0.0001); @endphp
            @foreach($work_order->bill_of_material->items as $item)
                <tr>
                    <td>{{ $item->raw_material_product->name ?? '' }}</td>
                    <td>{{ round($item->requiredQuantityFor($batches), 4) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endcomponent

    @if($work_order->material_consumptions->count() > 0)
        @component('components.widget', ['class' => 'box-primary', 'title' => __('manufacturing.materials_consumed')])
            <table class="table table-bordered">
                <thead><tr><th>@lang('manufacturing.raw_material')</th><th>@lang('manufacturing.quantity_consumed')</th></tr></thead>
                <tbody>
                @foreach($work_order->material_consumptions as $consumption)
                    <tr>
                        <td>{{ $consumption->raw_material_product->name ?? '' }}</td>
                        <td>{{ $consumption->quantity_consumed }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endcomponent
    @endif
</section>

<div class="modal fade complete_production_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['url' => action([\App\Http\Controllers\Manufacturing\WorkOrderController::class, 'complete'], [$work_order->id]), 'method' => 'post']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('manufacturing.complete_production')</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('produced_quantity', __('manufacturing.produced_quantity').':*') !!}
                    {!! Form::text('produced_quantity', $work_order->planned_quantity - $work_order->produced_quantity, ['class' => 'form-control input_number', 'required']); !!}
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

<form id="cancel_work_order_form" method="post" action="{{action([\App\Http\Controllers\Manufacturing\WorkOrderController::class, 'cancel'], [$work_order->id])}}" style="display:none;">
    @csrf
</form>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#cancel_work_order_btn').on('click', function() {
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((willCancel) => {
                if (willCancel) {
                    $('#cancel_work_order_form').submit();
                }
            });
        });
    });
</script>
@endsection
