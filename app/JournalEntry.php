<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use SoftDeletes;
    use BusinessAuditable;

    protected $guarded = ['id'];

    protected $casts = [
        'entry_date' => 'date',
        'is_posted' => 'boolean',
    ];

    public function lines()
    {
        return $this->hasMany(\App\JournalLine::class, 'journal_entry_id');
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function getTotalDebitAttribute()
    {
        return $this->lines->sum('debit');
    }

    public function getTotalCreditAttribute()
    {
        return $this->lines->sum('credit');
    }
}
