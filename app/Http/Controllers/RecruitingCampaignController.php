<?php

namespace App\Http\Controllers;

use App\Notifications\GeneralInterestNotification;
use Log;
use Notification;
use Carbon\Carbon;
use App\RecruitingCampaign;
use App\RecruitingCampaignRecipient;
use App\RecruitingVisit;
use Illuminate\Http\Request;

class RecruitingCampaignController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:send-notifications']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rc = RecruitingCampaign::all();
        return response()->json(['status' => 'success', 'campaigns' => $rc]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'notification_template_id' => 'numeric|exists:notification_templates,id',
            'start_date' => 'date|required',
            'end_date' => 'date|required'
        ]);

        // Store the campaign
        // Yes, I know there is an easier way to do this.
        $rc = new RecruitingCampaign();
        $fields = array_keys($request->all());
        foreach ($fields as $field) {
            $rc->$field = $request->input($field);
        }
        $rc->created_by = $request->user()->id;
        $rc->status = 'new';

        try {
            $rc->save();
        } catch (QueryException $e) {
            Bugsnag::notifyException($e);
            $errorMessage = $e->errorInfo[2];
            return response()->json(['status' => 'error', 'message' => $errorMessage], 500);
        }

        // Import recipients from visits
        $start = $request->input('start_date');
        $end = $request->input('end_date');
        $visits = RecruitingVisit::where('created_at', '>=', $start)
                ->where('created_at', '<=', $end)
                ->get();

        $added_recipient_emails = [];
        foreach ($visits as $v) {
            if (in_array($v->recruiting_email, $added_recipient_emails)) {
                Log::info(get_class() . ": Email '$v->recruiting_email' already in the list. Ignoring.'");
            } else {
                // Add new recipient
                $rcr = new RecruitingCampaignRecipient();
                $rcr->email_address = $v->recruiting_email;
                $rcr->source = 'recruiting_visit';
                $rcr->recruiting_visit_id = $v->id;
                $rcr->recruiting_campaign_id = $rc->id;
                if ($v->user_id != null) {
                    $rcr->user_id = $v->user_id;
                }
                $rcr->save();

                // Add to array for dedup
                $added_recipient_emails[] = $v->recruiting_email;
                Log::info(get_class(). ": Added email '$v->recruiting_email' as recipient for campaign $rc->id");
            }

        }

        $db_rc = RecruitingCampaign::where('id', $rc->id)->with('recipients')->first();
        if (is_numeric($db_rc->id)) {
            return response()->json(['status' => 'success', 'campaign' => $db_rc], 201);
        } else {
            return response()->json(['status' => 'error', 'message' => 'unknown_error'], 500);
        }
    }

    /**
     * Create queue entries for email send
     *
     * @param $id integer
     * @return \Illuminate\Http\Response
     */
    public function queue($id)
    {
        $delay_hours = 0;
        $rc = RecruitingCampaign::where('id', $id)->first();
        $rcr_q = RecruitingCampaignRecipient::where('recruiting_campaign_id', $id)->whereNull('notified_at');
        $rcr_count = $rcr_q->count();
        $rcr_chunk = $rcr_q->chunk(30, function($chunk) use (&$delay_hours){
            $when = Carbon::now()->addHours($delay_hours);
            Log::debug(get_class() . ": Scheduling chunk for delivery in $delay_hours hours at $when");

            // This accepts an array ($chunk) of "Notifiable" models, so it's 30 at once like M A G I C
            Notification::send($chunk, (new GeneralInterestNotification())->delay($when));

            //Bump to an additional hour for the next chunk
            $delay_hours++;
        });
        return response()->json(['status' => 'success', 'queue_result' => ['recipients' => $rcr_count, 'chunks' => $delay_hours]]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\RecruitingCampaign  $recruitingCampaign
     * @return \Illuminate\Http\Response
     */
    public function show(RecruitingCampaign $recruitingCampaign)
    {
        return response()->json(['status' => 'success', 'campaign' => $recruitingCampaign]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\RecruitingCampaign  $recruitingCampaign
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RecruitingCampaign $recruitingCampaign)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\RecruitingCampaign  $recruitingCampaign
     * @return \Illuminate\Http\Response
     */
    public function destroy(RecruitingCampaign $recruitingCampaign)
    {
        $recruitingCampaign->delete();
        return response()->json(['status' => 'success'], 201);
    }
}
