@extends('layouts.app')
@section('title', __('van_sales.start_trip'))

@section('content')

<section class="content-header">
    <h1>@lang('van_sales.start_trip')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\VanSalesTripController::class, 'store']), 'method' => 'post', 'id' => 'van_sales_trip_form']) !!}

    @component('components.widget', ['class' => 'box-primary', 'title' => __('van_sales.trip_details')])
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('van_sales_vehicle_id', __('van_sales.vehicle') . ':*') !!}
                    {!! Form::select('van_sales_vehicle_id', $vehicles, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('rep_id', __('van_sales.rep') . ':*') !!}
                    {!! Form::select('rep_id', $reps, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('source_location_id', __('van_sales.source_location') . ':*') !!}
                    {!! Form::select('source_location_id', $business_locations, null, ['class' => 'form-control select2', 'id' => 'source_location_id', 'placeholder' => __('messages.please_select'), 'required']); !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('opening_cash', __('van_sales.opening_cash') . ':') !!}
                    {!! Form::text('opening_cash', 0, ['class' => 'form-control', 'id' => 'opening_cash']); !!}
                </div>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('van_sales.loadout_products')])
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::select('add_product_search', [], null, ['class' => 'form-control', 'id' => 'add_product_search', 'style' => 'width:100%', 'placeholder' => __('van_sales.search_and_add_product')]); !!}
                </div>
            </div>
        </div>

        <table class="table table-bordered" id="loadout_products_table">
            <thead>
                <tr>
                    <th>@lang('van_sales.product')</th>
                    <th style="width: 150px;">@lang('van_sales.quantity')</th>
                    <th style="width: 60px;"></th>
                </tr>
            </thead>
            <tbody id="loadout_products_body">
            </tbody>
        </table>
        <p class="text-muted" id="no_products_msg">@lang('van_sales.no_products_yet')</p>
    @endcomponent

    <div class="row">
        <div class="col-sm-12 text-right">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-truck-loading"></i> @lang('van_sales.start_trip')
            </button>
        </div>
    </div>

    {!! Form::close() !!}
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        var row_index = 0;

        function toggleNoProductsMsg() {
            if ($('#loadout_products_body tr').length > 0) {
                $('#no_products_msg').hide();
            } else {
                $('#no_products_msg').show();
            }
        }
        toggleNoProductsMsg();

        $('#add_product_search').select2({
            placeholder: "@lang('van_sales.search_and_add_product')",
            ajax: {
                url: "{{action([\App\Http\Controllers\PurchaseController::class, 'getProducts'])}}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        term: params.term,
                        location_id: $('#source_location_id').val()
                    };
                },
                processResults: function (data) {
                    return { results: data };
                }
            },
            minimumInputLength: 2
        });

        $('#add_product_search').on('select2:select', function (e) {
            var product = e.params.data;
            if (!product.variation_id) {
                // A "select variation" placeholder row for a variable product — ignore.
                $(this).val(null).trigger('change');
                return;
            }

            row_index++;
            var row = $('<tr></tr>')
                .append($('<td></td>').text(product.text)
                    .append($('<input type="hidden" name="products['+row_index+'][product_id]">').val(product.product_id))
                    .append($('<input type="hidden" name="products['+row_index+'][variation_id]">').val(product.variation_id)))
                .append($('<td></td>').append(
                    $('<input type="number" step="0.01" min="0.01" class="form-control" required>')
                        .attr('name', 'products['+row_index+'][quantity]')
                        .val(1)
                ))
                .append($('<td></td>').append(
                    $('<button type="button" class="btn btn-xs btn-danger remove_loadout_row"><i class="fas fa-trash"></i></button>')
                ));

            $('#loadout_products_body').append(row);
            toggleNoProductsMsg();

            $(this).val(null).trigger('change');
        });

        $(document).on('click', '.remove_loadout_row', function () {
            $(this).closest('tr').remove();
            toggleNoProductsMsg();
        });

        $('#van_sales_trip_form').on('submit', function (e) {
            if ($('#loadout_products_body tr').length === 0) {
                e.preventDefault();
                toastr.error("@lang('van_sales.no_products_added')");
            }
        });
    });
</script>
@endsection
