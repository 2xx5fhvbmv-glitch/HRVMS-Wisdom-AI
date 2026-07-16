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
                    $hods = Employee::where('resort_id', $resort_id)
                        ->where('status', 'Active')
                        ->where('rank', 2)
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
        $notificationsType = config('settings.Notifications');
        $typeofCommets = config('settings.manningRequestLifeCycle');
        if($employeeRankPosition['position'] == 'HR') {
            $Message_id = $notificationsType[4];
            $typeofCommet = $typeofCommets[1];
            $budgetProcessStatus = 'Finance';
        }elseif($employeeRankPosition['position'] == 'Finance') {
            $Message_id = $notificationsType[5];
            $typeofCommet = $typeofCommets[2];
            $budgetProcessStatus = 'GM';
        }elseif($employeeRankPosition['position'] == 'GM') {
            $Message_id = $notificationsType[9];
            $typeofCommet = $typeofCommets[3];
            // $budgetProcessStatus = 'GM';
        }else{
            $Message_id = "";
            $typeofCommet = "";
        }
       
        DB::beginTransaction();
        try{
            $budgets = ManningResponse::where('resort_id', $resort_id)
                                    ->where('year', $request->year)
                                    ->get();

                foreach ($budgets as $key => $budget) {
                    $budget->update([
                        'budget_process_status' => $budgetProcessStatus,
                    ]);

                    $BudgetStatus =BudgetStatus::create([
                        'message_id'=>$Message_id,
                        'Department_id'=>$budget->dept_id,
                        'Budget_id'=>$budget->id,
                        'resort_id'=>$resort_id,
                        'comments'=>$typeofCommet,
                    ]);
                    // event( new ResortNotificationEvent(  Common::nofitication($resort_id, $this->type[4],$Message_id,$budget->id)));
                   
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
        $notificationsType = config('settings.Notifications');
        $typeofCommets = config('settings.manningRequestLifeCycle');
        if($employeeRankPosition['position'] == 'HR') {
            $Message_id = $notificationsType[4];
            $typeofCommet = $typeofCommets[1];
            $budgetProcessStatus = 'Finance';
        }elseif($employeeRankPosition['position'] == 'Finance') {
            $Message_id = $notificationsType[5];
            $typeofCommet = $typeofCommets[2];
            $budgetProcessStatus = 'GM';
        }elseif($employeeRankPosition['position'] == 'GM') {
            $Message_id = $notificationsType[9];
            $typeofCommet = $typeofCommets[3];
            // $budgetProcessStatus = 'GM';
        }else{
            $Message_id = "";
            $typeofCommet = "";
        }

        DB::beginTransaction();
        try{
            // ManningResponse::where([
            //     'id' => $Budget_id,
            //     'dept_id' => $Department_id,
            //     'resort_id' => $resort_id
            // ])->update([
            //     'budget_process_status' => 'Rejected'
            // ]);
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
            // event( new ResortNotificationEvent(  Common::nofitication($resort_id, $this->type[5],$Message_id,$Budget_id)));
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
