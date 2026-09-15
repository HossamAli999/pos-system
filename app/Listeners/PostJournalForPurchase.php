<?php

namespace App\Listeners;

use App\Events\PurchaseCreatedOrModified;
use App\Utils\JournalUtil;

class PostJournalForPurchase
{
    protected $journalUtil;

    public function __construct(JournalUtil $journalUtil)
    {
        $this->journalUtil = $journalUtil;
    }

    public function handle(PurchaseCreatedOrModified $event)
    {
        try {
            if (! empty($event->isDeleted)) {
                return;
            }

            $transaction = $event->transaction;

            if (empty($transaction) || ! $this->journalUtil->isEnabled($transaction->business_id)) {
                return;
            }

            $this->journalUtil->postForPurchase($transaction);
        } catch (\Throwable $e) {
            \Log::error('PostJournalForPurchase failed: '.$e->getMessage());
        }
    }
}
