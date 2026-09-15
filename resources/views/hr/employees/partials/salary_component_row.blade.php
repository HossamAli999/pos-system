@php $index = $index ?? '__INDEX__'; @endphp
<div class="salary-component-row" style="border:1px solid #ddd; border-radius:4px; padding:15px 15px 0 15px; margin-bottom:10px; position:relative;">
    <button type="button" class="btn btn-xs btn-danger remove-salary-component" style="position:absolute; top:10px; right:10px;">
        <i class="fa fa-times"></i>
    </button>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('', __('hr.component').':') !!}
                {!! Form::select('components['.$index.'][payroll_component_id]', $payroll_components->pluck('name', 'id'), null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('', __('hr.calc_type').':') !!}
                <select name="components[{{$index}}][calc_type]" class="form-control salary-calc-type-select">
                    <option value="fixed">@lang('hr.fixed')</option>
                    <option value="percentage_of_basic">@lang('hr.percentage_of_basic')</option>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group salary-amount-field">
                {!! Form::label('', __('hr.amount').':') !!}
                {!! Form::text('components['.$index.'][amount]', null, ['class' => 'form-control input_number']); !!}
            </div>
            <div class="form-group salary-percentage-field" style="display:none;">
                {!! Form::label('', __('hr.percentage').':') !!}
                {!! Form::text('components['.$index.'][percentage]', null, ['class' => 'form-control input_number']); !!}
            </div>
        </div>
    </div>
</div>
