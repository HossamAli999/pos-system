<?php

namespace App\Utils;

use App\CashRegister;
use App\Product;
use App\Transaction;
use App\VanSalesTrip;
use App\VanSalesTripReturnLine;
use App\VanSalesVehicle;
use App\VariationLocationDetails;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VanSalesUtil extends Util
{
    /**
     * @var ProductUtil
     */
    protected $productUtil;

    /**
     * @var TransactionUtil
     */
    protected $transactionUtil;

    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Whether a given user already has an open cash register (a rep can only
     * be on one trip / have one register open at a time, matching the rest
     * of the app's assumption in CashRegisterUtil::countOpenedRegister()).
     */
    public function repHasOpenRegister($user_id)
    {
        return CashRegister::where('user_id', $user_id)
                    ->where('status', 'open')
                    ->exists();
    }

    /**
     * Create the two linked transfer transactions (sell_transfer at $from,
     * purchase_transfer at $to) that move stock between two locations —
     * mirrors StockTransferController::store(), kept self-contained here
     * rather than calling that controller, so this module can't regress it.
     *
     * @param  array  $lines  [['product_id', 'variation_id', 'quantity', 'unit_price'], ...]
     * @return \App\Transaction the sell_transfer row (id is what callers store)
     */
    protected function createStockTransfer($business_id, $user_id, $from_location_id, $to_location_id, $lines, $ref_type = 'stock_transfer')
    {
        $ref_count = $this->setAndGetReferenceCount($ref_type, $business_id);

        $input_data = [
            'business_id' => $business_id,
            'location_id' => $from_location_id,
            'type' => 'sell_transfer',
            'status' => 'final',
            'payment_status' => 'paid',
            'created_by' => $user_id,
            'transaction_date' => Carbon::now(),
            'ref_no' => $this->generateReferenceNumber($ref_type, $ref_count, $business_id),
            'final_total' => 0,
            'total_before_tax' => 0,
        ];

        $sell_lines = [];
        $purchase_lines = [];
        foreach ($lines as $line) {
            $qty = $this->num_uf($line['quantity']);
            if ($qty <= 0) {
                continue;
            }
            $unit_price = $this->num_uf($line['unit_price'] ?? 0);

            $sell_lines[] = [
                'product_id' => $line['product_id'],
                'variation_id' => $line['variation_id'],
                'quantity' => $qty,
                'unit_price' => $unit_price,
                'unit_price_inc_tax' => $unit_price,
                'item_tax' => 0,
                'tax_id' => null,
            ];
            $purchase_lines[] = [
                'product_id' => $line['product_id'],
                'variation_id' => $line['variation_id'],
                'quantity' => $qty,
                'purchase_price' => $unit_price,
                'purchase_price_inc_tax' => $unit_price,
            ];
        }

        $sell_transfer = Transaction::create($input_data);

        $input_data['type'] = 'purchase_transfer';
        $input_data['location_id'] = $to_location_id;
        $input_data['transfer_parent_id'] = $sell_transfer->id;
        $input_data['status'] = 'received';
        $purchase_transfer = Transaction::create($input_data);

        if (! empty($sell_lines)) {
            $this->transactionUtil->createOrUpdateSellLines($sell_transfer, $sell_lines, $from_location_id, false, null, [], false);
        }
        if (! empty($purchase_lines)) {
            $purchase_transfer->purchase_lines()->createMany($purchase_lines);
        }

        foreach ($sell_lines as $line) {
            $product = Product::find($line['product_id']);
            if (empty($product) || ! $product->enable_stock) {
                continue;
            }

            $this->productUtil->decreaseProductQuantity(
                $line['product_id'],
                $line['variation_id'],
                $from_location_id,
                $line['quantity']
            );

            $this->productUtil->updateProductQuantity(
                $to_location_id,
                $line['product_id'],
                $line['variation_id'],
                $line['quantity'],
                0,
                null,
                false
            );

            // Stock physically moved to $to_location_id — make sure the
            // product is actually assignable/sellable there too (POS only
            // lists products assigned to the current location), without
            // touching any of its other existing location assignments.
            $product->product_locations()->syncWithoutDetaching([$to_location_id]);
        }

        $this->productUtil->adjustStockOverSelling($purchase_transfer);

        $business = ['id' => $business_id, 'accounting_method' => null, 'location_id' => $from_location_id];
        $this->transactionUtil->mapPurchaseSell($business, $sell_transfer->sell_lines, 'purchase');

        $this->activityLog($sell_transfer, 'added', null, [], false, $business_id);

        return $sell_transfer->fresh();
    }

    /**
     * Start a van sales trip: loads stock onto the vehicle (a real stock
     * transfer, source location -> vehicle location) and opens a cash
     * register for the rep at the vehicle location. Reps then sell normally
     * through the existing POS screen, which already scopes itself to
     * whichever cash register is open for the logged-in user.
     *
     * @param  array  $data  ['van_sales_vehicle_id','rep_id','source_location_id','opening_cash','products' => [...]]
     */
    public function startTrip($business_id, $created_by_user_id, array $data)
    {
        if ($this->repHasOpenRegister($data['rep_id'])) {
            throw new \Exception(__('van_sales.rep_has_open_register'));
        }

        return DB::transaction(function () use ($business_id, $created_by_user_id, $data) {
            $vehicle = VanSalesVehicle::where('business_id', $business_id)->findOrFail($data['van_sales_vehicle_id']);

            $transfer_out = $this->createStockTransfer(
                $business_id,
                $created_by_user_id,
                $data['source_location_id'],
                $vehicle->location_id,
                $data['products'],
                'van_sales_loadout'
            );

            $register = CashRegister::create([
                'business_id' => $business_id,
                'user_id' => $data['rep_id'],
                'location_id' => $vehicle->location_id,
                'status' => 'open',
                'created_at' => Carbon::now()->format('Y-m-d H:i:00'),
            ]);

            $opening_cash = $this->num_uf($data['opening_cash'] ?? 0);
            if ($opening_cash > 0) {
                $register->cash_register_transactions()->create([
                    'amount' => $opening_cash,
                    'pay_method' => 'cash',
                    'type' => 'credit',
                    'transaction_type' => 'initial',
                ]);
            }

            return VanSalesTrip::create([
                'business_id' => $business_id,
                'van_sales_vehicle_id' => $vehicle->id,
                'rep_id' => $data['rep_id'],
                'source_location_id' => $data['source_location_id'],
                'cash_register_id' => $register->id,
                'transfer_out_id' => $transfer_out->id,
                'status' => 'out',
                'opening_cash' => $opening_cash,
                'started_at' => Carbon::now(),
            ]);
        });
    }

    /**
     * Per-product reconciliation view: qty loaded, qty still sitting at the
     * vehicle location, and qty actually sold. Qty sold is summed directly
     * from this trip's tagged sales rather than inferred as loaded-minus-
     * remaining — that inference only holds while the trip is still 'out';
     * once closed, the return transfer zeroes out "remaining" for both
     * returned AND sold stock alike, which would make every unit look sold.
     */
    public function getTripReconciliation(VanSalesTrip $trip)
    {
        $loaded_lines = $trip->transfer_out
            ? $trip->transfer_out->sell_lines()->with(['product', 'variations'])->get()
            : collect();

        $vehicle_location_id = $trip->vehicle->location_id;

        $sold_by_variation = \App\TransactionSellLine::whereIn('transaction_id', $trip->sales()->where('status', 'final')->pluck('id'))
            ->selectRaw('product_id, variation_id, SUM(quantity) as qty')
            ->groupBy('product_id', 'variation_id')
            ->pluck('qty', 'variation_id');

        return $loaded_lines->map(function ($line) use ($vehicle_location_id, $sold_by_variation) {
            $qty_loaded = (float) $line->quantity;

            $remaining = VariationLocationDetails::where('product_id', $line->product_id)
                ->where('variation_id', $line->variation_id)
                ->where('location_id', $vehicle_location_id)
                ->value('qty_available');
            $qty_remaining = (float) ($remaining ?? 0);

            return [
                'product_id' => $line->product_id,
                'variation_id' => $line->variation_id,
                'product_name' => optional($line->product)->name,
                'qty_loaded' => $qty_loaded,
                'qty_remaining' => $qty_remaining,
                'qty_sold' => (float) ($sold_by_variation[$line->variation_id] ?? 0),
            ];
        });
    }

    /**
     * Rep submits their counted cash and counted returned quantities for
     * manager review. Nothing in the real ledgers changes yet — that only
     * happens on approval.
     *
     * @param  array  $return_lines  [['product_id','variation_id','qty_returned'], ...]
     */
    public function submitForApproval(VanSalesTrip $trip, $counted_cash, array $return_lines, $rep_note = null)
    {
        if ($trip->status !== 'out') {
            throw new \Exception(__('van_sales.trip_not_open'));
        }

        return DB::transaction(function () use ($trip, $counted_cash, $return_lines, $rep_note) {
            $trip->return_lines()->delete();

            foreach ($return_lines as $line) {
                $qty = $this->num_uf($line['qty_returned'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                VanSalesTripReturnLine::create([
                    'van_sales_trip_id' => $trip->id,
                    'product_id' => $line['product_id'],
                    'variation_id' => $line['variation_id'],
                    'qty_returned' => $qty,
                ]);
            }

            $trip->proposed_closing_cash = $this->num_uf($counted_cash);
            $trip->rep_note = $rep_note;
            $trip->submitted_for_approval_at = Carbon::now();
            $trip->status = 'pending_approval';
            $trip->save();

            return $trip->fresh();
        });
    }

    /**
     * Manager approves the closing request: creates the real return stock
     * transfer (vehicle -> source location) for the submitted quantities,
     * auto-adjusts any variance between submitted and live vehicle-location
     * stock as a stock adjustment (shortage/damage, separately auditable),
     * and closes the cash register with the submitted cash amount.
     */
    public function approveTrip(VanSalesTrip $trip, $approver_id, $note = null)
    {
        if ($trip->status !== 'pending_approval') {
            throw new \Exception(__('van_sales.trip_not_pending'));
        }

        return DB::transaction(function () use ($trip, $approver_id, $note) {
            $vehicle_location_id = $trip->vehicle->location_id;
            $submitted = $trip->return_lines;

            $return_transfer_lines = [];
            foreach ($submitted as $line) {
                $unit_price = optional(
                    $trip->transfer_out->sell_lines()
                        ->where('product_id', $line->product_id)
                        ->where('variation_id', $line->variation_id)
                        ->first()
                )->unit_price ?? 0;

                $return_transfer_lines[] = [
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'quantity' => $line->qty_returned,
                    'unit_price' => $unit_price,
                ];
            }

            $transfer_in = null;
            if (! empty($return_transfer_lines)) {
                $transfer_in = $this->createStockTransfer(
                    $trip->business_id,
                    $approver_id,
                    $vehicle_location_id,
                    $trip->source_location_id,
                    $return_transfer_lines,
                    'van_sales_return'
                );
            }

            // Whatever is still sitting at the vehicle location after the
            // return transfer is an unexplained variance (shortage/damage/
            // miscount) — zero it out with an auditable stock adjustment
            // rather than letting it linger on the vehicle's ledger.
            $adjustment_lines = [];
            foreach ($submitted as $line) {
                $remaining = (float) (VariationLocationDetails::where('product_id', $line->product_id)
                    ->where('variation_id', $line->variation_id)
                    ->where('location_id', $vehicle_location_id)
                    ->value('qty_available') ?? 0);

                if ($remaining > 0.0001) {
                    $adjustment_lines[] = [
                        'product_id' => $line->product_id,
                        'variation_id' => $line->variation_id,
                        'quantity' => $remaining,
                    ];
                }
            }

            if (! empty($adjustment_lines)) {
                $this->createShortageAdjustment($trip, $vehicle_location_id, $approver_id, $adjustment_lines);
            }

            CashRegister::where('id', $trip->cash_register_id)->update([
                'status' => 'close',
                'closed_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'closing_amount' => $trip->proposed_closing_cash,
                'closing_note' => __('van_sales.closed_via_trip', ['id' => $trip->id]),
            ]);

            $trip->transfer_in_id = optional($transfer_in)->id;
            $trip->closing_cash = $trip->proposed_closing_cash;
            $trip->approved_by = $approver_id;
            $trip->approved_at = Carbon::now();
            $trip->manager_note = $note;
            $trip->status = 'closed';
            $trip->closed_at = Carbon::now();
            $trip->save();

            return $trip->fresh();
        });
    }

    /**
     * Record a shortage/damage stock adjustment for whatever's left at the
     * vehicle location once the counted return has been transferred back —
     * mirrors StockAdjustmentController::store()'s core pattern.
     */
    protected function createShortageAdjustment(VanSalesTrip $trip, $location_id, $user_id, array $lines)
    {
        $ref_count = $this->setAndGetReferenceCount('stock_adjustment', $trip->business_id);

        $adjustment = Transaction::create([
            'business_id' => $trip->business_id,
            'location_id' => $location_id,
            'type' => 'stock_adjustment',
            'status' => 'final',
            'created_by' => $user_id,
            'transaction_date' => Carbon::now(),
            'ref_no' => $this->generateReferenceNumber('stock_adjustment', $ref_count, $trip->business_id),
            'additional_notes' => __('van_sales.shortage_note', ['id' => $trip->id]),
            'final_total' => 0,
            'total_amount_recovered' => 0,
        ]);

        $adjustment_data = [];
        foreach ($lines as $line) {
            $adjustment_data[] = [
                'product_id' => $line['product_id'],
                'variation_id' => $line['variation_id'],
                'quantity' => $line['quantity'],
                'unit_price' => 0,
            ];

            $this->productUtil->decreaseProductQuantity(
                $line['product_id'],
                $line['variation_id'],
                $location_id,
                $line['quantity']
            );
        }
        $adjustment->stock_adjustment_lines()->createMany($adjustment_data);

        $this->activityLog($adjustment, 'added', null, [], false, $trip->business_id);

        return $adjustment;
    }

    /**
     * Manager rejects the closing request — trip goes back to 'out' so the
     * rep can recount and resubmit.
     */
    public function rejectTrip(VanSalesTrip $trip, $approver_id, $note)
    {
        if ($trip->status !== 'pending_approval') {
            throw new \Exception(__('van_sales.trip_not_pending'));
        }

        $trip->status = 'out';
        $trip->manager_note = $note;
        $trip->approved_by = $approver_id;
        $trip->save();

        return $trip->fresh();
    }
}
