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

        $employee = Employee::where('resort_id', $resort_id)
            ->where('Rank', 2)  // two  no means HOD or Manager
            ->whereIn('Dept_id',$DepartmentIds)
            ->get(['Admin_Parent_id','id','Rank','Dept_id','Position_id']);
        DB::beginTransaction();
        try{
            $inactiveMsgId = ResortsParentNotifications::where('resort_id', $resort_id)
                ->where('user_id', $resort->id)
                ->update(['status' => 'Inactive']);

            $parentNotification= ResortsParentNotifications::create([
                'resort_id'=>$resort_id,
                'user_type'=>($user_type== 0) ? 'super':'sub',
                'user_id'=>$resort->id,
                'message_id'=>  $message_id,
                'message_subject'=>$manningRequest,
            ]);
            $parentmesgid= $parentNotification->message_id;
            foreach($employee as $key => $value)
            {
                $parentNotification =ResortsChildNotifications::create([
                    'Parent_msg_id'=>$parentmesgid,
                    'Department_id'=>$value->Dept_id,
                    'Position_id'=>$value->Position_id,
                    'response'=>  'No',
                ]);
// event( new ResortNotificationEvent(  Common::nofitication($resort_id, $this->type[1],$parentmesgid,0,0,$value->id,"WorkForce Planning")));
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
            $response['success'] = false;
            $response['msg'] = __('Somthing Wrong ', ['name' => 'Wrong']);
            return response()->json($response);
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
                event( new ResortNotificationEvent( Common::nofitication($resort_id,$this->type[2],$ManningPendingRequest->message_id)));
            }
            else
            {
                $getNotifications =  (object)[];
                $HODpendingResponse = 0;
            }
            $response['success'] = true;
            $response['html']= '' ;
            $response['msg'] = __('Message sent successfully');
            return response()->json($response);
        }
        catch (\Exception $e){
            DB::rollBack();
            $response['success'] = false;
            $response['msg'] = __('Somthing Wrong ', ['name' => 'Wrong']);
            return response()->json($response);
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
            $response['success'] = false;
            $response['msg'] = __('Somthing Wrong ', ['name' => 'Wrong']);
            return response()->json($response);
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
            $response['success'] = false;
            $response['msg'] = __('Somthing Wrong ', ['name' => 'Wrong']);
            return response()->json($response);
        }

    }
}
