<?php

namespace App\Http\Controllers\Crm;

use App\BusinessLocation;
use App\CrmLead;
use App\CrmLeadSource;
use App\CrmPipeline;
use App\Http\Controllers\Controller;
use App\User;
use App\Utils\CrmUtil;
use App\Utils\ProductUtil;
use App\Utils\Util;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LeadController extends Controller
{
    protected $crmUtil;

    protected $commonUtil;

    protected $productUtil;

    public function __construct(CrmUtil $crmUtil, Util $commonUtil, ProductUtil $productUtil)
    {
        $this->crmUtil = $crmUtil;
        $this->commonUtil = $commonUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! (auth()->user()->can('crm_lead.view_all') || auth()->user()->can('crm_lead.view_own'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $leads = CrmLead::where('crm_leads.business_id', $business_id)
                ->leftjoin('crm_pipeline_stages as s', 'crm_leads.stage_id', '=', 's.id')
                ->leftjoin('users as u', 'crm_leads.assigned_to', '=', 'u.id')
                ->select([
                    'crm_leads.id', 'crm_leads.lead_number', 'crm_leads.name', 'crm_leads.company_name',
                    'crm_leads.expected_value', 'crm_leads.status', 's.name as stage_name',
                    DB::raw("CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as assigned_to_name"),
                ]);

            if (! auth()->user()->can('crm_lead.view_all')) {
                $leads->where('crm_leads.assigned_to', auth()->user()->id);
            }

            return DataTables::of($leads)
                ->editColumn('status', function ($row) {
                    $labels = ['open' => 'bg-yellow', 'won' => 'bg-green', 'lost' => 'bg-red'];

                    return '<span class="label '.($labels[$row->status] ?? 'bg-gray').'">'.__('crm.status_'.$row->status).'</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="'.action([\App\Http\Controllers\Crm\LeadController::class, 'show'], [$row->id]).'" class="btn btn-xs btn-info"><i class="fa fa-eye"></i> '.__('messages.view').'</a>';
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        return view('crm.leads.index');
    }

    /**
     * Kanban board of open leads grouped by pipeline stage.
     *
     * @return \Illuminate\Http\Response
     */
    public function board()
    {
        if (! (auth()->user()->can('crm_lead.view_all') || auth()->user()->can('crm_lead.view_own'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $pipelines = CrmPipeline::where('business_id', $business_id)->with('stages')->get();
        $pipeline_id = request()->input('pipeline_id') ?? ($pipelines->first()->id ?? null);
        $pipeline = $pipelines->firstWhere('id', $pipeline_id);

        $leads = collect();
        if (! empty($pipeline)) {
            $query = CrmLead::where('business_id', $business_id)->where('pipeline_id', $pipeline->id);

            if (! auth()->user()->can('crm_lead.view_all')) {
                $query->where('assigned_to', auth()->user()->id);
            }

            $leads = $query->get()->groupBy('stage_id');
        }

        return view('crm.leads.board')->with(compact('pipelines', 'pipeline', 'leads'));
    }

    /**
     * Moves a lead to a different stage (used by the Kanban board's drag & drop).
     */
    public function postMoveStage(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');

            $this->crmUtil->moveStage($request->input('lead_id'), $request->input('stage_id'), $business_id, auth()->user()->id);

            $output = ['success' => true, 'msg' => __('crm.stage_updated_success')];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('crm_lead.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        return view('crm.leads.create')->with($this->formData($business_id));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('crm_lead.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            $input = $request->only(['name', 'company_name', 'email', 'phone', 'source_id', 'assigned_to', 'pipeline_id', 'stage_id', 'expected_value', 'expected_close_date']);
            $input['products'] = $request->input('products', []);

            $lead = $this->crmUtil->createLead($input, $business_id, $user_id);

            $output = ['success' => true, 'msg' => __('crm.lead_added_success')];

            return redirect()->action([\App\Http\Controllers\Crm\LeadController::class, 'show'], [$lead->id])->with('status', $output);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => $e->getMessage() ?: __('messages.something_went_wrong')];

            return redirect()->action([\App\Http\Controllers\Crm\LeadController::class, 'create'])->with('status', $output);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! (auth()->user()->can('crm_lead.view_all') || auth()->user()->can('crm_lead.view_own'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $lead = CrmLead::where('business_id', $business_id)
            ->with(['contact', 'source', 'assigned_to_user', 'pipeline.stages', 'stage', 'activities', 'lead_products.product', 'lead_products.variation', 'stage_history.to_stage', 'stage_history.changed_by_user'])
            ->findOrFail($id);

        $business_locations = BusinessLocation::forDropdown($business_id);
        $users = User::forDropdown($business_id, true);

        return view('crm.leads.show')->with(compact('lead', 'business_locations', 'users'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('crm_lead.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $lead = CrmLead::where('business_id', $business_id)->findOrFail($id);

        return view('crm.leads.edit')->with(array_merge(['lead' => $lead], $this->formData($business_id)));
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
        if (! auth()->user()->can('crm_lead.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $input = $request->only(['name', 'company_name', 'email', 'phone', 'source_id', 'assigned_to', 'expected_value', 'expected_close_date']);
            $this->crmUtil->updateLead($id, $input, $business_id);

            $output = ['success' => true, 'msg' => __('crm.lead_updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Crm\LeadController::class, 'show'], [$id])->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('crm_lead.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            CrmLead::where('business_id', $business_id)->findOrFail($id)->delete();

            $output = ['success' => true, 'msg' => __('crm.lead_deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Converts the lead into a real customer contact.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function convertToCustomer(Request $request, $id)
    {
        if (! auth()->user()->can('crm_lead.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $this->crmUtil->convertLeadToCustomer($id, $business_id, auth()->user()->id);

            $output = ['success' => true, 'msg' => __('crm.converted_to_customer_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        if ($request->ajax()) {
            return $output;
        }

        return redirect()->action([\App\Http\Controllers\Crm\LeadController::class, 'show'], [$id])->with('status', $output);
    }

    /**
     * Converts the lead's product list into a quotation using the normal Sell pipeline.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function convertToQuotation(Request $request, $id)
    {
        if (! auth()->user()->can('crm_lead.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $quotation = $this->crmUtil->convertLeadToQuotation($id, $business_id, $request->input('location_id'), auth()->user()->id);

            $output = ['success' => true, 'msg' => __('crm.converted_to_quotation_success')];

            return redirect()->action([\App\Http\Controllers\SellController::class, 'edit'], [$quotation->id])->with('status', $output);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => $e->getMessage() ?: __('messages.something_went_wrong')];

            return redirect()->action([\App\Http\Controllers\Crm\LeadController::class, 'show'], [$id])->with('status', $output);
        }
    }

    protected function formData($business_id)
    {
        $pipelines = CrmPipeline::where('business_id', $business_id)->with('stages')->get();
        $sources = CrmLeadSource::forDropdown($business_id);
        $users = User::forDropdown($business_id, true);

        return compact('pipelines', 'sources', 'users');
    }
}
