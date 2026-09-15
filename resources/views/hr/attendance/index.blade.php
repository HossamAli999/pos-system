@extends('layouts.app')
@section('title', __('hr.attendance'))

@section('content')

<section class="content-header">
    <h1>@lang('hr.attendance')</h1>
</section>

<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('att_filter_employee_id', __('hr.filter_by_employee').':') !!}
                {!! Form::select('att_filter_employee_id', $employees, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('att_filter_date_range', __('hr.filter_by_date_range').':') !!}
                {!! Form::text('att_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
        @can('attendance.manage')
            @slot('tool')
                <div class="box-tools">
                    <a href="{{action([\App\Http\Controllers\Hr\AttendanceController::class, 'create'])}}" class="btn btn-primary"><i class="fa fa-plus"></i> @lang('hr.add_attendance')</a>
                    <a href="{{action([\App\Http\Controllers\Hr\AttendanceController::class, 'importForm'])}}" class="btn btn-default"><i class="fa fa-upload"></i> @lang('hr.import_attendance')</a>
                </div>
            @endslot
        @endcan

        <table class="table table-bordered table-striped" id="hr_attendance_table" style="width: 100%;">
            <thead>
                <tr>
                    <th>@lang('hr.employee')</th>
                    <th>@lang('hr.attendance_date')</th>
                    <th>@lang('hr.clock_in')</th>
                    <th>@lang('hr.clock_out')</th>
                    <th>@lang('hr.total_hours')</th>
                    <th>@lang('hr.attendance_status')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var hr_attendance_table = $('#hr_attendance_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{action([\App\Http\Controllers\Hr\AttendanceController::class, "index"])}}',
                data: function(d) {
                    d.employee_id = $('#att_filter_employee_id').val();

                    var start = '';
                    var end = '';
                    if ($('#att_filter_date_range').val()) {
                        start = $('input#att_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        end = $('input#att_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    }
                    d.start_date = start;
                    d.end_date = end;
                },
            },
            columns: [
                { data: 'employee_name', name: 'employee_name' },
                { data: 'attendance_date', name: 'attendance_date' },
                { data: 'clock_in', name: 'clock_in' },
                { data: 'clock_out', name: 'clock_out' },
                { data: 'total_hours', name: 'total_hours' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $(document).on('change', '#att_filter_employee_id', function() {
            hr_attendance_table.ajax.reload();
        });

        $('#att_filter_date_range').daterangepicker(dateRangeSettings, function(start, end) {
            $('#att_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            hr_attendance_table.ajax.reload();
        });
        $('#att_filter_date_range').on('cancel.daterangepicker', function() {
            $('#att_filter_date_range').val('');
            hr_attendance_table.ajax.reload();
        });
    });
</script>
@endsection
