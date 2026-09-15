@extends('layouts.app')
@section('title', __('manufacturing.add_work_order'))

@section('content')

<section class="content-header">
    <h1>@lang('manufacturing.add_work_order')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Manufacturing\WorkOrderController::class, 'store']), 'method' => 'post']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('bill_of_material_id', __('manufacturing.bill_of_materials').':*') !!}
                    {!! Form::select('bill_of_material_id', $boms, $selected_bom_id, ['class' => 'form-control select2', 'style' => 'width:100%', 'required']); !!}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('location_id', __('manufacturing.location').':*') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'required']); !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('planned_quantity', __('manufacturing.planned_quantity').':*') !!}
                    {!! Form::text('planned_quantity', 1, ['class' => 'form-control input_number', 'required']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('planned_date', __('manufacturing.planned_date').':') !!}
                    {!! Form::text('planned_date', \Carbon::now()->toDateString(), ['class' => 'form-control', 'id' => 'wo_planned_date']); !!}
                </div>
            </div>
        </div>
        <div class="form-group">
            {!! Form::label('notes', __('manufacturing.notes').':') !!}
            {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 3]); !!}
        </div>
    @endcomponent
    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary btn-big">@lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#wo_planned_date').datepicker({ autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true });
    });
</script>
@endsection
