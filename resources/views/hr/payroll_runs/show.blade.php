@extends('layouts.app')
@section('title', __('hr.payroll_runs'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.payroll_runs')
        <small>{{ $payroll_run->pay_period_start->format('Y-m-d') }} &mdash; {{ $payroll_run->pay_period_end->format('Y-m-d') }}</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                @if($payroll_run->status == 'draft' && auth()->user()->can('payroll.approve'))
                    {!! Form::open(['url' => action([\App\Http\Controllers\Hr\PayrollRunController::class, 'approve'], [$payroll_run->id]), 'method' => 'post', 'style' => 'display:inline-block;']) !!}
                    <button type="submit" class="btn btn-info"><i class="fa fa-check"></i> @lang('hr.approve_run')</button>
                    {!! Form::close() !!}
                @endif

                @if(in_array($payroll_run->status, ['draft']) && auth()->user()->can('payroll.manage'))
                    {!! Form::open(['url' => action([\App\Http\Controllers\Hr\PayrollRunController::class, 'cancel'], [$payroll_run->id]), 'method' => 'post', 'style' => 'display:inline-block;']) !!}
                    <button type="submit" class="btn btn-danger"><i class="fa fa-times"></i> @lang('messages.cancel')</button>
                    {!! Form::close() !!}
                @endif

                @if($payroll_run->status == 'approved' && auth()->user()->can('payroll.post_to_ledger'))
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target=".post_to_ledger_modal"><i class="fa fa-money-check-alt"></i> @lang('hr.post_to_ledger')</button>
                @endif
            </div>
        @endslot

        <table class="table">
            <tr><th>@lang('hr.location')</th><td>{{ $payroll_run->location->name ?? __('lang_v1.all') }}</td></tr>
            <tr><th>@lang('hr.pay_date')</th><td>{{ $payroll_run->pay_date->format('Y-m-d') }}</td></tr>
            <tr><th>@lang('hr.status')</th><td>{{ __('hr.payroll_status_'.$payroll_run->status) }}</td></tr>
            <tr><th>@lang('hr.total_earnings')</th><td>@format_currency($payroll_run->total_earnings)</td></tr>
            <tr><th>@lang('hr.total_deductions')</th><td>@format_currency($payroll_run->total_deductions)</td></tr>
            <tr><th>@lang('hr.total_net_pay')</th><td><strong>@format_currency($payroll_run->total_net_pay)</strong></td></tr>
        </table>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('hr.payslips')])
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>@lang('hr.employee')</th>
                    <th>@lang('hr.gross_earnings')</th>
                    <th>@lang('hr.total_deductions')</th>
                    <th>@lang('hr.net_pay')</th>
                    <th>@lang('hr.unpaid_days')</th>
                    <th>@lang('hr.payment_status')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payroll_run->payslips as $payslip)
                    <tr>
                        <td>{{ $payslip->employee->full_name ?? '' }}</td>
                        <td>@format_currency($payslip->gross_earnings)</td>
                        <td>@format_currency($payslip->total_deductions)</td>
                        <td>@format_currency($payslip->net_pay)</td>
                        <td>{{ $payslip->unpaid_days }}</td>
                        <td>{{ $payslip->payment_status == 'paid' ? __('hr.paid') : __('hr.unpaid') }}</td>
                        <td>
                            <a href="{{action([\App\Http\Controllers\Hr\PayrollRunController::class, 'payslipPdf'], [$payslip->id])}}" target="_blank" class="btn btn-xs btn-default"><i class="fa fa-file-pdf"></i> @lang('hr.download_payslip')</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">--</td></tr>
                @endforelse
            </tbody>
        </table>
    @endcomponent
</section>

<div class="modal fade post_to_ledger_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['url' => action([\App\Http\Controllers\Hr\PayrollRunController::class, 'postToLedger'], [$payroll_run->id]), 'method' => 'post']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('hr.post_to_ledger')</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('account_id', __('hr.select_payment_account').':*') !!}
                    {!! Form::select('account_id', $accounts, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'required']); !!}
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

@stop
