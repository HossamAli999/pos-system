@extends('layouts.app')
@section('title', __('crm.my_tasks'))

@section('content')

<section class="content-header">
    <h1>@lang('crm.my_tasks')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @if($activities->count() == 0)
            <p class="text-muted text-center" style="padding:20px 0;">@lang('crm.no_pending_tasks')</p>
        @else
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>@lang('crm.activity_type')</th>
                        <th>@lang('crm.subject')</th>
                        <th>@lang('crm.lead')</th>
                        <th>@lang('crm.due_date')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activities as $activity)
                        <tr>
                            <td>{{ __('crm.type_'.$activity->type) }}</td>
                            <td>{{ $activity->subject }}</td>
                            <td>
                                @if(! empty($activity->lead))
                                    <a href="{{action([\App\Http\Controllers\Crm\LeadController::class, 'show'], [$activity->lead->id])}}">{{ $activity->lead->name }}</a>
                                @else
                                    --
                                @endif
                            </td>
                            <td>{{ @format_datetime($activity->due_date) }}</td>
                            <td>
                                <button type="button" class="btn btn-xs btn-default btn-complete-activity" data-id="{{ $activity->id }}"><i class="fa fa-check"></i> @lang('crm.mark_done')</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endcomponent
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $(document).on('click', '.btn-complete-activity', function() {
            var id = $(this).data('id');
            var $row = $(this).closest('tr');
            $.ajax({
                method: 'POST',
                url: '{{ url("crm/activities") }}/' + id + '/complete',
                dataType: 'json',
                success: function(result) {
                    if (result.success === true) {
                        toastr.success(result.msg);
                        $row.fadeOut();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });
    });
</script>
@endsection
