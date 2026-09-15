@extends('layouts.app')
@section('title', __('project.add_project'))

@section('content')

<section class="content-header">
    <h1>@lang('project.add_project')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\ProjectController::class, 'store']), 'method' => 'post']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('name', __('project.name').':*') !!}
                    {!! Form::text('name', null, ['class' => 'form-control', 'required']); !!}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('contact_id', __('project.client').':') !!}
                    {!! Form::select('contact_id', $contacts, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.none')]); !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('start_date', __('project.start_date').':') !!}
                    {!! Form::text('start_date', null, ['class' => 'form-control', 'id' => 'project_start_date']); !!}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('end_date', __('project.end_date').':') !!}
                    {!! Form::text('end_date', null, ['class' => 'form-control', 'id' => 'project_end_date']); !!}
                </div>
            </div>
        </div>
        <div class="form-group">
            {!! Form::label('description', __('project.description').':') !!}
            {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3]); !!}
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
        $('#project_start_date, #project_end_date').datepicker({ autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true });
    });
</script>
@endsection
