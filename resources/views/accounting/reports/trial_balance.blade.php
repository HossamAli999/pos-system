@extends('layouts.app')
@section('title', __('gl.trial_balance'))

@section('content')

<section class="content-header">
    <h1>@lang('gl.trial_balance')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Accounting\GlReportController::class, 'trialBalance']), 'method' => 'get']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('end_date', __('gl.as_of_date').':') !!}
                    {!! Form::text('end_date', $end_date, ['class' => 'form-control', 'id' => 'tb_end_date']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary form-control">@lang('messages.filter')</button>
            </div>
        </div>

        @if(!empty(round($total_debit, 2)) && round($total_debit, 2) != round($total_credit, 2))
            <div class="alert alert-danger">@lang('gl.trial_balance_not_zero')</div>
        @endif

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>@lang('gl.code')</th>
                    <th>@lang('gl.account')</th>
                    <th>@lang('gl.account_category')</th>
                    <th>@lang('gl.total_debit')</th>
                    <th>@lang('gl.total_credit')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->code }}</td>
                        <td>{{ $row->name }}</td>
                        <td>{{ __('gl.category_'.$row->account_category) }}</td>
                        <td>@format_currency($row->total_debit)</td>
                        <td>@format_currency($row->total_credit)</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">--</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">@lang('gl.total')</th>
                    <th>@format_currency($total_debit)</th>
                    <th>@format_currency($total_credit)</th>
                </tr>
            </tfoot>
        </table>
    @endcomponent
    {!! Form::close() !!}
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#tb_end_date').datepicker({ autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true });
    });
</script>
@endsection
