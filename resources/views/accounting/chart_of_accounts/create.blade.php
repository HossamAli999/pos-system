@extends('layouts.app')
@section('title', __('gl.add_account'))

@section('content')

<section class="content-header">
    <h1>@lang('gl.add_account')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Accounting\ChartOfAccountController::class, 'store']), 'method' => 'post']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        @include('accounting.chart_of_accounts.partials.form')
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
        $('#coa_opening_balance_date').datepicker({ autoclose: true, format: 'yyyy-mm-dd' });
    });
</script>
@endsection
