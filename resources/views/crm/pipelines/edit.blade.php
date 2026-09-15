@extends('layouts.app')
@section('title', __('crm.edit_pipeline'))

@section('content')

<section class="content-header">
    <h1>@lang('crm.edit_pipeline')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Crm\PipelineController::class, 'update'], [$pipeline->id]), 'method' => 'put']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('name', __('crm.pipeline_name').':*') !!}
                    {!! Form::text('name', $pipeline->name, ['class' => 'form-control', 'required']); !!}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="checkbox">
                        <label>{!! Form::checkbox('is_default', 1, $pipeline->is_default, ['class' => 'input-icheck']); !!} @lang('crm.is_default')</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-right">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
        </div>
    {!! Form::close() !!}
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('crm.stages')])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-xs btn-primary" data-toggle="modal" data-target=".add_stage_modal"><i class="fa fa-plus"></i> @lang('crm.add_stage')</button>
            </div>
        @endslot

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>@lang('crm.stage_name')</th>
                    <th>@lang('crm.probability_percent')</th>
                    <th>@lang('crm.is_won_stage')</th>
                    <th>@lang('crm.is_lost_stage')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pipeline->stages as $stage)
                    <tr>
                        <td>{{ $stage->name }}</td>
                        <td>{{ $stage->probability_percent }}%</td>
                        <td>{{ $stage->is_won ? __('lang_v1.yes') : __('lang_v1.no') }}</td>
                        <td>{{ $stage->is_lost ? __('lang_v1.yes') : __('lang_v1.no') }}</td>
                        <td>
                            <button type="button" class="btn btn-xs btn-primary btn-edit-stage"
                                data-id="{{ $stage->id }}" data-name="{{ $stage->name }}"
                                data-probability="{{ $stage->probability_percent }}"
                                data-is-won="{{ $stage->is_won ? 1 : 0 }}" data-is-lost="{{ $stage->is_lost ? 1 : 0 }}">
                                <i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')
                            </button>
                            <button type="button" class="btn btn-xs btn-danger delete_stage_button" data-href="{{action([\App\Http\Controllers\Crm\PipelineStageController::class, 'destroy'], [$stage->id])}}">
                                <i class="glyphicon glyphicon-trash"></i> @lang('messages.delete')
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endcomponent
</section>

<!-- Add stage modal -->
<div class="modal fade add_stage_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['url' => action([\App\Http\Controllers\Crm\PipelineStageController::class, 'store'], [$pipeline->id]), 'method' => 'post']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('crm.add_stage')</h4>
            </div>
            <div class="modal-body">
                @include('crm.pipelines.partials.stage_fields')
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

<!-- Edit stage modal -->
<div class="modal fade edit_stage_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="edit_stage_form" method="post">
                @csrf
                @method('put')
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">@lang('crm.edit_stage')</h4>
                </div>
                <div class="modal-body">
                    @include('crm.pipelines.partials.stage_fields', ['id_prefix' => 'edit_'])
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $(document).on('click', '.btn-edit-stage', function() {
            var id = $(this).data('id');
            $('#edit_stage_form').attr('action', '{{ url("crm/pipeline-stages") }}/' + id);
            $('#edit_name').val($(this).data('name'));
            $('#edit_probability_percent').val($(this).data('probability'));
            $('#edit_is_won').prop('checked', $(this).data('is-won') == 1);
            $('#edit_is_lost').prop('checked', $(this).data('is-lost') == 1);
            $('.edit_stage_modal').modal('show');
        });

        $(document).on('click', 'button.delete_stage_button', function() {
            swal({
                title: LANG.sure,
                text: "{{__('crm.confirm_delete_stage')}}",
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
