<?php

namespace App\Utils;

use App\Employee;
use App\Events\TransactionPaymentAdded;
use App\PayrollRun;
use App\Payslip;
use App\PayslipLine;
use App\Transaction;
use App\TransactionPayment;
use DB;

class PayrollUtil extends Util
{
    protected $attendanceUtil;

    public function __construct(AttendanceUtil $attendanceUtil)
    {
        $this->attendanceUtil = $attendanceUtil;
    }

    /**
     * Generates a draft payroll run: one Payslip per active employee who has a
     * salary structure in effect as of the period end, pro-rated for unpaid
     * absence/leave days within the period.
     */
    public function generatePayrollRun($business_id, $location_id, $pay_period_start, $pay_period_end, $pay_date, $user_id, $employee_ids = null)
    {
        DB::beginTransaction();
        try {
            $payroll_run = PayrollRun::create([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'pay_period_start' => $pay_period_start,
                'pay_period_end' => $pay_period_end,
                'pay_date' => $pay_date,
                'status' => 'draft',
                'created_by' => $user_id,
            ]);

            $query = Employee::where('business_id', $business_id)->where('status', 'active');

            if (! empty($location_id)) {
                $query->where(function ($q) use ($location_id) {
                    $q->where('location_id', $location_id)->orWhereNull('location_id');
                });
            }

            if (! empty($employee_ids)) {
                $query->whereIn('id', $employee_ids);
            }

            $employees = $query->get();

            $period_days = \Carbon::parse($pay_period_start)->diffInDays(\Carbon::parse($pay_period_end)) + 1;

            $total_earnings = 0;
            $total_deductions = 0;
            $total_net = 0;

            foreach ($employees as $employee) {
                $structure = $employee->currentSalaryStructure($pay_period_end);

                //No salary configured yet for this employee — skip rather than guess.
                if (empty($structure)) {
                    continue;
                }

                $components = $structure->resolvedComponents();

                $gross_earnings = $structure->basic_salary + $components->where('type', 'earning')->sum('amount');
                $deductions = $components->where('type', 'deduction')->sum('amount');

                $unpaid_days = $this->attendanceUtil->getUnpaidDaysInPeriod($employee->id, $pay_period_start, $pay_period_end);
                $per_day_rate = $period_days > 0 ? ($gross_earnings / $period_days) : 0;
                $unpaid_deduction = round($per_day_rate * $unpaid_days, 4);

                $net_pay = $gross_earnings - $deductions - $unpaid_deduction;

                $payslip = Payslip::create([
                    'payroll_run_id' => $payroll_run->id,
                    'employee_id' => $employee->id,
                    'basic_salary' => $structure->basic_salary,
                    'gross_earnings' => $gross_earnings,
                    'total_deductions' => $deductions + $unpaid_deduction,
                    'net_pay' => $net_pay,
                    'unpaid_days' => $unpaid_days,
                    'payment_status' => 'unpaid',
                ]);

                PayslipLine::create([
                    'payslip_id' => $payslip->id,
                    'component_name' => __('hr.basic_salary'),
                    'type' => 'earning',
                    'amount' => $structure->basic_salary,
                ]);

                foreach ($components as $component) {
                    PayslipLine::create([
                        'payslip_id' => $payslip->id,
                        'payroll_component_id' => $component['payroll_component_id'],
                        'component_name' => $component['name'],
                        'type' => $component['type'],
                        'amount' => $component['amount'],
                    ]);
                }

                if ($unpaid_deduction > 0) {
                    PayslipLine::create([
                        'payslip_id' => $payslip->id,
                        'component_name' => __('hr.unpaid_absence_deduction'),
                        'type' => 'deduction',
                        'amount' => $unpaid_deduction,
                    ]);
                }

                $total_earnings += $gross_earnings;
                $total_deductions += ($deductions + $unpaid_deduction);
                $total_net += $net_pay;
            }

            $payroll_run->total_earnings = $total_earnings;
            $payroll_run->total_deductions = $total_deductions;
            $payroll_run->total_net_pay = $total_net;
            $payroll_run->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $payroll_run->fresh('payslips');
    }

    public function approvePayrollRun($payroll_run_id, $business_id, $user_id)
    {
        $payroll_run = PayrollRun::where('business_id', $business_id)->findOrFail($payroll_run_id);

        if ($payroll_run->status != 'draft') {
            throw new \Exception(__('hr.payroll_run_not_draft'));
        }

        $payroll_run->status = 'approved';
        $payroll_run->approved_by = $user_id;
        $payroll_run->approved_at = \Carbon::now();
        $payroll_run->save();

        return $payroll_run;
    }

    /**
     * Posts the run's total net pay into the EXISTING cash/bank ledger by creating
     * one 'payroll'-type Transaction + TransactionPayment and firing the same
     * TransactionPaymentAdded event every other payment in the app fires —
     * App\Listeners\AddAccountTransaction then books it automatically, and
     * App\Http\Controllers\AccountReportsController reflects it with no changes
     * of its own, because App\AccountTransaction::getAccountTransactionType()
     * already maps 'payroll' => 'debit'.
     */
    public function postPayrollRunToLedger($payroll_run_id, $business_id, $account_id, $user_id)
    {
        $payroll_run = PayrollRun::where('business_id', $business_id)->with('payslips')->findOrFail($payroll_run_id);

        if (! empty($payroll_run->transaction_id)) {
            throw new \Exception(__('hr.payroll_already_posted'));
        }

        if ($payroll_run->status != 'approved') {
            throw new \Exception(__('hr.payroll_run_not_approved'));
        }

        if ($payroll_run->total_net_pay <= 0) {
            throw new \Exception(__('hr.payroll_nothing_to_post'));
        }

        DB::beginTransaction();
        try {
            $ref_count = $this->setAndGetReferenceCount('payroll', $business_id);
            $ref_no = $this->generateReferenceNumber('payroll', $ref_count, $business_id);

            $transaction = Transaction::create([
                'business_id' => $business_id,
                'location_id' => $payroll_run->location_id,
                'type' => 'payroll',
                'status' => 'final',
                'payment_status' => 'paid',
                'ref_no' => $ref_no,
                'transaction_date' => \Carbon::now()->toDateTimeString(),
                'total_before_tax' => $payroll_run->total_net_pay,
                'final_total' => $payroll_run->total_net_pay,
                'additional_notes' => __('hr.payroll_transaction_note', [
                    'start' => $payroll_run->pay_period_start->toDateString(),
                    'end' => $payroll_run->pay_period_end->toDateString(),
                ]),
                'created_by' => $user_id,
            ]);

            $payment_ref_count = $this->setAndGetReferenceCount('payroll_payment', $business_id);
            $payment_ref_no = $this->generateReferenceNumber('payroll_payment', $payment_ref_count, $business_id);

            $payment = TransactionPayment::create([
                'transaction_id' => $transaction->id,
                'business_id' => $business_id,
                'amount' => $payroll_run->total_net_pay,
                'method' => 'bank_transfer',
                'account_id' => $account_id,
                'paid_on' => \Carbon::now()->toDateTimeString(),
                'payment_ref_no' => $payment_ref_no,
                'created_by' => $user_id,
            ]);

            event(new TransactionPaymentAdded($payment, [
                'transaction_type' => 'payroll',
                'account_id' => $account_id,
                'amount' => $payroll_run->total_net_pay,
                'is_return' => 0,
            ]));

            //Additive: posts one combined Dr Salary Expense / Cr Cash entry to the
            //optional GL if the business has opted in — PostJournalForPayment
            //explicitly ignores 'payroll', so this is the only GL posting for this
            //run and can never double up with it.
            if (app(JournalUtil::class)->isEnabled($business_id)) {
                try {
                    app(JournalUtil::class)->postForPayroll($business_id, $account_id, $payroll_run->total_net_pay, $transaction->transaction_date, $payroll_run, $user_id);
                } catch (\Throwable $e) {
                    \Log::error('JournalUtil::postForPayroll failed: '.$e->getMessage());
                }
            }

            $payroll_run->transaction_id = $transaction->id;
            $payroll_run->status = 'paid';
            $payroll_run->save();

            foreach ($payroll_run->payslips as $payslip) {
                $payslip->payment_status = 'paid';
                $payslip->payment_account_id = $account_id;
                $payslip->paid_on = \Carbon::now();
                $payslip->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $payroll_run->fresh(['payslips', 'transaction']);
    }

    public function cancelPayrollRun($payroll_run_id, $business_id)
    {
        $payroll_run = PayrollRun::where('business_id', $business_id)->findOrFail($payroll_run_id);

        if (! empty($payroll_run->transaction_id)) {
            throw new \Exception(__('hr.payroll_cannot_cancel_posted'));
        }

        $payroll_run->status = 'cancelled';
        $payroll_run->save();

        return $payroll_run;
    }
}
