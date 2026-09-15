<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Utils\CrmUtil;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    protected $crmUtil;

    public function __construct(CrmUtil $crmUtil)
    {
        $this->crmUtil = $crmUtil;
    }

    /**
     * Logs a new activity, typically from a lead's show page.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('crm_activity.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            $input = $request->only(['crm_lead_id', 'type', 'subject', 'description', 'due_date', 'assigned_to']);
            $this->crmUtil->logActivity($input, $business_id, $user_id);

            $output = ['success' => true, 'msg' => __('crm.activity_added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        if ($request->ajax()) {
            return $output;
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Marks an activity as done.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function complete(Request $request, $id)
    {
        if (! auth()->user()->can('crm_activity.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $this->crmUtil->completeActivity($id, $business_id);

            $output = ['success' => true, 'msg' => __('crm.activity_completed_success')];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * "My Tasks" — the current user's upcoming/overdue follow-up activities.
     *
     * @return \Illuminate\Http\Response
     */
    public function myTasks()
    {
        if (! auth()->user()->can('crm_activity.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $activities = $this->crmUtil->getUpcomingFollowUps($business_id, auth()->user()->id, 30);

        return view('crm.activities.my_tasks')->with(compact('activities'));
    }
}
