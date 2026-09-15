@extends('layouts.app')
@section('title', __('gl.journal_entries'))

@section('content')

<section class="content-header">
    <h1>@lang('gl.journal_entries')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @can('journal_entry.create')
            @slot('tool')
                <div class="box-tools">
                    <a href="{{action([\App\Http\Controllers\Accounting\JournalEntryController::class, 'create'])}}" class="btn btn-primary"><i class="fa fa-plus"></i> @lang('gl.add_journal_entry')</a>
                </div>
            @endslot
        @endcan

        <table class="table table-bordered table-striped" id="journal_entry_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('gl.entry_date')</th>
                    <th>@lang('gl.entry_type')</th>
                    <th>@lang('gl.reference_number')</th>
                    <th>@lang('gl.narration')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#journal_entry_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            ajax: '{{action([\App\Http\Controllers\Accounting\JournalEntryController::class, "index"])}}',
            columns: [
                { data: 'entry_date', name: 'entry_date' },
                { data: 'entry_type', name: 'entry_type' },
                { data: 'reference_number', name: 'reference_number' },
                { data: 'narration', name: 'narration' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });
    });
</script>
@endsection
