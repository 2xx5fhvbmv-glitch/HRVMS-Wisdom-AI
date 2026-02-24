<?php

namespace App\Http\Controllers\Resorts\TimeAndAttendance;
use DB;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\ShiftSettings;
use App\Models\ParentAttendace;
use App\Models\LeaveCategory;
use App\Models\EmployeeLeave;
use App\Models\ResortDepartment;
use App\Imports\ImportAttandance;
use App\Http\Controllers\Controller;
use App\Jobs\ImportAttandanceJob;
use App\Exports\AttendanceTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Validator;
use App\Models\ResortHoliday;
use App\Models\EmployeeOvertime;
class AttandanceRegisterController extends Controller
{
    protected $resort;
    protected $underEmp_id=[];

    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        $reporting_to = $this->resort->GetEmployee->id ?? null;
        $this->underEmp_id = Common::getSubordinates($reporting_to);

        
    }

    /**
     * Get public holidays for a resort (including all Fridays)
     * Returns array of dates in Y-m-d format
     */
    private function getPublicHolidays($resort_id, $startDate = null, $endDate = null)
    {
        $publicHolidays = [];

        // Get public holidays from database
        $holidays = ResortHoliday::where('resort_id', $resort_id)
            ->whereNotNull('PublicHolidaydate')
            ->get(['PublicHolidaydate']);

        foreach ($holidays as $holiday) {
            if ($holiday->PublicHolidaydate) {
                $date = Carbon::parse($holiday->PublicHolidaydate)->format('Y-m-d');
                $publicHolidays[] = $date;
            }
        }

        // Add all Fridays of the year
        $currentYear = $startDate ? Carbon::parse($startDate)->year : Carbon::now()->year;
        $start = $startDate ? Carbon::parse($startDate) : Carbon::create($currentYear, 1, 1);
        $end = $endDate ? Carbon::parse($endDate) : Carbon::create($currentYear, 12, 31);

        $currentDate = $start->copy();
        while ($currentDate->lte($end)) {
            if ($currentDate->isFriday()) {
                $fridayDate = $currentDate->format('Y-m-d');
                if (!in_array($fridayDate, $publicHolidays)) {
                    $publicHolidays[] = $fridayDate;
                }
            }
            $currentDate->addDay();
        }

        return $publicHolidays;
    }
    public function index()
    {
       $page_title = 'Attandance Register';

        //    ResortBenifitGridChild::updateorcreate([
        //    ]);
       $Rank =  $this->resort->GetEmployee->rank ?? '';

        $ResortDepartment =  ResortDepartment::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();


        $WeekstartDate = Carbon::now()->startOfWeek(); //Week start Start date
        $WeekendDate = Carbon::now()->endOfWeek();
        $headers = [];
        $numberOfDays = 7;
        $days = [];
        for ($i = 0; $i < $numberOfDays; $i++)
        {
            $currentDate = $WeekstartDate->clone()->addDays($i);
            $headers[] = [
                'date' => $currentDate->format('d M'),
                'day' => $currentDate->format('D'),
                'newdate'=> $currentDate->format('Y-m-d'),
                'full_date' => $currentDate
            ];
            $days[] =$currentDate->format('D');
        }
        $attandanceregister = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                            ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                            ->join('duty_rosters as t3', "t3.Emp_id", "=", "employees.id")
                            ->leftjoin('parent_attendaces as t4', "t4.resort_id", "=", "t3.id")
                            ->select(
                                't1.id as Parentid',
                                't1.first_name',
                                't1.last_name',
                                't1.profile_picture',
                                'employees.id as emp_id',
                                'employees.Emp_id as EmployeeId',
                                't2.position_title',
                                't3.id as duty_roster_id'
                            )
                            ->groupBy('employees.id')
                            ->where("t1.resort_id", $this->resort->resort_id);

                              if($Rank != '3'){
                $attandanceregister->whereIn('employees.id', $this->underEmp_id);
            }
                        // Get the paginated results before mapping
                        $paginatedResults = $attandanceregister->paginate(10);

                        // Transform the paginated collection
                        $paginatedResults->getCollection()->transform(function ($item) {
                            $item->EmployeeName = ucfirst($item->first_name . ' ' . $item->last_name);
                            $item->Position = ucfirst($item->position_title);
                            $item->profileImg = Common::getResortUserPicture($item->Parentid);
                            return $item;
                        });

                        $attandanceregister = $paginatedResults;

                    $year = now()->year; // Current year
                    $month = now()->month; // Current month
                    $totalDays = Carbon::createFromDate($year, $month, 1)->daysInMonth; //


                    $monthwiseheaders=[];
                    for ($day = 1; $day <= $totalDays; $day++)
                    {
                        $date = Carbon::createFromDate($year, $month, $day); // Create a date for each day
                        $dayName = $date->format('D'); // Get the day name (e.g., Mon, Tue)
                        $newdate = $date->format('d-m-Y');
                        $monthwiseheaders[] = ["day"=>str_pad($day, 2, '0', STR_PAD_LEFT),"dayname" => $dayName,'newdate'=>$newdate];
                    }
                    $resort_id  = $this->resort->resort_id;
                    $LeaveCategory = LeaveCategory::where('resort_id',$this->resort->resort_id)->get();

                    $startOfMonth = Carbon::now()->startOfMonth(); // Get the first day of the month
                    $endOfMonth =Carbon::now()->endOfMonth(); // Get the last day of the month

                    // Get public holidays (including Fridays)
                    $publicHolidays = $this->getPublicHolidays($resort_id, $startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d'));

                    // Fetch overtime data from employee_overtimes table for all employees in the date range
                    $employeeIds = $attandanceregister->pluck('emp_id')->toArray();
                    $overtimeData = collect([]);
                    if (!empty($employeeIds)) {
                        $overtimeData = EmployeeOvertime::whereIn('Emp_id', $employeeIds)
                            ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                            ->where('status', 'approved') // Only get approved overtime
                            ->get()
                            ->groupBy('Emp_id')
                            ->map(function ($overtimes) {
                                return $overtimes->keyBy(function ($ot) {
                                    // $ot->date is already a Carbon date object due to model cast
                                    return $ot->date->format('Y-m-d');
                                });
                            });
                    }

       return view('resorts.timeandattendance.attandanceregister.index',compact('LeaveCategory','monthwiseheaders','page_title','ResortDepartment','headers','attandanceregister','resort_id','WeekstartDate','WeekendDate','startOfMonth','endOfMonth','publicHolidays','overtimeData'));
    }

    public function CheckoutTimeMissing(Request $request)
    {


        try{
            DB::beginTransaction();
            $AttdanceId = $request->AttdanceId;
            $CheckoutTime = $request->CheckoutTime;
            $action = $request->action;
            $Approved_id = $this->resort->id;

            $action == 'approve' ? $action = 'Approved' : $action = 'Rejected';

            // Safely parse CheckoutTime - validate it's a valid time format
            $checkoutTimeParsed = null;
            if (preg_match('/^(\d{1,2}):(\d{2})$/', $CheckoutTime, $matches)) {
                $hours = (int)$matches[1];
                $minutes = (int)$matches[2];
                if ($hours >= 0 && $hours <= 23 && $minutes >= 0 && $minutes <= 59) {
                    try {
                        $checkoutTimeParsed = Carbon::parse($CheckoutTime);
                        $CheckoutTime = $checkoutTimeParsed->format('H:i');
                    } catch (\Exception $e) {
                        return response()->json(['success'=>false,'message' => 'Invalid checkout time format.']);
                    }
                } else {
                    return response()->json(['success'=>false,'message' => 'Invalid checkout time format.']);
                }
            } else {
                return response()->json(['success'=>false,'message' => 'Invalid checkout time format.']);
            }

            $ParentAttendace = ParentAttendace::where('id', $AttdanceId)->first();
            $DayWiseTotalHours = $ParentAttendace->DayWiseTotalHours;
            $OldOverTime = $ParentAttendace->OverTime ?? "00:00";
            if ($ParentAttendace)
            {
                // Parse both times as Carbon instances - these are actual times, not durations
                $CheckingTimeParsed = null;
                if ($ParentAttendace->CheckingTime && preg_match('/^(\d{1,2}):(\d{2})$/', $ParentAttendace->CheckingTime, $matches)) {
                    $hours = (int)$matches[1];
                    $minutes = (int)$matches[2];
                    if ($hours >= 0 && $hours <= 23 && $minutes >= 0 && $minutes <= 59) {
                        try {
                            $CheckingTimeParsed = Carbon::parse($ParentAttendace->CheckingTime);
                        } catch (\Exception $e) {
                            return response()->json(['success'=>false,'message' => 'Invalid checking time format.']);
                        }
                    }
                }

                if (!$CheckingTimeParsed || !$checkoutTimeParsed) {
                    return response()->json(['success'=>false,'message' => 'Invalid time format.']);
                }

                // Calculate time difference
                $timeDifferenceInMinutes = $CheckingTimeParsed->diffInMinutes($checkoutTimeParsed, false); // False allows negative values
                $hours = intdiv(abs($timeDifferenceInMinutes), 60); // Total hours
                $minutes = abs($timeDifferenceInMinutes) % 60; // Remaining minutes
                $NewTotalHours = sprintf("%02d:%02d", $hours, $minutes);

                if( $DayWiseTotalHours == $NewTotalHours)
                {
                    // Parse both times as Carbon instances
                    $ParentAttendace ->CheckingOutTime  = $CheckoutTime;
                }
                elseif($DayWiseTotalHours < $NewTotalHours)
                {
                    // Parse durations (not times) - use explode instead of Carbon::parse
                    list($dayHours, $dayMinutes) = explode(':', $DayWiseTotalHours ?? '00:00');
                    list($newHours, $newMinutes) = explode(':', $NewTotalHours);

                    // Convert to minutes for comparison
                    $dayTotalMinutes = ((int)$dayHours * 60) + (int)$dayMinutes;
                    $newTotalMinutes = ((int)$newHours * 60) + (int)$newMinutes;

                    // Calculate difference in minutes
                    $diffMinutes = $newTotalMinutes - $dayTotalMinutes;
                    $hoursDifferent = intdiv(abs($diffMinutes), 60);
                    $minutesDifferent = abs($diffMinutes) % 60;
                    $newOverTime = sprintf("%02d:%02d", $hoursDifferent, $minutesDifferent);

                    // Add to old overtime (also a duration)
                    list($oldOThours, $oldOTminutes) = explode(':', $OldOverTime);
                    $totalOldOTMinutes = ((int)$oldOThours * 60) + (int)$oldOTminutes;
                    $totalNewOTMinutes = ((int)$hoursDifferent * 60) + (int)$minutesDifferent;
                    $totalUpdatedOTMinutes = $totalOldOTMinutes + $totalNewOTMinutes;
                    $updatedOThours = intdiv($totalUpdatedOTMinutes, 60);
                    $updatedOTminutes = $totalUpdatedOTMinutes % 60;
                    $UpdatedOverTime = sprintf("%02d:%02d", $updatedOThours, $updatedOTminutes);

                    $ParentAttendace->OverTime =  $UpdatedOverTime;
                    $ParentAttendace->DayWiseTotalHours = sprintf("%02d:%02d", $hours, $minutes);
                    $ParentAttendace->CheckingOutTime = $CheckoutTime;
                    $ParentAttendace->OTStatus    = $action;
                    $ParentAttendace->OTApproved_By = $Approved_id;
                }
                elseif($DayWiseTotalHours > $NewTotalHours)
                {

                    $ParentAttendace->Shift_id;
                    $ShiftSettings = ShiftSettings::find($ParentAttendace->Shift_id);
                    $CheckoutTimeFormatted = $CheckoutTime;

                    if($CheckoutTimeFormatted == $ShiftSettings->EndTime && isset($ParentAttendace->OverTime))
                    {
                        $ParentAttendace->OTStatus    = "Rejected";
                        $ParentAttendace->OTApproved_By = $Approved_id;
                    }

                        $ParentAttendace->DayWiseTotalHours = sprintf("%02d:%02d", $hours, $minutes);
                        $ParentAttendace->CheckingOutTime = $CheckoutTime;
                        if(isset($ParentAttendace->OverTime))
                        {
                            $ParentAttendace->OTStatus    = "Rejected";
                            $ParentAttendace->OTApproved_By = $Approved_id;
                        }
                }
                $ParentAttendace->save();
            }
             return response()->json(['success'=>true,'message' => 'OT '.$action.' successfully.']);
        }
        catch(Exception $e)
        {
            DB::rollback();
            return response()->json(['success'=>false,'message' => 'Oops somthing wrong to Update Ot Status.']);
        }
    }


    public function ResigterRosterSearch(Request $request)
    {


        $search = $request->search;
        $department = $request->department;
        $date = $request->date;
        // $month = $request->month;
        // $year = $request->year;
        $Rank =  $this->resort->GetEmployee->rank ?? '';
        $sendclass = $request->sendclass;
        $WeekstartDate = Carbon::now()->startOfWeek(); //Week start Start date
        $WeekendDate = Carbon::now()->endOfWeek();
        $headers = [];
        $numberOfDays = 7;
        $days = [];
        for ($i = 0; $i < $numberOfDays; $i++)
        {
            $currentDate = $WeekstartDate->clone()->addDays($i);
            $headers[] = [
                'date' => $currentDate->format('d M'),
                'day' => $currentDate->format('D'),
                'newdate'=> $currentDate->format('Y-m-d'),
                'full_date' => $currentDate
            ];
            $days[] =$currentDate->format('D');
        }

                $attandanceregister = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                                        ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
                                        ->join('duty_rosters as t3', 't3.Emp_id', '=', 'employees.id')
                                        ->leftJoin('parent_attendaces as t4', function ($join) use ($date) {
                                            // Fix the join condition - should likely be resort_id from duty_rosters
                                            $join->on('t4.Emp_id', '=', 't3.Emp_id'); // Changed to match employee IDs

                        })->select(
                                't1.id as Parentid',
                                't1.first_name',
                                't1.last_name',
                                't1.profile_picture',
                                'employees.id as emp_id',
                                'employees.Emp_id as EmployeeId',
                                't2.position_title',
                                't3.id as duty_roster_id',
                                't4.date'
                            )
                            ->groupBy('employees.id')


                            // ->whereIn('employees.id', $this->underEmp_id)
                            ->where('t1.resort_id', $this->resort->resort_id);
                            if($Rank != '3'){
                $attandanceregister->whereIn('employees.id', $this->underEmp_id);
            }

                        // Apply search filter
                        if (!empty($search)) {
                            $attandanceregister->where(function ($query) use ($search) {
                                $query->where('t1.first_name', 'LIKE', "%$search%")
                                    ->orWhere('t1.last_name', 'LIKE', "%$search%")
                                    ->orWhere('employees.Emp_id', 'LIKE', "%$search%");
                            });
                        }

                        // Apply department filter
                        if (!empty($department)) {
                            $attandanceregister->where('employees.Dept_id', $department);
                        }
                        // if (isset($date))
                        // {

                        //     $filterDate1 = Carbon::createFromFormat('d/m/Y', $date);
                        //     $attandanceregister->whereBetween('t4.date', [ $filterDate1->copy()->startOfMonth()->format('Y-m-d'), $filterDate1->copy()->endOfMonth()->format('Y-m-d')]);
                        // }

                        $attandanceregister = $attandanceregister->paginate(10);

                        $month = $request->month; // may be null
                        $year  = $request->year;  // may be null

                        // Handle cases
                        if (empty($month)) {
                            // No month selected → use current month/year
                            $year  = now()->year;
                            $month = now()->month;
                        } elseif (empty($year)) {
                            // Month selected but year missing → use current year
                            $year = now()->year;
                        }

                        // Base date for calculations
                        $baseDate = Carbon::createFromDate($year, $month, 1);

                        // Month info
                        $totalDays    = $baseDate->daysInMonth;
                        $startOfMonth = $baseDate->copy()->startOfMonth();
                        $endOfMonth   = $baseDate->copy()->endOfMonth();

                        // Week info (first week of the month)
                        $WeekstartDate = $baseDate->copy()->startOfWeek();
                        $WeekendDate   = $baseDate->copy()->endOfWeek();

                        // Transform the paginated results
                        $attandanceregister->getCollection()->transform(function ($item) {
                            $item->EmployeeName = ucfirst($item->first_name . ' ' . $item->last_name);
                            $item->Position = ucfirst($item->position_title);
                            $item->profileImg = Common::getResortUserPicture($item->Parentid);
                            return $item;
                        });


                    // $year = now()->year;
                    // $month = now()->month;
                    $totalDays = Carbon::createFromDate($year, $month, 1)->daysInMonth;

                    $monthwiseheaders=[];
                    for ($day = 1; $day <= $totalDays; $day++)
                    {
                        $date = Carbon::createFromDate($year, $month, $day);
                        $dayName = $date->format('D');
                        $newdate = $date->format('d-m-Y');
                        $monthwiseheaders[] = ["day"=>str_pad($day, 2, '0', STR_PAD_LEFT),"dayname" => $dayName,'newdate'=>$newdate];
                    }
                    $resort_id  = $this->resort->resort_id;

                    $LeaveCategory = LeaveCategory::where('resort_id',$this->resort->resort_id)->get();

                    // Get public holidays (including Fridays)
                    $publicHolidays = $this->getPublicHolidays($resort_id, $startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d'));

                    // Fetch overtime data from employee_overtimes table for all employees in the date range
                    $employeeIds = $attandanceregister->pluck('emp_id')->toArray();
                    $overtimeData = collect([]);
                    if (!empty($employeeIds)) {
                        $overtimeData = EmployeeOvertime::whereIn('Emp_id', $employeeIds)
                            ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                            ->where('status', 'approved') // Only get approved overtime
                            ->get()
                            ->groupBy('Emp_id')
                            ->map(function ($overtimes) {
                                return $overtimes->keyBy(function ($ot) {
                                    // $ot->date is already a Carbon date object due to model cast
                                    return $ot->date->format('Y-m-d');
                                });
                            });
                    }

                if(!$request->get('page'))
                {
                    $view = view('resorts.renderfiles.ResigterRosterSearch',compact('LeaveCategory','sendclass','monthwiseheaders','headers',
                                                        'attandanceregister','resort_id','WeekstartDate','WeekendDate','startOfMonth','endOfMonth','publicHolidays','overtimeData'))->render();
                    return response()->json(['success'=>true,'view' => $view]);
                }
                else{
                    $page_title = 'Attandance Register';


                    $ResortDepartment =      ResortDepartment::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();
                    return view('resorts.timeandattendance.attandanceregister.index',compact('LeaveCategory','sendclass','monthwiseheaders','headers',
                                                        'attandanceregister','resort_id','WeekstartDate','WeekendDate','startOfMonth','endOfMonth','page_title','ResortDepartment','publicHolidays','overtimeData'));

                }


    }

    public function ImportAttandance(Request $request)
    {



        $UploadImportattandance = $request->UploadImportattandance;


        $validator = Validator::make($request->all(), [
            'UploadImportattandance' => 'required|file|mimes:xls,xlsx',
        ],
            [
            'UploadImportattandance.mimes' => 'The Past Attandace  file must be a type of: xls, xlsx.',
        ]);


            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $filePath = $request->file('UploadImportattandance')->store('imports');

            $check =  ImportAttandanceJob::dispatch($filePath,);

                $response['success'] = true;

                $response['msg'] ="Attandance Imported successfully";
                return response()->json($response);

    }

    public function downloadTemplate()
    {
        return Excel::download(new AttendanceTemplateExport, 'attendance_import_template.xlsx');
    }
}
