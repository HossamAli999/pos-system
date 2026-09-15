@extends('layouts.app')
@section('title', __('gl.journal_entries'))

@section('content')

<section class="content-header">
    <h1>@lang('gl.journal_entries')
        <small>#{{ $entry->id }}</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @can('journal_entry.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" id="reverse_entry_btn" class="btn btn-danger btn-xs"><i class="fa fa-undo"></i> @lang('gl.reverse_entry')</button>
                </div>
            @endslot
        @endcan

        <table class="table">
            <tr><th>@lang('gl.entry_date')</th><td>{{ $entry->entry_date->format('Y-m-d') }}</td></tr>
            <tr><th>@lang('gl.entry_type')</th><td>{{ __('gl.type_'.$entry->entry_type) }}</td></tr>
            <tr><th>@lang('gl.narration')</th><td>{{ $entry->narration }}</td></tr>
            <tr><th>@lang('gl.source')</th><td>{{ $entry->source_type ? class_basename($entry->source_type).' #'.$entry->source_id : '--' }}</td></tr>
        </table>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>@lang('gl.account')</th>
                    <th>@lang('gl.debit')</th>
                    <th>@lang('gl.credit')</th>
                    <th>@lang('gl.narration')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entry->lines as $line)
                    <tr>
                        <td>{{ $line->chart_of_account->name ?? '' }}</td>
                        <td>@if($line->debit > 0)@format_currency($line->debit)@endif</td>
                        <td>@if($line->credit > 0)@format_currency($line->credit)@endif</td>
                        <td>{{ $line->memo }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th class="text-right">@lang('gl.total')</th>
                    <th>@format_currency($entry->total_debit)</th>
                    <th>@format_currency($entry->total_credit)</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    @endcomponent
</section>

<form id="reverse_entry_form" method="post" action="{{action([\App\Http\Controllers\Accounting\JournalEntryController::class, 'reverse'], [$entry->id])}}" style="display:none;">
    @csrf
</form>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#reverse_entry_btn').on('click', function() {
            swal({
                title: LANG.sure,
                text: "{{__('gl.confirm_reverse_entry')}}",
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((willReverse) => {
                if (willReverse) {
                    $('#reverse_entry_form').submit();
                }
            });
        });
    });
</script>
@endsection
