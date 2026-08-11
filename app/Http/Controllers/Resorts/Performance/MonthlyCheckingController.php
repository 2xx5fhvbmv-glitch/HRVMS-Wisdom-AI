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
            $reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id : 3;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }

    /**
     * Parse user-submitted date (dd/mm/yyyy preferred, falls back to Carbon::parse).
     * Returns Y-m-d string or null on failure.
     */
    private function parseDate($raw)
    {
        if (empty($raw)) return null;
        try {
            return Carbon::createFromFormat('d/m/Y', $raw)->format('Y-m-d');
        } catch (\Exception $e) {}
        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Exception $e) {}
        return null;
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

            $scopedIds = Common::getPerformanceScopedEmpIds();
            $monthly = MonthlyCheckingModel::join("employees as t1", "t1.id", "=", "monthly_checking_models.emp_id")
            ->join("resort_admins as t2", "t2.id", "=", "t1.Admin_Parent_id")
            ->join("resort_positions as t3", "t3.id", "=", "t1.Position_id")
            ->leftjoin("learning_programs as t4", "t4.id", "=", "monthly_checking_models.tranining_id")
            ->where("t1.resort_id", $this->resort->resort_id)
            ->when(is_array($scopedIds), function ($query) use ($scopedIds) {
                $query->whereIn("monthly_checking_models.emp_id", $scopedIds);
            })

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
                't1.Emp_id as employee_code',
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
            $ak->new_date_of_dicussion = $ak->date_discussion ? date('d M Y', strtotime($ak->date_discussion)) : '-';
            return $ak;
            });

            return datatables()->of($processed)
            ->addIndexColumn()
            ->addColumn('ID', function($row) {
                return $row->employee_code ?: $row->Checkin_id;
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
                if (empty($row->start_time) || empty($row->end_time)) return '-';
                try {
                    $formattedStart = Carbon::parse($row->start_time)->format('g:i A');
                    $formattedEnd = Carbon::parse($row->end_time)->format('g:i A');
                    return $formattedStart . ' - ' . $formattedEnd;
                } catch (\Exception $e) {
                    return '-';
                }
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

        $scopedIds = Common::getPerformanceScopedEmpIds();
        $Employee  = Employee::join("resort_admins as t1","t1.id","=","employees.Admin_Parent_id")
                               ->join("resort_positions as t2","t2.id","=","employees.Position_id")
                               ->where("employees.resort_id", $this->resort->resort_id)
                               ->when(is_array($scopedIds), fn($q) => $q->whereIn('employees.id', $scopedIds))
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
        $scopedIds = Common::getPerformanceScopedEmpIds();
        $Employee = Employee::join("resort_admins as t1", "t1.id", "=", "employees.Admin_Parent_id")
                            ->join("resort_positions as t2", "t2.id", "=", "employees.Position_id")
                            ->where("employees.resort_id", $this->resort->resort_id)
                            ->when(is_array($scopedIds), fn($q) => $q->whereIn('employees.id', $scopedIds))
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
            'learning_manager_id' => 'required_with:tranining_id',
        ], [
            'learning_manager_id.required_with' => 'Please select a Learning Manager when a training is chosen.',
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
                "date_discussion" => $this->parseDate($request->date_discussion),
                "Meeting_Place" =>$request->Meeting_Place,
                "Area_of_Discussion" =>$request->Area_of_Discussion,
                "Area_of_Improvement" =>$request->Area_of_Improvement,
                "Time_Line" =>$request->Time_Line,
                "comment" =>isset($request->comment) ?  $request->comment : null,
                "tranining_id" =>$request->tranining_id,
                "emp_id" =>$e->id,
            ]);
            if(!empty($request->tranining_id) && !empty($request->learning_manager_id))
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
        $scopedIds = Common::getPerformanceScopedEmpIds();
        $monthly =  MonthlyCheckingModel::join("employees as t1", "t1.id", "=", "monthly_checking_models.emp_id")
                                        ->join("resort_admins as t2", "t2.id", "=", "t1.Admin_Parent_id")
                                        ->join("resort_positions as t3", "t3.id", "=", "t1.Position_id")
                                        ->leftjoin("learning_programs as t4", "t4.id", "=", "monthly_checking_models.tranining_id")
                                        ->where("t1.resort_id", $this->resort->resort_id)
                                        ->where("monthly_checking_models.id", $id)
                                        ->when(is_array($scopedIds), fn($q) => $q->whereIn('monthly_checking_models.emp_id', $scopedIds))
                                        ->orderBy("id","desc")
                                        ->first(['monthly_checking_models.id as Parent_m_id','t4.name as traniningname','t2.first_name','t2.last_name','t2.id as ParentId','t1.Emp_id as OrignalEmp_id','t3.position_title as PositionName','monthly_checking_models.*']);
        if (!$monthly) {
            abort(403, 'You do not have access to this check-in.');
        }
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
                                                        return $row->date_discussion ? date('d M Y', strtotime($row->date_discussion)) : '-';
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

    /**
     * Stage 1 — HR schedules a monthly check-in request with only the first 4 fields.
     * Status stays 'pending' until the selected employee approves or rejects.
     */
    public function scheduleRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'emp_id'          => 'required',
            'date_discussion' => 'required',
            'start_time'      => 'required',
            'end_time'        => 'required',
            'Meeting_Place'   => 'required|max:255',
        ], [
            'emp_id.required'          => 'Please select an employee.',
            'date_discussion.required' => 'Please select the date of discussion.',
            'start_time.required'      => 'Start time is required.',
            'end_time.required'        => 'End time is required.',
            'Meeting_Place.required'   => 'Please enter the meeting place.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $e = Employee::where('resort_id', $this->resort->resort_id)->where('Emp_id', $request->emp_id)->first();
        if (!$e) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        DB::beginTransaction();
        try {
            $checkin = MonthlyCheckingModel::create([
                'Checkin_id'       => Common::getMonthlyCheckIn(),
                'resort_id'        => $this->resort->resort_id,
                'emp_id'           => $e->id,
                'date_discussion'  => $this->parseDate($request->date_discussion),
                'start_time'       => $request->start_time,
                'end_time'         => $request->end_time,
                'Meeting_Place'    => $request->Meeting_Place,
                'approval_status'  => 'pending',
            ]);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            \Log::emergency('scheduleRequest File: '.$ex->getFile());
            \Log::emergency('scheduleRequest Line: '.$ex->getLine());
            \Log::emergency('scheduleRequest Message: '.$ex->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to schedule request'], 500);
        }

        // Notifications outside transaction — don't fail the request if the push service is down
        try {
            $title      = 'Monthly Check-In Approval Required';
            $formatted = $this->parseDate($request->date_discussion);
            $msg        = 'A monthly check-in meeting has been scheduled with you on '.($formatted ? date('d M Y', strtotime($formatted)) : $request->date_discussion).' at '.$request->start_time.'. Please approve or reject.';
            $ModuleName = 'Performance';

            event(new ResortNotificationEvent(
                Common::nofitication($this->resort->resort_id, 10, $title, $msg, $checkin->id, $e->id, $ModuleName)
            ));
            // skipDbInsert=true — nofitication() above already wrote the row.
            Common::sendMobileNotification(
                $this->resort->resort_id, 2, null, null, $title, $msg, $ModuleName, [$e->id], $checkin->id, true, 'monthly-checkin-scheduled'
            );
        } catch (\Exception $ne) {
            \Log::warning('Monthly check-in schedule notification failed: '.$ne->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Check-in request sent to employee for approval.',
            'checkin' => [
                'id'               => $checkin->id,
                'Checkin_id'       => $checkin->Checkin_id,
                'date_discussion'  => $checkin->date_discussion,
                'start_time'       => $checkin->start_time,
                'end_time'         => $checkin->end_time,
                'Meeting_Place'    => $checkin->Meeting_Place,
            ],
        ]);
    }

    /**
     * Returns rows the HR can pick up for stage-2 (approved but not yet finalized).
     */
    public function approvedList(Request $request)
    {
        $rows = MonthlyCheckingModel::with('employee.resortAdmin', 'employee.position')
            ->where('resort_id', $this->resort->resort_id)
            ->where('approval_status', 'approved')
            ->whereNull('finalized_at')
            ->orderByDesc('approved_at')
            ->limit(30)
            ->get()
            ->map(function ($row) {
                return [
                    'id'              => $row->id,
                    'Checkin_id'      => $row->Checkin_id,
                    'emp_id'          => $row->employee->Emp_id ?? '',
                    'emp_name'        => $row->employee->resortAdmin->full_name ?? '',
                    'emp_position'    => $row->employee->position->position_title ?? '',
                    'emp_photo'       => Common::getResortUserPicture($row->employee->Admin_Parent_id ?? null),
                    'date_discussion' => $row->date_discussion,
                    'start_time'      => $row->start_time,
                    'end_time'        => $row->end_time,
                    'Meeting_Place'   => $row->Meeting_Place,
                    'approved_at'     => $row->approved_at,
                ];
            });

        return response()->json(['success' => true, 'data' => $rows]);
    }

    /**
     * Employee approves an in-flight check-in request.
     */
    public function employeeApprove(Request $request, $id)
    {
        $checkin = MonthlyCheckingModel::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$checkin) {
            return response()->json(['success' => false, 'message' => 'Check-in not found'], 404);
        }

        $authEmpId = optional($this->resort->GetEmployee)->id;
        if ($authEmpId && $checkin->emp_id != $authEmpId) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to approve this request'], 403);
        }

        if ($checkin->approval_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This request has already been '.$checkin->approval_status], 422);
        }

        $checkin->approval_status = 'approved';
        $checkin->approved_at = now();
        $checkin->save();

        $title      = 'Monthly Check-In Approved';
        $msg        = ($this->resort->full_name ?? 'Employee').' has approved the monthly check-in scheduled on '.date('d M Y', strtotime($checkin->date_discussion)).'.';
        $ModuleName = 'Performance';

        event(new ResortNotificationEvent(
            Common::nofitication($this->resort->resort_id, 10, $title, $msg, $checkin->id, $checkin->created_by, $ModuleName)
        ));
        Common::sendMobileNotification(
            $this->resort->resort_id, 2, null, null, $title, $msg, $ModuleName, [$checkin->created_by], $checkin->id, true, 'monthly-checkin-approved'
        );

        return response()->json(['success' => true, 'message' => 'Check-in approved']);
    }

    /**
     * Employee rejects an in-flight check-in request with a reason.
     */
    public function employeeReject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'Please provide a reason for rejection.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $checkin = MonthlyCheckingModel::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$checkin) {
            return response()->json(['success' => false, 'message' => 'Check-in not found'], 404);
        }

        $authEmpId = optional($this->resort->GetEmployee)->id;
        if ($authEmpId && $checkin->emp_id != $authEmpId) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to reject this request'], 403);
        }

        if ($checkin->approval_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This request has already been '.$checkin->approval_status], 422);
        }

        $checkin->approval_status = 'rejected';
        $checkin->rejected_at = now();
        $checkin->employee_rejection_reason = $request->reason;
        $checkin->save();

        $title      = 'Monthly Check-In Rejected';
        $msg        = ($this->resort->full_name ?? 'Employee').' has rejected the monthly check-in. Reason: '.$request->reason;
        $ModuleName = 'Performance';

        event(new ResortNotificationEvent(
            Common::nofitication($this->resort->resort_id, 10, $title, $msg, $checkin->id, $checkin->created_by, $ModuleName)
        ));
        Common::sendMobileNotification(
            $this->resort->resort_id, 2, null, null, $title, $msg, $ModuleName, [$checkin->created_by], $checkin->id, true, 'monthly-checkin-rejected'
        );

        return response()->json(['success' => true, 'message' => 'Check-in rejected']);
    }

    /**
     * Stage 2 — HR finalizes an approved check-in by filling the remaining fields.
     */
    public function finalize(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'Area_of_Discussion'  => 'required',
            'Area_of_Improvement' => 'required',
            'Time_Line'           => 'required',
            'comment'             => 'required|max:500',
            'learning_manager_id' => 'required_with:tranining_id',
        ], [
            'learning_manager_id.required_with' => 'Please select a Learning Manager when a training is chosen.',
            'Area_of_Discussion.required'       => 'Please enter area of discussion.',
            'Area_of_Improvement.required'      => 'Please enter area of improvement.',
            'Time_Line.required'                => 'Please enter timeline.',
            'comment.required'                  => 'Please enter comment.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $checkin = MonthlyCheckingModel::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$checkin) {
            return response()->json(['success' => false, 'message' => 'Check-in not found'], 404);
        }
        if ($checkin->approval_status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Check-in must be approved before it can be finalized.'], 422);
        }
        if ($checkin->finalized_at) {
            return response()->json(['success' => false, 'message' => 'Check-in already submitted.'], 422);
        }

        DB::beginTransaction();
        try {
            $checkin->update([
                'Area_of_Discussion'  => $request->Area_of_Discussion,
                'Area_of_Improvement' => $request->Area_of_Improvement,
                'Time_Line'           => $request->Time_Line,
                'comment'             => $request->comment,
                'tranining_id'        => $request->tranining_id ?: null,
                'finalized_at'        => now(),
            ]);

            if (!empty($request->tranining_id) && !empty($request->learning_manager_id)) {
                $l = LearningRequest::create([
                    'resort_id'           => $this->resort->resort_id,
                    'learning_id'         => $request->tranining_id,
                    'status'              => 'Pending',
                    'reason'              => $request->Area_of_Improvement,
                    'learning_manager_id' => $request->learning_manager_id,
                ]);
                LearningRequestEmployee::create([
                    'employee_id'         => $checkin->emp_id,
                    'learning_request_id' => $l->id,
                ]);
            }

            $title      = 'Monthly Check-In Submitted';
            $msg        = 'Your monthly check-in for '.date('d M Y', strtotime($checkin->date_discussion)).' has been recorded.';
            $ModuleName = 'Performance';
            event(new ResortNotificationEvent(
                Common::nofitication($this->resort->resort_id, 10, $title, $msg, $checkin->id, $checkin->emp_id, $ModuleName)
            ));
            Common::sendMobileNotification(
                $this->resort->resort_id, 2, null, null, $title, $msg, $ModuleName, [$checkin->emp_id], $checkin->id, true, 'monthly-checkin-finalized'
            );

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Monthly check-in submitted successfully.',
                'route'   => route('Performance.MonltyCheckIn'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('finalize File: '.$e->getFile());
            \Log::emergency('finalize Line: '.$e->getLine());
            \Log::emergency('finalize Message: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to submit check-in.'], 500);
        }
    }

    /**
     * View All page — finalized check-ins history.
     */
    public function history()
    {
        $page_title = 'Monthly Check-In History';
        return view('resorts.Performance.MonthlyCheckIn.history', compact('page_title'));
    }

    public function historyData(Request $request)
    {
        $scopedIds = Common::getPerformanceScopedEmpIds();
        $query = MonthlyCheckingModel::with('employee.resortAdmin', 'employee.position')
            ->where('resort_id', $this->resort->resort_id)
            ->when(is_array($scopedIds), fn($q) => $q->whereIn('emp_id', $scopedIds))
            ->orderByDesc('created_at');

        return datatables()->of($query)
            ->addColumn('employee', function ($row) {
                $name = $row->employee->resortAdmin->full_name ?? '';
                $pos  = $row->employee->position->position_title ?? '';
                return '<div class="tableUser-block"><span class="userApplicants-btn">'.e($name).'</span><div class="text-muted small">'.e($pos).'</div></div>';
            })
            ->addColumn('date', fn($row) => $row->date_discussion ? date('d M Y', strtotime($row->date_discussion)) : '-')
            ->addColumn('time', fn($row) => $row->start_time.' - '.$row->end_time)
            ->addColumn('meeting_place', fn($row) => e($row->Meeting_Place))
            ->addColumn('area', fn($row) => e($row->Area_of_Discussion) ?: '-')
            ->addColumn('improvement', fn($row) => e($row->Area_of_Improvement) ?: '-')
            ->addColumn('status_badge', function ($row) {
                // Reflects every stage of the real workflow (schedule ->
                // employee approve/reject -> HR finalize) — previously
                // hardcoded to "Submitted" for every row, which only ever
                // matched because the query itself was silently restricted
                // to finalized rows only.
                if ($row->approval_status === 'rejected') {
                    return '<span class="badge badge-themeDanger">Rejected</span>';
                }
                if ($row->finalized_at) {
                    return '<span class="badge badge-themeSuccess">Submitted</span>';
                }
                if ($row->approval_status === 'approved') {
                    return '<span class="badge badge-themeInfo">Approved - Awaiting Submission</span>';
                }
                return '<span class="badge badge-themeWarning">Pending Approval</span>';
            })
            ->addColumn('action', function ($row) {
                // GetMonthlyCheckInDetails() base64-decodes the id (every
                // other caller of this route already base64_encode()s it) —
                // this one passed the raw id, so the decoded garbage never
                // matched a row and every "View" from History 403'd.
                return '<a href="'.route('Performance.GetMonthlyCheckInDetails', base64_encode($row->id)).'" class="btn btn-theme btn-sm">View</a>';
            })
            ->rawColumns(['employee', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Employee-facing — page showing pending check-in requests for logged-in employee.
     */
    public function employeePending()
    {
        $page_title = 'Pending Monthly Check-In Approvals';
        $empId = optional($this->resort->GetEmployee)->id;
        $pending = collect();
        if ($empId) {
            $pending = MonthlyCheckingModel::where('resort_id', $this->resort->resort_id)
                ->where('emp_id', $empId)
                ->where('approval_status', 'pending')
                ->orderByDesc('created_at')
                ->get();
        }
        return view('resorts.Performance.MonthlyCheckIn.employee-pending', compact('page_title', 'pending'));
    }
}
