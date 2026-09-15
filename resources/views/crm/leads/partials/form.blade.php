@php
    $lead = $lead ?? null;
@endphp
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('name', __('crm.name').':*') !!}
            {!! Form::text('name', $lead->name ?? null, ['class' => 'form-control', 'required']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('company_name', __('crm.company_name').':') !!}
            {!! Form::text('company_name', $lead->company_name ?? null, ['class' => 'form-control']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('source_id', __('crm.source').':') !!}
            {!! Form::select('source_id', $sources, $lead->source_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.none')]); !!}
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('email', __('crm.email').':') !!}
            {!! Form::email('email', $lead->email ?? null, ['class' => 'form-control']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('phone', __('crm.phone').':') !!}
            {!! Form::text('phone', $lead->phone ?? null, ['class' => 'form-control']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('assigned_to', __('crm.assigned_to').':') !!}
            {!! Form::select('assigned_to', $users, $lead->assigned_to ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.none')]); !!}
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('expected_value', __('crm.expected_value').':') !!}
            {!! Form::text('expected_value', $lead->expected_value ?? null, ['class' => 'form-control input_number']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('expected_close_date', __('crm.expected_close_date').':') !!}
            {!! Form::text('expected_close_date', ! empty($lead->expected_close_date) ? $lead->expected_close_date->format('Y-m-d') : null, ['class' => 'form-control', 'id' => 'expected_close_date']); !!}
        </div>
    </div>
    @if(empty($lead))
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('pipeline_id', __('crm.pipeline').':') !!}
                {!! Form::select('pipeline_id', $pipelines->pluck('name', 'id'), null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'lead_pipeline_id']); !!}
            </div>
        </div>
    @endif
</div>

@if(empty($lead))
<div id="lead_stage_containers">
    @foreach($pipelines as $pipeline)
        <div class="form-group lead-stage-select-container" data-pipeline-id="{{ $pipeline->id }}" style="display:none;">
            {!! Form::label('', __('crm.stage').':') !!}
            {!! Form::select('stage_id', $pipeline->stages->pluck('name', 'id'), null, ['class' => 'form-control select2 lead-stage-select', 'style' => 'width:100%']); !!}
        </div>
    @endforeach
</div>
@endif
