<?php

namespace App\Http\Controllers\Accounting;

use App\Account;
use App\ChartOfAccount;
use App\Http\Controllers\Controller;
use App\JournalLine;
use App\Utils\Util;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ChartOfAccountController extends Controller
{
    protected $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('gl.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $accounts = ChartOfAccount::where('chart_of_accounts.business_id', $business_id)
                ->leftjoin('chart_of_accounts as parent', 'chart_of_accounts.parent_id', '=', 'parent.id')
                ->select([
                    'chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name',
                    'chart_of_accounts.account_category', 'chart_of_accounts.is_active',
                    'parent.name as parent_name',
                ]);

            return DataTables::of($accounts)
                ->editColumn('account_category', function ($row) {
                    return __('gl.category_'.$row->account_category);
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active ? '<span class="label bg-green">'.__('lang_v1.yes').'</span>' : '<span class="label bg-red">'.__('lang_v1.no').'</span>';
                })
                ->addColumn('action', function ($row) {
                    $html = '<a href="'.action([\App\Http\Controllers\Accounting\ChartOfAccountController::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</a>';
                    $html .= ' <button data-href="'.action([\App\Http\Controllers\Accounting\ChartOfAccountController::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_chart_of_account_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';

                    return $html;
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'is_active'])
                ->make(true);
        }

        return view('accounting.chart_of_accounts.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('gl.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        return view('accounting.chart_of_accounts.create')->with($this->formData($business_id));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('gl.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $input = $request->only(['code', 'name', 'account_category', 'account_subcategory', 'parent_id', 'linked_account_id', 'opening_balance_date']);
            $input['business_id'] = $business_id;
            $input['is_active'] = $request->boolean('is_active', true);
            $input['opening_balance'] = ! empty($request->input('opening_balance')) ? $this->commonUtil->num_uf($request->input('opening_balance')) : 0;

            ChartOfAccount::create($input);

            $output = ['success' => true, 'msg' => __('gl.account_added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Accounting\ChartOfAccountController::class, 'index'])->with('status', $output);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('gl.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $account = ChartOfAccount::where('business_id', $business_id)->findOrFail($id);

        return view('accounting.chart_of_accounts.edit')->with(array_merge(['account' => $account], $this->formData($business_id, $id)));
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
        if (! auth()->user()->can('gl.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $account = ChartOfAccount::where('business_id', $business_id)->findOrFail($id);

            $account->fill($request->only(['code', 'name', 'account_category', 'account_subcategory', 'parent_id', 'linked_account_id', 'opening_balance_date']));
            $account->is_active = $request->boolean('is_active', true);
            $account->opening_balance = ! empty($request->input('opening_balance')) ? $this->commonUtil->num_uf($request->input('opening_balance')) : 0;
            $account->save();

            $output = ['success' => true, 'msg' => __('gl.account_updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Accounting\ChartOfAccountController::class, 'index'])->with('status', $output);
    }

    /**
     * Remove the specified resource from storage — refused if any journal line
     * already posted against it, to keep the ledger's history intact.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('gl.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $account = ChartOfAccount::where('business_id', $business_id)->findOrFail($id);

            if (JournalLine::where('chart_of_account_id', $account->id)->exists()) {
                return ['success' => false, 'msg' => __('gl.cannot_delete_account_in_use')];
            }

            $account->delete();

            $output = ['success' => true, 'msg' => __('gl.account_deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Seeds a standard starter chart of accounts. Skips any code that already
     * exists for this business rather than erroring, so it's safe to run more
     * than once (e.g. after later adding a custom account with a colliding code).
     */
    public function importDefaultChart(Request $request)
    {
        if (! auth()->user()->can('gl.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $existing_codes = ChartOfAccount::where('business_id', $business_id)->pluck('code')->filter()->toArray();

            $default_accounts = [
                ['code' => '1000', 'name' => 'Cash', 'account_category' => 'asset'],
                ['code' => '1010', 'name' => 'Bank', 'account_category' => 'asset'],
                ['code' => '1100', 'name' => 'Accounts Receivable', 'account_category' => 'asset', 'mapping_key' => 'accounts_receivable'],
                ['code' => '1200', 'name' => 'Inventory', 'account_category' => 'asset', 'mapping_key' => 'inventory_asset'],
                ['code' => '2000', 'name' => 'Accounts Payable', 'account_category' => 'liability', 'mapping_key' => 'accounts_payable'],
                ['code' => '2100', 'name' => 'Sales Tax Payable', 'account_category' => 'liability', 'mapping_key' => 'sales_tax_payable'],
                ['code' => '2200', 'name' => 'Payroll Payable', 'account_category' => 'liability'],
                ['code' => '3000', 'name' => "Owner's Equity", 'account_category' => 'equity'],
                ['code' => '3100', 'name' => 'Retained Earnings', 'account_category' => 'equity'],
                ['code' => '4000', 'name' => 'Sales Income', 'account_category' => 'income', 'mapping_key' => 'sales_income'],
                ['code' => '5000', 'name' => 'Cost of Goods Sold', 'account_category' => 'expense', 'mapping_key' => 'cogs_expense'],
                ['code' => '5100', 'name' => 'General Expenses', 'account_category' => 'expense', 'mapping_key' => 'expense_general'],
                ['code' => '5200', 'name' => 'Payroll Expense', 'account_category' => 'expense', 'mapping_key' => 'payroll_expense'],
                ['code' => '5300', 'name' => 'Inventory Shrinkage', 'account_category' => 'expense', 'mapping_key' => 'inventory_shrinkage_expense'],
            ];

            \DB::beginTransaction();

            foreach ($default_accounts as $default_account) {
                if (in_array($default_account['code'], $existing_codes)) {
                    continue;
                }

                $mapping_key = $default_account['mapping_key'] ?? null;
                unset($default_account['mapping_key']);

                $default_account['business_id'] = $business_id;
                $default_account['is_active'] = 1;
                $default_account['is_system'] = 1;

                $account = ChartOfAccount::create($default_account);

                if (! empty($mapping_key)) {
                    \App\ChartOfAccountMapping::updateOrCreate(
                        ['business_id' => $business_id, 'mapping_key' => $mapping_key],
                        ['chart_of_account_id' => $account->id]
                    );
                }
            }

            \DB::commit();

            $output = ['success' => true, 'msg' => __('gl.default_chart_imported_success')];
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Accounting\ChartOfAccountController::class, 'index'])->with('status', $output);
    }

    protected function formData($business_id, $exclude_id = null)
    {
        $accounts_query = ChartOfAccount::where('business_id', $business_id);
        if (! empty($exclude_id)) {
            $accounts_query->where('id', '!=', $exclude_id);
        }
        $parent_accounts = $accounts_query->get()->pluck('name', 'id');

        $payment_accounts = Account::forDropdown($business_id, true);

        return compact('parent_accounts', 'payment_accounts');
    }
}
