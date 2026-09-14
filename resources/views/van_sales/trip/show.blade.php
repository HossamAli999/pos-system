@extends('layouts.app')
@section('title', __('van_sales.trip_details'))

@php
    $status_labels = [
        'out' => 'label-info',
        'pending_approval' => 'label-warning',
        'closed' => 'label-success',
        'rejected' => 'label-danger',
    ];
    $is_rep = $trip->rep_id == auth()->user()->id;
    $can_manage = auth()->user()->can('van_sales.manage');
    $can_approve = auth()->user()->can('van_sales.approve');
@endphp

@section('content')

<section class="content-header">
    <h1>
        @lang('van_sales.trip') #{{ $trip->id }}
        <span class="label {{ $status_labels[$trip->status] ?? 'label-default' }}">@lang('van_sales.status_' . $trip->status)</span>
    </h1>
</section>

<section class="content">

    @component('components.widget', ['class' => 'box-primary', 'title' => __('van_sales.trip_details')])
        <div class="row">
            <div class="col-sm-3">
                <strong>@lang('van_sales.vehicle'):</strong><br>
                {{ optional($trip->vehicle)->name }}
            </div>
            <div class="col-sm-3">
                <strong>@lang('van_sales.rep'):</strong><br>
                {{ optional($trip->rep)->first_name }} {{ optional($trip->rep)->last_name }}
            </div>
            <div class="col-sm-3">
                <strong>@lang('van_sales.source_location'):</strong><br>
                {{ optional($trip->source_location)->name }}
            </div>
            <div class="col-sm-3">
                <strong>@lang('van_sales.started_at'):</strong><br>
                {{ $trip->started_at }}
            </div>
        </div>
        @if($trip->status == 'closed')
        <div class="row" style="margin-top: 15px;">
            <div class="col-sm-3">
                <strong>@lang('van_sales.closed_at'):</strong><br>
                {{ $trip->closed_at }}
            </div>
            <div class="col-sm-3">
                <strong>@lang('van_sales.opening_cash'):</strong><br>
                {{ $trip->opening_cash }}
            </div>
            <div class="col-sm-3">
                <strong>@lang('van_sales.closing_cash'):</strong><br>
                {{ $trip->closing_cash }}
            </div>
            <div class="col-sm-3">
                <strong>@lang('van_sales.approved_by'):</strong><br>
                {{ optional($trip->approver)->first_name }} {{ optional($trip->approver)->last_name }}
            </div>
        </div>
        @endif
        @if(!empty($trip->manager_note))
        <div class="row" style="margin-top: 15px;">
            <div class="col-sm-12">
                <strong>@lang('van_sales.manager_note'):</strong> {{ $trip->manager_note }}
            </div>
        </div>
        @endif
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('van_sales.reconciliation')])
        @if($trip->status == 'out' && ($is_rep || $can_manage))
            {!! Form::open(['url' => action([\App\Http\Controllers\VanSalesTripController::class, 'submitForApproval'], [$trip->id]), 'method' => 'post', 'id' => 'submit_for_approval_form']) !!}
        @endif
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>@lang('van_sales.product')</th>
                    <th>@lang('van_sales.qty_loaded')</th>
                    <th>@lang('van_sales.qty_remaining')</th>
                    <th>@lang('van_sales.qty_sold')</th>
                    @if($trip->status == 'out' && ($is_rep || $can_manage))
                        <th>@lang('van_sales.qty_returned_counted')</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($reconciliation as $i => $line)
                <tr>
                    <td>
                        {{ $line['product_name'] }}
                        <input type="hidden" name="returns[{{ $i }}][product_id]" value="{{ $line['product_id'] }}">
                        <input type="hidden" name="returns[{{ $i }}][variation_id]" value="{{ $line['variation_id'] }}">
                    </td>
                    <td>{{ number_format($line['qty_loaded'], 2) }}</td>
                    <td>{{ number_format($line['qty_remaining'], 2) }}</td>
                    <td>{{ number_format($line['qty_sold'], 2) }}</td>
                    @if($trip->status == 'out' && ($is_rep || $can_manage))
                    <td>
                        <input type="number" step="0.01" min="0" class="form-control"
                            name="returns[{{ $i }}][qty_returned]" value="{{ $line['qty_remaining'] }}">
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($trip->status == 'out' && ($is_rep || $can_manage))
            <div class="row">
                <div class="col-sm-4">
                    {!! Form::label('counted_cash', __('van_sales.counted_cash') . ':') !!}
                    {!! Form::text('counted_cash', 0, ['class' => 'form-control']); !!}
                </div>
                <div class="col-sm-8">
                    {!! Form::label('rep_note', __('van_sales.rep_note') . ':') !!}
                    {!! Form::textarea('rep_note', null, ['class' => 'form-control', 'rows' => 2]); !!}
                </div>
            </div>
            <br>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> @lang('van_sales.submit_for_approval')
            </button>
            {!! Form::close() !!}
        @endif

        @if($trip->status == 'pending_approval')
            <h4>@lang('van_sales.rep_submitted_returns')</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>@lang('van_sales.product')</th>
                        <th>@lang('van_sales.qty_returned_counted')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($trip->return_lines as $line)
                    <tr>
                        <td>{{ optional($line->product)->name }}</td>
                        <td>{{ number_format($line->qty_returned, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <p><strong>@lang('van_sales.counted_cash'):</strong> {{ $trip->proposed_closing_cash }}</p>
            @if(!empty($trip->rep_note))
                <p><strong>@lang('van_sales.rep_note'):</strong> {{ $trip->rep_note }}</p>
            @endif

            @if($can_approve)
            <div class="row">
                <div class="col-sm-12">
                    {!! Form::open(['url' => action([\App\Http\Controllers\VanSalesTripController::class, 'approve'], [$trip->id]), 'method' => 'post', 'id' => 'approve_trip_form', 'style' => 'display:inline-block']) !!}
                        {!! Form::text('manager_note', null, ['class' => 'form-control', 'placeholder' => __('van_sales.manager_note'), 'style' => 'display:inline-block; width:300px;']) !!}
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> @lang('van_sales.approve')</button>
                    {!! Form::close() !!}

                    {!! Form::open(['url' => action([\App\Http\Controllers\VanSalesTripController::class, 'reject'], [$trip->id]), 'method' => 'post', 'id' => 'reject_trip_form', 'style' => 'display:inline-block']) !!}
                        <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> @lang('van_sales.reject')</button>
                    {!! Form::close() !!}
                </div>
            </div>
            @else
                <p class="text-muted">@lang('van_sales.awaiting_manager_approval')</p>
            @endif
        @endif
    @endcomponent

    @if(!empty($register_details))
    @component('components.widget', ['class' => 'box-primary', 'title' => __('van_sales.cash_register')])
        <div class="row">
            <div class="col-sm-4">
                <strong>@lang('van_sales.opening_cash'):</strong> {{ $trip->opening_cash }}
            </div>
            <div class="col-sm-4">
                <strong>@lang('van_sales.register_status'):</strong> {{ optional($trip->cash_register)->status }}
            </div>
            @if(optional($trip->cash_register)->status == 'close')
            <div class="col-sm-4">
                <strong>@lang('van_sales.closing_amount'):</strong> {{ $trip->cash_register->closing_amount }}
            </div>
            @endif
        </div>
    @endcomponent
    @endif

    @component('components.widget', ['class' => 'box-primary', 'title' => __('van_sales.sales_during_trip')])
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>@lang('sale.invoice_no')</th>
                    <th>@lang('contact.customer')</th>
                    <th>@lang('sale.total_amount')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                <tr>
                    <td>{{ $sale->invoice_no }}</td>
                    <td>{{ optional($sale->contact)->name }}</td>
                    <td>{{ number_format($sale->final_total, 2) }}</td>
                    <td>
                        <a href="{{ action([\App\Http\Controllers\SellController::class, 'show'], [$sale->id]) }}" class="btn btn-xs btn-primary">
                            <i class="fas fa-eye"></i> @lang('messages.view')
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4">@include('components.empty_state', ['icon' => 'fas fa-receipt'])</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    @endcomponent

</section>
@stop
