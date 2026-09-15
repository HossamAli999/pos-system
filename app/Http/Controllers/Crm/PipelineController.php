<?php

namespace App\Http\Controllers\Crm;

use App\CrmPipeline;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PipelineController extends Controller
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
            $pipelines = CrmPipeline::where('business_id', $business_id)
                ->withCount('stages')
                ->select(['id', 'name', 'is_default', 'stages_count']);

            return DataTables::of($pipelines)
                ->editColumn('is_default', function ($row) {
                    return $row->is_default ? __('lang_v1.yes') : __('lang_v1.no');
                })
                ->addColumn('action', function ($row) {
                    $html = '<a href="'.action([\App\Http\Controllers\Crm\PipelineController::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</a>';
                    $html .= ' <button data-href="'.action([\App\Http\Controllers\Crm\PipelineController::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_pipeline_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';

                    return $html;
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('crm.pipelines.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('crm_pipeline.manage')) {
            abort(403, 'Unauthorized action.');
        }

        return view('crm.pipelines.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('crm_pipeline.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            if ($request->boolean('is_default')) {
                CrmPipeline::where('business_id', $business_id)->update(['is_default' => 0]);
            }

            $pipeline = CrmPipeline::create([
                'business_id' => $business_id,
                'name' => $request->input('name'),
                'is_default' => $request->boolean('is_default'),
            ]);

            //Seed a sensible default set of stages so the pipeline is usable immediately.
            $pipeline->stages()->createMany([
                ['name' => __('crm.stage_new'), 'stage_order' => 1],
                ['name' => __('crm.stage_qualified'), 'stage_order' => 2],
                ['name' => __('crm.stage_proposal'), 'stage_order' => 3],
                ['name' => __('crm.stage_won'), 'stage_order' => 4, 'is_won' => 1, 'probability_percent' => 100],
                ['name' => __('crm.stage_lost'), 'stage_order' => 5, 'is_lost' => 1],
            ]);

            $output = ['success' => true, 'msg' => __('crm.pipeline_added_success')];

            return redirect()->action([\App\Http\Controllers\Crm\PipelineController::class, 'edit'], [$pipeline->id])->with('status', $output);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];

            return redirect()->action([\App\Http\Controllers\Crm\PipelineController::class, 'index'])->with('status', $output);
        }
    }

    /**
     * Show the form for editing the specified resource — the pipeline's stages are
     * managed inline here (add/edit/delete one at a time via PipelineStageController).
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('crm_pipeline.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $pipeline = CrmPipeline::where('business_id', $business_id)->with('stages')->findOrFail($id);

        return view('crm.pipelines.edit')->with(compact('pipeline'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('crm_pipeline.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $pipeline = CrmPipeline::where('business_id', $business_id)->findOrFail($id);

            if ($request->boolean('is_default')) {
                CrmPipeline::where('business_id', $business_id)->where('id', '!=', $id)->update(['is_default' => 0]);
            }

            $pipeline->name = $request->input('name');
            $pipeline->is_default = $request->boolean('is_default');
            $pipeline->save();

            $output = ['success' => true, 'msg' => __('crm.pipeline_updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Crm\PipelineController::class, 'edit'], [$id])->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('crm_pipeline.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $pipeline = CrmPipeline::where('business_id', $business_id)->withCount('leads')->findOrFail($id);

            if ($pipeline->leads_count > 0) {
                return ['success' => false, 'msg' => __('crm.cannot_delete_pipeline_with_leads')];
            }

            $pipeline->delete();

            $output = ['success' => true, 'msg' => __('crm.pipeline_deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}
