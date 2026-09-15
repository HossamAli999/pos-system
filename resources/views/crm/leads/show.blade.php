@extends('layouts.app')
@section('title', $lead->name)

@section('content')

<section class="content-header">
    <h1>{{ $lead->name }}
        <small>{{ $lead->lead_number }}</small>
        @php
            $status_labels = ['open' => 'bg-yellow', 'won' => 'bg-green', 'lost' => 'bg-red'];
        @endphp
        <span class="label {{ $status_labels[$lead->status] ?? 'bg-gray' }}">{{ __('crm.status_'.$lead->status) }}</span>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('crm.lead')])
                @can('crm_lead.update')
                    @slot('tool')
                        <div class="box-tools">
                            <a href="{{action([\App\Http\Controllers\Crm\LeadController::class, 'edit'], [$lead->id])}}" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i> @lang('messages.edit')</a>
                        </div>
                    @endslot
                @endcan

                <table class="table">
                    <tr><th>@lang('crm.company_name')</th><td>{{ $lead->company_name ?? '--' }}</td></tr>
                    <tr><th>@lang('crm.email')</th><td>{{ $lead->email ?? '--' }}</td></tr>
                    <tr><th>@lang('crm.phone')</th><td>{{ $lead->phone ?? '--' }}</td></tr>
                    <tr><th>@lang('crm.source')</th><td>{{ $lead->source->name ?? '--' }}</td></tr>
                    <tr><th>@lang('crm.assigned_to')</th><td>{{ trim(($lead->assigned_to_user->first_name ?? '').' '.($lead->assigned_to_user->last_name ?? '')) ?: '--' }}</td></tr>
                    <tr><th>@lang('crm.expected_value')</th><td>@if($lead->expected_value)@format_currency($lead->expected_value)@else --@endif</td></tr>
                    <tr><th>@lang('crm.expected_close_date')</th><td>{{ !empty($lead->expected_close_date) ? $lead->expected_close_date->format('Y-m-d') : '--' }}</td></tr>
                    <tr>
                        <th>@lang('crm.pipeline')</th>
                        <td>
                            {{ $lead->pipeline->name ?? '' }} &mdash; <strong>{{ $lead->stage->name ?? '' }}</strong>
                            @can('crm_lead.update')
                                @if($lead->status == 'open' && !empty($lead->pipeline))
                                    <form id="move_stage_form" style="display:inline-block; margin-left:10px;">
                                        <select id="move_stage_select" style="width:180px; display:inline-block;">
                                            @foreach($lead->pipeline->stages as $stage)
                                                <option value="{{ $stage->id }}" {{ $stage->id == $lead->stage_id ? 'selected' : '' }}>{{ $stage->name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                </table>

                <div style="margin-top:10px;">
                    @if(empty($lead->converted_contact_id))
                        @can('crm_lead.update')
                            <button type="button" class="btn btn-success btn-xs btn-convert-customer"><i class="fa fa-user-plus"></i> @lang('crm.convert_to_customer')</button>
                        @endcan
                    @else
                        <span class="label bg-green">{{ __('crm.already_converted') }}</span>
                        <a href="{{action([\App\Http\Controllers\ContactController::class, 'show'], [$lead->converted_contact_id])}}">{{ $lead->converted_contact->name ?? '' }}</a>
                    @endif

                    @can('crm_lead.update')
                        <button type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target=".convert_quotation_modal"><i class="fa fa-file-invoice"></i> @lang('crm.convert_to_quotation')</button>
                    @endcan
                </div>
            @endcomponent

            @component('components.widget', ['class' => 'box-primary', 'title' => __('crm.products')])
                <table class="table table-bordered">
                    <thead><tr><th>@lang('crm.product')</th><th>@lang('crm.quantity')</th><th>@lang('crm.unit_price')</th><th>@lang('crm.line_total')</th></tr></thead>
                    <tbody>
                    @forelse($lead->lead_products as $lp)
                        <tr>
                            <td>{{ $lp->product->name ?? '' }}</td>
                            <td>{{ $lp->quantity }}</td>
                            <td>@format_currency($lp->unit_price)</td>
                            <td>@format_currency($lp->line_total)</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">--</td></tr>
                    @endforelse
                    </tbody>
                </table>
            @endcomponent

            @component('components.widget', ['class' => 'box-primary', 'title' => __('crm.stage_history')])
                <ul class="list-unstyled">
                    @forelse($lead->stage_history as $h)
                        <li>
                            <small class="text-muted">
                                {{ @format_datetime($h->changed_at) }} &mdash;
                                {{ __('crm.moved_to') }} <strong>{{ $h->to_stage->name ?? '' }}</strong>
                                ({{ trim(($h->changed_by_user->first_name ?? '').' '.($h->changed_by_user->last_name ?? '')) }})
                            </small>
                        </li>
                    @empty
                        <li class="text-muted">--</li>
                    @endforelse
                </ul>
            @endcomponent
        </div>

        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('crm.activities')])
                @can('crm_activity.manage')
                    @slot('tool')
                        <div class="box-tools">
                            <button type="button" class="btn btn-xs btn-primary" data-toggle="modal" data-target=".log_activity_modal"><i class="fa fa-plus"></i> @lang('crm.log_activity')</button>
                        </div>
                    @endslot
                @endcan

                <ul class="list-unstyled" id="lead_activities_list">
                    @forelse($lead->activities as $activity)
                        <li style="margin-bottom:10px; {{ $activity->is_done ? 'opacity:0.6;' : '' }}">
                            <strong>{{ __('crm.type_'.$activity->type) }}:</strong> {{ $activity->subject }}
                            @if(! empty($activity->due_date))
                                <br><small class="text-muted">{{ __('crm.due_date') }}: {{ @format_datetime($activity->due_date) }}</small>
                            @endif
                            @if(! empty($activity->description))
                                <br><small>{{ $activity->description }}</small>
                            @endif
                            @can('crm_activity.manage')
                                @if(! $activity->is_done)
                                    <button type="button" class="btn btn-xs btn-default btn-complete-activity" data-id="{{ $activity->id }}"><i class="fa fa-check"></i> @lang('crm.mark_done')</button>
                                @else
                                    <span class="label bg-green">{{ __('crm.done') }}</span>
                                @endif
                            @endcan
                        </li>
                    @empty
                        <li class="text-muted">{{ __('crm.no_activities_yet') }}</li>
                    @endforelse
                </ul>
            @endcomponent
        </div>
    </div>
