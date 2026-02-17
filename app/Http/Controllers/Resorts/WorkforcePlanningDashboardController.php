<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\ResortAdmin;
use App\Models\Resort;
use App\Models\Division;
use App\Models\Department;
use App\Models\Section;
use App\Models\Position;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortSection;
use App\Models\ResortPosition;
use App\Models\Employee;
use App\Models\Settings;
use App\Models\ResortSiteSettings;
use App\Models\Role;
use App\Helpers\Common;
use Carbon\Carbon;
use DB;
use App\Models\Notification;
use  App\Models\Occuplany;

use App\Models\ResortsParentNotifications;
use App\Models\ResortsChildNotifications;
use App\Models\PositionMonthlyData;
use App\Models\ManningResponse;
use App\Models\BudgetStatus;
use App\Models\StoreManningResponseParent;
use App\Models\StoreManningResponseChild;
class WorkforcePlanningDashboardController extends Controller
{
    public $globalUser='';
    public $currency = '';
    public $currencylogo = '';
    public function __construct()
    {

        $this->globalUser = Auth::guard('resort-admin')->user();
        if(!$this->globalUser) return;
        $this->currency = Common::GetResortCurrentCurrency();
        $this->currencylogo = Common::GetResortCurrencyLogo();

    }
    public function admin_dashboard()
    {

        try {
            $page_title ='Workforce Planning Dashboard';
            $page_header = '<span class="arca-font">My</span> Dashboard';
            $currentYear = date('Y');
            $nextYear = $currentYear + 1;
            $resort_id = $this->globalUser->resort_id;
            $resort_divisions = ResortDivision::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();

            $resort_divisions_count = count($resort_divisions);
            $resort_departments_count = count($resort_departments);
            $resort_positions_count = count($resort_positions);

            $startDate = Carbon::now()->subDays(5)->toDateString(); // Five days before

            for ($i = 0; $i <= 5; $i++) {
                $nextDate[] = Carbon::now()->addDays($i)->toDateString();
            }

            for ($i = 5; $i >= 0; $i--) {
                $previousDate[] = Carbon::now()->subDay($i)->toDateString();
            }

            $newArray = array_merge($nextDate, $previousDate);

            // Query for occupancy data on previous, current, and next day
            $occupancies = Occuplany::where('resort_id', $resort_id)
                ->whereIn('occupancydate', $newArray)
                ->get(['occupancyinPer', 'occupancydate', 'occupancytotalRooms', 'occupancyOccupiedRooms']);
            $male_emp = ResortAdmin::with('EmployeeDetails')->where('resort_id', $resort_id)->where('gender','male')->count();
            $female_emp = ResortAdmin::with('EmployeeDetails')->where('resort_id', $resort_id)->where('gender','female')->count();

            $total_emp = ResortAdmin::where('resort_id', $resort_id)->count();

            // Calculate percentage
            $male_percentage = $total_emp > 0 ? ($male_emp / $total_emp) * 100 : 0;
            $female_percentage = $total_emp > 0 ? ($female_emp / $total_emp) * 100 : 0;

            // Round the percentages to 2 decimal places (optional)
            $male_percentage = round($male_percentage, 2);
            $female_percentage = round($female_percentage, 2);

            $localEmployees = Employee::where('nationality', 'Maldivian')->where('resort_id',$resort_id)->count();
            $expatEmployees = Employee::where('nationality', '!=', 'Maldivian')->where('resort_id',$resort_id)->count();

            $ManningPendingRequestCount = ResortsParentNotifications::where('resort_id',$resort_id)->orderBy('created_at', 'desc')->first();
            $PendingDepartmentResoponse=array();
            if($ManningPendingRequestCount != null)
            {

                $HODpendingResponse = ResortsChildNotifications::where("Parent_msg_id", $ManningPendingRequestCount->message_id)->where("response","No")->groupBy('Department_id')->orderBy('created_at', 'desc')->get();

                $totalsendtoDepartment = ResortsChildNotifications::where("Parent_msg_id", $ManningPendingRequestCount->message_id)->orderBy('created_at', 'desc')->groupBy('Department_id')->get();
                $ManningPendingRequestCount = count($totalsendtoDepartment);
                foreach($HODpendingResponse as $Dep)
                {
                $PendingDepartmentResoponse[$Dep->id][]= $Dep->department->name;
                }
                $HODpendingResponse=count($HODpendingResponse);
            }
            else
            {
                $HODpendingResponse=0;
                $ManningPendingRequestCount=0;
            }




        $vacant_positions = DB::table('resort_positions as p')
        ->leftJoin('employees as e', 'e.Position_id', '=', 'p.id')
        ->where('p.resort_id', '=', $resort_id);

        // // Apply department condition if the user is an employee and has a department
        // if (isset($this->globalUser->GetEmployee) && isset($this->globalUser->GetEmployee->Dept_id)) {
        //     $vacant_positions->where('p.dept_id', '=', $this->globalUser->GetEmployee->Dept_id);
        // }

        $vacant_positions = $vacant_positions->select(
            DB::raw('COUNT(DISTINCT p.id) as total_positions_count'), // Total number of unique positions
            DB::raw('COUNT(DISTINCT CASE WHEN e.id IS NOT NULL THEN p.id END) as positions_with_employees'), // Positions with at least one employee
            DB::raw('COUNT(DISTINCT p.id) - COUNT(DISTINCT CASE WHEN e.id IS NOT NULL THEN p.id END) as vacant_positions'), // Positions with no employees
        DB::raw('COUNT(e.id) as TotalBudgtedemp')
            )->first();

        $manning_response = (object) [
            "total_budgeted_employees" => $vacant_positions->total_positions_count, // Total unique positions
            "total_filled_positions_count" => $vacant_positions->positions_with_employees, // Positions where employees exist
            "total_vacant_count" => $vacant_positions->vacant_positions, // Positions with no employees
            "TotalBudgtedemp"=>$vacant_positions->TotalBudgtedemp,
        ];
        $employee_under_min_wage_usd = Employee::where('resort_id', $resort_id)
            ->where('basic_salary_currency', 'USD')
            ->where('basic_salary', '<', '520')
            ->where('status', 'active')
            ->count();

        $employee_under_min_wage_mvr = Employee::where('resort_id', $resort_id)
            ->where('basic_salary_currency', 'MVR')
            ->where('basic_salary', '<', '8021')
            ->where('status', 'active')
            ->count();

        $employee_under_min_wage = $employee_under_min_wage_usd + $employee_under_min_wage_mvr;

            $currency = $this->currencylogo; // Assuming this is set correctly

                return view('resorts.workforce_planning.dashboard',
                compact(
                    'currency',
                    'ManningPendingRequestCount',
                    'PendingDepartmentResoponse',
                    'HODpendingResponse',
                    'occupancies',
                    'resort_id',
                    'resort_divisions_count',
                    'resort_departments_count',
                    'resort_positions_count',
                    'male_percentage',
                    'female_percentage',
                    'total_emp',
                    'localEmployees',
                    'expatEmployees',
                    'resort_divisions',
                    'resort_departments',
                    'resort_positions',
                    'manning_response',
                    'employee_under_min_wage'
                )
            );
        } catch( \Exception $e ) {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            $male_percentage=0;
            $female_percentage=0;
            $total_emp=0;
            $localEmployees=0;
            $expatEmployees=0;
            return view('resorts.workforce_planning.dashboard',compact('occupancies','resort_id','resort_divisions_count','resort_departments_count','resort_positions_count','male_percentage','female_percentage','total_emp','localEmployees','expatEmployees','employee_under_min_wage'));
        }
    }
    public function hr_dashboard()
    {
        try {
            $page_title ='Workforce Planning Dashboard';
            $page_header = '<span class="arca-font">My</span> Dashboard';
            $currentYear = date('Y');
            $nextYear = $currentYear + 1;
            $resort_id = $this->globalUser->resort_id;
            $resort_divisions = ResortDivision::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();

            $resort_divisions_count = count($resort_divisions);
            $resort_departments_count = count($resort_departments);
            $resort_positions_count = count($resort_positions);
            $ResortData = Resort::find($resort_id);
            $sitesettings = ResortSiteSettings::where('resort_id', $resort_id)->first(['resort_id','header_img','footer_img','Footer']);

            $startDate = Carbon::now()->subDays(5)->toDateString(); // Five days before

            for ($i = 0; $i <= 5; $i++) {
                $nextDate[] = Carbon::now()->addDays($i)->toDateString();
            }

            for ($i = 5; $i >= 0; $i--) {
                $previousDate[] = Carbon::now()->subDay($i)->toDateString();
            }

            $newArray = array_merge($nextDate, $previousDate);

            // Query for occupancy data on previous, current, and next day
            $occupancies = Occuplany::where('resort_id', $resort_id)
                ->whereIn('occupancydate', $newArray)
                ->get(['occupancyinPer', 'occupancydate', 'occupancytotalRooms', 'occupancyOccupiedRooms']);
            $male_emp = ResortAdmin::with('EmployeeDetails')->where('resort_id', $resort_id)->where('gender','male')->count();
            $female_emp = ResortAdmin::with('EmployeeDetails')->where('resort_id', $resort_id)->where('gender','female')->count();

            $total_emp = ResortAdmin::where('resort_id', $resort_id)->count();

            // Calculate percentage
            $male_percentage = $total_emp > 0 ? ($male_emp / $total_emp) * 100 : 0;
            $female_percentage = $total_emp > 0 ? ($female_emp / $total_emp) * 100 : 0;

            // Round the percentages to 2 decimal places (optional)
            $male_percentage = round($male_percentage, 2);
            $female_percentage = round($female_percentage, 2);

            $localEmployees = Employee::where('nationality', 'Maldivian')->where('resort_id',$resort_id)->count();
            $expatEmployees = Employee::where('nationality', '!=', 'Maldivian')->where('resort_id',$resort_id)->count();

            $ManningPendingRequestCount = ResortsParentNotifications::where('resort_id',$resort_id)->orderBy('created_at', 'desc')->first();
            $PendingDepartmentResoponse=array();
            if($ManningPendingRequestCount != null)
            {

                $HODpendingResponse = ResortsChildNotifications::where("Parent_msg_id", $ManningPendingRequestCount->message_id)->where("response","No")->groupBy('Department_id')->orderBy('created_at', 'desc')->get();

                $totalsendtoDepartment = ResortsChildNotifications::where("Parent_msg_id", $ManningPendingRequestCount->message_id)->orderBy('created_at', 'desc')->groupBy('Department_id')->get();
                $ManningPendingRequestCount = count($totalsendtoDepartment);
                foreach($HODpendingResponse as $Dep)
                {
                $PendingDepartmentResoponse[$Dep->id][]= $Dep->department->name;
                }
                $HODpendingResponse=count($HODpendingResponse);
            }
            else
            {
                $HODpendingResponse=0;
                $ManningPendingRequestCount=0;
            }




        $vacant_positions = DB::table('resort_positions as p')
        ->leftJoin('employees as e', 'e.Position_id', '=', 'p.id')
        ->where('p.resort_id', '=', $resort_id);

    // // Apply department condition if the user is an employee and has a department
    // if (isset($this->globalUser->GetEmployee) && isset($this->globalUser->GetEmployee->Dept_id)) {
    //     $vacant_positions->where('p.dept_id', '=', $this->globalUser->GetEmployee->Dept_id);
    // }

    $vacant_positions = $vacant_positions->select(
        DB::raw('COUNT(DISTINCT p.id) as total_positions_count'), // Total number of unique positions
        DB::raw('COUNT(DISTINCT CASE WHEN e.id IS NOT NULL THEN p.id END) as positions_with_employees'), // Positions with at least one employee
        DB::raw('COUNT(DISTINCT p.id) - COUNT(DISTINCT CASE WHEN e.id IS NOT NULL THEN p.id END) as vacant_positions'), // Positions with no employees
      DB::raw('COUNT(e.id) as TotalBudgtedemp')
        )->first();

        $manning_response = (object) [
            "total_budgeted_employees" => $vacant_positions->total_positions_count, // Total unique positions
            "total_filled_positions_count" => $vacant_positions->positions_with_employees, // Positions where employees exist
            "total_vacant_count" => $vacant_positions->vacant_positions, // Positions with no employees
            "TotalBudgtedemp"=>$vacant_positions->TotalBudgtedemp,
        ];
        $employee_under_min_wage_usd = Employee::where('resort_id', $resort_id)
            ->where('basic_salary_currency', 'USD')
            ->where('basic_salary', '<', '520')
            ->where('status', 'active')
            ->count();

        $employee_under_min_wage_mvr = Employee::where('resort_id', $resort_id)
            ->where('basic_salary_currency', 'MVR')
            ->where('basic_salary', '<', '8021')
            ->where('status', 'active')
            ->count();

        $employee_under_min_wage = $employee_under_min_wage_usd + $employee_under_min_wage_mvr;

            $currency = $this->currencylogo; // Assuming this is set correctly

                return view('resorts.workforce_planning.dashboard',
                compact(
                    'currency',
                    'ManningPendingRequestCount',
                    'PendingDepartmentResoponse',
                    'HODpendingResponse',
                    'occupancies',
                    'resort_id',
                    'resort_divisions_count',
                    'resort_departments_count',
                    'resort_positions_count',
                    'male_percentage',
                    'female_percentage',
                    'total_emp',
                    'localEmployees',
                    'expatEmployees',
                    'resort_divisions',
                    'resort_departments',
                    'resort_positions',
                    'manning_response',
                    'employee_under_min_wage',
                    'ResortData','sitesettings'
                )
            );
        } catch( \Exception $e ) {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            $male_percentage=0;
            $female_percentage=0;
            $total_emp=0;
            $localEmployees=0;
            $expatEmployees=0;
            $employee_under_min_wage=0;
            $occupancies=0;
            return view('resorts.workforce_planning.dashboard',compact('resort_id','resort_divisions_count','resort_departments_count','resort_positions_count','male_percentage','female_percentage','total_emp','localEmployees','expatEmployees','employee_under_min_wage','occupancies'));
        }
    }

