<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Drop-in replacement for Spatie's LogsActivity that also stamps the
 * business_id column (activity_log.business_id) so tenant reports/filters
 * work the same way they do for the manual Util::activityLog() calls
 * used elsewhere in the app.
 */
trait BusinessAuditable
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        //LogOptions::defaults() only logs $fillable attributes; every model in this
        //app uses $guarded instead, so logUnguarded() is required or nothing logs.
        return LogOptions::defaults()
            ->logUnguarded()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $business_id = $this->business_id ?? (session()->has('business') ? session('business.id') : null);

        if (! empty($business_id)) {
            $activity->business_id = $business_id;
        }
    }
}
