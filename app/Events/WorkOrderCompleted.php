<?php

namespace App\Events;

use App\WorkOrder;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired once a work order's production is completed, so a later phase's GL
 * listener (Dr Finished Goods / Cr Raw Materials) can hook in without
 * App\Utils\ManufacturingUtil needing to know GL exists.
 */
class WorkOrderCompleted
{
    use Dispatchable, SerializesModels;

    public $workOrder;

    public function __construct(WorkOrder $workOrder)
    {
        $this->workOrder = $workOrder;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
