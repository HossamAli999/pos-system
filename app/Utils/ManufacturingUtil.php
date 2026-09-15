<?php

namespace App\Utils;

use App\BillOfMaterial;
use App\BomItem;
use App\Events\WorkOrderCompleted;
use App\WorkOrder;
use App\WorkOrderMaterialConsumption;
use DB;

class ManufacturingUtil extends Util
{
    protected $productUtil;

    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }

    /**
     * Creates a bill of materials with its raw-material lines.
     */
    public function createBom(array $input, $business_id, $user_id)
    {
        DB::beginTransaction();
        try {
            $bom = BillOfMaterial::create([
                'business_id' => $business_id,
                'product_id' => $input['product_id'],
                'variation_id' => $input['variation_id'],
                'output_quantity' => $input['output_quantity'] ?? 1,
                'unit_id' => $input['unit_id'] ?? null,
                'version' => 1,
                'is_active' => 1,
                'created_by' => $user_id,
            ]);

            $this->saveBomItems($bom, $input['items'] ?? []);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $bom->fresh('items');
    }

    /**
     * Updates a BOM's header and replaces its raw-material lines wholesale.
     * Safe to do (unlike CRM pipeline stages) because work_orders reference
     * bill_of_material_id directly, never an individual bom_item row.
     */
    public function updateBom($bom_id, array $input, $business_id)
    {
        DB::beginTransaction();
        try {
            $bom = BillOfMaterial::where('business_id', $business_id)->findOrFail($bom_id);

            $bom->output_quantity = $input['output_quantity'] ?? $bom->output_quantity;
            $bom->unit_id = $input['unit_id'] ?? $bom->unit_id;
            $bom->is_active = ! empty($input['is_active']) ? 1 : 0;
            $bom->version += 1;
            $bom->save();

            $bom->items()->delete();
            $this->saveBomItems($bom, $input['items'] ?? []);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $bom->fresh('items');
    }

    protected function saveBomItems(BillOfMaterial $bom, array $items)
    {
        foreach ($items as $item) {
            if (empty($item['raw_material_product_id']) || empty($item['raw_material_variation_id'])) {
                continue;
            }

            BomItem::create([
                'bill_of_material_id' => $bom->id,
                'raw_material_product_id' => $item['raw_material_product_id'],
                'raw_material_variation_id' => $item['raw_material_variation_id'],
                'quantity_required' => $item['quantity_required'] ?? 0,
                'wastage_percent' => $item['wastage_percent'] ?? 0,
            ]);
        }
    }

    public function createWorkOrder(array $input, $business_id, $user_id)
    {
        $ref_count = $this->setAndGetReferenceCount('work_order', $business_id);
        $ref_no = $this->generateReferenceNumber('work_order', $ref_count, $business_id, 'WO');

        return WorkOrder::create([
            'business_id' => $business_id,
            'location_id' => $input['location_id'],
            'bill_of_material_id' => $input['bill_of_material_id'],
            'ref_no' => $ref_no,
            'planned_quantity' => $input['planned_quantity'],
            'planned_date' => $input['planned_date'] ?? null,
            'notes' => $input['notes'] ?? null,
            'status' => 'planned',
            'created_by' => $user_id,
        ]);
    }

    public function startWorkOrder($work_order_id, $business_id)
    {
        $work_order = WorkOrder::where('business_id', $business_id)->findOrFail($work_order_id);

        if ($work_order->status != 'planned') {
            throw new \Exception(__('manufacturing.work_order_not_planned'));
        }

        $work_order->status = 'in_progress';
        $work_order->started_at = \Carbon::now();
        $work_order->save();

        return $work_order;
    }

    /**
     * Consumes raw materials (via the existing ProductUtil::decreaseProductQuantity())
     * and adds the finished-goods stock (via ProductUtil::updateProductQuantity()) for
     * the given work order, scaled to $produced_qty relative to the BOM's output_quantity.
     * Both stock movements happen inside one DB transaction so a work order never ends
     * up half-consumed. Fires WorkOrderCompleted so a later phase's GL listener can post
     * a Finished Goods / Raw Materials entry without this method knowing GL exists.
     */
    public function completeProduction($work_order_id, $produced_qty, $user_id, $business_id)
    {
        $work_order = WorkOrder::where('business_id', $business_id)->with('bill_of_material.items')->findOrFail($work_order_id);

        if (! in_array($work_order->status, ['planned', 'in_progress'])) {
            throw new \Exception(__('manufacturing.work_order_not_active'));
        }

        if ($produced_qty <= 0) {
            throw new \Exception(__('manufacturing.produced_quantity_required'));
        }

        $bom = $work_order->bill_of_material;
        $batches = $produced_qty / max($bom->output_quantity, 0.0001);

        DB::beginTransaction();
        try {
            foreach ($bom->items as $item) {
                $required_qty = $item->requiredQuantityFor($batches);

                $this->productUtil->decreaseProductQuantity(
                    $item->raw_material_product_id,
                    $item->raw_material_variation_id,
                    $work_order->location_id,
                    $required_qty
                );

                WorkOrderMaterialConsumption::create([
                    'work_order_id' => $work_order->id,
                    'raw_material_product_id' => $item->raw_material_product_id,
                    'raw_material_variation_id' => $item->raw_material_variation_id,
                    'quantity_consumed' => $required_qty,
                ]);
            }

            $this->productUtil->updateProductQuantity(
                $work_order->location_id,
                $bom->product_id,
                $bom->variation_id,
                $produced_qty,
                0,
                null,
                false
            );

            $work_order->produced_quantity += $produced_qty;
            $work_order->status = 'completed';
            $work_order->completed_at = \Carbon::now();
            if (empty($work_order->started_at)) {
                $work_order->started_at = \Carbon::now();
            }
            $work_order->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        event(new WorkOrderCompleted($work_order->fresh(['material_consumptions', 'bill_of_material'])));

        return $work_order;
    }

    public function cancelWorkOrder($work_order_id, $business_id)
    {
        $work_order = WorkOrder::where('business_id', $business_id)->findOrFail($work_order_id);

        if ($work_order->status == 'completed') {
            throw new \Exception(__('manufacturing.cannot_cancel_completed_work_order'));
        }

        $work_order->status = 'cancelled';
        $work_order->save();

        return $work_order;
    }
}
