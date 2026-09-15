<?php

namespace App\Listeners;

use App\Events\ExpenseCreatedOrModified;
use App\Utils\JournalUtil;

class PostJournalForExpense
{
    protected $journalUtil;

    public function __construct(JournalUtil $journalUtil)
    {
        $this->journalUtil = $journalUtil;
    }

    public function handle(ExpenseCreatedOrModified $event)
    {
        try {
            if (! empty($event->isDeleted)) {
                return;
            }

            $expense = $event->expense;

            if (empty($expense) || ! $this->journalUtil->isEnabled($expense->business_id)) {
                return;
            }

            $this->journalUtil->postForExpense($expense);
        } catch (\Throwable $e) {
            \Log::error('PostJournalForExpense failed: '.$e->getMessage());
        }
    }
}
