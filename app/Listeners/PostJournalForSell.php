<?php

namespace App\Listeners;

use App\Events\SellCreatedOrModified;
use App\Utils\JournalUtil;

class PostJournalForSell
{
    protected $journalUtil;

    public function __construct(JournalUtil $journalUtil)
    {
        $this->journalUtil = $journalUtil;
    }

    public function handle(SellCreatedOrModified $event)
    {
        try {
            $transaction = $event->transaction;

            if (empty($transaction) || ! $this->journalUtil->isEnabled($transaction->business_id)) {
                return;
            }

            $this->journalUtil->postForSell($transaction);
        } catch (\Throwable $e) {
            \Log::error('PostJournalForSell failed: '.$e->getMessage());
        }
    }
}
