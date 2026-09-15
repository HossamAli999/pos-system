@extends('layouts.app')
@section('title', __('hr.request_leave'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.request_leave')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Hr\LeaveRequestController::class, 'store']), 'method' => 'post']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            @if(! empty($employees))
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('employee_id', __('hr.employee').':*') !!}
                        {!! Form::select('employee_id', $employees, $employee->id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'required']); !!}
                    </div>
                </div>
            @endif
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('leave_type_id', __('hr.leave_type').':*') !!}
                    {!! Form::select('leave_type_id', $leave_types, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'required']); !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('start_date', __('hr.start_date').':*') !!}
                    {!! Form::text('start_date', null, ['class' => 'form-control', 'id' => 'leave_start_date', 'required']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('end_date', __('hr.end_date').':*') !!}
                    {!! Form::text('end_date', null, ['class' => 'form-control', 'id' => 'leave_end_date', 'required']); !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    {!! Form::label('reason', __('hr.reason').':') !!}
                    {!! Form::textarea('reason', null, ['class' => 'form-control', 'rows' => 3]); !!}
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
        $('#leave_start_date, #leave_end_date').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd',
            todayHighlight: true,
        });
    });
</script>
@endsection
