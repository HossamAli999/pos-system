<?php

namespace App\Http\Controllers\Crm;

use App\CrmLead;
use App\CrmPipeline;
use App\CrmPipelineStage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PipelineStageController extends Controller
{
    /**
     * Adds a new stage to a pipeline.
     *
     * @param  int  $pipeline_id
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $pipeline_id)
    {
        if (! auth()->user()->can('crm_pipeline.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $pipeline = CrmPipeline::where('business_id', $business_id)->findOrFail($pipeline_id);

            $next_order = $pipeline->stages()->max('stage_order') + 1;

            $pipeline->stages()->create([
                'name' => $request->input('name'),
                'stage_order' => $next_order,
                'probability_percent' => $request->input('probability_percent', 0),
                'is_won' => $request->boolean('is_won'),
                'is_lost' => $request->boolean('is_lost'),
            ]);

            $output = ['success' => true, 'msg' => __('crm.stage_added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Crm\PipelineController::class, 'edit'], [$pipeline_id])->with('status', $output);
    }

    /**
     * Updates an existing stage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('crm_pipeline.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $stage = CrmPipelineStage::whereHas('pipeline', function ($q) use ($request) {
                $q->where('business_id', $request->session()->get('user.business_id'));
            })->findOrFail($id);

            $stage->name = $request->input('name');
            $stage->probability_percent = $request->input('probability_percent', 0);
            $stage->is_won = $request->boolean('is_won');
            $stage->is_lost = $request->boolean('is_lost');
            $stage->save();

            $output = ['success' => true, 'msg' => __('crm.stage_updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Crm\PipelineController::class, 'edit'], [$stage->crm_pipeline_id])->with('status', $output);
    }

    /**
     * Removes a stage — refused if any lead is still sitting in it, since
     * crm_leads.stage_id cascades on delete and would silently wipe those leads.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        if (! auth()->user()->can('crm_pipeline.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $stage = CrmPipelineStage::whereHas('pipeline', function ($q) use ($request) {
                $q->where('business_id', $request->session()->get('user.business_id'));
            })->findOrFail($id);

            if (CrmLead::where('stage_id', $stage->id)->exists()) {
                return ['success' => false, 'msg' => __('crm.cannot_delete_stage_with_leads')];
            }

            $stage->delete();

            $output = ['success' => true, 'msg' => __('crm.stage_deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}
