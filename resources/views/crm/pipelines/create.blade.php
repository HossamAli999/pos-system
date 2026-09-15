@extends('layouts.app')
@section('title', __('crm.add_pipeline'))

@section('content')

<section class="content-header">
    <h1>@lang('crm.add_pipeline')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Crm\PipelineController::class, 'store']), 'method' => 'post']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('name', __('crm.pipeline_name').':*') !!}
                    {!! Form::text('name', null, ['class' => 'form-control', 'required']); !!}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="checkbox">
                        <label>{!! Form::checkbox('is_default', 1, false, ['class' => 'input-icheck']); !!} @lang('crm.is_default')</label>
                    </div>
                </div>
            </div>
        </div>
        <p class="help-block">{{ __('crm.stages') }}: {{ __('crm.stage_new') }}, {{ __('crm.stage_qualified') }}, {{ __('crm.stage_proposal') }}, {{ __('crm.stage_won') }}, {{ __('crm.stage_lost') }}</p>
    @endcomponent
    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary btn-big">@lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>

@stop
