@extends('layouts.app')
@section('title', __('hr.holidays'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.holidays')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary" data-toggle="modal" data-target=".holiday_modal">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </button>
            </div>
        @endslot

        <table class="table table-bordered table-striped" id="hr_holiday_table">
            <thead>
                <tr>
                    <th>@lang('hr.holiday_name')</th>
                    <th>@lang('hr.holiday_date')</th>
                    <th>@lang('hr.recurs_yearly')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent

    <div class="modal fade holiday_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                {!! Form::open(['url' => action([\App\Http\Controllers\Hr\AttendanceController::class, 'storeHoliday']), 'method' => 'post', 'id' => 'holiday_add_form']) !!}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">@lang('hr.add_holiday')</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        {!! Form::label('name', __('hr.holiday_name').':*') !!}
                        {!! Form::text('name', null, ['class' => 'form-control', 'required']); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('date', __('hr.holiday_date').':*') !!}
                        {!! Form::text('date', null, ['class' => 'form-control', 'id' => 'holiday_date', 'required']); !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('location_id', __('hr.location').':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                    <div class="form-group">
                        <div class="checkbox">
                            <label>{!! Form::checkbox('is_recurring_yearly', 1, false, ['class' => 'input-icheck']); !!} @lang('hr.recurs_yearly')</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var hr_holiday_table = $('#hr_holiday_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Hr\AttendanceController::class, "holidaysIndex"])}}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'date', name: 'date' },
                { data: 'is_recurring_yearly', name: 'is_recurring_yearly', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $('#holiday_date').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd',
            todayHighlight: true,
        });

        $(document).on('submit', 'form#holiday_add_form', function(e) {
            e.preventDefault();
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: $(this).serialize(),
                success: function(result) {
                    if (result.success === true) {
                        $('div.holiday_modal').modal('hide');
                        toastr.success(result.msg);
                        hr_holiday_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', 'button.delete_holiday_button', function() {
            swal({
                title: LANG.sure,
                text: "{{__('hr.confirm_delete_holiday')}}",
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: $(this).data('href'),
                        dataType: 'json',
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                hr_holiday_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                }
            });
        });
    });
</script>
@endsection
