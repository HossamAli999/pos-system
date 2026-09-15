@extends('layouts.app')
@section('title', __('crm.all_leads'))

@section('content')

<section class="content-header">
    <h1>@lang('crm.all_leads')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <a href="{{action([\App\Http\Controllers\Crm\LeadController::class, 'board'])}}" class="btn btn-default"><i class="fa fa-columns"></i> @lang('crm.board')</a>
                @can('crm_lead.create')
                    <a href="{{action([\App\Http\Controllers\Crm\LeadController::class, 'create'])}}" class="btn btn-primary"><i class="fa fa-plus"></i> @lang('crm.add_lead')</a>
                @endcan
            </div>
        @endslot

        <table class="table table-bordered table-striped" id="crm_lead_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('crm.lead_number')</th>
                    <th>@lang('crm.name')</th>
                    <th>@lang('crm.company_name')</th>
                    <th>@lang('crm.stage')</th>
                    <th>@lang('crm.expected_value')</th>
                    <th>@lang('crm.assigned_to')</th>
                    <th>@lang('crm.status')</th>
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
        $('#crm_lead_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\Crm\LeadController::class, "index"])}}',
            columns: [
                { data: 'lead_number', name: 'lead_number' },
                { data: 'name', name: 'name' },
                { data: 'company_name', name: 'company_name' },
                { data: 'stage_name', name: 'stage_name' },
                { data: 'expected_value', name: 'expected_value' },
                { data: 'assigned_to_name', name: 'assigned_to_name' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });
    });
</script>
@endsection
