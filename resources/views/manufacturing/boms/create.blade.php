@extends('layouts.app')
@section('title', __('manufacturing.add_bom'))

@section('content')

<section class="content-header">
    <h1>@lang('manufacturing.add_bom')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Manufacturing\ManufacturingController::class, 'store']), 'method' => 'post', 'id' => 'bom_form']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-5">
                <div class="form-group">
                    {!! Form::label('finished_product_select', __('manufacturing.finished_product').':*') !!}
                    <select id="finished_product_select" class="form-control select2" style="width:100%" required></select>
                    <input type="hidden" name="product_id" id="bom_product_id">
                    <input type="hidden" name="variation_id" id="bom_variation_id">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('output_quantity', __('manufacturing.output_quantity').':*') !!}
                    {!! Form::text('output_quantity', 1, ['class' => 'form-control input_number', 'required']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('unit_id', __('manufacturing.unit').':') !!}
                    {!! Form::select('unit_id', $units, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.none')]); !!}
                </div>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('manufacturing.raw_materials')])
        @slot('tool')
            <div class="box-tools">
                <button type="button" id="add_raw_material" class="btn btn-block btn-primary btn-xs">
                    <i class="fa fa-plus"></i> @lang('manufacturing.add_raw_material')
                </button>
            </div>
        @endslot
        <div id="raw_materials_container"></div>
    @endcomponent

    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary btn-big">@lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>

<div id="raw_material_row_template" style="display:none;">
    @include('manufacturing.boms.partials.raw_material_row', ['index' => '__INDEX__', 'item' => null])
</div>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var item_index = 0;

        function productSelect2Options() {
            return {
                width: '100%',
                minimumInputLength: 2,
                ajax: {
                    url: '/products/list',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { term: params.term }; },
                    processResults: function(data) {
                        return {
                            results: $.map(data, function(obj) {
                                var string = obj.name;
                                if (obj.type == 'variable') { string += ' - ' + obj.variation; }
                                string += ' (' + obj.sub_sku + ')';
                                return { id: obj.variation_id, text: string, product_id: obj.product_id };
                            })
                        };
                    },
                },
            };
        }

        $('#finished_product_select').select2(productSelect2Options());
        $('#finished_product_select').on('select2:select', function(e) {
            $('#bom_variation_id').val(e.params.data.id);
            $('#bom_product_id').val(e.params.data.product_id);
        });

        function initRowSelect2($row) {
            var $select = $row.find('.raw-material-select');
            $select.select2(productSelect2Options());
            $select.on('select2:select', function(e) {
                $row.find('.raw-material-variation-id-input').val(e.params.data.id);
                $row.find('.raw-material-product-id-input').val(e.params.data.product_id);
            });
        }

        $('#add_raw_material').on('click', function() {
            var html = $('#raw_material_row_template').html().split('__INDEX__').join(item_index);
            var $row = $(html);
            $('#raw_materials_container').append($row);
            initRowSelect2($row);
            item_index++;
        });

        $(document).on('click', '.remove-raw-material', function() {
            $(this).closest('.raw-material-row').remove();
        });

        $('#bom_form').on('submit', function() {
            if ($('#raw_materials_container .raw-material-row').length == 0) {
                toastr.error("{{__('manufacturing.add_raw_material')}}");
                return false;
            }
        });

        //Start with one empty raw material row
        $('#add_raw_material').trigger('click');
    });
</script>
@endsection
