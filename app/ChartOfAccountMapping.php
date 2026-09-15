<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccountMapping extends Model
{
    protected $guarded = ['id'];

    public function chart_of_account()
    {
        return $this->belongsTo(\App\ChartOfAccount::class, 'chart_of_account_id');
    }
}