    public function filledpositions()
    {
        $page_title = 'Filled Position';
        $resort_id =$this->globalUser->resort_id;
        return view('resorts.workforce_planning.filledpositions')->with(
            compact(
            'page_title',
            'resort_id'
            )
        );
    }

    public function get_filledpositions(Request $request)
    {
        // dd($request);
        $resort_id =$this->globalUser->resort_id;

        if ($request->ajax())
        {
            $resort_positions = DB::table('resort_positions as p')
                ->leftJoin('resort_departments as rd', 'p.dept_id', '=', 'rd.id')
                ->where('p.resort_id', '=', $resort_id)
                ->select('p.id', 'p.position_title', 'rd.name as department', 'p.created_at')
                ->groupBy('p.id', 'p.position_title', 'rd.name', 'p.created_at')
                ->orderBy('p.created_at', 'desc');
            return datatables()
                ->of($resort_positions)
                ->addColumn('no_of_employees', function ($row) {
                    return Employee::where("resort_id", $this->globalUser->resort_id)
                        ->where("Position_id", $row->id)
                        ->count();
                })
                ->rawColumns(['position_title', 'department', 'no_of_employees'])
                ->make(true);
        }

        return view('resorts.workforce_planning.filledpositions');
    }

    public function getEmployeeNames(Request $request)
    {
        $positionId = $request->get('position_id');
        $employees = Employee::where('Position_id', $positionId)
            ->with('resortAdmin:id,first_name,last_name')
            ->where('resort_id', $this->globalUser->resort_id)
            ->where('status', 'Active')
            ->get('Admin_Parent_id');

        // Map employees to include the image URL
        $employees = $employees->map(function($employee) {
            $employee->profile_picture = Common::getResortUserPicture($employee->Admin_Parent_id);
            return $employee;
        });

        return response()->json($employees);
    }

