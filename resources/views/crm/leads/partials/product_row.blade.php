@php $index = $index ?? '__INDEX__'; @endphp
<div class="lead-product-row row" style="border:1px solid #ddd; border-radius:4px; padding:15px 15px 0 15px; margin-bottom:10px; position:relative;">
    <button type="button" class="btn btn-xs btn-danger remove-lead-product" style="position:absolute; top:10px; right:10px;">
        <i class="fa fa-times"></i>
    </button>
    <div class="col-md-5">
        <div class="form-group">
            {!! Form::label('', __('crm.product').':') !!}
            <select class="form-control select2 lead-product-select" style="width:100%"></select>
            <input type="hidden" name="products[{{$index}}][product_id]" class="lead-product-id-input">
            <input type="hidden" name="products[{{$index}}][variation_id]" class="lead-variation-id-input">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('', __('crm.quantity').':') !!}
            {!! Form::text('products['.$index.'][quantity]', 1, ['class' => 'form-control input_number lead-qty-input']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('', __('crm.unit_price').':') !!}
            {!! Form::text('products['.$index.'][unit_price]', 0, ['class' => 'form-control input_number lead-price-input']); !!}
        </div>
    </div>
</div>
