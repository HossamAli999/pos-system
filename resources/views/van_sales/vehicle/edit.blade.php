<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\VanSalesVehicleController::class, 'update'], [$vehicle->id]), 'method' => 'put', 'id' => 'van_sales_vehicle_form']) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('van_sales.edit_vehicle')</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __('van_sales.name') . ':*') !!}
        {!! Form::text('name', $vehicle->name, ['class' => 'form-control', 'required', 'placeholder' => __('van_sales.name')]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('plate_number', __('van_sales.plate_number') . ':') !!}
        {!! Form::text('plate_number', $vehicle->plate_number, ['class' => 'form-control', 'placeholder' => __('van_sales.plate_number')]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('notes', __('van_sales.notes') . ':') !!}
        {!! Form::textarea('notes', $vehicle->notes, ['class' => 'form-control', 'placeholder' => __('van_sales.notes'), 'rows' => 3]); !!}
      </div>

      <div class="form-group">
        <label>
          {!! Form::checkbox('is_active', 1, !empty($vehicle->is_active), ['class' => 'input-icheck']); !!} <strong>@lang('lang_v1.is_active')</strong>
        </label>
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>

    {!! Form::close() !!}

  </div>
</div>
