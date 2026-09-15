@extends('layouts.app')
@section('title', __('gl.chart_of_accounts'))

@section('content')

<section class="content-header">
    <h1>@lang('gl.chart_of_accounts')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <a href="{{action([\App\Http\Controllers\Accounting\ChartOfAccountController::class, 'create'])}}" class="btn btn-primary"><i class="fa fa-plus"></i> @lang('gl.add_account')</a>
                <button type="button" id="import_default_chart_btn" class="btn btn-default"><i class="fa fa-download"></i> @lang('gl.import_default_chart')</button>
            </div>
        @endslot

        <table class="table table-bordered table-striped" id="chart_of_account_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('gl.code')</th>
                    <th>@lang('gl.name')</th>
                    <th>@lang('gl.account_category')</th>
                    <th>@lang('gl.parent_account')</th>
                    <th>@lang('gl.is_active')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent
</section>

<form id="import_default_chart_form" method="post" action="{{action([\App\Http\Controllers\Accounting\ChartOfAccountController::class, 'importDefaultChart'])}}" style="display:none;">
    @csrf
</form>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#chart_of_account_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Accounting\ChartOfAccountController::class, "index"])}}',
            columns: [
                { data: 'code', name: 'code' },
                { data: 'name', name: 'name' },
                { data: 'account_category', name: 'account_category' },
                { data: 'parent_name', name: 'parent_name' },
                { data: 'is_active', name: 'is_active', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $('#import_default_chart_btn').on('click', function() {
            swal({
                title: LANG.sure,
                text: "{{__('gl.import_default_chart_confirm')}}",
                icon: 'warning',
                buttons: true,
            }).then((willImport) => {
                if (willImport) {
                    $('#import_default_chart_form').submit();
                }
            });
        });

        $(document).on('click', 'button.delete_chart_of_account_button', function() {
            swal({
                title: LANG.sure,
                text: "{{__('gl.confirm_delete_account')}}",
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
