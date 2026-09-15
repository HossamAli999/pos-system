<?php

namespace App\Listeners;

use App\Events\StockAdjustmentCreatedOrModified;
use App\Utils\JournalUtil;

class PostJournalForStockAdjustment
{
    protected $journalUtil;

    public function __construct(JournalUtil $journalUtil)
    {
        $this->journalUtil = $journalUtil;
    }

    public function handle(StockAdjustmentCreatedOrModified $event)
    {
        try {
            $stock_adjustment = $event->stockAdjustment;

            if (empty($stock_adjustment) || ! $this->journalUtil->isEnabled($stock_adjustment->business_id)) {
                return;
            }

            $this->journalUtil->postForStockAdjustment($stock_adjustment);
        } catch (\Throwable $e) {
            \Log::error('PostJournalForStockAdjustment failed: '.$e->getMessage());
        }
    }
}
