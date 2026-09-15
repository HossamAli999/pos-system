<?php

namespace App\Http\Controllers\Accounting;

use App\ChartOfAccount;
use App\Http\Controllers\Controller;
use App\Utils\JournalUtil;

class GlReportController extends Controller
{
    protected $journalUtil;

    public function __construct(JournalUtil $journalUtil)
    {
        $this->journalUtil = $journalUtil;
    }

    /**
     * Trial balance — mirrors the layout of the existing (legacy)
     * account_reports/trial_balance.blade.php for familiarity, but is computed
     * entirely from journal_lines rather than the cash ledger.
     */
    public function trialBalance()
    {
        if (! auth()->user()->can('gl.view_reports')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $end_date = request()->input('end_date', \Carbon::now()->toDateString());

        $rows = $this->journalUtil->getTrialBalance($business_id, $end_date);

        $total_debit = $rows->sum('total_debit');
        $total_credit = $rows->sum('total_credit');

        return view('accounting.reports.trial_balance')->with(compact('rows', 'total_debit', 'total_credit', 'end_date'));
    }

    /**
     * Balance sheet as of a given date.
     */
    public function balanceSheet()
    {
        if (! auth()->user()->can('gl.view_reports')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $as_of_date = request()->input('as_of_date', \Carbon::now()->toDateString());

        $data = $this->journalUtil->getBalanceSheet($business_id, $as_of_date);

        return view('accounting.reports.balance_sheet')->with(array_merge($data, ['as_of_date' => $as_of_date]));
    }

    /**
     * Profit & loss for a date range.
     */
    public function profitAndLoss()
    {
        if (! auth()->user()->can('gl.view_reports')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $start_date = request()->input('start_date', \Carbon::now()->startOfMonth()->toDateString());
        $end_date = request()->input('end_date', \Carbon::now()->toDateString());

        $data = $this->journalUtil->getProfitAndLoss($business_id, $start_date, $end_date);

        return view('accounting.reports.profit_and_loss')->with(array_merge($data, ['start_date' => $start_date, 'end_date' => $end_date]));
    }

    /**
     * General ledger detail for a single account.
     */
    public function generalLedger()
    {
        if (! auth()->user()->can('gl.view_reports')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $accounts = ChartOfAccount::forDropdown($business_id);

        $chart_of_account_id = request()->input('chart_of_account_id');
        $start_date = request()->input('start_date', \Carbon::now()->startOfMonth()->toDateString());
        $end_date = request()->input('end_date', \Carbon::now()->toDateString());

        $lines = collect();
        $running_balance = 0;
        $account = null;

        if (! empty($chart_of_account_id)) {
            $account = ChartOfAccount::where('business_id', $business_id)->find($chart_of_account_id);
            $raw_lines = $this->journalUtil->getGeneralLedgerForAccount($business_id, $chart_of_account_id, $start_date, $end_date);

            $is_debit_normal = ! empty($account) && $account->isDebitNormal();

            $lines = $raw_lines->map(function ($line) use (&$running_balance, $is_debit_normal) {
                $running_balance += $is_debit_normal ? ($line->debit - $line->credit) : ($line->credit - $line->debit);
                $line->running_balance = $running_balance;

                return $line;
            });
        }

        return view('accounting.reports.general_ledger')->with(compact('accounts', 'lines', 'account', 'chart_of_account_id', 'start_date', 'end_date'));
    }
}
