@extends('layouts.app')
@section('title', __('van_sales.trips'))

@section('content')

<section class="content-header">
    <h1>@lang('van_sales.trips')</h1>
</section>

<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('status', __('sale.status') . ':') !!}
                {!! Form::select('status', [
                    '' => __('lang_v1.all'),
                    'out' => __('van_sales.status_out'),
                    'pending_approval' => __('van_sales.status_pending_approval'),
                    'closed' => __('van_sales.status_closed'),
                    'rejected' => __('van_sales.status_rejected'),
                ], null, ['class' => 'form-control', 'id' => 'status_filter']); !!}
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('van_sales.all_trips')])
        @slot('tool')
            <div class="box-tools">
                <a href="{{action([\App\Http\Controllers\VanSalesTripController::class, 'create'])}}" class="btn btn-block btn-primary">
                    <i class="fa fa-plus"></i> @lang('van_sales.start_trip')</a>
            </div>
        @endslot
        <table class="table table-bordered table-striped" id="van_sales_trip_table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>@lang('van_sales.vehicle')</th>
                    <th>@lang('van_sales.rep')</th>
                    <th>@lang('van_sales.source_location')</th>
                    <th>@lang('sale.status')</th>
                    <th>@lang('van_sales.started_at')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        var van_sales_trip_table = $('#van_sales_trip_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{action([\App\Http\Controllers\VanSalesTripController::class, 'index'])}}",
                data: function (d) {
                    d.status = $('#status_filter').val();
                }
            },
            columnDefs: [{
                "targets": 6,
                "orderable": false,
                "searchable": false
            }],
            columns: [
                { data: 'id', name: 'van_sales_trips.id' },
                { data: 'vehicle_name', name: 'v.name' },
                { data: 'rep_name', name: 'u.first_name', orderable: false },
                { data: 'source_location_name', name: 'bl.name' },
                { data: 'status', name: 'van_sales_trips.status' },
                { data: 'started_at', name: 'van_sales_trips.started_at' },
                { data: 'action', name: 'action' },
            ]
        });

        $(document).on('change', '#status_filter', function () {
            van_sales_trip_table.ajax.reload();
        });
    });
</script>
@endsection
