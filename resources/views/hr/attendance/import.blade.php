@extends('layouts.app')
@section('title', __('hr.import_attendance'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.import_attendance')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Hr\AttendanceController::class, 'import']), 'method' => 'post', 'files' => true]) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <p class="help-block">@lang('hr.import_attendance_help')</p>
        <div class="form-group">
            {!! Form::label('attendance_file', __('lang_v1.file').':*') !!}
            {!! Form::file('attendance_file', ['required']); !!}
        </div>
    @endcomponent
    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary btn-big">@lang('lang_v1.upload')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>

@stop