</section>

<!-- Log activity modal -->
<div class="modal fade log_activity_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['url' => action([\App\Http\Controllers\Crm\ActivityController::class, 'store']), 'method' => 'post', 'id' => 'log_activity_form']) !!}
            <input type="hidden" name="crm_lead_id" value="{{ $lead->id }}">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('crm.log_activity')</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('type', __('crm.activity_type').':') !!}
                    {!! Form::select('type', ['call' => __('crm.type_call'), 'email' => __('crm.type_email'), 'meeting' => __('crm.type_meeting'), 'note' => __('crm.type_note'), 'task' => __('crm.type_task')], 'call', ['class' => 'form-control']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('subject', __('crm.subject').':*') !!}
                    {!! Form::text('subject', null, ['class' => 'form-control', 'required']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('description', __('crm.description').':') !!}
                    {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3]); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('due_date', __('crm.due_date').':') !!}
                    {!! Form::text('due_date', null, ['class' => 'form-control', 'id' => 'activity_due_date']); !!}
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

<!-- Convert to quotation modal -->
<div class="modal fade convert_quotation_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['url' => action([\App\Http\Controllers\Crm\LeadController::class, 'convertToQuotation'], [$lead->id]), 'method' => 'post']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('crm.convert_to_quotation')</h4>
            </div>
            <div class="modal-body">
                <p class="help-block">@lang('crm.select_location_to_convert')</p>
                <div class="form-group">
                    {!! Form::label('location_id', __('hr.location').':*') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'required']); !!}
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
        $('#activity_due_date').datetimepicker ? $('#activity_due_date').datetimepicker({ format: 'YYYY-MM-DD HH:mm' }) : $('#activity_due_date').datepicker({ autoclose: true, format: 'yyyy-mm-dd' });

        $('#move_stage_select').on('change', function() {
            $.ajax({
                method: 'POST',
                url: '{{ action([\App\Http\Controllers\Crm\LeadController::class, "postMoveStage"]) }}',
                dataType: 'json',
                data: { lead_id: {{ $lead->id }}, stage_id: $(this).val() },
                success: function(result) {
                    if (result.success === true) {
                        toastr.success(result.msg);
                        setTimeout(function() { location.reload(); }, 600);
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $('.btn-convert-customer').on('click', function() {
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
            }).then((willConvert) => {
                if (willConvert) {
                    $.ajax({
                        method: 'POST',
                        url: '{{ action([\App\Http\Controllers\Crm\LeadController::class, "convertToCustomer"], [$lead->id]) }}',
                        dataType: 'json',
                        success: function(result) {
                            if (result.success === true) {
                                toastr.success(result.msg);
                                setTimeout(function() { location.reload(); }, 600);
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                }
            });
        });

        $(document).on('click', '.btn-complete-activity', function() {
            var id = $(this).data('id');
            $.ajax({
                method: 'POST',
                url: '{{ url("crm/activities") }}/' + id + '/complete',
                dataType: 'json',
                success: function(result) {
                    if (result.success === true) {
                        toastr.success(result.msg);
                        location.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });
    });
</script>
@endsection
