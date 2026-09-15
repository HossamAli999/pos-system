@extends('layouts.app')
@section('title', __('gl.profit_and_loss'))

@section('content')

<section class="content-header">
    <h1>@lang('gl.profit_and_loss')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Accounting\GlReportController::class, 'profitAndLoss']), 'method' => 'get']) !!}
    <div class="row" style="margin-bottom:15px;">
        <div class="col-md-3">
            {!! Form::label('start_date', __('gl.date_range').':') !!}
            {!! Form::text('start_date', $start_date, ['class' => 'form-control', 'id' => 'pl_start_date']); !!}
        </div>
        <div class="col-md-3">
            <label>&nbsp;</label>
            {!! Form::text('end_date', $end_date, ['class' => 'form-control', 'id' => 'pl_end_date']); !!}
        </div>
        <div class="col-md-3">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary form-control">@lang('messages.filter')</button>
        </div>
    </div>
    {!! Form::close() !!}

    @component('components.widget', ['class' => 'box-primary', 'title' => __('gl.income')])
        <table class="table table-bordered">
            @forelse($income as $row)
                <tr><td>{{ $row->name }}</td><td class="text-right">@format_currency($row->net_amount)</td></tr>
            @empty
                <tr><td class="text-center text-muted">--</td></tr>
            @endforelse
            <tr class="totals"><th>@lang('gl.total_income')</th><th class="text-right">@format_currency($total_income)</th></tr>
        </table>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('gl.expenses')])
        <table class="table table-bordered">
            @forelse($expense as $row)
                <tr><td>{{ $row->name }}</td><td class="text-right">@format_currency($row->net_amount)</td></tr>
            @empty
                <tr><td class="text-center text-muted">--</td></tr>
            @endforelse
            <tr class="totals"><th>@lang('gl.total_expenses')</th><th class="text-right">@format_currency($total_expense)</th></tr>
        </table>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
        <h4 class="text-right">@lang('gl.net_profit'): @format_currency($net_profit)</h4>
    @endcomponent
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#pl_start_date, #pl_end_date').datepicker({ autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true });
    });
</script>
@endsection
