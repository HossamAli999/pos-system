@extends('layouts.app')
@section('title', __('approval.add_approval_workflow'))

@section('content')

<section class="content-header">
    <h1>@lang('approval.add_approval_workflow')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\ApprovalWorkflowController::class, 'store']), 'method' => 'post', 'id' => 'approval_workflow_form']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('name', __('approval.name').':*') !!}
                    {!! Form::text('name', null, ['class' => 'form-control', 'required']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('module', __('approval.module').':*') !!}
                    {!! Form::text('module', null, ['class' => 'form-control', 'required', 'placeholder' => 'leave_request']); !!}
                    <p class="help-block">@lang('approval.module_help')</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('is_active', 1, true, ['class' => 'input-icheck']); !!} @lang('approval.is_active')
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('min_amount', __('approval.min_amount').':') !!}
                    {!! Form::text('min_amount', null, ['class' => 'form-control input_number']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('max_amount', __('approval.max_amount').':') !!}
                    {!! Form::text('max_amount', null, ['class' => 'form-control input_number']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <p class="help-block" style="margin-top:25px;">@lang('approval.amount_range_help')</p>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('approval.steps')])
        @slot('tool')
            <div class="box-tools">
                <button type="button" id="add_approval_step" class="btn btn-block btn-primary btn-xs">
                    <i class="fa fa-plus"></i> @lang('approval.add_step')
                </button>
            </div>
        @endslot

        <p class="help-block">@lang('approval.final_step_note')</p>

        <div id="approval_steps_container"></div>
        <p id="no_steps_message" class="text-muted">@lang('approval.no_steps_added')</p>
    @endcomponent

    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary btn-big">@lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>

<div id="approval_step_row_template" style="display:none;">
    @include('approval_workflow.partials.step_row', ['index' => '__INDEX__', 'users' => $users, 'roles' => $roles, 'step' => null])
</div>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var step_index = 0;

        function toggleNoStepsMessage() {
            if ($('#approval_steps_container .approval-step-row').length > 0) {
                $('#no_steps_message').hide();
            } else {
                $('#no_steps_message').show();
            }
        }

        function renumberSteps() {
            $('#approval_steps_container .approval-step-row').each(function(i) {
                $(this).find('.step-order-label').text((i + 1));
            });
        }

        function initSelect2InRow($row) {
            $row.find('select.select2').select2({ width: '100%' });
        }

        $('#add_approval_step').on('click', function() {
            var html = $('#approval_step_row_template').html().split('__INDEX__').join(step_index);
            var $row = $(html);
            $('#approval_steps_container').append($row);
            initSelect2InRow($row);
            step_index++;
            renumberSteps();
            toggleNoStepsMessage();
        });

        $(document).on('click', '.remove-approval-step', function() {
            $(this).closest('.approval-step-row').remove();
            renumberSteps();
            toggleNoStepsMessage();
        });

        $(document).on('change', '.approver-type-select', function() {
            var $row = $(this).closest('.approval-step-row');
            var type = $(this).val();
            $row.find('.approver-user-field, .approver-role-field, .approver-permission-field').hide();
            $row.find('.approver-' + type + '-field').show();
        });

        //Start with one empty step by default
        $('#add_approval_step').trigger('click');

        $('#approval_workflow_form').on('submit', function() {
            if ($('#approval_steps_container .approval-step-row').length == 0) {
                toastr.error('{{__('approval.no_steps_added')}}');
                return false;
            }
        });
    });
</script>
@endsection
