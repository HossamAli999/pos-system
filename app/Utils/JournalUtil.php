<?php

namespace App\Utils;

use App\Account;
use App\ChartOfAccountMapping;
use App\JournalEntry;
use App\JournalLine;
use App\Transaction;
use DB;

/**
 * Posts and reports on the double-entry general ledger. This ledger is entirely
 * additive/parallel to the existing app\Account / app\AccountTransaction cash
 * ledger — nothing here ever touches that table or AccountReportsController.
 * Every posting method is opt-in per business via isEnabled(); callers (the
 * PostJournalFor* listeners) must always call isEnabled() first and must never
 * let an exception from here escape into the original sell/purchase/expense
 * save that triggered it.
 */
class JournalUtil extends Util
{
    /**
     * Whether business.accounting_settings.enable_double_entry_accounting is on.
     * False (including for every pre-existing business, where the column is
     * simply null) means every listener must no-op.
     */
    public function isEnabled($business_id)
    {
        $business = \App\Business::find($business_id);
        if (empty($business) || empty($business->accounting_settings)) {
            return false;
        }

        return ! empty($business->accounting_settings['enable_double_entry_accounting']);
    }

    /**
     * Resolves a mapping_key (e.g. 'sales_income', 'accounts_receivable') to its
     * configured ChartOfAccount for the business. Throws — loudly, on purpose —
     * rather than silently posting to a wrong/default account when a business
     * hasn't finished configuring its mappings; every caller here is inside a
     * listener that catches broadly, so this never breaks the original save.
     */
    public function getMappedAccount($business_id, $mapping_key)
    {
        $mapping = ChartOfAccountMapping::where('business_id', $business_id)
            ->where('mapping_key', $mapping_key)
            ->with('chart_of_account')
            ->first();

        if (empty($mapping) || empty($mapping->chart_of_account)) {
            \Log::emergency("JournalUtil::getMappedAccount - no chart-of-account mapping for '{$mapping_key}' on business {$business_id}");

            throw new \Exception(__('gl.mapping_missing', ['key' => $mapping_key]));
        }

        return $mapping->chart_of_account;
    }

    /**
     * Posts one balanced journal entry. $lines is an array of
     * ['chart_of_account_id', 'debit', 'credit', 'contact_id'?, 'product_id'?, 'memo'?].
     * $source, when given, is any Eloquent model — recorded as a polymorphic
     * back-reference (source_type/source_id) to what caused this entry.
     *
     * @return JournalEntry|null null if every line is zero (nothing to post)
     *
     * @throws \Exception if the lines don't balance
     */
    public function postEntry($business_id, array $lines, $entry_date, $entry_type, $source = null, $narration = null, $created_by = null, $location_id = null)
    {
        $total_debit = 0;
        $total_credit = 0;
        foreach ($lines as $line) {
            $total_debit += $line['debit'] ?? 0;
            $total_credit += $line['credit'] ?? 0;
        }

        if (round($total_debit, 4) !== round($total_credit, 4)) {
            throw new \Exception(__('gl.entry_not_balanced', ['debit' => $total_debit, 'credit' => $total_credit]));
        }

        if (empty(round($total_debit, 4))) {
            return null;
        }

        return DB::transaction(function () use ($business_id, $lines, $entry_date, $entry_type, $source, $narration, $created_by, $location_id) {
            $journal_entry = JournalEntry::create([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'entry_date' => $entry_date,
                'entry_type' => $entry_type,
                'source_type' => ! empty($source) ? get_class($source) : null,
                'source_id' => ! empty($source) ? $source->id : null,
                'narration' => $narration,
                'is_posted' => 1,
                'created_by' => $created_by,
            ]);

            foreach ($lines as $line) {
                $line['journal_entry_id'] = $journal_entry->id;
                $line['debit'] = $line['debit'] ?? 0;
                $line['credit'] = $line['credit'] ?? 0;
                JournalLine::create($line);
            }

            return $journal_entry;
        });
    }

