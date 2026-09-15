@php
    $step = $step ?? null;
    $selected_type = $step->approver_type ?? 'user';
    $selected_user = $step->approver_type == 'user' ? $step->approver_id : null;
    $selected_role = $step->approver_type == 'role' ? $step->approver_id : null;
    $selected_permission = $step->permission_name ?? null;
@endphp
<div class="approval-step-row" style="border:1px solid #ddd; border-radius:4px; padding:15px 15px 0 15px; margin-bottom:15px; position:relative;">
    <button type="button" class="btn btn-xs btn-danger remove-approval-step" style="position:absolute; top:10px; right:10px;" title="{{__('approval.remove_step')}}">
        <i class="fa fa-times"></i>
    </button>
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('', __('approval.step').' #', ['class' => 'step-order-label']) !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('', __('approval.approver_type').':') !!}
                <select name="steps[{{$index}}][approver_type]" class="form-control approver-type-select">
                    <option value="user" {{ $selected_type == 'user' ? 'selected' : '' }}>@lang('approval.approver_type_user')</option>
                    <option value="role" {{ $selected_type == 'role' ? 'selected' : '' }}>@lang('approval.approver_type_role')</option>
                    <option value="permission" {{ $selected_type == 'permission' ? 'selected' : '' }}>@lang('approval.approver_type_permission')</option>
                </select>
            </div>
        </div>
        <div class="col-md-8">
            <div class="form-group approver-user-field" @if($selected_type != 'user') style="display:none;" @endif>
                {!! Form::label('', __('approval.select_user').':') !!}
                {!! Form::select('steps['.$index.'][approver_user_id]', $users, $selected_user, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
            </div>
            <div class="form-group approver-role-field" @if($selected_type != 'role') style="display:none;" @endif>
                {!! Form::label('', __('approval.select_role').':') !!}
                {!! Form::select('steps['.$index.'][approver_role_id]', $roles, $selected_role, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
            </div>
            <div class="form-group approver-permission-field" @if($selected_type != 'permission') style="display:none;" @endif>
                {!! Form::label('', __('approval.permission_name').':') !!}
                {!! Form::text('steps['.$index.'][permission_name]', $selected_permission, ['class' => 'form-control', 'placeholder' => 'e.g. purchase_requisition.approve']); !!}
                <p class="help-block">@lang('approval.permission_name_help')</p>
            </div>
        </div>
    </div>
</div>
