@extends('layouts.app')
@section('title', __('project.edit_project'))

@section('content')

<section class="content-header">
    <h1>@lang('project.edit_project')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\ProjectController::class, 'update'], [$project->id]), 'method' => 'put']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('name', __('project.name').':*') !!}
                    {!! Form::text('name', $project->name, ['class' => 'form-control', 'required']); !!}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('contact_id', __('project.client').':') !!}
                    {!! Form::select('contact_id', $contacts, $project->contact_id, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.none')]); !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('status', __('project.status').':') !!}
                    {!! Form::select('status', [
                        'active' => __('project.status_active'), 'on_hold' => __('project.status_on_hold'),
                        'completed' => __('project.status_completed'), 'cancelled' => __('project.status_cancelled'),
                    ], $project->status, ['class' => 'form-control']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('start_date', __('project.start_date').':') !!}
                    {!! Form::text('start_date', !empty($project->start_date) ? $project->start_date->format('Y-m-d') : null, ['class' => 'form-control', 'id' => 'project_start_date']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('end_date', __('project.end_date').':') !!}
                    {!! Form::text('end_date', !empty($project->end_date) ? $project->end_date->format('Y-m-d') : null, ['class' => 'form-control', 'id' => 'project_end_date']); !!}
                </div>
            </div>
        </div>
        <div class="form-group">
            {!! Form::label('description', __('project.description').':') !!}
            {!! Form::textarea('description', $project->description, ['class' => 'form-control', 'rows' => 3]); !!}
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

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#project_start_date, #project_end_date').datepicker({ autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true });
    });
</script>
@endsection