    /**
     * Reverses a posted entry with a mirrored entry (debits<->credits swapped)
     * rather than deleting it, keeping the ledger audit-safe.
     */
    public function reverseEntry($journal_entry_id, $reason = null, $user_id = null)
    {
        $entry = JournalEntry::with('lines')->findOrFail($journal_entry_id);

        $reversed_lines = $entry->lines->map(function ($line) {
            return [
                'chart_of_account_id' => $line->chart_of_account_id,
                'debit' => $line->credit,
                'credit' => $line->debit,
                'contact_id' => $line->contact_id,
                'product_id' => $line->product_id,
                'memo' => $line->memo,
            ];
        })->toArray();

        return $this->postEntry(
            $entry->business_id,
            $reversed_lines,
            \Carbon::now()->toDateString(),
            $entry->entry_type,
            null,
            $reason ?? __('gl.reversal_of', ['ref' => $entry->id]),
            $user_id,
            $entry->location_id
        );
    }

    /**
     * Recognizes revenue + receivable for a finalized sale, and the COGS/inventory
     * side separately. Only posts for status='final' — drafts/quotations create no
     * obligation yet. The cash side (when/if paid) posts separately via
     * postForPayment(), fired from the same TransactionPaymentAdded event the
     * legacy ledger already uses — so a sale on credit still posts correctly here
     * even though no payment ever arrives in this listener's lifetime.
     */
    public function postForSell($transaction)
    {
        if (! is_object($transaction)) {
            $transaction = Transaction::findOrFail($transaction);
        }

        if ($transaction->type != 'sell' || $transaction->status != 'final') {
            return null;
        }

        $business_id = $transaction->business_id;

        $ar_account = $this->getMappedAccount($business_id, 'accounts_receivable');
        $sales_account = $this->getMappedAccount($business_id, 'sales_income');

        $tax_amount = (float) $transaction->tax_amount;
        $revenue_amount = (float) $transaction->final_total - $tax_amount;

        $lines = [
            ['chart_of_account_id' => $ar_account->id, 'debit' => $transaction->final_total, 'credit' => 0, 'contact_id' => $transaction->contact_id],
            ['chart_of_account_id' => $sales_account->id, 'debit' => 0, 'credit' => $revenue_amount],
        ];

        if ($tax_amount > 0) {
            $tax_account = $this->getMappedAccount($business_id, 'sales_tax_payable');
            $lines[] = ['chart_of_account_id' => $tax_account->id, 'debit' => 0, 'credit' => $tax_amount];
        }

        $revenue_entry = $this->postEntry($business_id, $lines, $transaction->transaction_date, 'sell', $transaction, __('gl.narration_sell', ['ref' => $transaction->invoice_no]), $transaction->created_by, $transaction->location_id);

        $cogs_amount = $this->calculateCogsForSell($transaction);
        if ($cogs_amount > 0) {
            $cogs_account = $this->getMappedAccount($business_id, 'cogs_expense');
            $inventory_account = $this->getMappedAccount($business_id, 'inventory_asset');

            $this->postEntry($business_id, [
                ['chart_of_account_id' => $cogs_account->id, 'debit' => $cogs_amount, 'credit' => 0],
                ['chart_of_account_id' => $inventory_account->id, 'debit' => 0, 'credit' => $cogs_amount],
            ], $transaction->transaction_date, 'sell', $transaction, __('gl.narration_cogs', ['ref' => $transaction->invoice_no]), $transaction->created_by, $transaction->location_id);
        }

        return $revenue_entry;
    }

