<div class="modal-dialog" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Hr\DesignationController::class, 'store']), 'method' => 'post', 'id' => 'designation_add_form']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('hr.add_designation')</h4>
    </div>
    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __('hr.designation_name').':*') !!}
        {!! Form::text('name', null, ['class' => 'form-control', 'required']); !!}
      </div>
      <div class="form-group">
        {!! Form::label('department_id', __('hr.department').':') !!}
        {!! Form::select('department_id', $departments, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.none')]); !!}
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>
    {!! Form::close() !!}
  </div>
</div>
