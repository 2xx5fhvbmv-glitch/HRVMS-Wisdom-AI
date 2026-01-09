<?php

namespace App\Http\Controllers\Resorts\Performance;

use DB;
use URL;
use Auth;
use Carbon\Carbon;
use Validator;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\PeformanceMeeting;
use Illuminate\Http\Request;
use App\Mail\PerformanceMeetMail;
use Illuminate\Support\Facades\Mail;

use App\Http\Controllers\Controller;
use App\Models\PerformanceMeetingContent;
use App\Models\Resort;
class PerformanceMeetingController extends Controller
{


    public $resort='';
    protected $underEmp_id=[];

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $reporting_to = $this->globalUser->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }
    //
    public function index()
    {

        

        $employees = Employee::with(['resortAdmin', 'position'])
        ->where('resort_id', $this->resort->resort_id)
        ->get()
        ->map(function ($e)
        {
            $e->Emp_id = base64_encode($e->id);
            $e->positionName = $e->position->position_title;
            $e->profileImg = Common::getResortUserPicture(optional($e->resortAdmin)->id);
            $e->EmployeeName = optional($e->resortAdmin)->first_name . ' ' . optional($e->resortAdmin)->last_name;
            return $e;
        });

        $page_title="Performance Meeting";
        return view('resorts.Performance.Meeting.index',compact('page_title','employees'));
    }
   public function SendMeetingLink(Request $request)
{
    $validator = Validator::make($request->all(), [
        'title' => 'required|string|max:255',
        'date' => 'required|date',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i|after:start_time',
        'location' => 'required|string|max:255',
        'conference_link' => 'required|string|max:255',
        'description' => 'required|string|max:500',
        'Emp_id' => 'required|array|min:1',
    ], [
        'title.required' => 'Please provide a meeting title.',
        'date.required' => 'Please provide a meeting date.',
        'start_time.required' => 'Please provide a start time.',
        'end_time.required' => 'Please provide an end time.',
        'location.required' => 'Please provide a location.',
        'conference_link.required' => 'Please provide a conference link.',
        'description.required' => 'Please provide a description.',
        'Emp_id.required' => 'Please select at least one employee.',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        DB::beginTransaction();

        $meeting = PeformanceMeeting::create([
            'resort_id' => $this->resort->resort_id,
            'title' => $request->title,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'date' => Carbon::parse($request->date)->format('Y-m-d'),
            'location' => $request->location,
            'conference_links' => $request->conference_link,
            'description' => $request->description,
        ]);

        $content = PerformanceMeetingContent::where('resort_id', $this->resort->resort_id)->first();
        $emailTemplate = $content->content ?? '';

        $Your_Name = $this->resort->first_name . ' ' . $this->resort->last_name;
        $Designation = $this->resort->is_employee == 1
            ? optional($this->resort->GetEmployee->position)->position_title
            : 'GM';

        $resort_details = Resort::find($this->resort->resort_id);

        foreach ($request->Emp_id as $encodedId) {
            $employee = Employee::with(['resortAdmin', 'position'])->find(base64_decode($encodedId));

            if (!$employee || !$employee->resortAdmin) continue;

            $EmployeeName = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;

            $placeholders = [
                "{Employee_name}",
                "{Title}",
                "{Meeting_Date}",
                "{Meeting_Time}",
                "{Description}",
                "{Your_Name}",
                "{Your_Designation}",
                "{Resort_Name}",
                "{Meeting_Link}"
            ];

            $replacements = [
                $EmployeeName,
                $request->title,
                Carbon::parse($request->date)->format('d M Y'),
                $request->start_time . ' to ' . $request->end_time,
                $request->description,
                $Your_Name,
                $Designation,
                $resort_details->resort_name ?? 'Resort',
                $request->conference_link
            ];

            $finalBody = str_replace($placeholders, $replacements, $emailTemplate);
            Mail::to($employee->resortAdmin->email)->queue(
                new PerformanceMeetMail($request->title, $finalBody)
            );
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Meeting link sent successfully to employees.',
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        \Log::emergency("File: " . $e->getFile());
        \Log::emergency("Line: " . $e->getLine());
        \Log::emergency("Message: " . $e->getMessage());

        return response()->json(['error' => 'Failed to send meeting link.'], 500);
    }
}

    // public function SendMeetingLink(Request $request)
    // {

    //     $title = $request->title;
    //     $date  = $request->date;
    //     $start_time = $request->start_time;
    //     $end_time = $request->end_time;
    //     $date = $request->date;
    //     $location = $request->location;
    //     $conference_link = $request->conference_link;
    //     $description = $request->description;
    //     $Emp_id = $request->Emp_id;
    //     $validator = Validator::make($request->all(), [
    //         'title' => 'required|string|max:255',
    //         'date' => 'required',
    //         'start_time' => 'required|date_format:H:i',
    //         'end_time' => 'required|date_format:H:i', // Removed after:start_time
    //         'location' => 'required|string|max:255',
    //         'conference_link' => 'required|string|max:255', // Changed from url to string
    //         'description' => 'required|string|max:500',
    //         'Emp_id' => 'required|array|min:1',
    //     ], [
    //         'title.required' => 'Please provide a meeting title.',
    //         'date.required' => 'Please provide a meeting date.',
    //         'start_time.required' => 'Please provide a start time.',
    //         'end_time.required' => 'Please provide an end time.',
    //         'location.required' => 'Please provide a location.',
    //         'conference_link.required' => 'Please provide a conference link.',
    //         'description.required' => 'Please provide a description.',
    //         'Emp_id.required' => 'Please select at least one employee.',
    //     ]);

    //     // Check if validation fails

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }
    //     PeformanceMeeting::create([
    //         'resort_id'=>$this->resort->resort_id,
    //         'title'=>$title,
    //         'start_time'=>$start_time,
    //         'end_time'=>$end_time,
    //         'date'=>Carbon::parse($date)->format('Y-m-d'),
    //         'location'=>$location,
    //         'conference_links'=>$conference_link,
    //         'description'=>$description,
    //     ]);
    //     $content = PerformanceMeetingContent::where('resort_id',$this->resort->resort_id)->first();

    //     $body = $content->content;



    //     $Your_Name = $this->resort->first_name. '  '. $this->resort->last_name;
    //     $Designation='';
    //     if($this->resort->is_employee ==1)
    //     {
    //         $Designation =$this->resort->GetEmployee->position->position_title;
    //     }
    //     else
    //     {
    //         $Designation ='GM';
    //     }
    //     $resort_details =  Resort::where('id',  $this->resort->resort_id)->first();
       

    //         if(count($Emp_id)> 0)
    //         {
    //             foreach ($Emp_id as $key => $id)
    //             {
    //                 $employees = Employee::with(['resortAdmin', 'position'])->where('id',base64_decode($id))->first();
    //                 $EmployeeName = optional($employees->resortAdmin)->first_name . ' ' . optional($employees->resortAdmin)->last_name;
    //                 $data= [];

    //             $healthy = [
    //                 "{Employee_name}",
    //                 "{Title}",
    //                 "{Meeting_Date}",
    //                 "{Meeting_Time}",

    //                 "{Description}",
    //                 "{Your_Name}",
    //                 "{Your_Designation}",
    //                 "{Resort_Name}",
    //             ];

    //             $yummy = [
    //                 $EmployeeName,
    //                 $title,
    //                 $start_time .' to '. $end_time ,
    //                 $date,

    //                 $description,
    //                 $Your_Name,
    //                 $Designation,
    //                 $resort_details->resort_name
    //             ];
    //                 $emailTemplate =  $body;

    //                 $newbody = str_replace($healthy, $yummy, $emailTemplate);

    //                 Mail::to($employees->resortAdmin->email)->queue(new PerformanceMeetMail($title,$newbody));


    //             }

    //         }
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Meeting Link to employee Successfully',
    //         ], 200);
    //      try
    //     {}
    //     catch (\Exception $e)
    //     {
    //         DB::rollBack();
    //         \Log::emergency("File: " . $e->getFile());
    //         \Log::emergency("Line: " . $e->getLine());
    //         \Log::emergency("Message: " . $e->getMessage());
    //         return response()->json(['error' => 'Failed sent  Meeting Link'], 500);
    //     }
    // }

    public function GetPerformanceEmp(Request $request)
    {
        $searchValue= $request->searchValue;

        $department = $request->department;
        $position = $request->position;
        $employment_grade = $request->employment_grade;
        $employees = Employee::with(['resortAdmin', 'position'])
                                    ->where('resort_id', $this->resort->resort_id)
                                    ->where(function ($query) use ($searchValue) {
                                        $query->whereHas('resortAdmin', function ($q) use ($searchValue) {
                                            $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchValue}%"]);
                                        })
                                        ->orWhereHas('position', function ($q) use ($searchValue) {
                                            $q->where('position_title', 'LIKE', "%{$searchValue}%");
                                        });
                                    });
                                    if(isset($employment_grade))
                                    {
                                       $employees->where('rank',$employment_grade);
                                    }
                                    if(isset($department) && $department !="Department")
                                    {
                                       $employees->where('Dept_id',$department);
                                    }
                                    if(isset($position))
                                    {
                                       $employees->where('Position_id',$position);
                                    }
        $employees =  $employees ->distinct() // Ensures unique results
                                    ->get()
                                    ->map(function ($e) {
                                        $e->Emp_id = base64_encode($e->id);
                                        $e->positionName = optional($e->position)->position_title;
                                        $e->profileImg = Common::getResortUserPicture(optional($e->resortAdmin)->id);
                                        $e->EmployeeName = optional($e->resortAdmin)->first_name . ' ' . optional($e->resortAdmin)->last_name;
                                        return $e;
                                    });
            return response()->json([
                'success' => true,
                'data' =>$employees,
            ], 200);
    }
}
