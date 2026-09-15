<?php

namespace App\Http\Controllers\Accounting;

use App\Business;
use App\ChartOfAccount;
use App\ChartOfAccountMapping;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChartOfAccountMappingController extends Controller
{
    /**
     * The full set of mapping keys App\Utils\JournalUtil's posting methods rely on.
     * Kept here (UI-facing) rather than in JournalUtil, which only ever reads
     * whichever single key each posting method needs.
     */
    public static function mappingKeys()
    {
        return [
            'sales_income', 'accounts_receivable', 'sales_tax_payable',
            'cogs_expense', 'inventory_asset', 'accounts_payable',
            'expense_general', 'inventory_shrinkage_expense', 'payroll_expense',
        ];
    }

    /**
     * The "Setup accounting" screen — account mappings + the enable/disable toggle.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('gl.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $accounts = ChartOfAccount::forDropdown($business_id);

        $existing_mappings = ChartOfAccountMapping::where('business_id', $business_id)
            ->pluck('chart_of_account_id', 'mapping_key');

        $business = Business::find($business_id);
        $is_enabled = ! empty($business->accounting_settings['enable_double_entry_accounting']);

        $mapping_keys = self::mappingKeys();

        return view('accounting.mappings.index')->with(compact('accounts', 'existing_mappings', 'is_enabled', 'mapping_keys'));
    }

    /**
     * Saves every mapping row plus the enable/disable toggle in one submit.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (! auth()->user()->can('gl.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            \DB::beginTransaction();

            foreach (self::mappingKeys() as $key) {
                $account_id = $request->input('mapping_'.$key);

                if (empty($account_id)) {
                    ChartOfAccountMapping::where('business_id', $business_id)->where('mapping_key', $key)->delete();

                    continue;
                }

                ChartOfAccountMapping::updateOrCreate(
                    ['business_id' => $business_id, 'mapping_key' => $key],
                    ['chart_of_account_id' => $account_id]
                );
            }

            $business = Business::find($business_id);
            $settings = $business->accounting_settings ?? [];
            $settings['enable_double_entry_accounting'] = $request->boolean('enable_double_entry_accounting');
            $business->accounting_settings = $settings;
            $business->save();

            \DB::commit();

            $output = ['success' => true, 'msg' => __('gl.settings_saved_success')];
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Accounting\ChartOfAccountMappingController::class, 'index'])->with('status', $output);
    }
}
