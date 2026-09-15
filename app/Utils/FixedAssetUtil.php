<?php

namespace App\Utils;

use App\FixedAsset;
use App\FixedAssetCategory;
use App\FixedAssetDepreciationEntry;
use DB;

class FixedAssetUtil extends Util
{
    protected $journalUtil;

    public function __construct(JournalUtil $journalUtil)
    {
        $this->journalUtil = $journalUtil;
    }

    public function createCategory(array $input, $business_id)
    {
        return FixedAssetCategory::create([
            'business_id' => $business_id,
            'name' => $input['name'],
            'default_asset_account_id' => $input['default_asset_account_id'] ?? null,
            'default_depreciation_account_id' => $input['default_depreciation_account_id'] ?? null,
            'default_accumulated_depreciation_account_id' => $input['default_accumulated_depreciation_account_id'] ?? null,
        ]);
    }

    public function createAsset(array $input, $business_id, $user_id)
    {
        return FixedAsset::create([
            'business_id' => $business_id,
            'location_id' => $input['location_id'] ?? null,
            'fixed_asset_category_id' => $input['fixed_asset_category_id'] ?? null,
            'name' => $input['name'],
            'code' => $input['code'] ?? null,
            'purchase_date' => $input['purchase_date'],
            'purchase_cost' => $input['purchase_cost'],
            'salvage_value' => $input['salvage_value'] ?? 0,
            'useful_life_months' => $input['useful_life_months'],
            'depreciation_method' => 'straight_line',
            'asset_account_id' => $input['asset_account_id'] ?? null,
            'depreciation_account_id' => $input['depreciation_account_id'] ?? null,
            'accumulated_depreciation_account_id' => $input['accumulated_depreciation_account_id'] ?? null,
            'status' => 'active',
            'created_by' => $user_id,
        ]);
    }

    public function updateAsset($asset_id, array $input, $business_id)
    {
        $asset = FixedAsset::where('business_id', $business_id)->findOrFail($asset_id);
        $asset->fill([
            'location_id' => $input['location_id'] ?? null,
            'fixed_asset_category_id' => $input['fixed_asset_category_id'] ?? null,
            'name' => $input['name'],
            'code' => $input['code'] ?? null,
            'salvage_value' => $input['salvage_value'] ?? 0,
            'useful_life_months' => $input['useful_life_months'],
            'asset_account_id' => $input['asset_account_id'] ?? null,
            'depreciation_account_id' => $input['depreciation_account_id'] ?? null,
            'accumulated_depreciation_account_id' => $input['accumulated_depreciation_account_id'] ?? null,
        ]);
        $asset->save();

        return $asset;
    }

    public function disposeAsset($asset_id, $business_id, $disposed_date, $disposed_amount = 0)
    {
        $asset = FixedAsset::where('business_id', $business_id)->findOrFail($asset_id);

        if ($asset->status == 'disposed') {
            throw new \Exception(__('fixed_asset.already_disposed'));
        }

        $asset->status = 'disposed';
        $asset->disposed_date = $disposed_date;
        $asset->disposed_amount = $disposed_amount;
        $asset->save();

        return $asset;
    }

    /**
     * Runs straight-line depreciation for every active, not-fully-depreciated
     * asset as of $depreciation_date (defaults to today), posting a matching
     * journal entry directly via JournalUtil when the business has GL enabled
     * — this is a scheduled/batch operation, not triggered by an existing app
     * event, so it calls JournalUtil directly rather than through a listener.
     * Idempotent per (asset, date): running it twice for the same date is a
     * no-op the second time.
     */
    public function runMonthlyDepreciation($business_id, $depreciation_date = null, $user_id = null)
    {
        $depreciation_date = $depreciation_date ?? \Carbon::now()->toDateString();
        $gl_enabled = $this->journalUtil->isEnabled($business_id);

        $assets = FixedAsset::where('business_id', $business_id)->where('status', 'active')->with('category')->get();

        $results = ['posted' => 0, 'skipped' => 0];

        foreach ($assets as $asset) {
            $already_run = FixedAssetDepreciationEntry::where('fixed_asset_id', $asset->id)
                ->where('depreciation_date', $depreciation_date)
                ->exists();

            if ($already_run) {
                $results['skipped']++;

                continue;
            }

            $amount = $asset->monthlyDepreciationAmount();

            if ($amount <= 0) {
                $results['skipped']++;

                continue;
            }

            DB::beginTransaction();
            try {
                $journal_entry_id = null;

                if ($gl_enabled) {
                    $depreciation_account_id = $asset->depreciation_account_id_effective;
                    $accumulated_account_id = $asset->accumulated_depreciation_account_id_effective;

                    if (! empty($depreciation_account_id) && ! empty($accumulated_account_id)) {
                        $journal_entry = $this->journalUtil->postEntry($business_id, [
                            ['chart_of_account_id' => $depreciation_account_id, 'debit' => $amount, 'credit' => 0],
                            ['chart_of_account_id' => $accumulated_account_id, 'debit' => 0, 'credit' => $amount],
                        ], $depreciation_date, 'depreciation', $asset, __('fixed_asset.depreciation_narration', ['name' => $asset->name]), $user_id);

                        $journal_entry_id = $journal_entry->id ?? null;
                    } else {
                        \Log::emergency("FixedAssetUtil::runMonthlyDepreciation - asset {$asset->id} has no depreciation/accumulated-depreciation account configured");
                    }
                }

                FixedAssetDepreciationEntry::create([
                    'fixed_asset_id' => $asset->id,
                    'depreciation_date' => $depreciation_date,
                    'amount' => $amount,
                    'journal_entry_id' => $journal_entry_id,
                    'created_by' => $user_id,
                ]);

                $asset->accumulated_depreciation += $amount;
                $asset->save();

                DB::commit();
                $results['posted']++;
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
                $results['skipped']++;
            }
        }

        return $results;
    }
}
