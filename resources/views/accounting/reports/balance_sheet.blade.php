@extends('layouts.app')
@section('title', __('gl.balance_sheet'))

@section('content')

<section class="content-header">
    <h1>@lang('gl.balance_sheet')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Accounting\GlReportController::class, 'balanceSheet']), 'method' => 'get']) !!}
    <div class="row" style="margin-bottom:15px;">
        <div class="col-md-3">
            {!! Form::label('as_of_date', __('gl.as_of_date').':') !!}
            {!! Form::text('as_of_date', $as_of_date, ['class' => 'form-control', 'id' => 'bs_as_of_date']); !!}
        </div>
        <div class="col-md-3">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary form-control">@lang('messages.filter')</button>
        </div>
    </div>
    {!! Form::close() !!}

    <div class="row">
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('gl.assets')])
                <table class="table table-bordered">
                    @forelse($assets as $row)
                        <tr><td>{{ $row->name }}</td><td class="text-right">@format_currency($row->net_amount)</td></tr>
                    @empty
                        <tr><td class="text-center text-muted">--</td></tr>
                    @endforelse
                    <tr class="totals"><th>@lang('gl.total_assets')</th><th class="text-right">@format_currency($total_assets)</th></tr>
                </table>
            @endcomponent
        </div>
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('gl.liabilities')])
                <table class="table table-bordered">
                    @forelse($liabilities as $row)
                        <tr><td>{{ $row->name }}</td><td class="text-right">@format_currency($row->net_amount)</td></tr>
                    @empty
                        <tr><td class="text-center text-muted">--</td></tr>
                    @endforelse
                    <tr class="totals"><th>@lang('gl.total_liabilities')</th><th class="text-right">@format_currency($total_liabilities)</th></tr>
                </table>
            @endcomponent

            @component('components.widget', ['class' => 'box-primary', 'title' => __('gl.equity')])
                <table class="table table-bordered">
                    @forelse($equity as $row)
                        <tr><td>{{ $row->name }}</td><td class="text-right">@format_currency($row->net_amount)</td></tr>
                    @empty
                        <tr><td class="text-center text-muted">--</td></tr>
                    @endforelse
                    <tr><td>{{ __('gl.retained_earnings_plug') }}</td><td class="text-right">@format_currency($retained_earnings_plug)</td></tr>
                    <tr class="totals"><th>@lang('gl.total_equity')</th><th class="text-right">@format_currency($total_equity + $retained_earnings_plug)</th></tr>
                </table>
            @endcomponent
        </div>
    </div>
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#bs_as_of_date').datepicker({ autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true });
    });
</script>
@endsection
