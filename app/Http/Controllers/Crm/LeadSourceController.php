<?php

namespace App\Http\Controllers\Crm;

use App\CrmLeadSource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LeadSourceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('crm_pipeline.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $sources = CrmLeadSource::where('business_id', $business_id)->select(['id', 'name']);

            return DataTables::of($sources)
                ->addColumn('action', function ($row) {
                    return '<button data-href="'.action([\App\Http\Controllers\Crm\LeadSourceController::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary btn-modal" data-container=".lead_source_modal"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</button>
                        &nbsp;
                        <button data-href="'.action([\App\Http\Controllers\Crm\LeadSourceController::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_lead_source_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('crm.lead_sources.index');
    }

    public function create()
    {
        if (! auth()->user()->can('crm_pipeline.manage')) {
            abort(403, 'Unauthorized action.');
        }

        return view('crm.lead_sources.create');
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('crm_pipeline.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            CrmLeadSource::create([
                'business_id' => $request->session()->get('user.business_id'),
                'name' => $request->input('name'),
            ]);

            $output = ['success' => true, 'msg' => __('crm.lead_source_added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function edit($id)
    {
        if (! auth()->user()->can('crm_pipeline.manage')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $lead_source = CrmLeadSource::where('business_id', $business_id)->findOrFail($id);

            return view('crm.lead_sources.edit')->with(compact('lead_source'));
        }
    }

    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('crm_pipeline.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $lead_source = CrmLeadSource::where('business_id', $business_id)->findOrFail($id);
            $lead_source->name = $request->input('name');
            $lead_source->save();

            $output = ['success' => true, 'msg' => __('crm.lead_source_updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function destroy($id)
    {
        if (! auth()->user()->can('crm_pipeline.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            CrmLeadSource::where('business_id', $business_id)->findOrFail($id)->delete();

            $output = ['success' => true, 'msg' => __('crm.lead_source_deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}