    /**
     * Approximates a sale's cost of goods sold from the same purchase-line cost
     * links the app already maintains (transaction_sell_lines_purchase_lines).
     * Sell lines that don't have a cost link yet (stock mapping can lag behind
     * the sale in some flows) fall back to the variation's default_purchase_price
     * — a deliberate simplification flagged here for later refinement once true
     * FIFO/LIFO/AVCO costing is wired through to this posting point.
     */
    protected function calculateCogsForSell($transaction)
    {
        $linked_cost = DB::table('transaction_sell_lines_purchase_lines as tspl')
            ->join('transaction_sell_lines as tsl', 'tspl.sell_line_id', '=', 'tsl.id')
            ->join('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
            ->where('tsl.transaction_id', $transaction->id)
            ->selectRaw('SUM((tspl.quantity - tspl.qty_returned) * pl.purchase_price_inc_tax) as total')
            ->value('total');

        $linked_sell_line_ids = DB::table('transaction_sell_lines_purchase_lines as tspl')
            ->join('transaction_sell_lines as tsl', 'tspl.sell_line_id', '=', 'tsl.id')
            ->where('tsl.transaction_id', $transaction->id)
            ->pluck('tsl.id')
            ->unique()
            ->toArray();

        $unlinked_query = DB::table('transaction_sell_lines as tsl')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->where('tsl.transaction_id', $transaction->id)
            ->where('tsl.children_type', '!=', 'combo');

        if (! empty($linked_sell_line_ids)) {
            $unlinked_query->whereNotIn('tsl.id', $linked_sell_line_ids);
        }

        $unlinked_cost = $unlinked_query->selectRaw('SUM(tsl.quantity * v.default_purchase_price) as total')->value('total');

        return (float) $linked_cost + (float) $unlinked_cost;
    }

    /**
     * Recognizes inventory received + the resulting payable for a received purchase.
     * Purchase tax is booked into the same inventory cost for now rather than a
     * separate recoverable-tax asset — a simplification, since final_total already
     * nets everything the legacy ledger treats as one purchase cost.
     */
    public function postForPurchase($transaction)
    {
        if (! is_object($transaction)) {
            $transaction = Transaction::findOrFail($transaction);
        }

        if ($transaction->type != 'purchase' || ! in_array($transaction->status, ['received'])) {
            return null;
        }

        $business_id = $transaction->business_id;

        $inventory_account = $this->getMappedAccount($business_id, 'inventory_asset');
        $ap_account = $this->getMappedAccount($business_id, 'accounts_payable');

        $lines = [
            ['chart_of_account_id' => $inventory_account->id, 'debit' => $transaction->final_total, 'credit' => 0, 'contact_id' => $transaction->contact_id],
            ['chart_of_account_id' => $ap_account->id, 'debit' => 0, 'credit' => $transaction->final_total],
        ];

        return $this->postEntry($business_id, $lines, $transaction->transaction_date, 'purchase', $transaction, __('gl.narration_purchase', ['ref' => $transaction->ref_no]), $transaction->created_by, $transaction->location_id);
    }

    /**
     * Recognizes an expense against a single mapped expense account (per-category
     * GL mapping is a future refinement) and the payable it creates — cleared
     * later by postForPayment() exactly like a purchase.
     */
    public function postForExpense($expense)
    {
        if (! is_object($expense)) {
            $expense = Transaction::findOrFail($expense);
        }

        if ($expense->type != 'expense') {
            return null;
        }

        $business_id = $expense->business_id;

        $expense_account = $this->getMappedAccount($business_id, 'expense_general');
        $ap_account = $this->getMappedAccount($business_id, 'accounts_payable');

        $lines = [
            ['chart_of_account_id' => $expense_account->id, 'debit' => $expense->final_total, 'credit' => 0],
            ['chart_of_account_id' => $ap_account->id, 'debit' => 0, 'credit' => $expense->final_total],
        ];

        return $this->postEntry($business_id, $lines, $expense->transaction_date, 'expense', $expense, __('gl.narration_expense', ['ref' => $expense->ref_no]), $expense->created_by, $expense->location_id);
    }

    /**
     * Books shrinkage/loss (or gain, for positive adjustments) from a stock
     * adjustment. total_amount_recovered (e.g. insurance/damage recovery) is not
     * separately booked yet — a simplification.
     */
    public function postForStockAdjustment($stockAdjustment)
    {
        if (! is_object($stockAdjustment)) {
            $stockAdjustment = Transaction::findOrFail($stockAdjustment);
        }

        if ($stockAdjustment->type != 'stock_adjustment') {
            return null;
        }

        $amount = (float) $stockAdjustment->final_total;
        if ($amount <= 0) {
            return null;
        }

        $business_id = $stockAdjustment->business_id;

        $shrinkage_account = $this->getMappedAccount($business_id, 'inventory_shrinkage_expense');
        $inventory_account = $this->getMappedAccount($business_id, 'inventory_asset');

        $lines = [
            ['chart_of_account_id' => $shrinkage_account->id, 'debit' => $amount, 'credit' => 0],
            ['chart_of_account_id' => $inventory_account->id, 'debit' => 0, 'credit' => $amount],
        ];

        return $this->postEntry($business_id, $lines, $stockAdjustment->transaction_date, 'stock_adjustment', $stockAdjustment, __('gl.narration_stock_adjustment', ['ref' => $stockAdjustment->ref_no]), $stockAdjustment->created_by, $stockAdjustment->location_id);
    }

    /**
     * Books the cash-side settlement for ANY transaction payment (sell, purchase,
     * expense) — the same trigger point (TransactionPaymentAdded) the legacy
     * cash/bank ledger uses, so a sale on credit posts its AR/revenue here at sale
     * time (postForSell) and its cash-clearing here later when actually paid.
     * Payroll is deliberately excluded — PayrollUtil::postPayrollRunToLedger()
     * posts its own single combined entry directly, without going through this
     * listener, since it always creates and pays a payroll run in one step.
     */
    public function postForPayment($transactionPayment, $formInput)
    {
        $transaction_type = $formInput['transaction_type'] ?? null;

        if (empty($transaction_type) || $transaction_type == 'payroll' || $transaction_type == 'advance') {
            return null;
        }

        if (empty($formInput['account_id'])) {
            return null;
        }

        $account = Account::find($formInput['account_id']);
        if (empty($account) || empty($account->chart_of_account_id)) {
            \Log::emergency("JournalUtil::postForPayment - payment account {$formInput['account_id']} has no linked chart_of_account");

            return null;
        }

        $business_id = $transactionPayment->business_id;
        $amount = (float) ($formInput['amount'] ?? $transactionPayment->amount);
        $is_return = ! empty($formInput['is_return']);

        $cash_account_id = $account->chart_of_account_id;

        //Mirrors App\AccountTransaction::getAccountTransactionType() so both ledgers
        //agree on which side of the transaction cash sits on.
        $contra_mapping = [
            'sell' => 'accounts_receivable',
            'sell_return' => 'accounts_receivable',
            'purchase' => 'accounts_payable',
            'purchase_return' => 'accounts_payable',
            'expense' => 'accounts_payable',
            'expense_refund' => 'accounts_payable',
        ];

        if (! isset($contra_mapping[$transaction_type])) {
            return null;
        }

        $contra_account = $this->getMappedAccount($business_id, $contra_mapping[$transaction_type]);

        //Cash coming in (sell payment, purchase return refund) debits cash; cash
        //going out (purchase payment, sell return refund) credits cash. is_return
        //flips the normal direction for that type, same as the legacy listener.
        $cash_is_debit = in_array($transaction_type, ['sell', 'purchase_return']);
        if ($is_return) {
            $cash_is_debit = ! $cash_is_debit;
        }

        $lines = $cash_is_debit
            ? [
                ['chart_of_account_id' => $cash_account_id, 'debit' => $amount, 'credit' => 0],
                ['chart_of_account_id' => $contra_account->id, 'debit' => 0, 'credit' => $amount, 'contact_id' => $transactionPayment->payment_for],
            ]
            : [
                ['chart_of_account_id' => $contra_account->id, 'debit' => $amount, 'credit' => 0, 'contact_id' => $transactionPayment->payment_for],
                ['chart_of_account_id' => $cash_account_id, 'debit' => 0, 'credit' => $amount],
            ];

        return $this->postEntry($business_id, $lines, $transactionPayment->paid_on ?? \Carbon::now(), 'payment', $transactionPayment, __('gl.narration_payment', ['ref' => $transactionPayment->payment_ref_no]), $transactionPayment->created_by);
    }

    /**
     * Posts payroll's single combined entry (Dr Salary Expense / Cr Cash) — called
     * directly by PayrollUtil::postPayrollRunToLedger() right after it books the
     * same amount into the legacy ledger, rather than via a listener, because that
     * method always creates and pays a run in one atomic step.
     */
    public function postForPayroll($business_id, $account_id, $amount, $entry_date, $payrollRun, $user_id = null)
    {
        $account = Account::find($account_id);
        if (empty($account) || empty($account->chart_of_account_id)) {
            \Log::emergency("JournalUtil::postForPayroll - payment account {$account_id} has no linked chart_of_account for business {$business_id}");

            return null;
        }

        $expense_account = $this->getMappedAccount($business_id, 'payroll_expense');

        $lines = [
            ['chart_of_account_id' => $expense_account->id, 'debit' => $amount, 'credit' => 0],
            ['chart_of_account_id' => $account->chart_of_account_id, 'debit' => 0, 'credit' => $amount],
        ];

        return $this->postEntry($business_id, $lines, $entry_date, 'payroll', $payrollRun, __('gl.narration_payroll'), $user_id);
    }

    public function getTrialBalance($business_id, $end_date = null)
    {
        $query = JournalLine::join('journal_entries as je', 'journal_lines.journal_entry_id', '=', 'je.id')
            ->join('chart_of_accounts as coa', 'journal_lines.chart_of_account_id', '=', 'coa.id')
            ->where('je.business_id', $business_id);

        if (! empty($end_date)) {
            $query->whereDate('je.entry_date', '<=', $end_date);
        }

        return $query->groupBy('coa.id', 'coa.code', 'coa.name', 'coa.account_category')
            ->selectRaw('coa.id, coa.code, coa.name, coa.account_category, SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
            ->orderBy('coa.code')
            ->get();
    }

    public function getGeneralLedgerForAccount($business_id, $chart_of_account_id, $start_date = null, $end_date = null)
    {
        $query = JournalLine::join('journal_entries as je', 'journal_lines.journal_entry_id', '=', 'je.id')
            ->where('je.business_id', $business_id)
            ->where('journal_lines.chart_of_account_id', $chart_of_account_id);

        if (! empty($start_date) && ! empty($end_date)) {
            $query->whereDate('je.entry_date', '>=', $start_date)->whereDate('je.entry_date', '<=', $end_date);
        }

        return $query->select('journal_lines.*', 'je.entry_date', 'je.entry_type', 'je.narration', 'je.reference_number', 'je.id as journal_entry_id')
            ->orderBy('je.entry_date', 'asc')
            ->get();
    }

    public function getProfitAndLoss($business_id, $start_date, $end_date)
    {
        $rows = JournalLine::join('journal_entries as je', 'journal_lines.journal_entry_id', '=', 'je.id')
            ->join('chart_of_accounts as coa', 'journal_lines.chart_of_account_id', '=', 'coa.id')
            ->where('je.business_id', $business_id)
            ->whereIn('coa.account_category', ['income', 'expense'])
            ->whereDate('je.entry_date', '>=', $start_date)
            ->whereDate('je.entry_date', '<=', $end_date)
            ->groupBy('coa.id', 'coa.name', 'coa.account_category')
            ->selectRaw('coa.id, coa.name, coa.account_category, SUM(journal_lines.credit) - SUM(journal_lines.debit) as net_amount')
            ->get();

        $income = $rows->where('account_category', 'income')->values();
        $expense = $rows->where('account_category', 'expense')->map(function ($row) {
            $row->net_amount = $row->net_amount * -1;

            return $row;
        })->values();

        $total_income = $income->sum('net_amount');
        $total_expense = $expense->sum('net_amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'total_income' => $total_income,
            'total_expense' => $total_expense,
            'net_profit' => $total_income - $total_expense,
        ];
    }

    public function getBalanceSheet($business_id, $as_of_date)
    {
        $rows = JournalLine::join('journal_entries as je', 'journal_lines.journal_entry_id', '=', 'je.id')
            ->join('chart_of_accounts as coa', 'journal_lines.chart_of_account_id', '=', 'coa.id')
            ->where('je.business_id', $business_id)
            ->whereIn('coa.account_category', ['asset', 'liability', 'equity'])
            ->whereDate('je.entry_date', '<=', $as_of_date)
            ->groupBy('coa.id', 'coa.name', 'coa.account_category')
            ->selectRaw('coa.id, coa.name, coa.account_category, SUM(journal_lines.debit) - SUM(journal_lines.credit) as net_amount')
            ->get();

        $assets = $rows->where('account_category', 'asset')->values();
        $liabilities = $rows->where('account_category', 'liability')->map(function ($row) {
            $row->net_amount = $row->net_amount * -1;

            return $row;
        })->values();
        $equity = $rows->where('account_category', 'equity')->map(function ($row) {
            $row->net_amount = $row->net_amount * -1;

            return $row;
        })->values();

        $total_assets = $assets->sum('net_amount');
        $total_liabilities = $liabilities->sum('net_amount');
        $total_equity = $equity->sum('net_amount');

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $total_assets,
            'total_liabilities' => $total_liabilities,
            'total_equity' => $total_equity,
            //Retained earnings aren't posted as their own entry — this plug shows
            //what they'd need to be for the sheet to balance (assets = liabilities + equity).
            'retained_earnings_plug' => $total_assets - $total_liabilities - $total_equity,
        ];
    }
}
