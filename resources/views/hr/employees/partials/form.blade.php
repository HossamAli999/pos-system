@php
    $employee = $employee ?? null;
@endphp
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('first_name', __('hr.first_name').':*') !!}
            {!! Form::text('first_name', $employee->first_name ?? null, ['class' => 'form-control', 'required']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('last_name', __('hr.last_name').':') !!}
            {!! Form::text('last_name', $employee->last_name ?? null, ['class' => 'form-control']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('employee_code', __('hr.employee_code').':') !!}
            {!! Form::text('employee_code', $employee->employee_code ?? null, ['class' => 'form-control']); !!}
            <p class="help-block">@lang('hr.employee_code_help')</p>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('department_id', __('hr.department').':') !!}
            {!! Form::select('department_id', $departments, $employee->department_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.none')]); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('designation_id', __('hr.designation').':') !!}
            {!! Form::select('designation_id', $designations, $employee->designation_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.none')]); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('location_id', __('hr.location').':') !!}
            {!! Form::select('location_id', $business_locations, $employee->location_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.none')]); !!}
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('reporting_to', __('hr.reporting_to').':') !!}
            {!! Form::select('reporting_to', $employees, $employee->reporting_to ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.none')]); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('date_of_joining', __('hr.date_of_joining').':') !!}
            {!! Form::text('date_of_joining', ! empty($employee->date_of_joining) ? $employee->date_of_joining->format('Y-m-d') : null, ['class' => 'form-control', 'id' => 'date_of_joining']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('date_of_birth', __('hr.date_of_birth').':') !!}
            {!! Form::text('date_of_birth', ! empty($employee->date_of_birth) ? $employee->date_of_birth->format('Y-m-d') : null, ['class' => 'form-control', 'id' => 'date_of_birth']); !!}
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('phone', __('hr.phone').':') !!}
            {!! Form::text('phone', $employee->phone ?? null, ['class' => 'form-control']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('personal_email', __('hr.personal_email').':') !!}
            {!! Form::email('personal_email', $employee->personal_email ?? null, ['class' => 'form-control']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('national_id_number', __('hr.national_id_number').':') !!}
            {!! Form::text('national_id_number', $employee->national_id_number ?? null, ['class' => 'form-control']); !!}
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12"><h4>@lang('hr.bank_details')</h4></div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('bank_name', __('hr.bank_name').':') !!}
            {!! Form::text('bank_name', $employee->bank_name ?? null, ['class' => 'form-control']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('bank_account_number', __('hr.bank_account_number').':') !!}
            {!! Form::text('bank_account_number', $employee->bank_account_number ?? null, ['class' => 'form-control']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('bank_ifsc', __('hr.bank_ifsc').':') !!}
            {!! Form::text('bank_ifsc', $employee->bank_ifsc ?? null, ['class' => 'form-control']); !!}
        </div>
    </div>
</div>
