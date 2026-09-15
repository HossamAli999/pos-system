<div class="modal-dialog" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Hr\LeaveTypeController::class, 'update'], [$leave_type->id]), 'method' => 'put', 'id' => 'leave_type_edit_form']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('hr.edit_leave_type')</h4>
    </div>
    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __('hr.leave_type_name').':*') !!}
        {!! Form::text('name', $leave_type->name, ['class' => 'form-control', 'required']); !!}
      </div>
      <div class="form-group">
        {!! Form::label('days_allowed_per_year', __('hr.days_allowed_per_year').':*') !!}
        {!! Form::number('days_allowed_per_year', $leave_type->days_allowed_per_year, ['class' => 'form-control', 'step' => '0.5', 'required']); !!}
      </div>
      <div class="form-group">
        <div class="checkbox">
          <label>{!! Form::checkbox('is_paid', 1, $leave_type->is_paid, ['class' => 'input-icheck']); !!} @lang('hr.is_paid')</label>
        </div>
      </div>
      <div class="form-group">
        <div class="checkbox">
          <label>{!! Form::checkbox('carry_forward', 1, $leave_type->carry_forward, ['class' => 'input-icheck']); !!} @lang('hr.carry_forward')</label>
        </div>
      </div>
      <div class="form-group">
        {!! Form::label('max_carry_forward_days', __('hr.max_carry_forward_days').':') !!}
        {!! Form::number('max_carry_forward_days', $leave_type->max_carry_forward_days, ['class' => 'form-control', 'step' => '0.5']); !!}
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>
    {!! Form::close() !!}
  </div>
</div>
