@php $index = $index ?? '__INDEX__'; @endphp
<tr class="journal-line-row">
    <td>
        {!! Form::select('lines['.$index.'][chart_of_account_id]', $accounts, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
    </td>
    <td>{!! Form::text('lines['.$index.'][debit]', 0, ['class' => 'form-control input_number journal-debit-input']); !!}</td>
    <td>{!! Form::text('lines['.$index.'][credit]', 0, ['class' => 'form-control input_number journal-credit-input']); !!}</td>
    <td>{!! Form::text('lines['.$index.'][memo]', null, ['class' => 'form-control']); !!}</td>
    <td><button type="button" class="btn btn-xs btn-danger remove-journal-line"><i class="fa fa-times"></i></button></td>
</tr>
