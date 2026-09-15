<div class="modal-dialog" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Hr\AttendanceController::class, 'storeShift']), 'method' => 'post', 'id' => 'attendance_shift_add_form']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('hr.add_shift')</h4>
    </div>
    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __('hr.shift_name').':*') !!}
        {!! Form::text('name', null, ['class' => 'form-control', 'required']); !!}
      </div>
      <div class="form-group">
        {!! Form::label('start_time', __('hr.start_time').':*') !!}
        {!! Form::text('start_time', '09:00', ['class' => 'form-control', 'required', 'placeholder' => 'HH:MM']); !!}
      </div>
      <div class="form-group">
        {!! Form::label('end_time', __('hr.end_time').':*') !!}
        {!! Form::text('end_time', '17:00', ['class' => 'form-control', 'required', 'placeholder' => 'HH:MM']); !!}
      </div>
      <div class="form-group">
        {!! Form::label('grace_minutes', __('hr.grace_minutes').':') !!}
        {!! Form::number('grace_minutes', 0, ['class' => 'form-control']); !!}
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>
    {!! Form::close() !!}
  </div>
</div>
