@extends('layouts.app')
@section('title', __('hr.generate_payroll_run'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.generate_payroll_run')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Hr\PayrollRunController::class, 'store']), 'method' => 'post']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('location_id', __('hr.location').':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('pay_date', __('hr.pay_date').':*') !!}
                    {!! Form::text('pay_date', \Carbon::now()->toDateString(), ['class' => 'form-control', 'id' => 'pay_date', 'required']); !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('pay_period_start', __('hr.pay_period_start').':*') !!}
                    {!! Form::text('pay_period_start', \Carbon::now()->startOfMonth()->toDateString(), ['class' => 'form-control', 'id' => 'pay_period_start', 'required']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('pay_period_end', __('hr.pay_period_end').':*') !!}
                    {!! Form::text('pay_period_end', \Carbon::now()->endOfMonth()->toDateString(), ['class' => 'form-control', 'id' => 'pay_period_end', 'required']); !!}
                </div>
            </div>
        </div>
    @endcomponent
    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary btn-big">@lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#pay_date, #pay_period_start, #pay_period_end').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd',
            todayHighlight: true,
        });
    });
</script>
@endsection
