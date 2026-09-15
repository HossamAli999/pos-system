<?php

namespace App\Http\Controllers\Manufacturing;

use App\BillOfMaterial;
use App\Http\Controllers\Controller;
use App\Unit;
use App\Utils\ManufacturingUtil;
use App\Utils\Util;
use App\WorkOrder;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ManufacturingController extends Controller
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
        if (! auth()->user()->can('manufacturing.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $boms = BillOfMaterial::where('bill_of_materials.business_id', $business_id)
                ->join('products as p', 'bill_of_materials.product_id', '=', 'p.id')
                ->leftjoin('units as u', 'bill_of_materials.unit_id', '=', 'u.id')
                ->select([
                    'bill_of_materials.id', 'p.name as product_name', 'bill_of_materials.output_quantity',
                    'u.short_name as unit_name', 'bill_of_materials.version', 'bill_of_materials.is_active',
                ]);

            return DataTables::of($boms)
                ->editColumn('is_active', function ($row) {
                    return $row->is_active ? '<span class="label bg-green">'.__('lang_v1.yes').'</span>' : '<span class="label bg-red">'.__('lang_v1.no').'</span>';
                })
                ->addColumn('action', function ($row) {
                    $html = '<a href="'.action([\App\Http\Controllers\Manufacturing\ManufacturingController::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</a>';
                    $html .= ' <a href="'.action([\App\Http\Controllers\Manufacturing\WorkOrderController::class, 'create'], ['bom_id' => $row->id]).'" class="btn btn-xs btn-success"><i class="fa fa-industry"></i> '.__('manufacturing.create_work_order').'</a>';
                    $html .= ' <button data-href="'.action([\App\Http\Controllers\Manufacturing\ManufacturingController::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_bom_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';

                    return $html;
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'is_active'])
                ->make(true);
        }

        return view('manufacturing.boms.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('manufacturing.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $units = Unit::where('business_id', $business_id)->pluck('short_name', 'id');

        return view('manufacturing.boms.create')->with(compact('units'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('manufacturing.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            $input = [
                'product_id' => $request->input('product_id'),
                'variation_id' => $request->input('variation_id'),
                'output_quantity' => $this->commonUtil->num_uf($request->input('output_quantity', 1)),
                'unit_id' => $request->input('unit_id'),
                'items' => $this->formatItemsFromRequest($request->input('items', [])),
            ];

            $bom = $this->manufacturingUtil->createBom($input, $business_id, $user_id);

            $output = ['success' => true, 'msg' => __('manufacturing.bom_added_success')];

            return redirect()->action([\App\Http\Controllers\Manufacturing\ManufacturingController::class, 'index'])->with('status', $output);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];

            return redirect()->action([\App\Http\Controllers\Manufacturing\ManufacturingController::class, 'create'])->with('status', $output);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('manufacturing.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $bom = BillOfMaterial::where('business_id', $business_id)->with(['items.raw_material_product', 'items.raw_material_variation', 'product', 'variation'])->findOrFail($id);
        $units = Unit::where('business_id', $business_id)->pluck('short_name', 'id');

        return view('manufacturing.boms.edit')->with(compact('bom', 'units'));
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
        if (! auth()->user()->can('manufacturing.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $input = [
                'output_quantity' => $this->commonUtil->num_uf($request->input('output_quantity', 1)),
                'unit_id' => $request->input('unit_id'),
                'is_active' => $request->boolean('is_active'),
                'items' => $this->formatItemsFromRequest($request->input('items', [])),
            ];

            $this->manufacturingUtil->updateBom($id, $input, $business_id);

            $output = ['success' => true, 'msg' => __('manufacturing.bom_updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Manufacturing\ManufacturingController::class, 'edit'], [$id])->with('status', $output);
    }

    /**
     * Remove the specified resource from storage — refused while any work order
     * (planned or otherwise) still references it.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('manufacturing.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $bom = BillOfMaterial::where('business_id', $business_id)->findOrFail($id);

            if (WorkOrder::where('bill_of_material_id', $bom->id)->exists()) {
                return ['success' => false, 'msg' => __('manufacturing.cannot_delete_bom_in_use')];
            }

            $bom->delete();

            $output = ['success' => true, 'msg' => __('manufacturing.bom_deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    protected function formatItemsFromRequest(array $items)
    {
        $formatted = [];
        foreach ($items as $item) {
            if (empty($item['raw_material_variation_id'])) {
                continue;
            }

            $formatted[] = [
                'raw_material_product_id' => $item['raw_material_product_id'] ?? null,
                'raw_material_variation_id' => $item['raw_material_variation_id'],
                'quantity_required' => $this->commonUtil->num_uf($item['quantity_required'] ?? 0),
                'wastage_percent' => $this->commonUtil->num_uf($item['wastage_percent'] ?? 0),
            ];
        }

        return $formatted;
    }
}
