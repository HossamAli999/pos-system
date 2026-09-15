@extends('layouts.app')
@section('title', __('crm.add_lead'))

@section('content')

<section class="content-header">
    <h1>@lang('crm.add_lead')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Crm\LeadController::class, 'store']), 'method' => 'post', 'id' => 'crm_lead_form']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        @include('crm.leads.partials.form')
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('crm.products')])
        @slot('tool')
            <div class="box-tools">
                <button type="button" id="add_lead_product" class="btn btn-block btn-primary btn-xs">
                    <i class="fa fa-plus"></i> @lang('crm.add_product_line')
                </button>
            </div>
        @endslot
        <div id="lead_products_container"></div>
    @endcomponent

    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary btn-big">@lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>

<div id="lead_product_row_template" style="display:none;">
    @include('crm.leads.partials.product_row', ['index' => '__INDEX__'])
</div>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var product_index = 0;

        $('#expected_close_date').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd',
            todayHighlight: true,
        });

        function toggleStageSelect() {
            var pipeline_id = $('#lead_pipeline_id').val();
            $('.lead-stage-select-container').each(function() {
                if ($(this).data('pipeline-id') == pipeline_id) {
                    $(this).show();
                    $(this).find('select').prop('disabled', false);
                } else {
                    $(this).hide();
                    $(this).find('select').prop('disabled', true);
                }
            });
        }
        $('#lead_pipeline_id').on('change', toggleStageSelect);
        toggleStageSelect();

        function initProductSelect2($row) {
            var $select = $row.find('.lead-product-select');
            $select.select2({
                width: '100%',
                minimumInputLength: 2,
                placeholder: '{{ __("crm.product") }}',
                ajax: {
                    url: '/products/list',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { term: params.term };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data, function(obj) {
                                var string = obj.name;
                                if (obj.type == 'variable') {
                                    string += ' - ' + obj.variation;
                                }
                                string += ' (' + obj.sub_sku + ')';
                                return { id: obj.variation_id, text: string, product_id: obj.product_id };
                            })
                        };
                    },
                },
            });
            $select.on('select2:select', function(e) {
                var data = e.params.data;
                $row.find('.lead-variation-id-input').val(data.id);
                $row.find('.lead-product-id-input').val(data.product_id);
            });
        }

        $('#add_lead_product').on('click', function() {
            var html = $('#lead_product_row_template').html().split('__INDEX__').join(product_index);
            var $row = $(html);
            $('#lead_products_container').append($row);
            initProductSelect2($row);
            product_index++;
        });

        $(document).on('click', '.remove-lead-product', function() {
            $(this).closest('.lead-product-row').remove();
        });
    });
</script>
@endsection
