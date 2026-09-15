<?php

namespace App\Http\Controllers\Manufacturing;

use App\BillOfMaterial;
use App\BusinessLocation;
use App\Http\Controllers\Controller;
use App\Utils\ManufacturingUtil;
use App\Utils\Util;
use App\WorkOrder;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class WorkOrderController extends Controller
{
    protected $manufacturingUtil;

    protected $commonUtil;

    public function __construct(ManufacturingUtil $manufacturingUtil, Util $commonUtil)
    {
        $this->manufacturingUtil = $manufacturingUtil;
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('work_order.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $work_orders = WorkOrder::where('work_orders.business_id', $business_id)
                ->join('bill_of_materials as bom', 'work_orders.bill_of_material_id', '=', 'bom.id')
                ->join('products as p', 'bom.product_id', '=', 'p.id')
                ->leftjoin('business_locations as bl', 'work_orders.location_id', '=', 'bl.id')
                ->select([
                    'work_orders.id', 'work_orders.ref_no', 'p.name as product_name',
                    'work_orders.planned_quantity', 'work_orders.produced_quantity',
                    'work_orders.status', 'bl.name as location_name',
                ]);

            return DataTables::of($work_orders)
                ->editColumn('status', function ($row) {
                    $labels = ['planned' => 'bg-yellow', 'in_progress' => 'bg-info', 'completed' => 'bg-green', 'cancelled' => 'bg-red'];

                    return '<span class="label '.($labels[$row->status] ?? 'bg-gray').'">'.__('manufacturing.status_'.$row->status).'</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="'.action([\App\Http\Controllers\Manufacturing\WorkOrderController::class, 'show'], [$row->id]).'" class="btn btn-xs btn-info"><i class="fa fa-eye"></i> '.__('messages.view').'</a>';
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        return view('manufacturing.work_orders.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('work_order.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $boms = BillOfMaterial::forDropdown($business_id);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $selected_bom_id = request()->input('bom_id');

        return view('manufacturing.work_orders.create')->with(compact('boms', 'business_locations', 'selected_bom_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('work_order.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            $input = [
                'location_id' => $request->input('location_id'),
                'bill_of_material_id' => $request->input('bill_of_material_id'),
                'planned_quantity' => $this->commonUtil->num_uf($request->input('planned_quantity')),
                'planned_date' => $request->input('planned_date'),
                'notes' => $request->input('notes'),
            ];

            $work_order = $this->manufacturingUtil->createWorkOrder($input, $business_id, $user_id);

            $output = ['success' => true, 'msg' => __('manufacturing.work_order_added_success')];

            return redirect()->action([\App\Http\Controllers\Manufacturing\WorkOrderController::class, 'show'], [$work_order->id])->with('status', $output);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];

            return redirect()->action([\App\Http\Controllers\Manufacturing\WorkOrderController::class, 'create'])->with('status', $output);
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
        if (! auth()->user()->can('work_order.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $work_order = WorkOrder::where('business_id', $business_id)
            ->with(['bill_of_material.product', 'bill_of_material.items.raw_material_product', 'location', 'material_consumptions.raw_material_product'])
            ->findOrFail($id);

        return view('manufacturing.work_orders.show')->with(compact('work_order'));
    }

    /**
     * Marks the work order as in progress.
     */
    public function start(Request $request, $id)
    {
        if (! auth()->user()->can('work_order.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $this->manufacturingUtil->startWorkOrder($id, $business_id);

            $output = ['success' => true, 'msg' => __('manufacturing.work_order_started_success')];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => $e->getMessage()];
        }

        return redirect()->action([\App\Http\Controllers\Manufacturing\WorkOrderController::class, 'show'], [$id])->with('status', $output);
    }

    /**
     * Completes production — consumes raw materials and adds finished-goods stock.
     */
    public function complete(Request $request, $id)
    {
        if (! auth()->user()->can('work_order.complete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            $produced_qty = $this->commonUtil->num_uf($request->input('produced_quantity'));

            $this->manufacturingUtil->completeProduction($id, $produced_qty, $user_id, $business_id);

            $output = ['success' => true, 'msg' => __('manufacturing.production_completed_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => $e->getMessage()];
        }

        return redirect()->action([\App\Http\Controllers\Manufacturing\WorkOrderController::class, 'show'], [$id])->with('status', $output);
    }

    /**
     * Cancels a work order that hasn't been completed yet.
     */
    public function cancel(Request $request, $id)
    {
        if (! auth()->user()->can('work_order.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $this->manufacturingUtil->cancelWorkOrder($id, $business_id);

            $output = ['success' => true, 'msg' => __('manufacturing.work_order_cancelled_success')];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => $e->getMessage()];
        }

        return redirect()->action([\App\Http\Controllers\Manufacturing\WorkOrderController::class, 'show'], [$id])->with('status', $output);
    }
}
