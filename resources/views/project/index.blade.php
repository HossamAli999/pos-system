@extends('layouts.app')
@section('title', __('project.all_projects'))

@section('content')

<section class="content-header">
    <h1>@lang('project.all_projects')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @can('project.create')
            @slot('tool')
                <div class="box-tools">
                    <a href="{{action([\App\Http\Controllers\ProjectController::class, 'create'])}}" class="btn btn-primary"><i class="fa fa-plus"></i> @lang('project.add_project')</a>
                </div>
            @endslot
        @endcan

        <table class="table table-bordered table-striped" id="project_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('project.name')</th>
                    <th>@lang('project.client')</th>
                    <th>@lang('project.status')</th>
                    <th>@lang('project.start_date')</th>
                    <th>@lang('project.end_date')</th>
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
        $('#project_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\ProjectController::class, "index"])}}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'contact_name', name: 'contact_name' },
                { data: 'status', name: 'status' },
                { data: 'start_date', name: 'start_date' },
                { data: 'end_date', name: 'end_date' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });
    });
</script>
@endsection
