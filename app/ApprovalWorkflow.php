<?php

namespace App;

use App\Traits\BusinessAuditable;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflow extends Model
{
    use BusinessAuditable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function steps()
    {
        return $this->hasMany(\App\ApprovalStep::class)->orderBy('step_order', 'asc');
    }

    public function requests()
    {
        return $this->hasMany(\App\ApprovalRequest::class);
    }

    public function created_by_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    /**
     * Finds the active workflow configured for a given module, business and (optional) amount,
     * or null if none is configured — callers should fall back to their existing status-flag
     * behavior when this returns null.
     */
    public static function findForModule($business_id, $module, $amount = null)
    {
        $query = self::where('business_id', $business_id)
            ->where('module', $module)
            ->where('is_active', 1);

        if (! is_null($amount)) {
            $query->where(function ($q) use ($amount) {
                $q->whereNull('min_amount')->orWhere('min_amount', '<=', $amount);
            })->where(function ($q) use ($amount) {
                $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
            });
        }

        return $query->orderBy('id', 'asc')->first();
    }
}
