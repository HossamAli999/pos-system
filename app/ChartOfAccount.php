<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use BusinessAuditable;

    protected $guarded = ['id'];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'opening_balance_date' => 'date',
    ];

    public function parent()
    {
        return $this->belongsTo(\App\ChartOfAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(\App\ChartOfAccount::class, 'parent_id');
    }

    public function linked_account()
    {
        return $this->belongsTo(\App\Account::class, 'linked_account_id');
    }

    public function journal_lines()
    {
        return $this->hasMany(\App\JournalLine::class, 'chart_of_account_id');
    }

    /**
     * debit-normal categories (asset, expense) increase with a debit;
     * credit-normal categories (liability, equity, income) increase with a credit.
     */
    public function isDebitNormal()
    {
        return in_array($this->account_category, ['asset', 'expense']);
    }

    public static function forDropdown($business_id, $active_only = true)
    {
        $query = self::where('business_id', $business_id);

        if ($active_only) {
            $query->where('is_active', 1);
        }

        return $query->orderBy('code', 'asc')->get()->mapWithKeys(function ($account) {
            $label = ! empty($account->code) ? $account->code.' - '.$account->name : $account->name;

            return [$account->id => $label];
        });
    }
}
