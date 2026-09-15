@extends('layouts.app')
@section('title', __('crm.board'))

@section('content')

<section class="content-header">
    <h1>@lang('crm.board')</h1>
</section>

<section class="content">
    <div class="row" style="margin-bottom:15px;">
        <div class="col-md-4">
            <select id="board_pipeline_select" class="form-control select2" style="width:100%;">
                @foreach($pipelines as $p)
                    <option value="{{ $p->id }}" {{ !empty($pipeline) && $pipeline->id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if(empty($pipeline))
        <div class="alert alert-warning">@lang('crm.no_pipeline_configured')</div>
    @else
        <div class="row crm-board" style="overflow-x:auto; white-space:nowrap;">
            @foreach($pipeline->stages as $stage)
                <div class="crm-board-column" data-stage-id="{{ $stage->id }}" style="display:inline-block; vertical-align:top; width:280px; white-space:normal; margin-right:10px; background:#f4f4f4; border-radius:4px; padding:10px; min-height:400px;">
                    <h4>{{ $stage->name }} <span class="badge">{{ isset($leads[$stage->id]) ? $leads[$stage->id]->count() : 0 }}</span></h4>
                    <div class="crm-board-cards" data-stage-id="{{ $stage->id }}" style="min-height:350px;">
                        @forelse(($leads[$stage->id] ?? []) as $lead)
                            <div class="crm-board-card" draggable="true" data-lead-id="{{ $lead->id }}" style="background:#fff; border:1px solid #ddd; border-radius:4px; padding:8px; margin-bottom:8px; cursor:move;">
                                <a href="{{action([\App\Http\Controllers\Crm\LeadController::class, 'show'], [$lead->id])}}">{{ $lead->name }}</a>
                                <br><small class="text-muted">{{ $lead->company_name }}</small>
                                @if($lead->expected_value)
                                    <br><small>@format_currency($lead->expected_value)</small>
                                @endif
                            </div>
                        @empty
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#board_pipeline_select').on('change', function() {
            window.location = '{{ action([\App\Http\Controllers\Crm\LeadController::class, "board"]) }}?pipeline_id=' + $(this).val();
        });

        var dragged_lead_id = null;

        $(document).on('dragstart', '.crm-board-card', function(e) {
            dragged_lead_id = $(this).data('lead-id');
            e.originalEvent.dataTransfer.setData('text/plain', dragged_lead_id);
        });

        $(document).on('dragover', '.crm-board-cards', function(e) {
            e.preventDefault();
        });

        $(document).on('drop', '.crm-board-cards', function(e) {
            e.preventDefault();
            var $target = $(this);
            var new_stage_id = $target.data('stage-id');
            var $card = $('.crm-board-card[data-lead-id="' + dragged_lead_id + '"]');

            $target.append($card);

            $.ajax({
                method: 'POST',
                url: '{{ action([\App\Http\Controllers\Crm\LeadController::class, "postMoveStage"]) }}',
                dataType: 'json',
                data: { lead_id: dragged_lead_id, stage_id: new_stage_id },
                success: function(result) {
                    if (result.success === true) {
                        toastr.success(result.msg);
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });
    });
</script>
@endsection
