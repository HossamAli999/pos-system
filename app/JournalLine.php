<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    protected $guarded = ['id'];

    public function journal_entry()
    {
        return $this->belongsTo(\App\JournalEntry::class, 'journal_entry_id');
    }

    public function chart_of_account()
    {
        return $this->belongsTo(\App\ChartOfAccount::class, 'chart_of_account_id');
    }

    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }
}
