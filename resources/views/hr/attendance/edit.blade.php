@extends('layouts.app')
@section('title', __('hr.edit_attendance'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.edit_attendance')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Hr\AttendanceController::class, 'update'], [$log->id]), 'method' => 'put']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>@lang('hr.employee')</label>
                    <p class="form-control-static">{{ $log->employee->full_name ?? '' }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>@lang('hr.attendance_date')</label>
                    <p class="form-control-static">{{ $log->attendance_date->format('Y-m-d') }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('status', __('hr.attendance_status').':') !!}
                    {!! Form::select('status', ['present' => __('hr.present'), 'late' => __('hr.late'), 'half_day' => __('hr.half_day'), 'absent' => __('hr.absent')], $log->status, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('clock_in', __('hr.clock_in').':') !!}
                    {!! Form::text('clock_in', !empty($log->clock_in) ? \Carbon::parse($log->clock_in)->format('H:i') : null, ['class' => 'form-control', 'placeholder' => 'HH:MM']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('clock_out', __('hr.clock_out').':') !!}
                    {!! Form::text('clock_out', !empty($log->clock_out) ? \Carbon::parse($log->clock_out)->format('H:i') : null, ['class' => 'form-control', 'placeholder' => 'HH:MM']); !!}
                </div>
            </div>
        </div>
    @endcomponent
    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary btn-big">@lang('messages.update')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>

@stop
