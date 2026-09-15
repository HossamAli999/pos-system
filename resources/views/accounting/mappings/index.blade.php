@extends('layouts.app')
@section('title', __('gl.setup_accounting'))

@section('content')

<section class="content-header">
    <h1>@lang('gl.setup_accounting')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\Accounting\ChartOfAccountMappingController::class, 'update']), 'method' => 'post']) !!}

    @component('components.widget', ['class' => 'box-primary'])
        <p class="help-block">@lang('gl.setup_wizard_intro')</p>

        <p>
            <a href="{{action([\App\Http\Controllers\Accounting\ChartOfAccountController::class, 'index'])}}" class="btn btn-default btn-xs">
                <i class="fa fa-list"></i> @lang('gl.chart_of_accounts')
            </a>
        </p>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width:40%;">@lang('gl.account_mappings')</th>
                    <th>@lang('gl.select_account')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mapping_keys as $key)
                    <tr>
                        <td>{{ __('gl.mapping_'.$key) }}</td>
                        <td>
                            {!! Form::select('mapping_'.$key, $accounts, $existing_mappings[$key] ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('gl.select_account')]); !!}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
        <div class="checkbox">
            <label>
                {!! Form::checkbox('enable_double_entry_accounting', 1, $is_enabled, ['class' => 'input-icheck']); !!}
                <strong>@lang('gl.enable_double_entry_accounting')</strong>
            </label>
        </div>
        <p class="help-block">@lang('gl.enable_double_entry_accounting_help')</p>
    @endcomponent

    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary btn-big">@lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>

@stop
