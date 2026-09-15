@php $id_prefix = $id_prefix ?? ''; @endphp
<div class="form-group">
    {!! Form::label($id_prefix.'name', __('crm.stage_name').':*') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'id' => $id_prefix.'name', 'required']); !!}
</div>
<div class="form-group">
    {!! Form::label($id_prefix.'probability_percent', __('crm.probability_percent').':') !!}
    {!! Form::number('probability_percent', 0, ['class' => 'form-control', 'id' => $id_prefix.'probability_percent', 'min' => 0, 'max' => 100]); !!}
</div>
<div class="form-group">
    <div class="checkbox">
        <label>{!! Form::checkbox('is_won', 1, false, ['class' => 'input-icheck', 'id' => $id_prefix.'is_won']); !!} @lang('crm.is_won_stage')</label>
    </div>
</div>
<div class="form-group">
    <div class="checkbox">
        <label>{!! Form::checkbox('is_lost', 1, false, ['class' => 'input-icheck', 'id' => $id_prefix.'is_lost']); !!} @lang('crm.is_lost_stage')</label>
    </div>
</div>
