<?php

namespace App\Http\Controllers\Resorts\Performance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use URL;
use DB;
use Auth;
use Illuminate\Validation\Rule;
use Validator;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\ResortPosition;
use App\Models\LearningRequest;
use App\Models\LearningProgram;
use App\Models\MonthlyCheckingModel;
use App\Models\LearningRequestEmployee;

use App\Events\ResortNotificationEvent;

class MonthlyCheckingController extends Controller
{
    public $resort='';
    protected $underEmp_id=[];

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            $reporting_to = $this->globalUser->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }
    public function index(Request $request)
    {
        if(Common::checkRouteWisePermission('Performance.MonltyCheckIn',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        
        if($request->ajax())
        {
            $date_discussion = $request->date_discussion;
            $searchTerm = $request->searchTerm;
      
            $monthly = MonthlyCheckingModel::join("employees as t1", "t1.id", "=", "monthly_checking_models.emp_id")
            ->join("resort_admins as t2", "t2.id", "=", "t1.Admin_Parent_id")
            ->join("resort_positions as t3", "t3.id", "=", "t1.Position_id")
            ->leftjoin("learning_programs as t4", "t4.id", "=", "monthly_checking_models.tranining_id")
            ->where("t1.resort_id", $this->resort->resort_id)
            
            ->when($date_discussion, function ($query) use ($date_discussion) {
                try { 
                $convertedDate = date('Y-d-m', strtotime($date_discussion));
                $query->whereDate("monthly_checking_models.date_discussion", $convertedDate);
                
                  } catch (\Exception $e) {
                // Handle invalid date silently or log it
                }
            })
            
            ->when($searchTerm, function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw("CONCAT(t2.first_name, ' ', t2.last_name) LIKE ?", ["%{$searchTerm}%"])
                  ->orWhere("monthly_checking_models.Checkin_id", "like", "%{$searchTerm}%")
                  ->orWhere("monthly_checking_models.comment", "like", "%{$searchTerm}%")
                  ->orWhere("monthly_checking_models.Time_Line", "like", "%{$searchTerm}%")
                  ->orWhere("t3.position_title", "like", "%{$searchTerm}%");
        
                if (strtolower($searchTerm) === "yes") {
                    $q->orWhereNotNull("monthly_checking_models.tranining_id");
                }
        
                if (strtolower($searchTerm) === "no") {
                    $q->orWhereNull("monthly_checking_models.tranining_id");
                }
                });
            })
            
            ->orderBy("monthly_checking_models.id", "desc")
            ->select([
                't1.id as emp_orignal_id',
                't4.name as traniningname',
                't2.first_name',
                't2.last_name',
                't3.position_title as PositionName',
                'monthly_checking_models.*'
            ]);
            
            // Get the collection after executing the query
            $monthlyCollection = $monthly->get();
            
            // Process each record
            $processed = $monthlyCollection->map(function($ak) {
            if (isset($ak->tranining_id)) {
                $l = LearningRequest::with('employees')
                ->where("learning_id", $ak->tranining_id)
                ->where("resort_id", $this->resort->resort_id)
                ->whereHas('employees', function($q) use ($ak) {
                    $q->where('id', $ak->emp_orignal_id);
                })
                ->latest('id')
                ->first();
                
                $ak->duration = isset($l->start_date) && isset($l->end_date)
                ? $l->start_date . '-' . $l->end_date
                : '-';
        
                $ak->status = isset($l->status) && $l->status == 'Approved'
                ? 'In Progress'
                : (isset($l->status) ? $l->status : 'Pending');
            } else {
                $ak->duration = '-';
                $ak->status = 'Pending';
            }
            $ak->new_date_of_dicussion = date("d M Y",strtotime($ak->date_discussion));
            return $ak; 
            });
        
            return datatables()->of($processed)
            ->addIndexColumn()
            ->addColumn('ID', function($row) {
                return $row->Checkin_id;
            })
            ->addColumn('Name', function($row) {
                return $row->first_name.' '.$row->last_name;
            })
            ->addColumn('Position', function($row) {
                return $row->PositionName;
            })
            ->addColumn('Duration', function($row) {
                return $row->duration;
            })
            ->addColumn('Training', function($row) {
                return isset($row->tranining_id) ? 'Yes' : 'No';
            })
            ->addColumn('Date', function($row) {
            
                return $row->new_date_of_dicussion ;
               
            })
            ->addColumn('Time', function($row) {

         
                $formattedStart = Carbon::parse($row->start_time)->format('g:i A');
                $formattedEnd = Carbon::parse($row->end_time)->format('g:i A');
                return $formattedStart . ' - ' . $formattedEnd;
            })
            ->addColumn('Summary', function($row) {
                return $row->comment;
            })
            ->addColumn('Status', function($row) {
                return $row->status;
            })
            ->addColumn('Action', function($row) {
                $route = route('Performance.GetMonthlyCheckInDetails', base64_encode($row->id));
                $img = URL::asset('resorts_assets/images/history.svg');
                return '<a target="_blank" href="'.$route.'" class="btn-lg-icon icon-bg-blue" data-bs-toggle="tooltip" 
                data-bs-placement="bottom" title="" data-bs-original-title="View History" aria-label="View History">
                <img src="'.$img.'" alt="icon"></a>';
            })
            ->rawColumns(['ID','Name','Position','Duration','Date','Training','Time','Summary','Status','Action'])
            ->make(true);
        }
        $page_title="Monthly Check In";
        return view("resorts.Performance.MonthlyCheckIn.index", compact('page_title'));
    }
    public function create()
    {
        if(Common::checkRouteWisePermission('Performance.CreateMonltyCheckIn',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
        }
        
        $Employee  = Employee::join("resort_admins as t1","t1.id","=","employees.Admin_Parent_id")
                               ->join("resort_positions as t2","t2.id","=","employees.Position_id")
                               ->where("employees.resort_id", $this->resort->resort_id)
                                ->orderBy("employees.id","DESC")
                                ->get(['t1.id as ParentId','t1.last_name','t1.first_name','employees.*','t2.position_title as PositionName'])
                                ->map(function($ak)
                                {
                                    $ak->emp_id =  base64_encode($ak->id);
                                    $ak->profileImg = Common::getResortUserPicture(    $ak->ParentId);
                                    return $ak;
                                });
        $learningProgram = LearningProgram::where('resort_id', $this->resort->resort_id)->orderBy("id","desc")->get();
        $page_title="Create Monthly Check In";

        
        $trainingManagerTitles = ['Training Director', 'L&D Manager', 'Learning & Development Head'];

        // Get position IDs that match the titles in the current resort
        $positionIds = ResortPosition::where('resort_id',  $this->resort->resort_id)
                        ->whereIn('position_title', $trainingManagerTitles)
                        ->pluck('id'); // Get the position IDs
        $learningManagers = Employee::with(['resortAdmin','position'])->whereIn('Position_id', $positionIds)
                            ->where('resort_id',$this->resort->resort_id)
                            ->get();
        return view("resorts.Performance.MonthlyCheckIn.create", compact('learningManagers','page_title','Employee','learningProgram'));
    }
    
    public function GetEmployeeDetails(Request $request)
    {
        
        $search = $request->search;
        $Employee = Employee::join("resort_admins as t1", "t1.id", "=", "employees.Admin_Parent_id")
                            ->join("resort_positions as t2", "t2.id", "=", "employees.Position_id")
                            ->where("employees.resort_id", $this->resort->resort_id)
                            ->when($search, function ($query, $search) {
                                $query->where(function ($q) use ($search) {
                                    $q->where("t1.first_name", "like", "%$search%")
                                    ->orWhere("t1.last_name", "like", "%$search%")
                                    ->orWhere("employees.emp_id", "like", "%$search%")
                                    ->orWhere("t2.position_title", "like", "%$search%");
                                });
                            })
                            ->orderBy("employees.id", "DESC")
                            ->get([
                                't1.id as ParentId',
                                't1.last_name',
                                't1.first_name',
                                'employees.*',
                                't2.position_title as PositionName'
                            ])
                            ->map(function ($ak) {
                                $ak->emp_id = base64_encode($ak->id);
                                $ak->profileImg = Common::getResortUserPicture($ak->ParentId);
                                return $ak;
                            });
          $html = '';
        if ($Employee->isNotEmpty()) {
            // initialize string if you plan to use later
            foreach ($Employee as $e) {
                $html .= '<div class="d-flex Employee" 
                            data-id="' . $e->emp_id . '" 
                            data-profile="' . $e->profileImg . '"
                            data-position="' . $e->PositionName . '"
                            data-first_name="' . $e->first_name . '"
                            data-last_name="' . $e->last_name . '"
                            data-Emp_id="' . $e->Emp_id . '">
                            <div class="img-circle userImg-block"><img src="' . $e->profileImg . '" alt="user"></div>
                            <div>
                                <h6>' . $e->first_name . ' ' . $e->last_name . '</h6>
                                <p>' . $e->PositionName . '</p>
                            </div>
                        </div>';
            }
            // return or echo $html based on your context
        }
        else
        {
            $html .= '<div class="d-flex Employee">
                    <div class="img-circle userImg-block"></div>
                    <div>
                        <h6>No Record Found</h6>
                        <p></p>
                    </div>
                </div>';                
        }
        return response()->json([
            'success' => true,
            'data' =>$html,
        ], 200);
    }
    public function MonltyCheckInStore(Request $request)
    {
       
        $validator = Validator::make($request->all(), [
            'date_discussion' => 'required', // Ensure it's an array
            // 'tranining_id' => [
            //     'required',
            //     'max:50',
            //     Rule::unique('performance_review_types', 'tranining_id')->where(function ($query) use ($request) {
            //         return $query->where('resort_id', $this->resort->resort_id);
            //     }),
            // ],
            'Meeting_Place' => 'required',
            'Area_of_Discussion' => 'required',
            'Area_of_Improvement' => 'required',
            'Time_Line' => 'required',
            'emp_id' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'comment' => 'required|max:500',
            

            
        ], [
            'date_discussion.required' => 'Please Select Date of Discussion.',
            'start_time.required' => 'Start Time is required.',
            'end_time.required' => 'End Time is required.',
            'Meeting_Place' => 'Please enter meeting place.',
            'Area_of_Improvement'=> 'Please Enter Area of Imporvement.',
            'Time_Line.required' => 'Please Enter Time Line',
            'emp_id' => 'Please Select Employee ',
            'comment.required' => 'Please Enter Comment',
        ]);
        if($validator->fails())
        {
            return response()->json(['success' => false,'errors' => $validator->errors()], 422);
        }

        $e =Employee::where("resort_id",$this->resort->resort_id)->where("Emp_id",$request->emp_id)->first('id');
        DB::beginTransaction();
        try
        {
            MonthlyCheckingModel::create([
                "Checkin_id"=>Common::getMonthlyCheckIn(),
                "resort_id"=>$this->resort->resort_id,
                "start_time" =>$request->start_time, 
                "end_time" =>$request->end_time, 
                "date_discussion" =>date("Y-m-d", strtotime($request->date_discussion)), //$request->date_discussion, 
                "Meeting_Place" =>$request->Meeting_Place, 
                "Area_of_Discussion" =>$request->Area_of_Discussion, 
                "Area_of_Improvement" =>$request->Area_of_Improvement,
                "Time_Line" =>$request->Time_Line, 
                "comment" =>isset($request->comment) ?  $request->comment : null,
                "tranining_id" =>$request->tranining_id, 
                "emp_id" =>$e->id, 
            ]);
            if(isset($request->tranining_id))
            {
                $l = LearningRequest::create([
                                                "resort_id"=>$this->resort->resort_id,
                                                "learning_id" =>$request->tranining_id,
                                                'status'=>'Pending',
                                                "reason"=>$request->Area_of_Improvement,
                                                "learning_manager_id" => $request->learning_manager_id, 
                                            ]);

                    LearningRequestEmployee::create([
                        "employee_id" =>$e->id,
                        "learning_request_id" =>$l->id,               
                    ]);

            }
            $msg                                =   'Meeting scheduled by HR for Monthly Check-In. Subject: ' . ($request->Area_of_Improvement ?? $request->Area_of_Discussion);
            $title                              =   'Monthly check-in Meeting Scheduled';
            $ModuleName                         =   "Performance";
            event(new ResortNotificationEvent(Common::nofitication($this->resort->resort_id, 10,$title,$msg,0,$e->id,$ModuleName)));
            // $sendMobileNotification             =   Common::sendMobileNotification(
            //                                             $this->resort->resort_id,
            //                                             null,
            //                                             null,
            //                                             $title,
            //                                             $msg,
            //                                             $ModuleName,
            //                                             [$e->id],
            //                                             null,
            //                                         );
            DB::commit();
            return response()->json([
                                    'success' => true,
                                    'message' =>"Monthly Check in Stored successfully",
                                    'route'=> route('Performance.MonltyCheckIn'),
                                ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to add  Review Type'], 500);
        }
                        
    }
    public function GetMonthlyCheckInDetails($id)
    {
        if(Common::checkRouteWisePermission('Performance.MonltyCheckIn',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $id = base64_decode($id);
        $monthly =  MonthlyCheckingModel::join("employees as t1", "t1.id", "=", "monthly_checking_models.emp_id")
                                        ->join("resort_admins as t2", "t2.id", "=", "t1.Admin_Parent_id")
                                        ->join("resort_positions as t3", "t3.id", "=", "t1.Position_id")
                                        ->leftjoin("learning_programs as t4", "t4.id", "=", "monthly_checking_models.tranining_id")
                                        ->where("t1.resort_id", $this->resort->resort_id)
                                        ->where("monthly_checking_models.id", $id)
                                        ->orderBy("id","desc")
                                        ->first(['monthly_checking_models.id as Parent_m_id','t4.name as traniningname','t2.first_name','t2.last_name','t2.id as ParentId','t1.Emp_id as OrignalEmp_id','t3.position_title as PositionName','monthly_checking_models.*']);
        $monthly->profileImg = Common::getResortUserPicture($monthly->ParentId);
        $page_title="Monthly Check In Details";
        return view("resorts.Performance.MonthlyCheckIn.Details", compact('page_title','monthly'));
    }
    public function MonltyCheckInDetailsPageList(Request $request)
    {
   

        if($request->ajax())
        {   
            $id = base64_decode($request->Parent_id);
            $monthlyDetails = MonthlyCheckingModel::join("employees as t1", "t1.id", "=", "monthly_checking_models.emp_id")
                                                    ->join("resort_admins as t2", "t2.id", "=", "t1.Admin_Parent_id")
                                                    ->join("resort_positions as t3", "t3.id", "=", "t1.Position_id")
                                                    ->leftjoin("learning_programs as t4", "t4.id", "=", "monthly_checking_models.tranining_id")
                                                    ->where("t1.resort_id", $this->resort->resort_id)
                                                    ->where("monthly_checking_models.id", $id)
                                                    ->orderBy("id","desc")
                                                    ->get(['t1.id as emp_orignal_id','t4.name as traniningname','t2.first_name','t2.last_name','t3.position_title as PositionName','monthly_checking_models.*'])
                                                    ->map(function($ak)
                                                    {
                                                        if(isset($ak->tranining_id))
                                                        {
                                                            $l = LearningRequest::with('employees')
                                                                                ->where("learning_id", $ak->tranining_id)
                                                                                ->whereHas("employees", function ($q) use ($ak) {
                                                                                    $q->where("employee_id", $ak->emp_orignal_id);
                                                                                })
                                                                                ->where("resort_id", $this->resort->resort_id)
                                                                                ->latest('id')
                                                                                ->first();
                                                        
                                                            $ak->duration = isset($l->start_date) && isset($l->end_date) ?   $l->start_date.'-'. $l->end_date :' ';
                                                            if(isset($l->status) && $l->status == 'Approved')
                                                            {
                                                                $ak->status  = 'In Progress';
                                                            }
                                                            else
                                                            {
                                                                $ak->status  = isset($l->status) ? $l->status: 'Pending';
                                                            }
                                                        }
                                                        else
                                                        {
                                                            $ak->duration ='-';
                                                            $ak->status  ='Pending';
                                                        }
                                                        return $ak; 
                                                    });

                                                    return datatables()->of($monthlyDetails)
                                                    
                                                    ->editColumn('DateOfDisussion', function($row)  {
                                                        return date('d/m/Y',strtotime ($row->date_discussion));
                                                    })
                                                     ->editColumn('Time', function($row)  {
                                                        $formattedStart = Carbon::parse($row->start_time)->format('g:i A');
                                                        $formattedEnd = Carbon::parse($row->end_time)->format('g:i A');
                                                        return $formattedStart . ' - ' . $formattedEnd;
                                                     })
                                                    ->editColumn('AreaOfImprovement', function($row)  {
                                                        return $row->Area_of_Improvement;
                                                     })
                                                     ->editColumn('AreaOfDiscussion', function($row)  {
                                                        return $row->Area_of_Discussion;
                                                     })
                                                     ->editColumn('Comment', function($row)  {
                                                        return $row->comment;
                                                     })
                                                     
                                                     ->editColumn('TimeLine', function($row)  {
                                                        return $row->Time_Line;
                                                     })
                                               
                                                     ->editColumn('Training', function($row)  {
                                
                                                        return  isset($row->tranining_id) ?  $row->traniningname: '-';
                                                     })
                                                     ->editColumn('Duration', function($row)  {
                                                        return $row->duration;
                                                     })
                                                    ->editColumn('Status', function($row) {
                                                        return $row->status;
                                                    })
                                             
                                        
                                                    ->rawColumns(['DateOfDisussion','Time','AreaOfDiscussion','AreaOfImprovement','Comment','TimeLine','Training','Duration','Status']) 
                                                    ->make(true);
        }            
    }
}
