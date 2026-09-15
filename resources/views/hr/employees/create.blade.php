@extends('layouts.app')
@section('title', __('hr.add_employee'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.add_employee')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Hr\EmployeeController::class, 'store']), 'method' => 'post']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        @include('hr.employees.partials.form')
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
        $('#date_of_joining, #date_of_birth').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd',
            todayHighlight: true,
        });
    });
</script>
@endsection
