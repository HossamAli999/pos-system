@extends('layouts.app')
@section('title', __('gl.add_journal_entry'))

@section('content')

<section class="content-header">
    <h1>@lang('gl.add_journal_entry')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Accounting\JournalEntryController::class, 'store']), 'method' => 'post', 'id' => 'journal_entry_form']) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('entry_date', __('gl.entry_date').':*') !!}
                    {!! Form::text('entry_date', \Carbon::now()->toDateString(), ['class' => 'form-control', 'id' => 'je_entry_date', 'required']); !!}
                </div>
            </div>
            <div class="col-md-8">
                <div class="form-group">
                    {!! Form::label('narration', __('gl.narration').':') !!}
                    {!! Form::text('narration', null, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>

        <table class="table table-bordered" id="journal_lines_table">
            <thead>
                <tr>
                    <th>@lang('gl.account')</th>
                    <th style="width:15%;">@lang('gl.debit')</th>
                    <th style="width:15%;">@lang('gl.credit')</th>
                    <th>@lang('gl.narration')</th>
                    <th style="width:5%;"></th>
                </tr>
            </thead>
            <tbody id="journal_lines_container"></tbody>
            <tfoot>
                <tr>
                    <th colspan="1" class="text-right">@lang('gl.total')</th>
                    <th id="journal_total_debit">0.00</th>
                    <th id="journal_total_credit">0.00</th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>

        <button type="button" id="add_journal_line" class="btn btn-default btn-sm"><i class="fa fa-plus"></i> @lang('gl.add_line')</button>
        <span id="journal_balance_warning" class="label bg-red" style="display:none; margin-left:10px;"></span>
    @endcomponent
    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary btn-big">@lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>

<div id="journal_line_row_template" style="display:none;">
    @include('accounting.journal_entries.partials.line_row', ['index' => '__INDEX__'])
</div>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var line_index = 0;

        $('#je_entry_date').datepicker({ autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true });

        function initSelect2InRow($row) {
            $row.find('select.select2').select2({ width: '100%' });
        }

        function recalcTotals() {
            var total_debit = 0;
            var total_credit = 0;
            $('.journal-debit-input').each(function() { total_debit += __read_number ? __read_number($(this)) : parseFloat($(this).val() || 0); });
            $('.journal-credit-input').each(function() { total_credit += __read_number ? __read_number($(this)) : parseFloat($(this).val() || 0); });

            total_debit = Math.round(total_debit * 100) / 100;
            total_credit = Math.round(total_credit * 100) / 100;

            $('#journal_total_debit').text(total_debit.toFixed(2));
            $('#journal_total_credit').text(total_credit.toFixed(2));

            if (total_debit !== total_credit) {
                $('#journal_balance_warning').text('{{ __("gl.entry_not_balanced", ["debit" => "", "credit" => ""]) }}').show();
            } else {
                $('#journal_balance_warning').hide();
            }
        }

        $('#add_journal_line').on('click', function() {
            var html = $('#journal_line_row_template').html().split('__INDEX__').join(line_index);
            var $row = $(html);
            $('#journal_lines_container').append($row);
            initSelect2InRow($row);
            line_index++;
        });

        $(document).on('click', '.remove-journal-line', function() {
            $(this).closest('tr').remove();
            recalcTotals();
        });

        $(document).on('input', '.journal-debit-input, .journal-credit-input', recalcTotals);

        //Start with two empty lines
        $('#add_journal_line').trigger('click');
        $('#add_journal_line').trigger('click');
    });
</script>
@endsection
