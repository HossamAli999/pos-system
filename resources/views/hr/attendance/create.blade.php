@extends('layouts.app')
@section('title', __('hr.add_attendance'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.add_attendance')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Hr\AttendanceController::class, 'store']), 'method' => 'post']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('employee_id', __('hr.employee').':*') !!}
                    {!! Form::select('employee_id', $employees, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'required']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('attendance_date', __('hr.attendance_date').':*') !!}
                    {!! Form::text('attendance_date', \Carbon::now()->toDateString(), ['class' => 'form-control', 'id' => 'attendance_date', 'required']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('status', __('hr.attendance_status').':') !!}
                    {!! Form::select('status', ['present' => __('hr.present'), 'late' => __('hr.late'), 'half_day' => __('hr.half_day'), 'absent' => __('hr.absent')], 'present', ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('clock_in', __('hr.clock_in').':') !!}
                    {!! Form::text('clock_in', null, ['class' => 'form-control', 'placeholder' => 'HH:MM']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('clock_out', __('hr.clock_out').':') !!}
                    {!! Form::text('clock_out', null, ['class' => 'form-control', 'placeholder' => 'HH:MM']); !!}
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
        $('#attendance_date').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd',
            todayHighlight: true,
        });
    });
</script>
@endsection
