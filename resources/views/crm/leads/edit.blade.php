@extends('layouts.app')
@section('title', __('crm.edit_lead'))

@section('content')

<section class="content-header">
    <h1>@lang('crm.edit_lead')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Crm\LeadController::class, 'update'], [$lead->id]), 'method' => 'put']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        @include('crm.leads.partials.form')
    @endcomponent
    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary btn-big">@lang('messages.update')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#expected_close_date').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd',
            todayHighlight: true,
        });
    });
</script>
@endsection
