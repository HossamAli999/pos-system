<?php

namespace App\Listeners;

use App\Events\TransactionPaymentAdded;
use App\Utils\JournalUtil;

/**
 * Registered alongside the existing AddAccountTransaction listener on the same
 * event — never edits that listener or its output. Every branch is wrapped so a
 * bug here can never break the sell/purchase/expense payment that triggered it.
 */
class PostJournalForPayment
{
    protected $journalUtil;

    public function __construct(JournalUtil $journalUtil)
    {
        $this->journalUtil = $journalUtil;
    }

    public function handle(TransactionPaymentAdded $event)
    {
        try {
            $business_id = $event->transactionPayment->business_id;

            if (empty($business_id) || ! $this->journalUtil->isEnabled($business_id)) {
                return;
            }

            $this->journalUtil->postForPayment($event->transactionPayment, $event->formInput);
        } catch (\Throwable $e) {
            \Log::error('PostJournalForPayment failed: '.$e->getMessage());
        }
    }
}
