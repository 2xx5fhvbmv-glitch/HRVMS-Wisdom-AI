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
use App\Models\PayrollConfig;
use App\Models\DutyRoster;
use App\Models\DutyRosterEntry;
use App\Models\ChildAttendace;
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
       $ResortDepartment = ResortDepartment::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();

       return view('resorts.timeandattendance.attandanceregister.index',compact('page_title','ResortDepartment'));
    }

    /**
     * Web-portal counterpart to Phase 5.3 of the Casual/Intern support
     * plan — the existing Attendance Register has no single-employee
     * mark action at all (only the bulk Excel importer below), and
     * Casual/Intern never had a mobile app to self check-in from. Own
     * page rather than retrofitting the 1260-line legacy register view:
     * same underlying tables (parent_attendaces/duty_roster_entries), so
     * a status marked here shows up identically on the mobile HOD
     * attendance screen and vice versa — they're not two systems, just
     * two doors into the same data.
     */
    public function nonPermanentIndex()
    {
        $page_title = 'Attendance — Casual & Intern';
        $ResortDepartment = ResortDepartment::where('status', 'active')->where('resort_id', $this->resort->resort_id)->get();
        $shifts = ShiftSettings::where('resort_id', $this->resort->resort_id)->get(['id', 'ShiftName', 'StartTime', 'EndTime']);

        return view('resorts.timeandattendance.attandanceregister.nonpermanent', compact('page_title', 'ResortDepartment', 'shifts'));
    }

    /**
     * Employee list for the tab currently open (Permanent / Casual /
     * Intern / Everyone), each with today's attendance status if any.
     */
    public function nonPermanentList(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $category = $request->input('category', 'Casual'); // Permanent|Casual|Intern|All
        $date = $request->input('date') ?: Carbon::now()->format('Y-m-d');

        $query = Employee::where('resort_id', $resort_id)->where('status', 'Active');
        if ($category !== 'All') {
            $query->whereIn('employment_type', Common::manningCategoryEmploymentTypes($category));
        }
        // Same department-scoped visibility as every other list in this
        // controller (Common::getSubordinates() populated in __construct).
        if (!empty($this->underEmp_id)) {
            $query->whereIn('id', $this->underEmp_id);
        }

        $employees = $query->with('resortAdmin')->get(['id', 'Emp_id', 'Admin_Parent_id', 'Dept_id', 'Position_id', 'employment_type']);

        $attendanceByEmp = ParentAttendace::where('resort_id', $resort_id)
            ->whereIn('Emp_id', $employees->pluck('id'))
            ->whereDate('date', $date)
            ->get()
            ->keyBy('Emp_id');

        $rows = $employees->map(function ($emp) use ($attendanceByEmp) {
            $att = $attendanceByEmp->get($emp->id);
            return [
                'emp_id' => $emp->id,
                'name' => trim(($emp->resortAdmin->first_name ?? '') . ' ' . ($emp->resortAdmin->last_name ?? '')),
                'emp_code' => $emp->Emp_id,
                'employment_type' => $emp->employment_type,
                'manning_category' => Common::manningCategory($emp->employment_type),
                'status' => $att->Status ?? null,
            ];
        })->values();

        return response()->json(['success' => true, 'date' => $date, 'employees' => $rows]);
    }

    /**
     * Mark one employee's daily status for a date. Mirrors
     * API\TimeAndAttendanceController::hodMarkAttendancePresent()'s
     * per-employee logic exactly (same auto-provisioned roster for
     * Casual/Intern with none yet) — kept as its own method rather than
     * refactoring that already-shipped mobile endpoint to share code,
     * since the two have different request/response contracts and this
     * avoids any regression risk to the verified mobile path.
     */
    public function nonPermanentMark(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'emp_id' => 'required|integer',
            'status' => 'required|in:Present,Absent,Sick,DayOff,ShortLeave,HalfDayLeave,FullDayLeave',
            'date' => 'nullable|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $resort_id = $this->resort->resort_id;
        $empId = (int) $request->emp_id;
        $status = $request->status;
        $date = $request->date ?: Carbon::now()->format('Y-m-d');
        $location = 'Web Attendance Register';

        if (!Employee::where('id', $empId)->where('resort_id', $resort_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Employee not found in this resort.'], 404);
        }

        try {
            DB::beginTransaction();

            $parentAttendance = ParentAttendace::where('resort_id', $resort_id)
                ->where('Emp_id', $empId)
                ->whereDate('date', $date)
                ->first();

            if (!$parentAttendance) {
                $rosterEntry = DutyRosterEntry::where('resort_id', $resort_id)
                    ->where('Emp_id', $empId)
                    ->whereDate('date', $date)
                    ->first();

                if (!$rosterEntry) {
                    $targetEmployee = Employee::find($empId);
                    $isNonPermanent = $targetEmployee && Common::manningCategory($targetEmployee->employment_type) !== 'Permanent';

                    if (!$isNonPermanent) {
                        DB::rollBack();
                        return response()->json(['success' => false, 'message' => 'No duty roster for this date. Ensure roster exists for ' . $date . '.'], 422);
                    }

                    $defaultShift = ShiftSettings::where('resort_id', $resort_id)->first();
                    if (!$defaultShift) {
                        DB::rollBack();
                        return response()->json(['success' => false, 'message' => 'No shift configured for this resort — cannot auto-create a roster for this Casual/Intern employee.'], 422);
                    }

                    $rosterParentId = DB::table('duty_rosters')->insertGetId([
                        'resort_id' => $resort_id,
                        'Shift_id' => $defaultShift->id,
                        'Emp_id' => $empId,
                        'ShiftDate' => $date . ' - ' . $date,
                        'Year' => Carbon::parse($date)->format('Y'),
                        'created_by' => $this->resort->id,
                        'modified_by' => $this->resort->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $rosterEntry = DutyRosterEntry::create([
                        'roster_id' => $rosterParentId,
                        'resort_id' => $resort_id,
                        'Shift_id' => $defaultShift->id,
                        'Emp_id' => $empId,
                        'date' => $date,
                        'Status' => 'Present',
                        'CheckingTime' => $defaultShift->StartTime,
                        'CheckingOutTime' => $defaultShift->EndTime,
                    ]);
                }

                $shiftData = ShiftSettings::where('resort_id', $resort_id)->where('id', $rosterEntry->Shift_id)->first();
                $startTime = $shiftData ? $shiftData->StartTime : ($rosterEntry->CheckingTime ?? '00:00');
                $endTime = $shiftData ? $shiftData->EndTime : ($rosterEntry->CheckingOutTime ?? '00:00');

                $parentAttendance = ParentAttendace::create([
                    'Emp_id' => $empId,
                    'date' => $date,
                    'roster_id' => $rosterEntry->roster_id,
                    'resort_id' => $resort_id,
                    'Shift_id' => $rosterEntry->Shift_id,
                    'CheckingTime' => $startTime,
                    'CheckingOutTime' => $endTime,
                    'DayWiseTotalHours' => $rosterEntry->DayWiseTotalHours ?? '00:00',
                    'Status' => $status,
                    'CheckInCheckOut_Type' => 'Manual',
                ]);
                ChildAttendace::create([
                    'Parent_attd_id' => $parentAttendance->id,
                    'InTime_out' => $startTime,
                    'OutTime_out' => $endTime,
                    'InTime_Location' => $location,
                    'OutTime_Location' => $location,
                ]);
            } else {
                $shiftData = ShiftSettings::where('resort_id', $resort_id)->where('id', $parentAttendance->Shift_id)->first();
                $startTime = $shiftData ? $shiftData->StartTime : ($parentAttendance->CheckingTime ?? '00:00');
                $endTime = $shiftData ? $shiftData->EndTime : ($parentAttendance->CheckingOutTime ?? '00:00');
                $parentAttendance->CheckingTime = $startTime;
                $parentAttendance->CheckingOutTime = $endTime;
                $parentAttendance->Status = $status;
                $parentAttendance->CheckInCheckOut_Type = 'Manual';
                $parentAttendance->save();

                ChildAttendace::updateOrCreate(
                    ['Parent_attd_id' => $parentAttendance->id],
                    ['InTime_out' => $startTime, 'OutTime_out' => $endTime, 'InTime_Location' => $location, 'OutTime_Location' => $location]
                );
            }

            DB::commit();

            try {
                Common::notifyEmployees(
                    $resort_id,
                    [$empId],
                    'Attendance Marked ' . $status,
                    'Your attendance for ' . $date . ' was marked ' . $status . ' by your supervisor.',
                    'Attendance',
                    null
                );
            } catch (\Exception $notifErr) {
                \Log::warning('nonPermanentMark notify failed: ' . $notifErr->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance marked ' . $status . '.',
                'emp_id' => $empId,
                'status' => $parentAttendance->Status,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Lets a supervisor allocate/adjust a Casual/Intern employee's duty
     * roster for a date directly from the web — the mobile-first
     * auto-provisioned row from nonPermanentMark() is the default; this
     * lets a supervisor set the actual shift without needing the app
     * (which Casual/Intern don't have anyway).
     */
    public function nonPermanentAllocateRoster(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'emp_id' => 'required|integer',
            'shift_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $resort_id = $this->resort->resort_id;
        $empId = (int) $request->emp_id;

        $employee = Employee::where('id', $empId)->where('resort_id', $resort_id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found in this resort.'], 404);
        }
        if (Common::manningCategory($employee->employment_type) === 'Permanent') {
            return response()->json(['success' => false, 'message' => 'This action is for Casual/Intern staff only — Permanent rosters are managed via the Duty Roster module.'], 422);
        }

        $shift = ShiftSettings::where('resort_id', $resort_id)->where('id', $request->shift_id)->first();
        if (!$shift) {
            return response()->json(['success' => false, 'message' => 'Shift not found in this resort.'], 404);
        }

        $existingEntry = DutyRosterEntry::where('resort_id', $resort_id)
            ->where('Emp_id', $empId)
            ->whereDate('date', $request->date)
            ->first();

        if ($existingEntry) {
            $existingEntry->Shift_id = $shift->id;
            $existingEntry->CheckingTime = $shift->StartTime;
            $existingEntry->CheckingOutTime = $shift->EndTime;
            $existingEntry->save();
            DB::table('duty_rosters')->where('id', $existingEntry->roster_id)->update([
                'Shift_id' => $shift->id,
                'modified_by' => $this->resort->id,
                'updated_at' => now(),
            ]);
        } else {
            $rosterParentId = DB::table('duty_rosters')->insertGetId([
                'resort_id' => $resort_id,
                'Shift_id' => $shift->id,
                'Emp_id' => $empId,
                'ShiftDate' => $request->date . ' - ' . $request->date,
                'Year' => Carbon::parse($request->date)->format('Y'),
                'created_by' => $this->resort->id,
                'modified_by' => $this->resort->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DutyRosterEntry::create([
                'roster_id' => $rosterParentId,
                'resort_id' => $resort_id,
                'Shift_id' => $shift->id,
                'Emp_id' => $empId,
                'date' => $request->date,
                'Status' => 'Present',
                'CheckingTime' => $shift->StartTime,
                'CheckingOutTime' => $shift->EndTime,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Roster allocated for ' . $request->date . '.']);
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

            // Was an unscoped find-by-id — any resort-admin could read and
            // overwrite another resort's checkout time/overtime by guessing
            // an id, and would also fatal on the property access below
            // instead of hitting the (never-reached) null check that used
            // to come after it.
            $ParentAttendace = ParentAttendace::where('id', $AttdanceId)
                ->where('resort_id', $this->resort->resort_id)
                ->first();

            if (!$ParentAttendace) {
                DB::rollback();
                return response()->json(['success'=>false,'message' => 'Record not found.'], 404);
            }

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

                try {
                    Common::notifyEmployees(
                        $this->resort->resort_id,
                        [(int) $ParentAttendace->Emp_id],
                        'Overtime Request ' . $ParentAttendace->OTStatus,
                        'Your overtime request for ' . $ParentAttendace->CheckingTime . ' has been ' . strtolower($ParentAttendace->OTStatus) . '.',
                        'DutyRoster',
                        $ParentAttendace->id
                    );
                } catch (\Exception $ne) {
                    \Log::warning('Overtime approve/reject notification failed: ' . $ne->getMessage());
                }
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
                            ->where('t1.resort_id', $this->resort->resort_id)
                            ->where('employees.status', 'Active');

                            $employeeRankPosition = Common::getEmployeeRankPosition($this->resort->getEmployee);
                            $userDeptId = $this->resort->GetEmployee->Dept_id ?? '';

                            if($employeeRankPosition['position'] != "HR" && $employeeRankPosition['position'] != "EXCOM"){
                                // Non-HR/EXCOM users only see their own department
                                $attandanceregister->where('employees.Dept_id', $userDeptId);
                            }

                        // Apply search filter
                        if (!empty($search)) {
                            $attandanceregister->where(function ($query) use ($search) {
                                $query->where('t1.first_name', 'LIKE', "%$search%")
                                    ->orWhere('t1.last_name', 'LIKE', "%$search%")
                                    ->orWhere('employees.Emp_id', 'LIKE', "%$search%");
                            });
                        }

                        // Apply department filter (for HR/EXCOM users who have department dropdown)
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
                            $year  = now()->year;
                            $month = now()->month;
                        } elseif (empty($year)) {
                            $year = now()->year;
                        }

                        // Get cutoff day from payroll configuration
                        $cutoffDay = PayrollConfig::where('resort_id', $this->resort->resort_id)->value('cutoff_day') ?? 1;

                        // Calculate cutoff period based on selected month/year
                        // Cutoff day = last day of period. Period starts on cutoff+1
                        // If cutoff is 25 and month is March 2026: period = 26 Feb 2026 → 25 Mar 2026
                        $baseDate = Carbon::createFromDate($year, $month, 1);
                        $prevMonth = $baseDate->copy()->subMonthNoOverflow();
                        $startOfMonth = $prevMonth->copy()->day(min($cutoffDay, $prevMonth->daysInMonth))->addDay(); // cutoff + 1
                        $endOfMonth = $baseDate->copy()->day(min($cutoffDay, $baseDate->daysInMonth)); // cutoff day of selected month

                        // Total days in the cutoff period
                        $totalDays = $startOfMonth->diffInDays($endOfMonth) + 1;

                        // Week info (first week of the cutoff period)
                        $WeekstartDate = $startOfMonth->copy()->startOfWeek();
                        $WeekendDate   = $startOfMonth->copy()->endOfWeek();

                        // Transform the paginated results
                        $attandanceregister->getCollection()->transform(function ($item) {
                            $item->EmployeeName = ucfirst($item->first_name . ' ' . $item->last_name);
                            $item->Position = ucfirst($item->position_title);
                            $item->profileImg = Common::getResortUserPicture($item->Parentid);
                            return $item;
                        });

                    // Build monthwise headers for the cutoff period (day by day)
                    $monthwiseheaders=[];
                    $headerDate = $startOfMonth->copy();
                    for ($i = 0; $i < $totalDays; $i++)
                    {
                        $dayName = $headerDate->format('D');
                        $newdate = $headerDate->format('d M Y');
                        $monthwiseheaders[] = ["day"=>$headerDate->format('d'),"dayname" => $dayName,'newdate'=>$newdate,'month'=>$headerDate->format('M')];
                        $headerDate->addDay();
                    }
                    $resort_id  = $this->resort->resort_id;

                    $LeaveCategory = LeaveCategory::where('resort_id',$this->resort->resort_id)->get();

                    // Pre-compute which leave categories have data across ALL employees (not just current page)
                    $leaveCategoriesWithData = DB::table('employees_leaves as el')
                        ->where('el.resort_id', $this->resort->resort_id)
                        ->where('el.status', 'Approved')
                        ->where(function($q) use ($startOfMonth, $endOfMonth) {
                            $q->where('el.from_date', '<=', $endOfMonth->format('Y-m-d'))
                              ->where('el.to_date', '>=', $startOfMonth->format('Y-m-d'));
                        })
                        ->distinct()
                        ->pluck('el.leave_category_id')
                        ->map(function($id) { return (int)$id; })
                        ->toArray();

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

                // AJAX (filter or pagination): always return partial so page does not reload
                if ($request->ajax()) {
                    $view = view('resorts.renderfiles.ResigterRosterSearch',compact('LeaveCategory','leaveCategoriesWithData','sendclass','monthwiseheaders','headers',
                                                        'attandanceregister','resort_id','WeekstartDate','WeekendDate','startOfMonth','endOfMonth','publicHolidays','overtimeData'))->render();
                    return response()->json(['success'=>true,'view' => $view]);
                }
                // Non-AJAX (e.g. opened pagination link in new tab): return full page
                $page_title = 'Attandance Register';
                $ResortDepartment = ResortDepartment::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();
                return view('resorts.timeandattendance.attandanceregister.index',compact('LeaveCategory','leaveCategoriesWithData','sendclass','monthwiseheaders','headers',
                                                    'attandanceregister','resort_id','WeekstartDate','WeekendDate','startOfMonth','endOfMonth','page_title','ResortDepartment','publicHolidays','overtimeData'));


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
