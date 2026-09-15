<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FixedAssetDepreciationEntry extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'depreciation_date' => 'date',
    ];

    public function fixed_asset()
    {
        return $this->belongsTo(\App\FixedAsset::class, 'fixed_asset_id');
    }

    public function journal_entry()
    {
        return $this->belongsTo(\App\JournalEntry::class, 'journal_entry_id');
    }
}
