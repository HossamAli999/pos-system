@extends('layouts.app')
@section('title', $project->name)

@php
    $status_labels = ['active' => 'bg-green', 'on_hold' => 'bg-yellow', 'completed' => 'bg-info', 'cancelled' => 'bg-red'];
@endphp

@section('content')

<section class="content-header">
    <h1>{{ $project->name }}
        <span class="label {{ $status_labels[$project->status] ?? 'bg-gray' }}">{{ __('project.status_'.$project->status) }}</span>
        @can('project.update')
            <a href="{{action([\App\Http\Controllers\ProjectController::class, 'edit'], [$project->id])}}" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i> @lang('messages.edit')</a>
        @endcan
    </h1>
    <p class="text-muted">{{ $project->description }}</p>
</section>

<section class="content">
    <p>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target=".add_task_modal">
            <i class="fa fa-plus"></i> @lang('project.add_task')
        </button>
    </p>

    <div class="row project-board" style="overflow-x:auto; white-space:nowrap;">
        @foreach($statuses as $status)
            <div class="project-board-column" data-status="{{ $status }}" style="display:inline-block; vertical-align:top; width:280px; white-space:normal; margin-right:10px; background:#f4f4f4; border-radius:4px; padding:10px; min-height:400px;">
                <h4>{{ __('project.status_'.$status) }} <span class="badge">{{ isset($tasks_by_status[$status]) ? $tasks_by_status[$status]->count() : 0 }}</span></h4>
                <div class="project-board-cards" data-status="{{ $status }}" style="min-height:350px;">
                    @forelse(($tasks_by_status[$status] ?? []) as $task)
                        <div class="project-task-card" draggable="true" data-task-id="{{ $task->id }}" data-toggle="modal" data-target="#task_detail_modal_{{ $task->id }}" style="background:#fff; border:1px solid #ddd; border-radius:4px; padding:8px; margin-bottom:8px; cursor:pointer;">
                            <strong>{{ $task->title }}</strong>
                            @if(! empty($task->assigned_to_user))
                                <br><small class="text-muted">{{ trim(($task->assigned_to_user->first_name ?? '').' '.($task->assigned_to_user->last_name ?? '')) }}</small>
                            @endif
                            @if(! empty($task->due_date))
                                <br><small>{{ __('project.due_date') }}: {{ $task->due_date->format('Y-m-d') }}</small>
                            @endif
                        </div>
                    @empty
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</section>

<!-- Add task modal -->
<div class="modal fade add_task_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['url' => action([\App\Http\Controllers\ProjectTaskController::class, 'store'], [$project->id]), 'method' => 'post']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('project.add_task')</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('title', __('project.task_title').':*') !!}
                    {!! Form::text('title', null, ['class' => 'form-control', 'required']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('assigned_to', __('project.assigned_to').':') !!}
                    {!! Form::select('assigned_to', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.none')]); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('due_date', __('project.due_date').':') !!}
                    {!! Form::text('due_date', null, ['class' => 'form-control', 'id' => 'task_due_date']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('description', __('project.description').':') !!}
                    {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 2]); !!}
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

<!-- Per-task detail modals -->
@foreach($project->tasks as $task)
    <div class="modal fade" id="task_detail_modal_{{ $task->id }}" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">{{ $task->title }}</h4>
                </div>
                <div class="modal-body">
                    <p>{{ $task->description }}</p>

                    <h5>@lang('project.comments')</h5>
                    <ul class="list-unstyled">
                        @forelse($task->comments as $comment)
                            <li>
                                <small class="text-muted">{{ @format_datetime($comment->created_at) }} &mdash; {{ trim(($comment->created_by_user->first_name ?? '').' '.($comment->created_by_user->last_name ?? '')) }}:</small>
                                {{ $comment->comment }}
                            </li>
                        @empty
                            <li class="text-muted">--</li>
                        @endforelse
                    </ul>
                    {!! Form::open(['url' => action([\App\Http\Controllers\ProjectTaskController::class, 'addComment'], [$task->id]), 'method' => 'post']) !!}
                    <div class="form-group">
                        {!! Form::text('comment', null, ['class' => 'form-control', 'placeholder' => __('project.add_comment'), 'required']); !!}
                    </div>
                    <button type="submit" class="btn btn-xs btn-primary">@lang('project.add_comment')</button>
                    {!! Form::close() !!}

                    <hr>
                    <h5>@lang('project.log_time') ({{ __('project.total_hours_logged') }}: {{ $task->time_logs->sum('hours') }})</h5>
                    {!! Form::open(['url' => action([\App\Http\Controllers\ProjectTaskController::class, 'logTime'], [$task->id]), 'method' => 'post']) !!}
                    <div class="row">
                        <div class="col-xs-5">
                            {!! Form::text('log_date', \Carbon::now()->toDateString(), ['class' => 'form-control task-log-date']); !!}
                        </div>
                        <div class="col-xs-4">
                            {!! Form::text('hours', null, ['class' => 'form-control input_number', 'placeholder' => __('project.hours')]); !!}
                        </div>
                        <div class="col-xs-3">
                            <button type="submit" class="btn btn-primary btn-block">@lang('project.log_time')</button>
                        </div>
                    </div>
                    {!! Form::close() !!}

                    <div class="text-right" style="margin-top:15px;">
                        <button type="button" class="btn btn-xs btn-danger delete_task_button" data-href="{{action([\App\Http\Controllers\ProjectTaskController::class, 'destroy'], [$task->id])}}">
                            <i class="fa fa-trash"></i> @lang('messages.delete')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#task_due_date, .task-log-date').datepicker({ autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true });

        var dragged_task_id = null;

        $(document).on('dragstart', '.project-task-card', function(e) {
            dragged_task_id = $(this).data('task-id');
            e.originalEvent.dataTransfer.setData('text/plain', dragged_task_id);
        });

        $(document).on('dragover', '.project-board-cards', function(e) {
            e.preventDefault();
        });

        $(document).on('drop', '.project-board-cards', function(e) {
            e.preventDefault();
            var $target = $(this);
            var new_status = $target.data('status');
            var $card = $('.project-task-card[data-task-id="' + dragged_task_id + '"]');

            $target.append($card);

            $.ajax({
                method: 'POST',
                url: '{{ url("project-tasks") }}/' + dragged_task_id + '/move-status',
                dataType: 'json',
                data: { status: new_status },
                success: function(result) {
                    if (result.success === true) {
                        toastr.success(result.msg);
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', 'button.delete_task_button', function() {
            swal({
                title: LANG.sure,
                text: "{{__('project.confirm_delete_task')}}",
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
                                location.reload();
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