    public function getPositions(){
      $page_title ='Workforce Planning Dashboard';
      $page_header = '<span class="arca-font">HOD</span> Dashboard';
      $resort= $this->globalUser;
      $year = date('Y');
      $resort_id =$resort->resort_id;
      $id = $this->globalUser->id;
      $emp_details = Employee::where('Admin_Parent_id',$id)->get();
      $department_details = ResortDepartment::where('id',$emp_details[0]->Dept_id)->get();
      $Dept_id = $department_details[0]->id;
      // dd($Dept_id);
      $vacant_positions = DB::table('resort_positions as p')
                ->leftJoin('position_monthly_data as pmd', 'p.id', '=', 'pmd.position_id')
                ->leftJoin('manning_responses as mr', function($join) use ($resort_id, $Dept_id, $year) {
                    $join->on('pmd.manning_response_id', '=', 'mr.id')
                    ->where('mr.year', '=', $year);
                })
                ->where('p.resort_id', '=', $resort_id)
                        ->where('p.dept_id', '=', $Dept_id)
                ->select(
                    'p.id',
                    'p.position_title',
                    DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                    DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
                )
                ->groupBy('p.id', 'p.position_title')
                ->get();

                // Attach employees to each position
            foreach ($vacant_positions as $position)
            {
                $position->employees = DB::table('employees as e') // Assuming you have an employees table
                    ->leftJoin('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
                    ->where('position_id', $position->id)
                    // ->where('rank','others')
                    ->get(['first_name', 'last_name','Admin_Parent_id','rank','nationality']); // Adjust according to your employee table fields
            }


        return view('resorts.Positions.index',compact('vacant_positions'));
    }

    public function hod_dashboard()
    {

        try {
                $page_title ='Workforce Planning Dashboard';
                $page_header = '<span class="arca-font">HOD</span> Dashboard';
                $resort= Auth::guard('resort-admin')->user();
                $resort_id =$resort->resort_id;
                $id = Auth::guard('resort-admin')->user()->id;
                $position_id = $resort->GetEmployee->Position_id;
                $Dept_id = $resort->GetEmployee->Dept_id;
                $emp_details = Employee::where('Admin_Parent_id',$id)->get();
                $department_details = ResortDepartment::where('id',$emp_details[0]->Dept_id)->get();
                // dd($department_details);
                $positions = ResortPosition::where('status','active')->where('dept_id',$emp_details[0]->Dept_id)->get();
                // dd($positions);
                $resort_divisions_count = ResortDivision::where('status','active')->where('resort_id',$resort_id)->count();
                $resort_departments_count = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->count();
                $resort_positions_count = ResortPosition::where('status','active')->where('dept_id',$emp_details[0]->Dept_id)->count();

                $total_emp = ResortAdmin::where('resort_id', $resort_id)->count();

                $ManningPendingRequestCount =  ResortsParentNotifications::where('resort_id', $resort_id)
                                            ->where('status','Active')
                                            ->orderBy('created_at', 'desc')
                                            ->first();

                if($ManningPendingRequestCount )
                {
                    $HODpendingResponse = ResortsChildNotifications::where("Parent_msg_id", $ManningPendingRequestCount->message_id)->where('Department_id',$Dept_id)->where("response","No")->orderBy('created_at', 'desc')->count();

                    $getNotifications =  ResortsParentNotifications::join('resort_admins as t1', 't1.id', '=', 'resorts_parent_notifications.user_id')
                        ->leftJoin('employees as t2', 't2.Admin_Parent_id', '=', 't1.id')
                        ->leftJoin('resort_departments as t3', 't3.id', '=', 't2.Dept_id')
                        ->join('resorts_child_notifications as t4', 't4.Parent_msg_id', '=', 'resorts_parent_notifications.message_id')
                        ->leftJoin('hr_reminder_request_mannings as t5', 't5.message_id', '=', 'resorts_parent_notifications.message_id')
                        ->where('t4.Parent_msg_id', $ManningPendingRequestCount->message_id)
                        ->where('t4.response', "No")
                        ->where('t4.Department_id', $Dept_id)
                        ->orderByRaw('COALESCE(t5.id, 0) DESC')
                        ->first([
                            't3.name as DepartmentName',
                            't1.first_name',
                            't1.middle_name',
                            't1.last_name',
                            't1.id as loginid',
                            't1.resort_id',
                            't5.reminder_message_subject',
                            'resorts_parent_notifications.message_subject',
                            'resorts_parent_notifications.message_id'
                        ]);

                // BudgetStatus

            }
            else
            {
                $getNotifications = collect();
                $getNotifications = collect();
                $HODpendingResponse = 0;
            }
            $BudgetStatus = BudgetStatus::whereIn('status', ['Genrated', 'Approved', 'Pending'])
            ->where('resort_id', $resort_id)
            ->where('Department_id', $Dept_id)
            ->whereNotIn('resort_id', function($query) use ($resort_id, $Dept_id) {
                $query->select('resort_id')
                    ->from('budget_statuses')
                    ->where('status', 'Rejected')
                    ->where('resort_id', $resort_id)
                    ->where('Department_id', $Dept_id);
            })
            ->groupBy('message_id')
            ->get()
            ->toArray();
            $BudgetRejactedStatus  =  ResortsParentNotifications::join('resort_admins as t1', 't1.id', '=', 'resorts_parent_notifications.user_id')
            ->join('employees as t2', 't2.Admin_Parent_id', '=', 't1.id')
            ->leftJoin('resort_departments as t3', 't3.id', '=', 't2.Dept_id')
            ->join('resorts_child_notifications as t4', 't4.Parent_msg_id', '=', 'resorts_parent_notifications.message_id')
            ->join('budget_statuses as t5', 't5.message_id', '=', 'resorts_parent_notifications.message_id')
            ->join('manning_responses as t6', 't6.id', '=', 't5.Budget_id')
            ->where('t5.resort_id', $resort_id)
            ->where('t5.Department_id', $Dept_id)
            ->where('t4.response', "Yes")
            ->orderBy('t5.id',  'desc')
            ->first([
                't3.name as DepartmentName',
                't1.first_name',
                't1.middle_name',
                't1.last_name',
                't1.created_by as loginid',
                't5.resort_id',
                't5.OtherComments as reminder_message_subject',
                'resorts_parent_notifications.message_id',
                't5.Budget_id',
                't6.year'
            ]);


            $totalemployees = Employee::where('resort_id', $resort->resort_id)
                ->with('resortAdmin')
                ->where('dept_id', $Dept_id)
                ->where('resort_id', $resort->resort_id)
                ->count();

            $employees = Employee::where('resort_id', $resort->resort_id)
                ->with('resortAdmin')
                ->where('position_id', $position_id)
                ->where('dept_id', $Dept_id)
                ->where('resort_id', $resort->resort_id)
                ->limit(6)
                ->get();

            $leftemp=[];

            foreach($employees as $emp)
            {
                $leftemp[] = $emp->id;
            }

            $LeftemployeesCount = Employee::where('resort_id', $resort->resort_id)
                ->with('resortAdmin')
                ->where('position_id', $position_id)
                ->where('dept_id', $Dept_id)
                ->where('resort_id', $resort->resort_id)
                ->where('rank', '=', 'others')
                ->whereNotIn('id', $leftemp)

                ->count();

            $positionsWithEmployees = [];

            foreach ($positions as $pos) {
                $employeesForMonths = [];

                for ($i = 1; $i <= 12; $i++) { // Loop through months
                    // Use the Employee Eloquent model instead of DB::table()
                    $employee = Employee::with('resortAdmin') // Eager load the resortAdmin relationship
                        ->where('position_id', $pos->id) // Assuming $pos->id is the position ID
                        ->where('dept_id', $Dept_id)
                        ->where('resort_id', $resort->resort_id)
                        ->where('rank', '=', 'others')
                        ->first(); // Fetch the employee for the given month and year

                    $employeesForMonths[$i] = $employee;
                }
                $positionsWithEmployees[$pos->id] = $employeesForMonths;
            }

            $currentYear = date('Y');
            $nextYear = $currentYear + 1;

            $vacant_positions = DB::table('resort_positions as p')
                ->leftJoin('position_monthly_data as pmd', 'p.id', '=', 'pmd.position_id')
                ->leftJoin('manning_responses as mr', function($join) use ($resort, $Dept_id, $currentYear) {
                    $join->on('pmd.manning_response_id', '=', 'mr.id')
                    ->where('mr.year', '=', $currentYear);

                })
                ->where('p.resort_id', '=', $resort->resort_id)
                        ->where('p.dept_id', '=', $Dept_id)

                ->select(
                    'p.id',
                    'p.position_title',
                    DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                    DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
                )
                ->groupBy('p.id', 'p.position_title')
                ->get();


            // Attach employees to each position
            foreach ($vacant_positions as $position)
            {
                $position->employees = DB::table('employees as e') // Assuming you have an employees table
                    ->leftJoin('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
                    ->where('position_id', $position->id)
                    // ->where('rank','others')
                    ->get(['first_name', 'last_name','Admin_Parent_id','rank','nationality']); // Adjust according to your employee table fields
            }
            return view('resorts.workforce_planning.hoddashboard',compact('totalemployees','BudgetRejactedStatus','BudgetStatus','getNotifications','employees','LeftemployeesCount','HODpendingResponse','ManningPendingRequestCount','resort_id','resort_divisions_count','resort_departments_count','resort_positions_count','total_emp','positions','department_details','positionsWithEmployees','nextYear','Dept_id','vacant_positions'));
        }
        catch( \Exception $e ) {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
        }
    }

    public function GetYearBasePositions(Request $request)
    {
        try
        {
            $ResortId =  $request->ResortId;
            $Position_id = $request->Position_id;
            $Dept_id = $request->Dept_id;
            $year = $request->year;
            $vacant_positions = DB::table('resort_positions as p')
            ->leftJoin('position_monthly_data as pmd', 'p.id', '=', 'pmd.position_id')
            ->leftJoin('manning_responses as mr', function($join) use ($ResortId, $Dept_id, $year) {
                $join->on('pmd.manning_response_id', '=', 'mr.id')
                ->where('mr.year', '=', $year);
            })
            ->where('p.resort_id', '=', $ResortId)
                     ->where('p.dept_id', '=', $Dept_id)
            ->select(
                'p.id',
                'p.position_title',
                DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
            )
            ->groupBy('p.id', 'p.position_title')
            ->get();

            // Attach employees to each position
            foreach ($vacant_positions as $position)
            {
                $position->employees = DB::table('employees as e') // Assuming you have an employees table
                    ->leftJoin('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
                    ->where('position_id', $position->id)
                    // ->where('rank','others')
                    ->get(['first_name', 'last_name','Admin_Parent_id','rank','nationality']); // Adjust according to your employee table fields
            }
            $html =  view('resorts.renderfiles.DepartmentWisePositions',compact('vacant_positions'))->render();
            $response['success'] = true;
            $response['html'] = $html;

            $response['msg'] ="Fetched Successfully";

        }
        catch(\Exception $e)
        {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );

            $response['success'] = false;
            $response['msg'] = $e->getMessage();
        }
        return response()->json($response);
    }

    public function PendingDeartment()
    {
        $resort_id= $this->globalUser->resort_id;
        try
        {
            $ManningPendingRequestCount = ResortsParentNotifications::where('resort_id',$resort_id)->orderBy('created_at', 'desc')->first();
            $PendingDepartmentResoponse='';
            if($ManningPendingRequestCount != null)
            {
                $HODpendingResponse = ResortsChildNotifications::where("Parent_msg_id", $ManningPendingRequestCount->message_id)->where("response","No")->groupBy('Department_id')->orderBy('created_at', 'desc')->get();
                $iteration =1;
                if($HODpendingResponse->isNotEmpty())
                {
                    foreach($HODpendingResponse as $Dep)
                    {
                        $PendingDepartmentResoponse.= '<tr><td><p colspan="2">'.$iteration.'</td><td>'.$Dep->department->name.'</td></p></tr>';
                        $iteration ++;
                    }
                }
                else
                {
                    $PendingDepartmentResoponse= '<tr><td colspan="2" style="text-align: center;"> No Record Found..!</td></tr>';
                }

            }
            else
            {
                $PendingDepartmentResoponse= '<tr><td colspan="2" style="text-align: center;">No Record Found..!</td></tr>';
            }
            $response['success'] = true;
            $response['PendingDepartmentResoponse'] = $PendingDepartmentResoponse;
            $response['msg'] ="Fetched Successfully";
        }
        catch(\Exception $e)
        {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );

            $response['success'] = false;
            $response['msg'] = $e->getMessage();

        }
        return response()->json($response);

    }

    // public function ManningBudgetMonthWise(Request $request)
    // {


    //         $ManningBudgetmonthYearWise = explode("-",$request->ManningBudgetmonthYearWise);
    //         $month = array_key_exists(0,$ManningBudgetmonthYearWise)?$ManningBudgetmonthYearWise[0]:null;
    //         $year = array_key_exists(1,$ManningBudgetmonthYearWise)?$ManningBudgetmonthYearWise[1]:null;
    //         $message_id ='';

    //         $resort_departments = ResortDepartment::where('status', 'active')
    //         ->where('resort_id', $this->globalUser->resort_id)
    //         ->get();

    //         $departmenet_total=array();


    //         foreach ($resort_departments as $d) {
    //             $ManningResponse = ManningResponse::where("year", $year)
    //                 ->where("dept_id", $d->id)
    //                 ->where("resort_id", $this->globalUser->resort_id)
    //                 ->first();

    //             if ($ManningResponse)
    //             {
    //                 $monthWiseBudget = PositionMonthlyData::where("manning_response_id", $ManningResponse->id)
    //                 ->where("month", $month)
    //                 ->get();
    //                 $monthWiseBudgetVancatCount = PositionMonthlyData::where("manning_response_id", $ManningResponse->id)
    //                     ->select(
    //                         'position_monthly_data.*',
    //                         DB::raw('COALESCE(MAX(vacantcount), 0) as vacantcount'),
    //                     DB::raw('COALESCE(MAX(headcount), 0) as headcount')
    //                     )
    //                 ->where("month", $month)
    //                 ->first();
    //                 $old_emp_b_sum=0;
    //                 $new_emp_vacant =0;
    //                 $total_VacantS_sum=0;

    //                 foreach ($monthWiseBudget as $position)
    //                 {
    //                     if($position->headcount !=  0 )
    //                     {
    //                         $employee   = DB::table('employees as e') // Assuming you have an employees table
    //                         ->leftJoin('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
    //                         ->where('position_id', $position->position_id)
    //                         ->get(['ra.first_name','e.Dept_id','e.basic_salary','e.resort_id','e.id as Empid','ra.first_name', 'ra.last_name','e.Admin_Parent_id','e.rank','e.Dept_id','e.nationality','e.basic_salary']); // Adjust according to your employee table fields
    //                         $salary=0;
    //                         foreach($employee  as $e)
    //                         {

    //                             if($position->vacantcount)
    //                             {
    //                                 $maxVacantCount=0;

    //                                 for($i=0; $i < $monthWiseBudgetVancatCount->vacantcount; $i++)
    //                                 {

    //                                      if ($monthWiseBudgetVancatCount->vacantcount> $maxVacantCount)
    //                                      {
    //                                         $vacantDifference = $monthWiseBudgetVancatCount->vacantcount - $maxVacantCount;
    //                                         $vacant = Common::CheckVacantBudgetCost($vacantDifference);
    //                                         $new_emp_vacant = $vacant['total_cost'];
    //                                         $maxVacantCount =  $monthWiseBudgetVancatCount->vacantcount;
    //                                     }
    //                                 }
    //                                 $total_VacantS_sum += $new_emp_vacant;

    //                                 $position->vacant = $new_emp_vacant ;
    //                             }

    //                             $checkBudetSalary = DB::table('store_manning_response_parents as t1') // Assuming you have an employees table
    //                             ->leftJoin('store_manning_response_children as t2','t1.id','=','t2.Parent_SMRP_id')
    //                             ->where('t1.Resort_id',  $this->globalUser->resort_id)
    //                             ->where('t1.Budget_id', $ManningResponse->id)
    //                             ->where('t1.Department_id', $e->Dept_id)
    //                             ->where('t2.Emp_id', $e->Empid )
    //                             ->first(['t2.Current_Basic_salary','t2.Proposed_Basic_salary']);
    //                                 // Adjust according to your employee table fields

    //                             $position->emp_id = $e->Empid;
    //                             $position->first_name = $e->first_name;

    //                             $salary = ( isset($checkBudetSalary) &&  $checkBudetSalary->Proposed_Basic_salary > 0) ? $checkBudetSalary->Proposed_Basic_salary:  $e->basic_salary;

    //                             $position->budgetSum =  Common::CheckemployeeBudgetCost($e->nationality, $this->globalUser->resort_id, $salary );
    //                             $old_emp_b_sum +=  Common::CheckemployeeBudgetCost($e->nationality, $this->globalUser->resort_id, $salary );
    //                         }


    //                         $position->salary = $salary;
    //                         $monthWiseBudgetArray = $monthWiseBudget->map(function ($budget) use ($d,$ManningResponse) {
    //                             $budget['dept_id'] = $d->id;
    //                             return $budget;
    //                         })->toArray();
    //                         $d->monthWiseBudget = $monthWiseBudgetArray;
    //                         $BudgetStatus = BudgetStatus::where("Department_id",$d->id)->where("Budget_id",$ManningResponse->id)->latest()->first();

    //                         $d->BudgetStatus = isset($BudgetStatus) ? $BudgetStatus->comments : 'Pending from the HOD';
    //                         $d->BudgetPageLink =1;
    //                         $d->Message_id = isset($BudgetStatus) ? $BudgetStatus->message_id : '';


    //                     }
    //                     else
    //                     {

    //                     }
    //                     $departmenet_total[$d->division_id]= ceil($total_VacantS_sum);
    //                     $d->OldEmployeesBudgetValue =   ceil($total_VacantS_sum );
    //                 }


    //             }
    //             else
    //             {
    //                 $d->BudgetStatus = 'No Record found..!';
    //                 $d->BudgetPageLink =0;
    //                 $d->Message_id ='';
    //             }


    //         }

    //         $resort_divisions = ResortDivision::where('status','active')->where('resort_id',$this->globalUser->resort_id)->get();


    //         $currency =  $this->currencylogo;
    //         $html =  view('resorts.renderfiles.MonthWiseManningBudget',compact('year','currency',
    //                     'resort_divisions','resort_departments','departmenet_total'))->render();
    //         $response['success'] = true;
    //         $response['TotalBudget'] =  "<img class='currency-budget-icon h-18' src=".$currency."  >". number_format(15000,2);
    //         $response['html'] = $html;
    //         $response['msg'] ="Fetched Successfully";
    //      try{}
    //     catch(\Exception $e)
    //     {
    //         \Log::emergency( "File: ".$e->getFile() );
    //         \Log::emergency( "Line: ".$e->getLine() );
    //         \Log::emergency( "Message: ".$e->getMessage() );

    //         $response['success'] = false;
    //         $response['msg'] = $e->getMessage();
    //     }
    //     return response()->json($response);
    // }
    public function  ManningBudgetMonthWise (Request $request)
    {

             $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
            $ManningBudgetmonthYearWise = explode("-",$request->ManningBudgetmonthYearWise);
            $month = array_key_exists(0,$ManningBudgetmonthYearWise)? (string) (int) $ManningBudgetmonthYearWise[0]:null;
            $year = array_key_exists(1,$ManningBudgetmonthYearWise)?$ManningBudgetmonthYearWise[1]:null;
            $message_id ='';
            $resort_departments = ResortDepartment::where('status', 'active')->where('resort_id', $this->globalUser->resort_id) ->get();
            $departmenet_total=array();


            $departmenet_OldEmptotal=array();
            foreach ($resort_departments as $d)
            {
                $ManningResponse = ManningResponse::where("year", $year) ->where("dept_id", $d->id)->where("resort_id", $this->globalUser->resort_id)->first();

                $dept_id = $d->id;
                if ($ManningResponse)
                {
                    $monthWiseBudget = PositionMonthlyData::where("manning_response_id", $ManningResponse->id)->where("month", $month)->get();

                     $getPositions = DB::table('resort_positions as p')
                                        ->leftJoin('employees as e', 'p.id', '=', 'e.Position_id')
                                        ->leftJoin('position_monthly_data as pmd', 'p.id', '=', 'pmd.position_id')
                                        ->leftJoin('manning_responses as mr', function($join) use ( $dept_id, $year, $month) {
                                            $join->on('pmd.manning_response_id', '=', 'mr.id')
                                                ->where('mr.year', '=', $year);
                                        })
                                        ->leftJoin('budget_statuses as bs', function($join) {
                                            $join->on('mr.id', '=', 'bs.Budget_id')
                                                ->whereRaw('bs.id = (SELECT MAX(id) FROM budget_statuses WHERE Budget_id = mr.id)');
                                        })
                                        ->where('p.resort_id', '=',  $this->globalUser->resort_id)
                                        ->where('p.dept_id', '=', $dept_id)
                                        ->select(
                                            'p.dept_id',
                                            'p.id as Position_id',
                                            'mr.id as Budget_id',
                                            'p.position_title',
                                            'p.dept_id',
                                            'pmd.month',
                                            DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                                            DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount'),
                                            'bs.id as budget_status_id',
                                            'bs.status as budget_status',

                                        )
                                        ->where('bs.status',"!=", "Rejected")
                                        ->orderBy('bs.id', 'desc')
                                        ->groupBy('p.id', 'p.position_title', 'mr.id', 'p.dept_id', 'bs.id', 'bs.status')
                                        ->havingRaw('COUNT(e.id) > 0')
                                        ->get();

                    if($getPositions->isNotEmpty())
                    {

                            $totalMothwiseOldEmployeecost = 0;
                            $totalVacantCostMontwise = [];
                            foreach ($getPositions as $position)
                            {
                                $employees = DB::table('employees as e')
                                        ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                        ->where('position_id', $position->Position_id)
                                        ->where('Dept_id', $position->dept_id)
                                        ->get([
                                            'e.resort_id',
                                            'e.id as Empid',
                                            'ra.first_name',
                                            'ra.last_name',
                                            'e.Position_id',
                                            'e.Admin_Parent_id',
                                            'e.rank',
                                            'e.Dept_id',
                                            'e.nationality',
                                            'e.basic_salary',
                                            'e.incremented_date',
                                            DB::raw('0 as Proposed_Basic_salary')

                                        ]);


                                if ($employees->isNotEmpty() && $position->Budget_id != "")
                                {
                                    $sumofVacantCost = [];
                                    $VacantSum = 0;

                                    foreach ($employees as $emp)
                                    {

                                        $vacant_positions = DB::table('store_manning_response_parents as t1')
                                                                ->join("store_manning_response_children as t2", "t2.Parent_SMRP_id", "=", "t1.id")
                                                                ->join('employees as t3', 't3.id', "=", "t2.Emp_id")
                                                                ->join('resort_positions as t4', 't4.id', "=", "t3.Position_id")
                                                                ->leftJoin('position_monthly_data as pmd', 't4.id', '=', 'pmd.position_id')
                                                                ->leftJoin('manning_responses as mr', function($join) use ( $year) {
                                                                    $join->on('pmd.manning_response_id', '=', 'mr.id');
                                                                        // ->where('mr.year', '=', $year);
                                                                })
                                                                ->where('t1.resort_id', '=',  $this->globalUser->resort_id)
                                                                ->where('t1.Department_id', '=',  $position->dept_id)
                                                                ->where('t3.Position_id', '=',  $emp->Position_id)
                                                                ->where('t2.Emp_id', '=',  $emp->Empid)

                                                                ->where('t1.Budget_id', '=', $position->Budget_id)
                                                                ->select(
                                                                    't1.id as smrp_id',
                                                                    't2.id as smrp_child_id',
                                                                    't2.Current_Basic_salary as basic_salary',
                                                                    't4.id',
                                                                    'mr.id as Budget_id',
                                                                    't4.position_title',
                                                                    't4.dept_id',
                                                                    't2.Emp_id',
                                                                    't2.Months',
                                                                    'pmd.month',
                                                                    't2.Proposed_Basic_salary',
                                                                    DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                                                                    DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
                                                                )
                                                                ->groupBy('t4.id', 't4.position_title')
                                                                ->first();
                                                                $emp->Proposed_Basic_salary  =   $vacant_positions->Proposed_Basic_salary ?? $emp->basic_salary;
                                                                $emp->vacantData = $vacant_positions;
                                                                    // dd($vacant_positions);
                                        if($vacant_positions)
                                        {
                                            $lastIncrementMonth = date("m",strtotime($emp->incremented_date));
                                            $basicSalary = $vacant_positions->basic_salary;
                                            $proposedSalary = $vacant_positions->Proposed_Basic_salary > 0 ? $vacant_positions->Proposed_Basic_salary : $basicSalary;
                                            $monthlySalary = ( $month  < $lastIncrementMonth) ? $basicSalary : $proposedSalary;



                                            $totalMothwiseOldEmployeecost+= (float)    Common::CheckemployeeBudgetCost($emp->nationality, $emp->resort_id, $monthlySalary);
                                        }
                                    }
                                }

                                if($position->vacantcount > 0)
                                {

                                    $maxVacantCount = 0;
                                    for($i=1; $i < 12; $i++)
                                    {

                                         $monthlyData = DB::table('position_monthly_data')
                                                    ->where('position_id', $position->Position_id)
                                                    ->where('month', $i)
                                                    ->where('manning_response_id', $position->Budget_id)
                                                    ->first();
                                                $vacantcount = $monthlyData->vacantcount ?? 0;
                                                if ($vacantcount > $maxVacantCount)
                                                {
                                                    $vacantDifference = $vacantcount - $maxVacantCount;

                                                    $vacant = Common::CheckVacantBudgetCost($vacantDifference);

                                                    $totalVacantCostMontwise[$i][] =  [number_format($vacant['total_cost'],2)];


                                                    $maxVacantCount =  $vacantcount;
                                                }
                                                else
                                                {

                                                    $totalVacantCostMontwise[$i][] =  [$i=>number_format(0.00,2)];
                                                }
                                    }

                                    $totalVacantCostMontwise[] =  $VacantSum;
                                    $position->vacant =  $VacantSum ;
                                }
                                else
                                {
                                    $position->vacant = 0;
                                }
                            }
                            $sum = 0;


                            if(array_key_exists($month, $totalVacantCostMontwise))
                            {
                               if($totalVacantCostMontwise[$month]!=0)
                                {
                                    foreach ($totalVacantCostMontwise[$month] as $subArray)
                                    {
                                        foreach ($subArray as $value)
                                        {
                                            $sum += (float) str_replace(',', '', $value);
                                        }
                                    }
                                }
                                else
                                {
                                    $sum = 0;
                                }

                            }
                            else
                            {
                                $sum = 0;
                            }
                            $BudgetStatus = BudgetStatus::where("Department_id",$d->id)->where("Budget_id",$ManningResponse->id)->latest()->first();
                            $d->BudgetStatus = isset($BudgetStatus) ? $BudgetStatus->comments : 'Pending from the HOD';

                            $d->BudgetPageLink =1;
                            $d->Message_id = isset($BudgetStatus) ? $BudgetStatus->message_id : '';

                            $departmenet_total[$d->division_id]= ceil($totalMothwiseOldEmployeecost+$sum );
                            $d->OldEmployeesBudgetValue =   ceil( $totalMothwiseOldEmployeecost+$sum);


                                $monthWiseBudgetArray = $monthWiseBudget->map(function ($budget) use ($d,$ManningResponse)
                                        {
                                            $budget['dept_id'] = $d->id;
                                            return $budget;
                                        })->toArray();
                                    $d->monthWiseBudget = $monthWiseBudgetArray;

                            $d->monthWiseBudget = $monthWiseBudgetArray;
                        $d->getPositions = $getPositions;
                    }
                    else{
                        $budget = (object)collect();
                        $d->getPositions = (object)collect();

                    }
                }


            }


            $resort_divisions = ResortDivision::where('status','active')->where('resort_id',$this->globalUser->resort_id)->get();


            $currency =  $this->currencylogo;
            $html =  view('resorts.renderfiles.MonthWiseManningBudget',compact('year','currency',
                        'resort_divisions','resort_departments','departmenet_total','available_rank'))->render();
            $response['success'] = true;
            $response['TotalBudget'] =  "<img class='currency-budget-icon h-18' src=".$currency."  >". number_format(15000,2);
            $response['html'] = $html;
            $response['msg'] ="Fetched Successfully";


        return response()->json($response);

    }


    public function getUpdatedData()
    {
        $resort_id = $this->globalUser->resort_id;
        for ($i = 0; $i <= 5; $i++) {
            $nextDate[] = Carbon::now()->addDays($i)->toDateString();
        }

        for ($i = 5; $i >= 0; $i--) {
            $previousDate[] = Carbon::now()->subDay($i)->toDateString();
        }

        $newArray = array_merge($nextDate, $previousDate);

        // Query for occupancy data on previous, current, and next day
        $occupancies = Occuplany::where('resort_id', $resort_id)
            ->whereIn('occupancydate', $newArray)
            ->get(['id','occupancyinPer', 'occupancydate', 'occupancytotalRooms', 'occupancyOccupiedRooms']);
        return response()->json(['occupancies' => $occupancies]);
    }


    public function getAiInsights(Request $request)
{
    try {
        $resort_id = $this->globalUser->resort_id;
        $months = $request->input('months', []);

        $aiInsights = [];

        foreach ($months as $monthYear) {
            $month_name = strtolower(substr($monthYear, 0, 3));
            $monthYearParts = explode(' ', $monthYear);
            $month = date('m', strtotime($monthYearParts[0]));
            $year = $monthYearParts[1];

            $url = env('AI_URL') . 'predict_staff';

            $occupancy = Occuplany::where('resort_id', $resort_id)
                ->whereYear('occupancydate', $year)
                ->whereMonth('occupancydate', $month)
                ->avg('occupancyinPer');

            $occupancy = round($occupancy ?? 0, 2);

            $curl = curl_init();
            $postFields = json_encode([
                "month" => $month_name,
                "occupancy_percent" => $occupancy
            ]);
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                ],
            ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                return response()->json(['status' => false, 'message' => $err]);
            }

            $AI_Data = json_decode($response, true);

            $aiInsights[] = [
                'month' => $monthYear,
                'occupancyRate' => $occupancy,
                'hiringData' => $AI_Data['required_staff'] ?? 0,
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'AI Insights fetched successfully',
            'occupancyRates' => array_column($aiInsights, 'occupancyRate'),
            'hiringData' => array_column($aiInsights, 'hiringData'),
        ]);

    } catch (\Exception $e) {
        \Log::error('Error fetching AI Insights: ' . $e->getMessage());
        \Log::error('File: ' . $e->getFile());
        \Log::error('Line: ' . $e->getLine());
        \Log::error('Trace: ' . $e->getTraceAsString());

        return response()->json(['status' => false, 'error' => $e->getMessage()], 500);
    }
}

}
