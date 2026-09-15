@extends('layouts.app')
@section('title', $employee->full_name)

@section('content')

<section class="content-header">
    <h1>{{ $employee->full_name }}
        <small>{{ $employee->employee_code }}</small>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('hr.employee_profile')])
                @can('employee.update')
                    @slot('tool')
                        <div class="box-tools">
                            <a href="{{action([\App\Http\Controllers\Hr\EmployeeController::class, 'edit'], [$employee->id])}}" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i> @lang('messages.edit')</a>
                            <button type="button" class="btn btn-xs btn-danger btn-modal" data-href="#" data-container=".terminate_employee_modal" onclick="$('.terminate_employee_modal').modal('show'); return false;"><i class="fa fa-user-times"></i> @lang('hr.terminate_employee')</button>
                        </div>
                    @endslot
                @endcan

                <table class="table">
                    <tr><th>@lang('hr.status')</th><td>{{ __('hr.status_'.$employee->status) }}</td></tr>
                    <tr><th>@lang('hr.department')</th><td>{{ $employee->department->name ?? '--' }}</td></tr>
                    <tr><th>@lang('hr.designation')</th><td>{{ $employee->designation->name ?? '--' }}</td></tr>
                    <tr><th>@lang('hr.location')</th><td>{{ $employee->location->name ?? '--' }}</td></tr>
                    <tr><th>@lang('hr.reporting_to')</th><td>{{ $employee->reporting_to_employee->full_name ?? '--' }}</td></tr>
                    <tr><th>@lang('hr.date_of_joining')</th><td>{{ !empty($employee->date_of_joining) ? $employee->date_of_joining->format('Y-m-d') : '--' }}</td></tr>
                    <tr><th>@lang('hr.phone')</th><td>{{ $employee->phone ?? '--' }}</td></tr>
                    <tr><th>@lang('hr.personal_email')</th><td>{{ $employee->personal_email ?? '--' }}</td></tr>
                    <tr><th>@lang('hr.bank_details')</th><td>{{ $employee->bank_name }} {{ $employee->bank_account_number }}</td></tr>
                </table>
            @endcomponent
        </div>

        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('hr.salary_structure')])
                @can('employee.update')
                    @slot('tool')
                        <div class="box-tools">
                            <button type="button" class="btn btn-xs btn-primary" data-toggle="modal" data-target=".salary_structure_modal"><i class="fa fa-plus"></i> @lang('hr.add_salary_structure')</button>
                        </div>
                    @endslot
                @endcan

                @if(empty($current_structure))
                    <p class="text-muted">@lang('hr.no_salary_structure')</p>
                @else
                    <table class="table">
                        <tr><th>@lang('hr.effective_from')</th><td>{{ $current_structure->effective_from->format('Y-m-d') }}</td></tr>
                        <tr><th>@lang('hr.basic_salary')</th><td>@format_currency($current_structure->basic_salary)</td></tr>
                        <tr><th>@lang('hr.pay_frequency')</th><td>{{ __('hr.'.$current_structure->pay_frequency) }}</td></tr>
                    </table>
                    <table class="table table-bordered">
                        <thead><tr><th>@lang('hr.component')</th><th>@lang('hr.component_type')</th><th>@lang('hr.amount')</th></tr></thead>
                        <tbody>
                        @foreach($current_structure->resolvedComponents() as $rc)
                            <tr>
                                <td>{{ $rc['name'] }}</td>
                                <td>{{ __('hr.'.$rc['type']) }}</td>
                                <td>@format_currency($rc['amount'])</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('hr.salary_structure_history')])
                <table class="table table-bordered">
                    <thead><tr><th>@lang('hr.effective_from')</th><th>@lang('hr.basic_salary')</th><th>@lang('hr.pay_frequency')</th></tr></thead>
                    <tbody>
                    @forelse($salary_structures as $structure)
                        <tr>
                            <td>{{ $structure->effective_from->format('Y-m-d') }}</td>
                            <td>@format_currency($structure->basic_salary)</td>
                            <td>{{ __('hr.'.$structure->pay_frequency) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">@lang('hr.no_salary_structure')</td></tr>
                    @endforelse
                    </tbody>
                </table>
            @endcomponent
        </div>

        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('hr.leave_balances').' ('.$year.')'])
                <table class="table table-bordered">
                    <thead><tr><th>@lang('hr.leave_type')</th><th>@lang('hr.allocated_days')</th><th>@lang('hr.used_days')</th><th>@lang('hr.remaining_days')</th></tr></thead>
                    <tbody>
                    @forelse($leave_balances as $balance)
                        <tr>
                            <td>{{ $balance->leave_type->name ?? '' }}</td>
                            <td>{{ $balance->allocated_days }}</td>
                            <td>{{ $balance->used_days }}</td>
                            <td>{{ $balance->remaining_days }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">--</td></tr>
                    @endforelse
                    </tbody>
                </table>
            @endcomponent
        </div>
    </div>
</section>

<!-- Add salary structure modal -->
<div class="modal fade salary_structure_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            {!! Form::open(['url' => action([\App\Http\Controllers\Hr\EmployeeController::class, 'storeSalaryStructure'], [$employee->id]), 'method' => 'post', 'id' => 'salary_structure_form']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('hr.add_salary_structure')</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('effective_from', __('hr.effective_from').':*') !!}
                            {!! Form::text('effective_from', \Carbon::now()->toDateString(), ['class' => 'form-control', 'id' => 'salary_effective_from', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('basic_salary', __('hr.basic_salary').':*') !!}
                            {!! Form::text('basic_salary', null, ['class' => 'form-control input_number', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('pay_frequency', __('hr.pay_frequency').':') !!}
                            {!! Form::select('pay_frequency', ['monthly' => __('hr.monthly'), 'biweekly' => __('hr.biweekly'), 'weekly' => __('hr.weekly')], 'monthly', ['class' => 'form-control']); !!}
                        </div>
                    </div>
                </div>

                <h4>@lang('hr.components')
                    <button type="button" id="add_salary_component" class="btn btn-xs btn-primary pull-right"><i class="fa fa-plus"></i> @lang('hr.add_component_line')</button>
                </h4>
                <div id="salary_components_container"></div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

<div id="salary_component_row_template" style="display:none;">
    @include('hr.employees.partials.salary_component_row', ['index' => '__INDEX__', 'payroll_components' => $payroll_components])
</div>

<!-- Terminate employee modal -->
<div class="modal fade terminate_employee_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['url' => action([\App\Http\Controllers\Hr\EmployeeController::class, 'terminate'], [$employee->id]), 'method' => 'post']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('hr.terminate_employee')</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('status', __('hr.status').':') !!}
                    {!! Form::select('status', ['terminated' => __('hr.status_terminated'), 'resigned' => __('hr.status_resigned')], 'terminated', ['class' => 'form-control']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('termination_date', __('hr.termination_date').':*') !!}
                    {!! Form::text('termination_date', \Carbon::now()->toDateString(), ['class' => 'form-control', 'id' => 'termination_date', 'required']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('termination_reason', __('hr.termination_reason').':') !!}
                    {!! Form::textarea('termination_reason', null, ['class' => 'form-control', 'rows' => 3]); !!}
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

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var salary_component_index = 0;

        function initSelect2InRow($row) {
            $row.find('select.select2').select2({ width: '100%' });
        }

        $('#add_salary_component').on('click', function() {
            var html = $('#salary_component_row_template').html().split('__INDEX__').join(salary_component_index);
            var $row = $(html);
            $('#salary_components_container').append($row);
            initSelect2InRow($row);
            salary_component_index++;
        });

        $(document).on('click', '.remove-salary-component', function() {
            $(this).closest('.salary-component-row').remove();
        });

        $(document).on('change', '.salary-calc-type-select', function() {
            var $row = $(this).closest('.salary-component-row');
            if ($(this).val() == 'percentage_of_basic') {
                $row.find('.salary-amount-field').hide();
                $row.find('.salary-percentage-field').show();
            } else {
                $row.find('.salary-amount-field').show();
                $row.find('.salary-percentage-field').hide();
            }
        });

        $('#salary_effective_from, #termination_date').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd',
            todayHighlight: true,
        });
    });
</script>
@endsection
