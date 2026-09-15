<div class="modal-dialog" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Crm\LeadSourceController::class, 'update'], [$lead_source->id]), 'method' => 'put', 'id' => 'lead_source_edit_form']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('crm.edit_lead_source')</h4>
    </div>
    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __('crm.source_name').':*') !!}
        {!! Form::text('name', $lead_source->name, ['class' => 'form-control', 'required']); !!}
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>
    {!! Form::close() !!}
  </div>
</div>
