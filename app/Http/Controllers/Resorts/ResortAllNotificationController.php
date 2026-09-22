<?php
namespace App\Http\Controllers\Resorts;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\ResortNotificationEvent;
use App\Helpers\Common;
use Auth;
use App\Models\Employee;
use App\Models\ResortsParentNotifications;
use App\Models\ResortsChildNotifications;
use App\Models\ResortNotification;
use App\Models\ResortDepartment;
use Str;
use DB;
use Validator;
use App\Models\HrReminderRequestManning;
use App\Models\BudgetStatus;
use App\Models\ManningResponse;

class ResortAllNotificationController extends Controller
{
    protected $type;
    function __construct() {
        $this->type = config('settings.Notifications');
    }

    /**
     * Web-portal counterpart of API\ProfileController::testPushNotification
     * — same underlying Common::sendTestPushToResort(), just reachable from
     * a browser session (auth:resort-admin) instead of a Passport bearer
     * token, so HR/admin can trigger a real test push without needing to
     * mint an API token first.
     */
    public function testPushNotification(Request $request)
    {
        $resort = Auth::guard('resort-admin')->user();
        if (!$resort) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return response()->json(Common::sendTestPushToResort($resort->resort_id));
    }

    public function ManningNotification(Request $request)
    {
        $resort = Auth::guard('resort-admin')->user();
        $resort_id = $resort->resort_id;
        $user_type = $resort->is_employee;


        $manningRequest = $request->manningRequest;
        $parts = explode(' ', $resort->resort->resort_name);
        $message_id = '';
        foreach ($parts as $part)
        {
            $message_id .= Str::substr($part, 0, 1);
        }
        $message_id = strtoupper($message_id).Rand();

        $DepartmentIds = $resort->resort->ResortDepartment->pluck('id')->toArray();

        // $Auth_departmentId = Auth::guard('resort-admin')->user()->GetEmployee->Dept_id;


        // $DepartmentIds = $resort->resort->ResortDepartment
        // ->reject(function ($department) use ($Auth_departmentId) {
        //     return $department->id == $Auth_departmentId;
        // })
        // ->pluck('id') ->toArray();

        // HODs/EXCOM (rank 2/1) — keyed by Dept_id so each department gets a
        // child row even when no HOD is assigned. Previous logic looped over
        // HODs only, so departments without a Rank=2 employee were silently
        // dropped from the pending-response list (cause: 25 depts → only 6
        // child rows), and separately only rank 2 was ever queried at all —
        // department heads with rank 1 (EXCOM) never got notified.
        $hodsByDept = Employee::where('resort_id', $resort_id)
            ->whereIn('Rank', [1, 2])
            ->whereIn('Dept_id', $DepartmentIds)
            ->get(['Admin_Parent_id','id','Rank','Dept_id','Position_id'])
            ->groupBy('Dept_id');

        // HR users (rank 3) — should also be alerted that a manning request
        // went out, but they don't generate per-department response rows.
        $hrEmployees = Employee::where('resort_id', $resort_id)
            ->where('Rank', 3)
            ->get(['Admin_Parent_id','id','Rank','Dept_id','Position_id']);

        DB::beginTransaction();
        try{
            $inactiveMsgId = ResortsParentNotifications::where('resort_id', $resort_id)
                ->where('user_id', $resort->id)
                ->update(['status' => 'Inactive']);

            $parentNotification = ResortsParentNotifications::create([
                'resort_id'       => $resort_id,
                'user_type'       => ($user_type == 0) ? 'super' : 'sub',
                'user_id'         => $resort->id,
                'message_id'      => $message_id,
                'message_subject' => $manningRequest,
            ]);
            $parentmesgid = $parentNotification->message_id;

            // ONE child row per department — driven by $DepartmentIds, not the
            // HOD list. Departments without an HOD still appear as pending so
            // HR can chase them up. Position_id falls back to 0 because the
            // column is NOT NULL in the schema (no default).
            foreach ($DepartmentIds as $deptId) {
                $deptHods = $hodsByDept->get($deptId, collect());
                $primaryPositionId = (int) (optional($deptHods->first())->Position_id ?? 0);

                ResortsChildNotifications::create([
                    'Parent_msg_id' => $parentmesgid,
                    'Department_id' => $deptId,
                    'Position_id'   => $primaryPositionId,
                    'response'      => 'No',
                ]);

                // Dispatch the in-app event to every HOD in this department.
                // Departments without an HOD will simply have no recipient
                // (still tracked as pending until the missing HOD is added).
                foreach ($deptHods as $hod) {
                    try {
                        event(new ResortNotificationEvent(
                            Common::nofitication($resort_id, $this->type[1], $parentmesgid, 0, '', $hod->id, 'WorkForce Planning')
                        ));
                        // Also write a persistent bell-tray row — Common::nofitication
                        // with type=2 only broadcasts via Pusher; it does NOT write
                        // resort_notifications, which is what the bell dropdown
                        // reads from. So HODs saw the Request widget on their
                        // dashboard but no bell entry. Mirror what the Disciplinary
                        // / Incident modules do.
                        ResortNotification::create([
                            'resort_id'  => $resort_id,
                            'user_id'    => $hod->id,
                            'module'     => 'WorkForce Planning',
                            'type'       => 'Manning Request',
                            'message'    => 'A new manning request requires your response.',
                            'status'     => 'unread',
                            'request_id' => $parentmesgid,
                        ]);
                    } catch (\Exception $notifErr) {
                        \Log::warning('Manning notification dispatch failed for HOD ' . $hod->id . ': ' . $notifErr->getMessage());
                    }
                }
            }

            // Also notify HR — they raised the request and need confirmation
            // it went out, plus a per-HR copy in the bell-tray.
            foreach ($hrEmployees as $hr) {
                try {
                    event(new ResortNotificationEvent(
                        Common::nofitication($resort_id, $this->type[1], $parentmesgid, 0, '', $hr->id, 'WorkForce Planning')
                    ));
                    ResortNotification::create([
                        'resort_id'  => $resort_id,
                        'user_id'    => $hr->id,
                        'module'     => 'WorkForce Planning',
                        'type'       => 'Manning Request',
                        'message'    => 'Manning request dispatched to department HODs.',
                        'status'     => 'unread',
                        'request_id' => $parentmesgid,
                    ]);
                } catch (\Exception $notifErr) {
                    \Log::warning('Manning notification dispatch failed for HR ' . $hr->id . ': ' . $notifErr->getMessage());
                }
            }

            DB::commit();
            $totalPendingResponse =ResortsChildNotifications::where('Parent_msg_id',$parentmesgid)->where("response","No")->groupBy('Department_id')->count();

            $PendingDepartmentResoponse=array();
            if(isset($parentmesgid))
            {
                $totalPendingResponse =ResortsChildNotifications::where("Parent_msg_id", $parentmesgid)->where("response","No")->groupBy('Department_id')->orderBy('created_at', 'desc')->get();
                $totalsendtoDepartment =ResortsChildNotifications::where("Parent_msg_id", $parentmesgid)->groupBy('Department_id')->orderBy('created_at', 'desc')->get();
                $ManningPendingRequestCount = count($totalsendtoDepartment);
                foreach($totalPendingResponse as $Dep)
                {
                    $PendingDepartmentResoponse[$Dep->id][]= $Dep->department->name;
                }
                $totalPendingResponse=count($totalPendingResponse);
            }
            else
            {
                $totalPendingResponse=0;
                $ManningPendingRequestCount=0;
            }

            $totalDepartmentscount= count($DepartmentIds);
            $HODpendingResponse=$totalPendingResponse;
            $totalDepartments=count($DepartmentIds);
            $view = view('resorts.renderfiles.HrRequestCardView',compact('ManningPendingRequestCount','PendingDepartmentResoponse','HODpendingResponse','totalDepartments', 'totalPendingResponse'))->render();

            $response['success'] = true;
            $response['html']= $view ;
            $response['msg'] = __('Message sent successfully');
            return response()->json($response);

        }
        catch (\Exception $e){
            DB::rollBack();
            \Log::error('Manning notification handler failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'msg'     => 'Failed to send manning request.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ]);
        }
    }

    public function ReminderRequestManning(Request $request)
    {
        $validator = Validator::make($request->all(), ['ManningReminderRequest' => 'required'],
        ['ManningReminderRequest.required' => 'Please Enter Your Manning Request Massage.',]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $ManningReminderRequest = $request->ManningReminderRequest;
        DB::beginTransaction();
        try{

            $resort = Auth::guard('resort-admin')->user();
            $resort_id = $resort->resort_id;
            $user_type = $resort->is_employee;

            $ManningPendingRequest =ResortsParentNotifications::where('resort_id',$resort_id)
                ->where('status','Active')
                //->where('user_id',$resort->id)
                ->orderBy('created_at', 'desc')
                ->first();


            if($ManningPendingRequest )
            {
                HrReminderRequestManning::create([
                    'message_id'=>  $ManningPendingRequest->message_id,
                    'reminder_message_subject'=>$ManningReminderRequest,
                ]);
                $notificationPayload = Common::nofitication($resort_id,$this->type[2],$ManningPendingRequest->message_id);
                event( new ResortNotificationEvent( $notificationPayload));

                // The broadcast above only reaches HODs who are actively on
                // the page at this exact moment (Pusher real-time, no
                // persistent row) — unlike the type==1 admin-notice path
                // (Common::fanOutAdminNotice), so a HOD who wasn't online
                // when this fired never saw the reminder at all. Write a
                // persistent bell-list row per pending department's HOD too.
                $pendingDeptIds = $notificationPayload['PendingDepartment_id'] ?? [];
                if (!empty($pendingDeptIds)) {
                    // rank 2 (HOD) and rank 1 (EXCOM) — a department headed
                    // by EXCOM with no HOD was never getting this persistent
                    // reminder row (the live broadcast above already covers
                    // both, see $hodsByDept).
                    $hods = Employee::where('resort_id', $resort_id)
                        ->where('status', 'Active')
                        ->whereIn('rank', [1, 2])
                        ->whereIn('Dept_id', $pendingDeptIds)
                        ->pluck('id');

                    foreach ($hods as $hodId) {
                        $alreadyNotified = ResortNotification::where('request_id', $ManningPendingRequest->message_id)
                            ->where('user_id', $hodId)
                            ->where('module', 'Manning Reminder')
                            ->exists();
                        if ($alreadyNotified) continue;

                        Common::sendMobileNotification(
                            $resort_id,
                            1,
                            null,
                            null,
                            'Manning Request Reminder',
                            $ManningReminderRequest,
                            'Manning Reminder',
                            [$hodId],
                            $ManningPendingRequest->message_id,
                            false,
                            'manning-request-reminder'
                        );
                    }
                }
            }
            else
            {
                $getNotifications =  (object)[];
                $HODpendingResponse = 0;
            }

            // DB::beginTransaction() above was never followed by a commit on
            // the success path — every write in this method (the original
            // HrReminderRequestManning::create() row AND the persistent
            // per-HOD ResortNotification rows added above) silently rolled
            // back when the request ended instead of actually persisting.
            // This is the real reason the reminder "still wasn't fixed" —
            // the notification logic itself was correct, nothing was ever
            // being saved to begin with.
            DB::commit();

            $response['success'] = true;
            $response['html']= '' ;
            $response['msg'] = __('Message sent successfully');
            return response()->json($response);
        }
        catch (\Exception $e){
            DB::rollBack();
            \Log::error('Manning notification handler failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'msg'     => 'Failed to send manning request.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ]);
        }
    }

    public function SendToFinance(Request $request)
    {
        $resort = Auth::guard('resort-admin')->user();
        $employeeRankPosition = Common::getEmployeeRankPosition($resort->getEmployee);
        $resort_id = $resort->resort_id;

        // C2 — no role check existed at all; any authenticated resort-admin
        // could hit this and forward every department's budget forward a
        // stage. Only HR/Finance/GM ever legitimately do this.
        if (!in_array($employeeRankPosition['position'], ['HR', 'Finance', 'GM'], true)) {
            return response()->json(['success' => false, 'msg' => 'Unauthorized access'], 403);
        }

        $typeofCommets = config('settings.manningRequestLifeCycle');
        // Always defined now (was left unset on the GM/else branches,
        // relying on PHP's undefined-variable-is-null fallback — harmless
        // as a value but threw a notice, and read again further down).
        $budgetProcessStatus = null;
        if($employeeRankPosition['position'] == 'HR') {
            $typeofCommet = $typeofCommets[1]; // "Reviewed by HR and Sent to Finance" — correct for this action
            $budgetProcessStatus = 'Finance';
        }elseif($employeeRankPosition['position'] == 'Finance') {
            // WP5 — was $typeofCommets[2] "Revise Budget", wrong text for
            // Finance forwarding to GM (that's index [3]).
            $typeofCommet = $typeofCommets[3];
            $budgetProcessStatus = 'GM';
        }elseif($employeeRankPosition['position'] == 'GM') {
            $typeofCommet = $typeofCommets[3];
            // GM has no "forward to next stage" here — GM IS the final
            // stage. Final approval is a separate, dedicated action
            // (BudgetController::approveBudget()), not this method.
        }else{
            $typeofCommet = "";
        }

        // Signature snapshot — HR sending to Finance, and Finance sending
        // to GM, are each themselves a real sign-off (this person reviewed
        // this department's budget and is forwarding it). Frozen ONCE for
        // the whole batch (same actor, same moment) — never re-derived
        // later from the live ResortAdmin.signature_img. GM's own final
        // approval is a separate action (BudgetController::approveBudget())
        // and isn't signed here.
        $signatureFields = in_array($employeeRankPosition['position'], ['HR', 'Finance'], true)
            ? Common::snapshotSignature($resort->id, 'budget-approval', $resort_id . '-' . $request->year . '-' . $employeeRankPosition['position'])
            : [];

        // WP7(D4) — was resort+year only, forwarding EVERY department's
        // EVERY category in one call regardless of which tab the user was
        // on, and with no server-side check that every department had
        // actually submitted (the button's disabled state on the Consolidated
        // Budget page was the only gate, and it was broken — see
        // BudgetController::viewConsolidatedBudget()).
        $employmentType = $request->input('employment_type', 'Permanent');
        $categoriesToSend = $employmentType === 'all' ? ['Permanent', 'Casual', 'Intern'] : [$employmentType];

        // WP7.3 — an inactive department still counted toward the "every
        // department must submit" total, which could make the gate
        // permanently unsatisfiable (or, if that department also happened
        // to have a stale manning row, silently satisfiable) for no
        // legitimate reason.
        $totalDepartments = ResortDepartment::where('resort_id', $resort_id)->where('status', 'active')->count();
        foreach ($categoriesToSend as $cat) {
            // WP7.1 — was counting ANY manning_responses row (draft or
            // submitted), so a department that had merely opened the tab
            // (auto-saved a draft) counted as "submitted", letting the send
            // proceed while real submissions were still missing.
            $submittedCount = ManningResponse::where('resort_id', $resort_id)
                ->where('year', $request->year)
                ->where('employment_type', $cat)
                ->where('status', 'submitted')
                ->distinct('dept_id')
                ->count('dept_id');
            if ($submittedCount < $totalDepartments) {
                return response()->json([
                    'success' => false,
                    'msg' => "Not every department has submitted their {$cat} budget for {$request->year} yet.",
                ]);
            }
        }

        DB::beginTransaction();
        try{
            // WP7.2 — same status filter as the gate above: a draft (or a
            // row ReviseBudget() sent back to draft — see A3) must never be
            // forwarded, even if some other department's row for the same
            // category happens to be submitted.
            $budgets = ManningResponse::where('resort_id', $resort_id)
                                    ->where('year', $request->year)
                                    ->whereIn('employment_type', $categoriesToSend)
                                    ->where('status', 'submitted')
                                    ->get();

                foreach ($budgets as $key => $budget) {
                    // $budgetProcessStatus is only ever set for the HR/
                    // Finance branches above — GM has no "forward to next
                    // stage" here (GM IS the final stage; that's
                    // approveBudget()), so this used to unconditionally
                    // write the GM branch's undefined variable (null),
                    // silently blanking an already-set status.
                    if ($budgetProcessStatus !== null) {
                        $budget->update([
                            'budget_process_status' => $budgetProcessStatus,
                        ]);
                    }

                    $BudgetStatus =BudgetStatus::create([
                        // WP5 — per-budget, not a single value resolved once
                        // outside the loop: this loop spans every
                        // department/category for the year, and each one has
                        // its own message_id chain traced back to its own
                        // "Respond to HR" submission.
                        'message_id'=>Common::resolveMessageIdForBudget($budget->id),
                        'Department_id'=>$budget->dept_id,
                        'Budget_id'=>$budget->id,
                        'resort_id'=>$resort_id,
                        'comments'=>$typeofCommet,
                        'signature_img' => $signatureFields['signature_img'] ?? null,
                        'signature_name' => $signatureFields['name'] ?? null,
                        'signed_at' => $signatureFields['timestamp'] ?? null,
                    ]);

                    // Was commented out — the old call only ever hit
                    // nofitication()'s broadcast-only branches (types 5/6/9,
                    // no ResortNotification row, no push), so even
                    // uncommented as-is it would never reach mobile or
                    // survive a page reload. Notify whoever the budget was
                    // just routed to, via the current recommended helper.
                    $recipientIds = match ($budgetProcessStatus ?? null) {
                        'Finance' => Common::getResortFinanceEmployeeIds($resort_id),
                        'GM' => Common::getResortGmEmployeeIds($resort_id),
                        default => [],
                    };
                    if (!empty($recipientIds)) {
                        try {
                            // F4 — same category/year fix as ReviseBudget()'s
                            // notification: a Finance/GM reviewer with more
                            // than one department's budget forwarded to them
                            // had no way to tell WHICH one this was about
                            // until they opened it.
                            $categoryLabel = $budget->employment_type ?? 'Permanent';
                            $yearLabel = $budget->year ?? '';
                            Common::notifyEmployees(
                                $resort_id,
                                $recipientIds,
                                'Budget Review Update',
                                "{$categoryLabel} budget {$yearLabel} has been forwarded to {$budgetProcessStatus} for review.",
                                'WorkForce Planning',
                                $budget->id
                            );
                        } catch (\Exception $notifErr) {
                            \Log::warning('SendToFinance notification failed for budget ' . $budget->id . ': ' . $notifErr->getMessage());
                        }
                    }
                }
                DB::commit();
                $response['success'] = true;
                $response['html']= '' ;
                $response['msg'] = __('Message sent successfully');
                return response()->json($response);
        }
        catch (\Exception $e){
            DB::rollBack();
            \Log::error('Manning notification handler failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'msg'     => 'Failed to send manning request.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ]);
        }


    }

    public function ReviseBudget(Request $request)
    {
        $resort = Auth::guard('resort-admin')->user();
        $employeeRankPosition = Common::getEmployeeRankPosition($resort->getEmployee);
        $resort_id = $resort->resort_id;
        $Budget_id = $request->budget_id;
        $Department_id =  $request->department_id;
        $revise_Comment = $request->ReviseBudgetComment;

        // C2 — this route had no role check at all; an HOD who knew (or
        // guessed) the endpoint could hit the BudgetStatus::create path
        // below with an empty $typeofCommet. Only HR/Finance/GM ever
        // legitimately revise a budget.
        if (!in_array($employeeRankPosition['position'], ['HR', 'Finance', 'GM'], true)) {
            return response()->json(['success' => false, 'msg' => 'Unauthorized access'], 403);
        }

        // C2/C3 — $Budget_id/$Department_id were client-supplied and never
        // checked against the caller's own resort: resolveMessageIdForBudget()
        // and the later ManningResponse::find() were both unscoped, so a
        // resort_id + budget_id pair belonging to ANOTHER resort would
        // still resolve and get revised. Load + verify once here.
        $manningResponse = \App\Models\ManningResponse::where('id', $Budget_id)
            ->where('resort_id', $resort_id)
            ->first();
        if (!$manningResponse || (int) $manningResponse->dept_id !== (int) $Department_id) {
            return response()->json(['success' => false, 'msg' => 'Budget not found for this resort.'], 404);
        }
        // WP5 — was config('settings.Notifications')[4|5|9], a flat array
        // of stringified numbers ("5", "6", "10"...) never meant to be an
        // id at all — writing that into budget_statuses.message_id broke
        // every downstream dashboard query joining back to
        // resorts_parent_notifications on message_id (0 rows, so the HOD
        // never saw the revision request). The real message_id is only
        // ever stored once, on the original "Respond to HR" row.
        $Message_id = Common::resolveMessageIdForBudget($Budget_id);
        $typeofCommets = config('settings.manningRequestLifeCycle');
        if($employeeRankPosition['position'] == 'HR') {
            // WP5 — was $typeofCommets[1] "Reviewed by HR and Sent to
            // Finance" on a status='Rejected' row — actively misleading;
            // HR is sending the budget BACK to the HOD, not forward.
            $typeofCommet = 'Sent back to department for revision';
            $budgetProcessStatus = 'Finance';
        }elseif($employeeRankPosition['position'] == 'Finance') {
            $typeofCommet = 'Sent back to department for revision';
            $budgetProcessStatus = 'GM';
        }elseif($employeeRankPosition['position'] == 'GM') {
            $typeofCommet = $typeofCommets[3];
            // $budgetProcessStatus = 'GM';
        }else{
            $typeofCommet = "";
        }

        DB::beginTransaction();
        try{
            // A3 — was commented out, so a revised category's
            // manning_responses.status stayed 'submitted'. saveDraft()'s
            // own guard (status !== 'draft' => skip) then dropped every
            // in-progress edit the HOD made on that category on the next
            // tab switch, and getCategoriesWithData() (status='draft' only)
            // never surfaced it for the multi-category resubmit either.
            // Flip it back to draft so the HOD can actually edit + resubmit.
            $manningResponse->update(['status' => 'draft']);

            if($employeeRankPosition['position'] != 'GM') {
                $BudgetStatus =BudgetStatus::create([
                    'message_id'=>$Message_id,
                    'Department_id'=>$Department_id,
                    'Budget_id'=>$Budget_id,
                    'resort_id'=>$resort_id,
                    'comments'=>$typeofCommet,
                    'OtherComments'=>$revise_Comment,
                    'status'=>'Rejected',
                ]);
            }

            // Was commented out — budget sent back for revision, the
            // department head who actually needs to act on it was never
            // told. Unlike SendToFinance (forwards to the next role), this
            // sends the budget back DOWN to whoever owns $Department_id —
            // same HOD/EXCOM lookup convention used elsewhere (org chart,
            // manning dispatch).
            try {
                $deptHead = Common::FindResortHODDepartment($resort_id, $Department_id);

                // WP5 — the message had no category/year, so an HOD with
                // more than one open manning request had no way to tell
                // WHICH one this bell entry was about until they opened
                // the popup. ($manningResponse already resort-scoped above.)
                $categoryLabel = $manningResponse->employment_type ?? 'Permanent';
                $yearLabel = $manningResponse->year ?? '';
                $pageId = \DB::table('module_pages')->where('internal_route', 'resort.workforceplan.hoddashboard')->value('id');

                Common::notifyEmployees(
                    $resort_id,
                    $deptHead ? [$deptHead->id] : [],
                    'Budget Sent Back for Revision',
                    "{$categoryLabel} budget {$yearLabel} has been sent back for revision" . ($revise_Comment ? (': ' . $revise_Comment) : '.'),
                    'WorkForce Planning',
                    $Budget_id,
                    $pageId
                );
            } catch (\Exception $notifErr) {
                \Log::warning('ReviseBudget notification failed for department ' . $Department_id . ': ' . $notifErr->getMessage());
            }

            DB::commit();
            $response['success'] = true;
            $response['html']= '' ;
            $response['msg'] = __('Message sent successfully');
            return response()->json($response);
        }
        catch (\Exception $e){
            DB::rollBack();
            \Log::error('Manning notification handler failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'msg'     => 'Failed to send manning request.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ]);
        }

    }
}
