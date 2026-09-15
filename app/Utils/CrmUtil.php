<?php

namespace App\Utils;

use App\Contact;
use App\CrmActivity;
use App\CrmLead;
use App\CrmLeadProduct;
use App\CrmLeadStageHistory;
use App\CrmPipeline;
use App\CrmPipelineStage;
use DB;

class CrmUtil extends Util
{
    protected $contactUtil;

    protected $productUtil;

    protected $transactionUtil;

    public function __construct(ContactUtil $contactUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    public function createLead(array $input, $business_id, $user_id)
    {
        $pipeline_id = $input['pipeline_id'] ?? null;
        if (empty($pipeline_id)) {
            $pipeline = CrmPipeline::defaultFor($business_id);
            $pipeline_id = $pipeline->id ?? null;
        }

        $stage_id = $input['stage_id'] ?? null;
        if (empty($stage_id) && ! empty($pipeline_id)) {
            $stage_id = CrmPipelineStage::where('crm_pipeline_id', $pipeline_id)->orderBy('stage_order', 'asc')->value('id');
        }

        if (empty($pipeline_id) || empty($stage_id)) {
            throw new \Exception(__('crm.no_pipeline_configured'));
        }

        $ref_count = $this->setAndGetReferenceCount('crm_lead', $business_id);
        $lead_number = $this->generateReferenceNumber('crm_lead', $ref_count, $business_id, 'LEAD');

        $lead = CrmLead::create([
            'business_id' => $business_id,
            'lead_number' => $lead_number,
            'name' => $input['name'],
            'company_name' => $input['company_name'] ?? null,
            'email' => $input['email'] ?? null,
            'phone' => $input['phone'] ?? null,
            'source_id' => $input['source_id'] ?? null,
            'assigned_to' => $input['assigned_to'] ?? null,
            'pipeline_id' => $pipeline_id,
            'stage_id' => $stage_id,
            'expected_value' => ! empty($input['expected_value']) ? $this->num_uf($input['expected_value']) : null,
            'expected_close_date' => $input['expected_close_date'] ?? null,
            'status' => 'open',
            'created_by' => $user_id,
        ]);

        if (! empty($input['products'])) {
            foreach ($input['products'] as $p) {
                if (empty($p['product_id']) || empty($p['variation_id'])) {
                    continue;
                }

                CrmLeadProduct::create([
                    'crm_lead_id' => $lead->id,
                    'product_id' => $p['product_id'],
                    'variation_id' => $p['variation_id'],
                    'quantity' => ! empty($p['quantity']) ? $this->num_uf($p['quantity']) : 1,
                    'unit_price' => ! empty($p['unit_price']) ? $this->num_uf($p['unit_price']) : 0,
                ]);
            }
        }

        return $lead->fresh('lead_products');
    }

    public function updateLead($lead_id, array $input, $business_id)
    {
        $lead = CrmLead::where('business_id', $business_id)->findOrFail($lead_id);
        $lead->fill([
            'name' => $input['name'],
            'company_name' => $input['company_name'] ?? null,
            'email' => $input['email'] ?? null,
            'phone' => $input['phone'] ?? null,
            'source_id' => $input['source_id'] ?? null,
            'assigned_to' => $input['assigned_to'] ?? null,
            'expected_value' => ! empty($input['expected_value']) ? $this->num_uf($input['expected_value']) : null,
            'expected_close_date' => $input['expected_close_date'] ?? null,
        ]);
        $lead->save();

        return $lead;
    }

    /**
     * Moves a lead to a different pipeline stage, recording the transition in
     * crm_lead_stage_history and updating the lead's open/won/lost status based
     * on the destination stage's is_won/is_lost flags.
     */
    public function moveStage($lead_id, $new_stage_id, $business_id, $user_id)
    {
        $lead = CrmLead::where('business_id', $business_id)->findOrFail($lead_id);
        $new_stage = CrmPipelineStage::findOrFail($new_stage_id);

        DB::beginTransaction();
        try {
            CrmLeadStageHistory::create([
                'crm_lead_id' => $lead->id,
                'from_stage_id' => $lead->stage_id,
                'to_stage_id' => $new_stage_id,
                'changed_by' => $user_id,
                'changed_at' => \Carbon::now(),
            ]);

            $lead->stage_id = $new_stage_id;

            if ($new_stage->is_won) {
                $lead->status = 'won';
            } elseif ($new_stage->is_lost) {
                $lead->status = 'lost';
            } else {
                $lead->status = 'open';
            }

            $lead->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $lead->fresh();
    }

    public function logActivity(array $input, $business_id, $user_id)
    {
        return CrmActivity::create([
            'business_id' => $business_id,
            'crm_lead_id' => $input['crm_lead_id'] ?? null,
            'contact_id' => $input['contact_id'] ?? null,
            'type' => $input['type'],
            'subject' => $input['subject'],
            'description' => $input['description'] ?? null,
            'due_date' => $input['due_date'] ?? null,
            'assigned_to' => $input['assigned_to'] ?? $user_id,
            'created_by' => $user_id,
        ]);
    }

    public function completeActivity($activity_id, $business_id)
    {
        $activity = CrmActivity::where('business_id', $business_id)->findOrFail($activity_id);
        $activity->is_done = 1;
        $activity->done_at = \Carbon::now();
        $activity->save();

        return $activity;
    }

    /**
     * Not-yet-done activities due within $days_ahead days (including overdue ones) —
     * meant for a "follow-ups" widget. Reminders/notifications for these are left to
     * a later phase; this only surfaces the list.
     */
    public function getUpcomingFollowUps($business_id, $user_id = null, $days_ahead = 7)
    {
        $query = CrmActivity::where('business_id', $business_id)
            ->where('is_done', 0)
            ->whereNotNull('due_date')
            ->where('due_date', '<=', \Carbon::now()->addDays($days_ahead));

        if (! empty($user_id)) {
            $query->where('assigned_to', $user_id);
        }

        return $query->orderBy('due_date', 'asc')->with(['lead', 'contact'])->get();
    }

    /**
     * Converts a lead into a real Contact via the existing ContactUtil::createNewContact(),
     * reusing its reference-number generation and defaults rather than duplicating them.
     * Idempotent — returns the already-converted contact if called again.
     */
    public function convertLeadToCustomer($lead_id, $business_id, $user_id)
    {
        $lead = CrmLead::where('business_id', $business_id)->findOrFail($lead_id);

        if (! empty($lead->converted_contact_id)) {
            return Contact::find($lead->converted_contact_id);
        }

        $result = $this->contactUtil->createNewContact([
            'business_id' => $business_id,
            'type' => 'customer',
            'name' => ! empty($lead->company_name) ? $lead->company_name : $lead->name,
            'supplier_business_name' => $lead->company_name,
            'mobile' => ! empty($lead->phone) ? $lead->phone : '',
            'email' => $lead->email,
            'created_by' => $user_id,
        ]);

        $contact = $result['data'];

        $lead->converted_contact_id = $contact->id;
        $lead->contact_id = $contact->id;
        $lead->converted_at = \Carbon::now();
        $lead->save();

        return $contact;
    }

    /**
     * Converts a lead's product list into a quotation, reusing the exact same
     * TransactionUtil methods the normal "Add Quotation" screen calls
     * (createSellTransaction + createOrUpdateSellLines) rather than
     * reimplementing tax/invoice-numbering logic. Converts the lead to a
     * customer first if it hasn't been already.
     *
     * @return \App\Transaction the created quotation
     */
    public function convertLeadToQuotation($lead_id, $business_id, $location_id, $user_id)
    {
        $lead = CrmLead::where('business_id', $business_id)->with('lead_products')->findOrFail($lead_id);

        if (empty($lead->contact_id)) {
            $this->convertLeadToCustomer($lead_id, $business_id, $user_id);
            $lead->refresh();
        }

        if ($lead->lead_products->isEmpty()) {
            throw new \Exception(__('crm.no_products_to_convert'));
        }

        $products = [];
        foreach ($lead->lead_products as $lp) {
            $products[] = [
                'product_id' => $lp->product_id,
                'variation_id' => $lp->variation_id,
                'quantity' => (float) $lp->quantity,
                'unit_price' => (float) $lp->unit_price,
                'unit_price_inc_tax' => (float) $lp->unit_price,
                'item_tax' => 0,
                'tax_id' => null,
            ];
        }

        $invoice_total = $this->productUtil->calculateInvoiceTotal($products, null, null, false);

        $input = [
            'location_id' => $location_id,
            'status' => 'quotation',
            'is_quotation' => 1,
            'contact_id' => $lead->contact_id,
            'transaction_date' => \Carbon::now()->toDateTimeString(),
            'final_total' => $invoice_total['final_total'],
            'discount_amount' => 0,
        ];

        DB::beginTransaction();
        try {
            $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id, false);
            $this->transactionUtil->createOrUpdateSellLines($transaction, $products, $location_id, false, null, [], false);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $transaction;
    }
}
