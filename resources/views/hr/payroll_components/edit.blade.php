<div class="modal-dialog" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Hr\PayrollComponentController::class, 'update'], [$payroll_component->id]), 'method' => 'put', 'id' => 'payroll_component_edit_form']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('hr.edit_payroll_component')</h4>
    </div>
    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __('hr.component_name').':*') !!}
        {!! Form::text('name', $payroll_component->name, ['class' => 'form-control', 'required']); !!}
      </div>
      <div class="form-group">
        {!! Form::label('type', __('hr.component_type').':*') !!}
        {!! Form::select('type', ['earning' => __('hr.earning'), 'deduction' => __('hr.deduction')], $payroll_component->type, ['class' => 'form-control', 'required']); !!}
      </div>
      <div class="form-group">
        <div class="checkbox">
          <label>{!! Form::checkbox('is_taxable', 1, $payroll_component->is_taxable, ['class' => 'input-icheck']); !!} @lang('hr.is_taxable')</label>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>
    {!! Form::close() !!}
  </div>
</div>
