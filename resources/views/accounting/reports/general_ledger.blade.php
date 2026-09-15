@extends('layouts.app')
@section('title', __('gl.general_ledger'))

@section('content')

<section class="content-header">
    <h1>@lang('gl.general_ledger')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Accounting\GlReportController::class, 'generalLedger']), 'method' => 'get']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-4">
                {!! Form::label('chart_of_account_id', __('gl.account').':') !!}
                {!! Form::select('chart_of_account_id', $accounts, $chart_of_account_id, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('gl.select_account')]); !!}
            </div>
            <div class="col-md-3">
                {!! Form::label('start_date', __('gl.date_range').':') !!}
                {!! Form::text('start_date', $start_date, ['class' => 'form-control', 'id' => 'gl_start_date']); !!}
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                {!! Form::text('end_date', $end_date, ['class' => 'form-control', 'id' => 'gl_end_date']); !!}
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary form-control">@lang('messages.filter')</button>
            </div>
        </div>

        @if(! empty($account))
            <table class="table table-bordered" style="margin-top:15px;">
                <thead>
                    <tr>
                        <th>@lang('gl.entry_date')</th>
                        <th>@lang('gl.entry_type')</th>
                        <th>@lang('gl.narration')</th>
                        <th>@lang('gl.debit')</th>
                        <th>@lang('gl.credit')</th>
                        <th>@lang('gl.balance')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lines as $line)
                        <tr>
                            <td><a href="{{action([\App\Http\Controllers\Accounting\JournalEntryController::class, 'show'], [$line->journal_entry_id])}}">{{ \Carbon::parse($line->entry_date)->format('Y-m-d') }}</a></td>
                            <td>{{ __('gl.type_'.$line->entry_type) }}</td>
                            <td>{{ $line->narration }}</td>
                            <td>@if($line->debit > 0)@format_currency($line->debit)@endif</td>
                            <td>@if($line->credit > 0)@format_currency($line->credit)@endif</td>
                            <td>@format_currency($line->running_balance)</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">--</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    @endcomponent
    {!! Form::close() !!}
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#gl_start_date, #gl_end_date').datepicker({ autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true });
    });
</script>
@endsection
