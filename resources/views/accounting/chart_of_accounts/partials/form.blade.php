@php $account = $account ?? null; @endphp
<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('code', __('gl.code').':') !!}
            {!! Form::text('code', $account->code ?? null, ['class' => 'form-control']); !!}
        </div>
    </div>
    <div class="col-md-5">
        <div class="form-group">
            {!! Form::label('name', __('gl.name').':*') !!}
            {!! Form::text('name', $account->name ?? null, ['class' => 'form-control', 'required']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('account_category', __('gl.account_category').':*') !!}
            {!! Form::select('account_category', [
                'asset' => __('gl.category_asset'), 'liability' => __('gl.category_liability'),
                'equity' => __('gl.category_equity'), 'income' => __('gl.category_income'),
                'expense' => __('gl.category_expense'),
            ], $account->account_category ?? null, ['class' => 'form-control', 'required']); !!}
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('account_subcategory', __('gl.account_subcategory').':') !!}
            {!! Form::text('account_subcategory', $account->account_subcategory ?? null, ['class' => 'form-control']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('parent_id', __('gl.parent_account').':') !!}
            {!! Form::select('parent_id', $parent_accounts, $account->parent_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.none')]); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('linked_account_id', __('gl.linked_account').':') !!}
            {!! Form::select('linked_account_id', $payment_accounts, $account->linked_account_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.none')]); !!}
            <p class="help-block">@lang('gl.linked_account_help')</p>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('opening_balance', __('gl.opening_balance').':') !!}
            {!! Form::text('opening_balance', $account->opening_balance ?? 0, ['class' => 'form-control input_number']); !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('opening_balance_date', __('gl.opening_balance_date').':') !!}
            {!! Form::text('opening_balance_date', ! empty($account->opening_balance_date) ? $account->opening_balance_date->format('Y-m-d') : null, ['class' => 'form-control', 'id' => 'coa_opening_balance_date']); !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>&nbsp;</label>
            <div class="checkbox">
                <label>{!! Form::checkbox('is_active', 1, $account->is_active ?? true, ['class' => 'input-icheck']); !!} @lang('gl.is_active')</label>
            </div>
        </div>
    </div>
</div>
