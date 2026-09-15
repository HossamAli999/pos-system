@extends('layouts.app')
@section('title', __('hr.payroll_runs'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.payroll_runs')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @can('payroll.manage')
            @slot('tool')
                <div class="box-tools">
                    <a href="{{action([\App\Http\Controllers\Hr\PayrollRunController::class, 'create'])}}" class="btn btn-block btn-primary">
                        <i class="fa fa-plus"></i> @lang('hr.generate_payroll_run')
                    </a>
                </div>
            @endslot
        @endcan

        <table class="table table-bordered table-striped" id="hr_payroll_run_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('hr.pay_period_start')</th>
                    <th>@lang('hr.pay_period_end')</th>
                    <th>@lang('hr.pay_date')</th>
                    <th>@lang('hr.location')</th>
                    <th>@lang('hr.total_net_pay')</th>
                    <th>@lang('hr.status')</th>
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
        $('#hr_payroll_run_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Hr\PayrollRunController::class, "index"])}}',
            columns: [
                { data: 'pay_period_start', name: 'pay_period_start' },
                { data: 'pay_period_end', name: 'pay_period_end' },
                { data: 'pay_date', name: 'pay_date' },
                { data: 'location_name', name: 'location_name' },
                { data: 'total_net_pay', name: 'total_net_pay' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });
    });
</script>
@endsection
