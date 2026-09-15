@php
    $index = $index ?? '__INDEX__';
    $item = $item ?? null;
    $selected_text = ! empty($item) ? ($item->raw_material_product->name ?? '').' ('.($item->raw_material_variation->name ?? '').')' : null;
@endphp
<div class="raw-material-row row" style="border:1px solid #ddd; border-radius:4px; padding:15px 15px 0 15px; margin-bottom:10px; position:relative;">
    <button type="button" class="btn btn-xs btn-danger remove-raw-material" style="position:absolute; top:10px; right:10px;">
        <i class="fa fa-times"></i>
    </button>
    <div class="col-md-5">
        <div class="form-group">
            {!! Form::label('', __('manufacturing.raw_material').':') !!}
            <select class="form-control select2 raw-material-select" style="width:100%">
                @if(! empty($item))
                    <option value="{{ $item->raw_material_variation_id }}" selected>{{ $selected_text }}</option>
                @endif
            </select>
            <input type="hidden" name="items[{{$index}}][raw_material_product_id]" class="raw-material-product-id-input" value="{{ $item->raw_material_product_id ?? '' }}">
            <input type="hidden" name="items[{{$index}}][raw_material_variation_id]" class="raw-material-variation-id-input" value="{{ $item->raw_material_variation_id ?? '' }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('', __('manufacturing.quantity_required').':') !!}
            {!! Form::text('items['.$index.'][quantity_required]', $item->quantity_required ?? 1, ['class' => 'form-control input_number']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('', __('manufacturing.wastage_percent').':') !!}
            {!! Form::text('items['.$index.'][wastage_percent]', $item->wastage_percent ?? 0, ['class' => 'form-control input_number']); !!}
        </div>
    </div>
</div>
