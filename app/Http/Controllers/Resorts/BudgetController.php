<?php
namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ManningandbudgetingConfigfiles;
use App\Services\BudgetCalculationService;
use App\Jobs\ConsolidateBudgetImportJob;
use App\Models\StoreConsolidateBudgetParent;
use App\Models\StoreConsolidateBudgetChild;
use App\Models\StoreManningResponseParent;
use App\Models\StoreManningResponseChild;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortPosition;
use App\Models\ResortBudgetCost;
use App\Helpers\Common;
use App\Models\ManningResponse;
use App\Models\PositionMonthlyData;
use URL;
use DB;
use Validator;
use Auth;
use Carbon\Carbon;
use App\Models\BudgetStatus;
use App\Models\ResortsChildNotifications;
use App\Models\ResortVacantBudgetCostAssignment;
use App\Models\ResortSection;
use App\Models\ResortEmployeeBudgetCostConfiguration;
use App\Models\ResortVacantBudgetCost;
use App\Models\ResortVacantBudgetCostConfiguration;
use App\Models\ResortSiteSettings;
use App\Models\PublicHoliday;


class BudgetController extends Controller
{
    protected $budgetCalculationService;
    protected $resort;

    public function __construct(BudgetCalculationService $budgetCalculationService)
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function ViewManning(Request $request)
    {
        $page_title = 'View Manning';
        $year = $request->input('year') ?? date('Y');
        $resortId = auth()->guard('resort-admin')->user()->resort_id;
        $Budget_id = $data['manning_response_id'] ?? null;
        $Message_id = $data['Message_id'] ?? null;
        $departmentsData = collect();
        $rank = config('settings.Position_Rank');
        $employeeRankPosition = Common::getEmployeeRankPosition( $this->resort->getEmployee);

        if(($employeeRankPosition['position'] != "HR" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" )) && ($employeeRankPosition['position'] != "GM" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" )) && ($employeeRankPosition['position'] != "Finance" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" ))) {
            $departments = ResortDepartment::where('id',$this->resort->getEmployee->Dept_id)->where('resort_id', $resortId)->get();
        }elseif($employeeRankPosition['position'] == "Finance" && ($employeeRankPosition['rank'] == "HOD" || $employeeRankPosition['rank'] == "XCOM" )){
            $employeeDeptId = $this->resort->getEmployee->Dept_id;
            // Get all Finance/GM approved dept ids
            $manningResponseDeptsId = ManningResponse::where('year', $year)
                ->where('resort_id', $resortId)
                ->whereIn('budget_process_status', ['Finance', 'GM'])
                ->pluck('dept_id')
                ->toArray();

            // If no finance/GM dept found → fallback to employee dept
            $deptIds = !empty($manningResponseDeptsId)
                ? $manningResponseDeptsId
                : [$employeeDeptId];

            // Final optimized department fetch
            $departments = ResortDepartment::where('resort_id', $resortId)
                ->whereIn('id', $deptIds)
                ->get();

        }elseif($employeeRankPosition['position'] == "GM" && ($employeeRankPosition['rank'] == "HOD" || $employeeRankPosition['rank'] == "XCOM" )){
            $employeeDeptId = $this->resort->getEmployee->Dept_id;
            // Get all Finance/GM approved dept ids
            $manningResponseDeptsId = ManningResponse::where('year', $year)
                ->where('resort_id', $resortId)
                ->where('budget_process_status', 'GM')
                ->pluck('dept_id')
                ->toArray();

            // If no finance/GM dept found → fallback to employee dept
            $deptIds = !empty($manningResponseDeptsId)
                ? $manningResponseDeptsId
                : [$employeeDeptId];

            // Final optimized department fetch
            $departments = ResortDepartment::where('resort_id', $resortId)
                ->whereIn('id', $deptIds)
                ->get();
        }
        else
        {
            $departments = ResortDepartment::where('resort_id', $resortId)->get();
        }

        foreach ($departments as $department) {
            // Get positions for each department
            // Ensure we get vacant count from manning_responses properly filtered by position, dept_id, year, resort_id
            $departmentPositions = DB::table('resort_positions as p')
                ->leftJoin('position_monthly_data as pmd', 'p.id', '=', 'pmd.position_id')
                ->leftJoin('manning_responses as mr', function($join) use ($year, $resortId, $department) {
                    $join->on('pmd.manning_response_id', '=', 'mr.id')
                         ->where('mr.year', '=', $year)
                         ->where('mr.resort_id', '=', $resortId)
                         ->where('mr.dept_id', '=', $department->id);
                })
                ->where('p.resort_id', '=', $resortId)
                ->where('p.dept_id', '=', $department->id)
                ->select(
                    'p.id',
                    'mr.id as Budget_id',
                    'p.position_title',
                    'p.dept_id',
                    DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                    DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
                )
                ->groupBy('p.id', 'p.position_title', 'mr.id')
                ->get();

            // Get employees for each position
            foreach ($departmentPositions as $position) {
                $position->employees = DB::table('employees as e')
                    ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                    ->where('position_id', $position->id)
                    ->where('e.status', 'Active')
                    ->get([
                        'e.resort_id',
                        'e.id as Empid',
                        'ra.first_name',
                        'ra.last_name',
                        'e.Admin_Parent_id',
                        'e.rank',
                        'e.Dept_id',
                        'e.nationality',
                        'e.basic_salary'
                    ]);

                // Initialize vacant position properties
                $position->proper_vacant_count = 0;
                $position->is_in_manning_request = false;
                $position->vacant_details = [];

                // Get vacant count using resorts_child_notifications table (joined with manning_responses)
                // resorts_child_notifications -> budget_statuses -> manning_responses -> position_monthly_data
                $budgetStatus = BudgetStatus::where('Budget_id', $position->Budget_id)->first();

                if ($budgetStatus) {
                    // Check if THIS specific position is in the manning request via resorts_child_notifications
                    $childNotification = ResortsChildNotifications::where('Parent_msg_id', $budgetStatus->message_id)
                        ->where('Position_id', $position->id)
                        ->where('Department_id', $position->dept_id)
                        ->first();

                    if ($childNotification) {
                        $position->is_in_manning_request = true;

                        // Get vacant count from position_monthly_data for this position
                        // Filtered by the correct manning_response_id (from Budget_id)
                        $positionMonthlyData = PositionMonthlyData::where('position_id', $position->id)
                            ->where('manning_response_id', $position->Budget_id)
                            ->get();

                        // Get maximum vacant count across all months for this position
                        $maxVacantCount = 0;
                        foreach ($positionMonthlyData as $monthlyData) {
                            $vacantCount = $monthlyData->vacantcount ?? 0;
                            $maxVacantCount = max($maxVacantCount, $vacantCount);
                        }

                        // Set vacant count from position_monthly_data (from manning_responses)
                        $position->proper_vacant_count = $maxVacantCount;

                        // If no vacant count from position_monthly_data, check resort_vacant_budget_costs as fallback
                        if ($position->proper_vacant_count == 0) {
                            $actualVacantCount = DB::table('resort_vacant_budget_costs')
                                ->where('position_id', $position->id)
                                ->where('department_id', $position->dept_id)
                                ->where('resort_id', $resortId)
                                ->where('year', $year)
                                ->distinct('vacant_index')
                                ->count('vacant_index');

                            if ($actualVacantCount > 0) {
                                $position->proper_vacant_count = $actualVacantCount;
                            }
                        }

                        // Get vacant details from resort_vacant_budget_costs for each vacant index
                        if ($position->proper_vacant_count > 0) {
                            $vacantRecords = ResortVacantBudgetCost::where('position_id', $position->id)
                                ->where('department_id', $position->dept_id)
                                ->where('resort_id', $resortId)
                                ->where('year', $year)
                                ->orderBy('vacant_index')
                                ->get();

                            foreach ($vacantRecords as $vacantBudgetCost) {
                                $position->vacant_details[$vacantBudgetCost->vacant_index] = $vacantBudgetCost;
                            }
                        }
                    }
                }
            }


            // Store manning response parent for department
            if ($Budget_id) {
                $smrp = StoreManningResponseParent::updateOrCreate(
                    [
                        "Resort_id" => $resortId,
                        "Department_id" => $department->id,
                        "Budget_id" => $Budget_id
                    ],
                    [
                        "Resort_id" => $resortId,
                        "Department_id" => $department->id,
                        "Budget_id" => $Budget_id
                    ]
                );

                // Store manning response children
                foreach ($departmentPositions as $position) {
                    foreach ($position->employees as $employee)
                    {
                        StoreManningResponseChild::updateOrCreate(
                            [
                                "Parent_SMRP_id" => $smrp->id,
                                'Emp_id' => $employee->Empid
                            ],
                            [
                                "Parent_SMRP_id" => $smrp->id,
                                'Emp_id' => $employee->Empid,
                                'Current_Basic_salary' => $employee->basic_salary ?? 0,
                            ]
                        );
                    }
                }
            }

            // Get vacant positions for department
            $vacant_positions = null;
            if ($Budget_id) {
                $vacant_positions = DB::table('store_manning_response_parents as t1')
                    ->join("store_manning_response_children as t2", "t2.Parent_SMRP_id", "=", "t1.id")
                    ->join('employees as t3', 't3.id', "=", "t2.Emp_id")
                    ->join('resort_positions as t4', 't4.id', "=", "t3.Position_id")
                    ->leftJoin('position_monthly_data as pmd', 't4.id', '=', 'pmd.position_id')
                    ->leftJoin('manning_responses as mr', function($join) use ($resortId, $year) {
                        $join->on('pmd.manning_response_id', '=', 'mr.id')
                            ->where('mr.year', '=', $year);
                    })
                    ->where('t1.resort_id', '=', $resortId)
                    ->where('t1.Department_id', '=', $department->id)
                    ->where('t1.Budget_id', '=', $Budget_id)
                    ->select(
                        't1.id as smrp_id',
                        't2.id as smrp_child_id',
                        't2.Current_Basic_salary as basic_salary',
                        't4.id',
                        'mr.id as Budget_id',
                        't4.position_title',
                        't4.dept_id',
                        't2.Proposed_Basic_salary',
                        DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                        DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
                    )
                    ->groupBy('t4.id', 't4.position_title')
                    ->get();

                foreach ($vacant_positions as $position) {
                    $position->employees = DB::table('employees as e')
                        ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                        ->where('position_id', $position->id)
                        ->get([
                            'e.resort_id',
                            'e.id as Empid',
                            'ra.first_name',
                            'ra.last_name',
                            'e.Admin_Parent_id',
                            'e.rank',
                            'e.Dept_id',
                            'e.nationality',
                            'e.basic_salary'
                        ]);
                }
            }

            $Budget_id = ManningResponse::where('year', $year)->where('resort_id', $resortId)->where('dept_id', $department->id)->latest()->first();
            // Add department data to collection
            if($Budget_id){
                $departmentsData->push([
                    'department' => $department,
                    'positions' => $departmentPositions,
                    'vacant_positions' => $vacant_positions,
                    'Budget_id' => $Budget_id->id ?? null
                ]);
            }
        }

        // Calculate summary statistics
        $summary = [
            'total_positions' => 0,
            'total_employees' => 0,
            'total_vacant' => 0,
            'total_budget' => 0
        ];

        foreach ($departmentsData as $deptData) {
            foreach ($deptData['positions'] as $position) {
                $summary['total_positions']++;
                $summary['total_employees'] += count($position->employees);
                $summary['total_vacant'] += $position->vacantcount;
                foreach ($position->employees as $employee) {
                    $summary['total_budget'] += $employee->basic_salary;
                }
            }
        }


        $resortDepartmentsCount = ResortDepartment::where('resort_id', $resortId)->count();
        $resortManningResponseCount = ManningResponse::where('year', $year) ->where('resort_id', $resortId)->count();
            if($resortDepartmentsCount == $resortManningResponseCount){
                $isBudgetCompleted = true;
            }else{
                $isBudgetCompleted = false;
            }
            // dd($departmentsData);

            return view('resorts.budget.manning', compact(
                'page_title',
                'Budget_id',
                'Message_id',
                'resortId',
                'departmentsData',
                'summary',
                'year',
                'employeeRankPosition',
                'isBudgetCompleted'
            ));

       try {  } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile(). " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return back()->with('error', 'An error occurred while fetching budget data.');
        }
    }

    public function CompareBudget($deptID, $budgetId)
    {
        if(Common::checkRouteWisePermission('resort.budget.comparebudget,{id}',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }

        $page_title = 'Compare Budget';
        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        if ($deptID) {

                // $positionMonthlyDataIds = $data['position_monthly_data_id'];
                // $Budget_id = $data['manning_response_id'];
                // $Message_id = $data['Message_id'];
                // $year
                $getPositions = DB::table('resort_positions as p')
                    ->leftJoin('position_monthly_data as pmd', 'p.id', '=', 'pmd.position_id')
                    ->leftJoin('manning_responses as mr', function($join) use ( $deptID,$budgetId) {
                        $join->on('pmd.manning_response_id', '=', 'mr.id')
                        // ->where('mr.year', '=', $year)
                        ->where('mr.resort_id', '=', $this->resort->resort_id)

                        ->where('mr.dept_id', '=', $deptID)
                        ->where('mr.id', '=', $budgetId);
                    })

                            ->where('p.dept_id', '=', $deptID)
                    ->select(
                       'p.id',
                                'mr.id as Budget_id',
                                'p.position_title',
                                'p.dept_id',
                                DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                                DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
                    )
                    ->groupBy('p.id', 'p.position_title')
                    ->get();


                foreach ($getPositions as $position)
                {
                    $position->employees = DB::table('employees as e') // Assuming you have an employees table
                        ->leftJoin('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
                        ->where('position_id', $position->id)
                        // ->where('rank','others')
                        ->get(['e.id as Empid','ra.first_name', 'ra.last_name','e.Admin_Parent_id','e.rank','e.Dept_id','e.nationality','e.basic_salary','e.incremented_date']); // Adjust according to your employee table fields
                }
                $vacant_positions = DB::table('store_manning_response_parents as t1')
                ->join("store_manning_response_children as t2","t2.Parent_SMRP_id","=","t1.id")
                ->join('employees as t3','t3.id',"=","t2.Emp_id")
                ->join('resort_positions as t4','t4.id',"=","t3.Position_id")
                ->leftJoin('position_monthly_data as pmd', 't4.id', '=', 'pmd.position_id')
                ->leftJoin('manning_responses as mr', function($join) use ( $deptID, $budgetId) {
                        $join->on('pmd.manning_response_id', '=', 'mr.id')
                        ->where('mr.id', '=', $budgetId);
                })
                ->where('t1.resort_id', '=', $this->resort->resort_id)
                ->where('t1.Department_id', '=',  $deptID)
                ->select(
                    't1.id as smrp_id',
                    't2.id as smrp_child_id',
                    't2.Current_Basic_salary as basic_salary',
                    't4.id',
                    'mr.id as Budget_id',
                    't4.position_title',
                    't4.dept_id',
                    't2.Proposed_Basic_salary',
                    DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                    DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
                )
                ->groupBy('t4.id', 't4.position_title')
                ->get(['t1.*' ]);

                foreach ($vacant_positions as $position)
                {
                    $position->employees = DB::table('employees as e') // Assuming you have an employees table
                        ->leftJoin('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
                        ->where('position_id', $position->id)
                        // ->where('rank','others')
                        ->get(['e.resort_id','e.id as Empid','ra.first_name', 'ra.last_name','e.Admin_Parent_id','e.rank','e.Dept_id','e.nationality','e.basic_salary','e.incremented_date']); // Adjust according to your employee table fields
                }
            }
            return view('resorts.budget.compare',compact('page_title','vacant_positions','available_rank'));
        try {} catch( \Exception $e ) {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
        }
    }

    public function ViewBudget(Request $request)
    {
        if(Common::checkRouteWisePermission('resort.budget.viewbudget',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }

        $page_title = 'View Budget';

        // Use requested year or fallback to current year
        $year = $request->input('year') ?? date('Y');

        $resortId = auth()->guard('resort-admin')->user()->resort_id;
        $Budget_id = $request->input('manning_response_id') ?? null;
        $Message_id = $request->input('Message_id') ?? null;

        $rank = config('settings.Position_Rank');

        $employeeRankPosition = Common::getEmployeeRankPosition( $this->resort->getEmployee);

        if($this->resort->is_master_admin == 0){
            if(($employeeRankPosition['position'] != "HR" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" )) && ($employeeRankPosition['position'] != "GM" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" )) && ($employeeRankPosition['position'] != "Finance" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" ))) {
                $rank_wise_departments = ResortDepartment::where('id', $this->resort->getEmployee->Dept_id)
                    ->where('resort_id', $resortId)
                    ->pluck('id')->toArray();
            }
            elseif($employeeRankPosition['position'] == "Finance" && ($employeeRankPosition['rank'] == "HOD" || $employeeRankPosition['rank'] == "XCOM" )){
                $employeeDeptId = $this->resort->getEmployee->Dept_id;
                // Get all Finance/GM approved dept ids
                $manningResponseDeptsId = ManningResponse::where('year', $year)
                    ->where('resort_id', $resortId)
                    ->whereIn('budget_process_status', ['Finance', 'GM'])
                    ->pluck('dept_id')
                    ->toArray();

                // If no finance/GM dept found → fallback to employee dept
                $deptIds = !empty($manningResponseDeptsId)
                    ? $manningResponseDeptsId
                    : [$employeeDeptId];

                // Final optimized department fetch
                $rank_wise_departments = ResortDepartment::where('resort_id', $resortId)
                    ->whereIn('id', $deptIds)
                    ->pluck('id')
                    ->toArray();

            }elseif($employeeRankPosition['position'] == "GM" && ($employeeRankPosition['rank'] == "HOD" || $employeeRankPosition['rank'] == "XCOM" )){
                $employeeDeptId = $this->resort->getEmployee->Dept_id;
                // Get all Finance/GM approved dept ids
                $manningResponseDeptsId = ManningResponse::where('year', $year)
                    ->where('resort_id', $resortId)
                    ->where('budget_process_status', 'GM')
                    ->pluck('dept_id')
                    ->toArray();

                // If no finance/GM dept found → fallback to employee dept
                $deptIds = !empty($manningResponseDeptsId)
                    ? $manningResponseDeptsId
                    : [$employeeDeptId];

                // Final optimized department fetch
                $rank_wise_departments = ResortDepartment::where('resort_id', $resortId)
                    ->whereIn('id', $deptIds)
                    ->pluck('id')
                    ->toArray();
            }
            else{
                $rank_wise_departments = ResortDepartment::where('resort_id', $resortId)
                    ->pluck('id')->toArray();
            }
        }else{
            $rank_wise_departments = ResortDepartment::where('resort_id', $resortId)
                    ->pluck('id')->toArray();
        }

        $departments = ManningResponse::with('department')
            ->whereHas('department', function ($query) use ($resortId, $rank_wise_departments) {
                $query->where('resort_id', $resortId)
                    ->whereIn('id', $rank_wise_departments);
            })
            ->leftJoin('budget_statuses as bs', function ($join) {
                $join->on('bs.Budget_id', '=', 'manning_responses.id');
            })
            ->where('manning_responses.year', $year)
            ->where('manning_responses.resort_id', $resortId)
            ->whereIn('bs.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('budget_statuses')
                    ->groupBy('Budget_id');
            })
            ->groupBy('manning_responses.id')
            ->orderBy('bs.id', 'desc')
            ->whereNotIn('bs.status', ['Rejected', 'Accepted', 'Approved'])
            ->get(['bs.Budget_id','bs.message_id as Message_id', 'manning_responses.*']);

        foreach ($departments as $department) {
            // Ensure we get vacant count from manning_responses properly filtered by position, dept_id, year, resort_id
            $department->departmentPositions = DB::table('resort_positions as p')
                ->leftJoin('employees as e', 'e.Position_id', '=', 'p.id')
                ->leftJoin('position_monthly_data as pmd', 'p.id', '=', 'pmd.position_id')
                ->leftJoin('manning_responses as mr', function($join) use ($year, $resortId, $department) {
                    $join->on('pmd.manning_response_id', '=', 'mr.id')
                         ->where('mr.year', '=', $year)
                         ->where('mr.resort_id', '=', $resortId)
                         ->where('mr.dept_id', '=', $department->dept_id);
                })
                ->where('p.resort_id', '=', $resortId)
                ->where('p.dept_id', '=', $department->dept_id)
                ->select(
                    'p.id',
                    'mr.id as Budget_id',
                    'p.position_title',
                    'p.id as Position_id',
                    'p.dept_id',
                    DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                    DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
                )
                ->groupBy('p.id', 'p.position_title', 'mr.id')
                ->havingRaw('COUNT(e.id) > 0')
                ->get();

            if ($department->departmentPositions->isNotEmpty()) {
                foreach ($department->departmentPositions as $position) {
                    $employees = DB::table('employees as e')
                        ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                        ->where('position_id', $position->Position_id)
                        ->where('Dept_id', $position->dept_id)
                        ->where('e.status', 'Active')
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

                    $position->employees = $employees;

                    // Initialize vacant position properties
                    $position->proper_vacant_count = 0;
                    $position->is_in_manning_request = false;
                    $position->vacant_details = [];

                    // Get vacant count using resorts_child_notifications table (joined with manning_responses)
                    // resorts_child_notifications -> budget_statuses -> manning_responses -> position_monthly_data
                    $budgetStatus = BudgetStatus::where('Budget_id', $position->Budget_id)->first();

                    if ($budgetStatus) {
                        // Check if THIS specific position is in the manning request via resorts_child_notifications
                        $childNotification = ResortsChildNotifications::where('Parent_msg_id', $budgetStatus->message_id)
                            ->where('Position_id', $position->id)
                            ->where('Department_id', $position->dept_id)
                            ->first();

                        if ($childNotification) {
                            $position->is_in_manning_request = true;

                            // Get vacant count from position_monthly_data for this position
                            // Filtered by the correct manning_response_id (from Budget_id)
                            $positionMonthlyData = PositionMonthlyData::where('position_id', $position->id)
                                ->where('manning_response_id', $position->Budget_id)
                                ->get();

                            // Get maximum vacant count across all months for this position
                            $maxVacantCount = 0;
                            foreach ($positionMonthlyData as $monthlyData) {
                                $vacantCount = $monthlyData->vacantcount ?? 0;
                                $maxVacantCount = max($maxVacantCount, $vacantCount);
                            }

                            // Set vacant count from position_monthly_data (from manning_responses)
                            $position->proper_vacant_count = $maxVacantCount;

                            // If no vacant count from position_monthly_data, check resort_vacant_budget_costs as fallback
                            if ($position->proper_vacant_count == 0) {
                                $actualVacantCount = DB::table('resort_vacant_budget_costs')
                                    ->where('position_id', $position->id)
                                    ->where('department_id', $position->dept_id)
                                    ->where('resort_id', $resortId)
                                    ->where('year', $year)
                                    ->distinct('vacant_index')
                                    ->count('vacant_index');

                                if ($actualVacantCount > 0) {
                                    $position->proper_vacant_count = $actualVacantCount;
                                }
                            }

                            // Get vacant details from resort_vacant_budget_costs for each vacant index
                            if ($position->proper_vacant_count > 0) {
                                $vacantRecords = ResortVacantBudgetCost::where('position_id', $position->id)
                                    ->where('department_id', $position->dept_id)
                                    ->where('resort_id', $resortId)
                                    ->where('year', $year)
                                    ->orderBy('vacant_index')
                                    ->get();

                                foreach ($vacantRecords as $vacantBudgetCost) {
                                    $position->vacant_details[$vacantBudgetCost->vacant_index] = $vacantBudgetCost;
                                }
                            }
                        }
                    }

                    if ($employees->isNotEmpty() && $position->Budget_id != "") {
                        $smrp = StoreManningResponseParent::updateOrCreate(
                            [
                                "Resort_id" => $resortId,
                                "Department_id" => $position->dept_id,
                                "Budget_id" => $position->Budget_id
                            ],
                            [
                                "Resort_id" => $resortId,
                                "Department_id" => $position->dept_id,
                                "Budget_id" => $position->Budget_id
                            ]
                        );

                        foreach ($employees as $emp) {
                            $basic_salary = ((float)$emp->basic_salary > 0.0) ? $emp->basic_salary : 0.0;

                            $budgetData = StoreManningResponseChild::updateOrCreate(
                                [
                                    "Parent_SMRP_id" => $smrp->id,
                                    'Emp_id' => $emp->Empid
                                ],
                                [
                                    "Parent_SMRP_id" => $smrp->id,
                                    'Emp_id' => $emp->Empid,
                                    'Current_Basic_salary' => $basic_salary,
                                ]
                            );

                            $vacant_positions = DB::table('store_manning_response_parents as t1')
                                ->join("store_manning_response_children as t2", "t2.Parent_SMRP_id", "=", "t1.id")
                                ->join('employees as t3', 't3.id', "=", "t2.Emp_id")
                                ->join('resort_positions as t4', 't4.id', "=", "t3.Position_id")
                                ->leftJoin('position_monthly_data as pmd', 't4.id', '=', 'pmd.position_id')
                                ->leftJoin('manning_responses as mr', function ($join) use ($resortId, $year) {
                                    $join->on('pmd.manning_response_id', '=', 'mr.id')
                                        ->where('mr.year', '=', $year);
                                })
                                ->where('t1.resort_id', '=', $resortId)
                                ->where('t1.Department_id', '=', $position->dept_id)
                                ->where('t3.Position_id', '=', $emp->Position_id)
                                ->where('t2.Emp_id', '=', $emp->Empid)
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
                                    't2.Proposed_Basic_salary',
                                    't2.Months',
                                    DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                                    DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
                                )
                                ->groupBy('t4.id', 't4.position_title')
                                ->first();

                            $emp->vacantData = $vacant_positions;
                        }
                    }
                }
            }
        }

        // Get divisions with departments for hierarchical view
        $divisions = ResortDivision::where('resort_id', $resortId)
            ->where('status', 'active')
            ->with(['departments' => function($query) use ($rank_wise_departments, $resortId, $year) {
                $query->where('resort_id', $resortId)
                    ->whereIn('id', $rank_wise_departments)
                    ->where('status', 'active')
                    ->with(['sections' => function($q) use ($resortId) {
                        $q->where('resort_id', $resortId)->where('status', 'active');
                    }]);
            }])
            ->get();

        // Use the $departments variable that already has vacant logic applied
        // (prepared in lines 492-669 with proper vacant_details and is_in_manning_request)
        $manningResponses = $departments;

        $available_rank = $employeeRankPosition['position'];

        // Get resort budget costs for the modal
        $resortCosts = ResortBudgetCost::where('resort_id', $resortId)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        return view('resorts.budget.view_budget_hierarchical')->with(compact(
            'page_title',
            'divisions',
            'resortId',
            'year',
            'employeeRankPosition',
            'available_rank',
            'manningResponses',
            'departments',
            'resortCosts'
        ));

        try { } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
        }
    }

    public function ConsolidateBudget()
    {
        if(Common::checkRouteWisePermission('resort.budget.consolidatedbudget',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }

        $page_title = 'Consolidated Budget';
        try {
            $resortId = auth()->guard('resort-admin')->user()->resort_id;
            $parent_Consolidate = StoreConsolidateBudgetParent::where('Resort_id',auth()->guard('resort-admin')->user()->resort_id)->latest()->first();
            $MainArray=array();
            $DepartmentTotal=array();
            $DepartmentArray=array();
            if(isset($parent_Consolidate))
            {
                $child_Consolidate = StoreConsolidateBudgetChild::where("Parent_SCB_id",$parent_Consolidate->id)->latest()->first();
                $header = json_decode($child_Consolidate->header);
                $data = json_decode($child_Consolidate->Data);
                $header = array_slice($header, 7);
                if(!empty($data))
                {
                    foreach($data as $k=>$p)
                    {
                        $internalArray=array();
                        $division = $p[0];
                        $Department = $p[1];
                        $Position = $p[2];
                        $NoOfPosition = $p[3];
                        $Rank = $p[4];
                        $Nation= $p[5];
                        $Salary = $p[6];
                        $Resortdepartment = ResortDepartment::where('resort_id', $this->resort->resort_id)->where('slug',$Department)->first();
                        $Resortposition = ResortPosition::where('resort_id', $this->resort->resort_id)
                                        ->where('slug', $Position)->first();
                        $remainingValues = array_slice($p, 6);

                        if(!in_array($Resortdepartment->id,$DepartmentArray) || array_key_exists($Resortdepartment->name, $MainArray))
                        {
                            $entry = [
                                $Resortposition->position_title,
                                $NoOfPosition,
                                $Rank,
                                $Nation,
                            ];
                            $MainArray[$Resortdepartment->name][] = array_merge($entry, $remainingValues);
                            $oldArray_value = array_key_exists($Resortdepartment->name, $DepartmentTotal)  ?  $DepartmentTotal[$Resortdepartment->name] : 0;
                            $DepartmentTotal[$Resortdepartment->name]= array_sum($remainingValues) + $oldArray_value ;
                            $DepartmentArray[] = $Resortdepartment->id;
                        }
                    }
                }
            }
            else
            {
                $header  = [];
                $data  = [];
                $Resortposition =     collect();
                $DepartmentTotal=[];
            }
            // dd($DepartmentTotal);
            $employeeRankPosition = Common::getEmployeeRankPosition( $this->resort->getEmployee);

            return view('resorts.budget.consolidated')->with(compact('page_title','MainArray','header','DepartmentTotal','resortId','employeeRankPosition'));
        } catch( \Exception $e ) {
            \Log::emergency("File: ".$e->getFile ());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
        }
    }

    public function viewConsolidatedBudget(Request $request, $resortId)
    {
        $selectedYear = $request->get('year', Carbon::now()->year);
        $employeeRankPosition = Common::getEmployeeRankPosition( $this->resort->getEmployee);

        // Retrieve manning responses by resort and year
        if(($employeeRankPosition['position'] != "HR" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" )) && ($employeeRankPosition['position'] != "GM" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" )) && ($employeeRankPosition['position'] != "Finance" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" ))) {
            $yearlyBudgets = ManningResponse::where('year', $selectedYear)
                                ->where('resort_id', $resortId)
                                ->where('dept_id', $this->resort->getEmployee->Dept_id)
                                ->with(['positionMonthlyData', 'GetBudgetStatus'])
                                ->get();
        }elseif($employeeRankPosition['position'] == "Finance" && ($employeeRankPosition['rank'] == "HOD" || $employeeRankPosition['rank'] == "XCOM" )){
            $yearlyBudgets = ManningResponse::where('year', $selectedYear)
                            ->where('resort_id', $resortId)
                            ->whereIn('budget_process_status', ['Finance', 'GM'])
                            ->with(['positionMonthlyData', 'GetBudgetStatus'])
                            ->get();
        }elseif($employeeRankPosition['position'] == "GM" && ($employeeRankPosition['rank'] == "HOD" || $employeeRankPosition['rank'] == "XCOM" )){
            $yearlyBudgets = ManningResponse::where('year', $selectedYear)
                            ->where('resort_id', $resortId)
                            ->where('budget_process_status', 'GM')
                            ->with(['positionMonthlyData', 'GetBudgetStatus'])
                            ->get();
        }
        else
        {
            $yearlyBudgets = ManningResponse::where('year', $selectedYear)
            ->where('resort_id', $resortId)
            ->with(['positionMonthlyData', 'GetBudgetStatus'])
            ->get();
        }

        if ($yearlyBudgets->isNotEmpty())
        {
            // Initialize the consolidated budget array and retrieve unique headers
            $consolidatedBudget = [];
            $header = ResortBudgetCost::where('resort_id', $resortId)
                ->distinct()
                ->pluck('particulars')
                ->toArray();

            foreach ($yearlyBudgets as $response) {
                $department = ResortDepartment::with('division', 'sections')->find($response->dept_id);

                if (!$department) continue;

                $divisionName = $department->division ? $department->division->name : 'No Division';
                $divisionId = $department->division ? $department->division->id : 0;
                $departmentName = $department->name;
                $departmentId = $department->id;

                // Initialize division if not exists
                if (!isset($consolidatedBudget[$divisionName])) {
                    $consolidatedBudget[$divisionName] = [
                        'division_id' => $divisionId,
                        'departments' => []
                    ];
                }

                // Initialize department if not exists
                if (!isset($consolidatedBudget[$divisionName]['departments'][$departmentName])) {
                    $consolidatedBudget[$divisionName]['departments'][$departmentName] = [
                        'department_id' => $departmentId,
                        'manning_response_id' => $response->id,
                        'total_headcount' => $response->total_headcount,
                        'filled_positions' => $response->total_filled_positions,
                        'vacant_positions' => $response->total_vacant_positions,
                        'sections' => [],
                        'positions' => []
                    ];
                }

                // Group by unique positions to avoid duplicates
                $uniquePositions = $response->positionMonthlyData->unique('position_id');

                foreach ($uniquePositions as $monthlyData) {
                    $position = $monthlyData->position;
                    $positionName = $position->position_title;
                    $positionRank = $position->Rank;
                    $positionId = $position->id;
                    $sectionId = $position->section_id ?? null;

                    // Get vacant count from resorts_child_notifications through manning_response
                    // resorts_child_notifications -> budget_statuses -> manning_responses -> position_monthly_data
                    $budgetStatus = BudgetStatus::where('Budget_id', $response->id)->first();
                    $isPositionInManningRequest = false;

                    // Initialize max counts
                    $maxHeadcount = 0;
                    $maxFilledcount = 0;
                    $maxVacantFromMonthly = 0;
                    $maxVacantcount = 0;

                    // Get position monthly data for this specific position from the manning_response
                    $positionMonthlyDataForPosition = $response->positionMonthlyData->where('position_id', $positionId);

                    // Calculate max counts across all months for this position
                    foreach ($positionMonthlyDataForPosition as $dataByMonth) {
                        $headcount = $dataByMonth->headcount ?? 0;
                        $filledcount = $dataByMonth->filledcount ?? 0;
                        $vacantcount = $dataByMonth->vacantcount ?? 0;

                        // Calculate max counts for the current position
                        $maxHeadcount = max($maxHeadcount, $headcount);
                        $maxFilledcount = max($maxFilledcount, $filledcount);
                        $maxVacantFromMonthly = max($maxVacantFromMonthly, $vacantcount);
                    }

                    if ($budgetStatus) {
                        // Check if THIS specific position is in the manning request via resorts_child_notifications
                        $childNotification = ResortsChildNotifications::where('Parent_msg_id', $budgetStatus->message_id)
                            ->where('Position_id', $positionId)
                            ->where('Department_id', $departmentId)
                            ->first();

                        if ($childNotification) {
                            $isPositionInManningRequest = true;

                            // Priority 1: Use vacant count from position_monthly_data (from manning_responses)
                            // This comes from manning_responses.total_vacant_positions properly filtered by position, dept_id, year
                            $maxVacantcount = $maxVacantFromMonthly;

                        } else {
                            // Position not in resorts_child_notifications, use calculated value from position_monthly_data
                            $maxVacantcount = $maxVacantFromMonthly > 0 ? $maxVacantFromMonthly : max(0, $maxHeadcount - $maxFilledcount);
                        }
                    } else {
                        // No budget status, calculate from position_monthly_data if available
                        $maxVacantcount = $maxVacantFromMonthly > 0 ? $maxVacantFromMonthly : max(0, $maxHeadcount - $maxFilledcount);
                    }

                    // Get employees for this position
                    $employees = DB::table('employees as e')
                        ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                        ->where('e.Position_id', $positionId)
                        ->where('e.status', 'Active')
                        ->where('e.resort_id', $resortId)
                        ->select(
                            'e.id as emp_id',
                            'ra.first_name',
                            'ra.last_name',
                            'e.rank',
                            'e.nationality',
                            'e.basic_salary',
                            'e.proposed_salary'
                        )
                        ->get();

                    // Load budget cost configurations for each employee - SUM FOR ENTIRE YEAR
                    foreach ($employees as $employee) {
                        // Get all configurations for the year (all months)
                        $employeeConfigs = ResortEmployeeBudgetCostConfiguration::where('employee_id', $employee->emp_id)
                            ->where('department_id', $departmentId)
                            ->where('position_id', $positionId)
                            ->where('resort_id', $resortId)
                            ->where('year', $selectedYear)
                            ->get();

                        // Calculate yearly totals for salaries
                        // For consolidated budget: Always use employees table values * 12 (same as budget view)
                        $employee->configured_basic_salary = ($employee->basic_salary ?? 0) * 12;
                        $employee->configured_current_salary = ($employee->proposed_salary ?? 0) * 12;

                        // Aggregate budget costs by resort_budget_cost_id (sum all months)
                        $aggregatedConfigs = [];
                        foreach ($employeeConfigs as $config) {
                            $costId = $config->resort_budget_cost_id;

                            if (!isset($aggregatedConfigs[$costId])) {
                                $aggregatedConfigs[$costId] = (object)[
                                    'resort_budget_cost_id' => $costId,
                                    'value' => 0,
                                    'currency' => $config->currency,
                                    'hours' => 0
                                ];
                            }

                            $aggregatedConfigs[$costId]->value += $config->value;
                            $aggregatedConfigs[$costId]->hours += $config->hours ?? 0;
                        }

                        // Convert aggregated array to collection for consistency
                        $employee->budget_configurations = collect(array_values($aggregatedConfigs));

                        // Calculate yearly total for this employee using unified function
                        $employee->yearly_total = Common::calculateYearlyTotal($employee, $resortId);
                    }

                    // Load vacant budget cost configurations - SUM FOR ENTIRE YEAR
                    $vacantConfigurations = [];
                    for ($i = 1; $i <= $maxVacantcount; $i++) {
                        $vacantBudgetCost = ResortVacantBudgetCost::where('position_id', $positionId)
                            ->where('department_id', $departmentId)
                            ->where('resort_id', $resortId)
                            ->where('year', $selectedYear)
                            ->where('vacant_index', $i)
                            ->first();

                        if ($vacantBudgetCost) {
                            // Get all monthly configurations for this vacant position
                            $vacantCostConfigs = ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCost->id)
                                ->get();

                            // For consolidated budget: Always use base values from resort_vacant_budget_costs * 12 (same as budget view)
                            $yearlyBasicSalary = ($vacantBudgetCost->basic_salary ?? 0) * 12;
                            $yearlyCurrentSalary = ($vacantBudgetCost->current_salary ?? 0) * 12;

                            // Update the vacant budget cost with yearly totals
                            $vacantBudgetCost->basic_salary = $yearlyBasicSalary;
                            $vacantBudgetCost->current_salary = $yearlyCurrentSalary;

                            // Aggregate budget costs by resort_budget_cost_id (sum all months)
                            $aggregatedVacantConfigs = [];
                            foreach ($vacantCostConfigs as $config) {
                                $costId = $config->resort_budget_cost_id;

                                if (!isset($aggregatedVacantConfigs[$costId])) {
                                    $aggregatedVacantConfigs[$costId] = (object)[
                                        'resort_budget_cost_id' => $costId,
                                        'value' => 0,
                                        'currency' => $config->currency,
                                        'hours' => 0
                                    ];
                                }

                                $aggregatedVacantConfigs[$costId]->value += $config->value;
                                $aggregatedVacantConfigs[$costId]->hours += $config->hours ?? 0;
                            }

                            $vacantConfigurations[$i] = [
                                'vacant_budget_cost' => $vacantBudgetCost,
                                'configurations' => collect(array_values($aggregatedVacantConfigs))
                            ];

                            // Calculate yearly total for this vacant position using unified function
                            $vacantConfigurations[$i]['yearly_total'] = Common::calculateVacantYearlyTotal($vacantConfigurations[$i], $resortId);
                        }
                    }

                    $positionData = [
                        'position_id' => $positionId,
                        'rank' => $positionRank,
                        'max_counts' => [
                            'max_headcount' => $maxHeadcount,
                            'max_vacantcount' => $maxVacantcount,
                            'max_filledcount' => $maxFilledcount,
                        ],
                        'employees' => $employees,
                        'vacant_count' => $maxVacantcount,
                        'vacant_configurations' => $vacantConfigurations
                    ];

                    // If position belongs to a section
                    if ($sectionId) {
                        $section = ResortSection::find($sectionId);
                        $sectionName = $section ? $section->name : 'Unknown Section';

                        if (!isset($consolidatedBudget[$divisionName]['departments'][$departmentName]['sections'][$sectionName])) {
                            $consolidatedBudget[$divisionName]['departments'][$departmentName]['sections'][$sectionName] = [
                                'section_id' => $sectionId,
                                'positions' => []
                            ];
                        }

                        $consolidatedBudget[$divisionName]['departments'][$departmentName]['sections'][$sectionName]['positions'][$positionName] = $positionData;
                    } else {
                        // Position directly under department
                        $consolidatedBudget[$divisionName]['departments'][$departmentName]['positions'][$positionName] = $positionData;
                    }
                }
            }

            // Get additional resort costs by particular cost title
            $resortCosts = ResortBudgetCost::where('resort_id', $resortId)
                ->select('id', 'particulars', 'amount', 'amount_unit')
                ->get();

            // Calculate and store totals for all levels (Position, Section, Department, Division)
            if (!empty($consolidatedBudget)) {
                foreach ($consolidatedBudget as $divisionName => &$divisionData) {
                    $divisionTotal = 0;

                    foreach ($divisionData['departments'] as $departmentName => &$departmentData) {
                        $departmentTotal = 0;

                        // Calculate totals for sections
                        if (!empty($departmentData['sections'])) {
                            foreach ($departmentData['sections'] as $sectionName => &$sectionData) {
                                $sectionTotal = 0;

                                // Calculate totals for positions in section
                                if (!empty($sectionData['positions'])) {
                                    foreach ($sectionData['positions'] as $positionName => &$positionData) {
                                        $positionTotal = Common::calculatePositionTotal($positionData, $resortCosts, $resortId);
                                        $positionData['calculated_total'] = $positionTotal;
                                        $sectionTotal += $positionTotal;
                                    }
                                }

                                $sectionData['calculated_total'] = $sectionTotal;
                                $departmentTotal += $sectionTotal;
                            }
                        }

                        // Calculate totals for direct positions (not in sections)
                        if (!empty($departmentData['positions'])) {
                            foreach ($departmentData['positions'] as $positionName => &$positionData) {
                                $positionTotal = Common::calculatePositionTotal($positionData, $resortCosts, $resortId);
                                $positionData['calculated_total'] = $positionTotal;
                                $departmentTotal += $positionTotal;
                            }
                        }

                        $departmentData['calculated_total'] = $departmentTotal;
                        $divisionTotal += $departmentTotal;
                    }

                    $divisionData['calculated_total'] = $divisionTotal;
                }
                unset($divisionData, $departmentData, $sectionData, $positionData); // Clean up references
            }

                $resortDepartmentsCount = ResortDepartment::where('resort_id', $resortId)->count();
                $resortManningResponseCount = ManningResponse::where('year', $selectedYear) ->where('resort_id', $resortId)->count();

                    if($resortDepartmentsCount == $resortManningResponseCount){
                        $isBudgetCompleted = true;
                    }else{
                        $isBudgetCompleted = false;
                    }
            // Return the partial view for AJAX requests

            // dd($consolidatedBudget);
            if ($request->ajax()) {
                $html = view('resorts.renderfiles.consolidated', compact(
                    'consolidatedBudget',
                    'header',
                    'resortCosts',
                    'selectedYear',
                    'employeeRankPosition'
                ))->render();

                $isBudgetCompleted = true; // ← your custom condition

                return response()->json([
                    'html' => $html,
                    'isBudgetCompleted' => $isBudgetCompleted
                ]);
            }
        }
        else{
            $resortId = auth()->guard('resort-admin')->user()->resort_id;
            $parent_Consolidate = StoreConsolidateBudgetParent::where('Resort_id',auth()->guard('resort-admin')->user()->resort_id)->where('year',$selectedYear)->latest()->first();
            $MainArray=array();
            $DepartmentTotal=array();
            $DepartmentArray=array();

            if(isset($parent_Consolidate))
            {
                $child_Consolidate = StoreConsolidateBudgetChild::where("Parent_SCB_id",$parent_Consolidate->id)->latest()->first();
                $header = json_decode($child_Consolidate->header);
                $data = json_decode($child_Consolidate->Data);
                $header = array_slice($header, 7);
                if(!empty($data))
                {
                    foreach($data as $k=>$p)
                    {
                        $internalArray=array();
                        $division = $p[0];
                        $Department = $p[1];
                        $Position = $p[2];
                        $NoOfPosition = $p[3];
                        $Rank = $p[4];
                        $Nation= $p[5];
                        $Salary = $p[6];
                        $Resortdepartment = ResortDepartment::where('resort_id', $this->resort->resort_id)->where('slug',$Department)->first();
                        $Resortposition = ResortPosition::where('resort_id', $this->resort->resort_id)
                                        ->where('slug', $Position)->first();
                        $remainingValues = array_slice($p, 6);

                        if(!in_array($Resortdepartment->id,$DepartmentArray) || array_key_exists($Resortdepartment->name, $MainArray))
                        {
                            $entry = [
                                $Resortposition->position_title,
                                $NoOfPosition,
                                $Rank,
                                $Nation,
                            ];
                            $MainArray[$Resortdepartment->name][] = array_merge($entry, $remainingValues);
                            $oldArray_value = array_key_exists($Resortdepartment->name, $DepartmentTotal)  ?  $DepartmentTotal[$Resortdepartment->name] : 0;
                            $DepartmentTotal[$Resortdepartment->name]= array_sum($remainingValues) + $oldArray_value ;
                            $DepartmentArray[] = $Resortdepartment->id;
                        }
                    }
                }
            }
            else
            {
                $header  = [];
                $data  = [];
                $Resortposition =     collect();
                $DepartmentTotal=[];
            }

            if ($request->ajax()) {
                return view('resorts.renderfiles.consolidatedold', compact('MainArray','header','DepartmentTotal','resortId'));
            }
        }

        // For non-AJAX requests, return the full view
        return view('budget.consolidated', compact(
            'consolidatedBudget',
            'header',
            'resortCosts',
            'selectedYear'
        ));
    }

    public function config()
    {
        try
        {
            if(Common::checkRouteWisePermission('resort.budget.config',config('settings.resort_permissions.view')) == false){
                return abort(403, 'Unauthorized access');
            }
            $page_title = 'Configuration';
            return view('resorts.budget.config')->with(compact('page_title'));
        } catch( \Exception $e ) {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
        }
    }

    public function UploadconfigFiles(Request $request)
    {
        $consolidatdebudget_Year = $request->consolidatdebudget_Year;
        $consolidatedbudgetFile = $request->hasfile('consolidatedbudget');
        if(isset( $consolidatedbudgetFile ))
        {
            $validator = Validator::make($request->all(), [
                'consolidatedbudget' => 'required|file|mimes:xls,xlsx|max:2048',
            ],
            [
                'consolidatedbudget.mimes' => 'The consolidated budget file must be a type of: jpg, jpeg, png, xls, xlsx.',
                'consolidatedbudget.max' => 'The consolidated budget file may not be greater than 2MB.',
                'consolidatedbudget.required' => 'The consolidated budget file is required.',
            ]);

        }
        $validator = Validator::make($request->all(), [
            'xpat' => 'required|numeric|min:0',
            'local' => 'required|numeric|min:0',
        ],
        [
            'xpat.required' => 'The Xpat Value Required .',
            'local.required' => 'The Local Value Required .',
        ]);
        if ($validator->fails()) {
          return response()->json(['success' => false, 'message' => $validator->errors()]);
        }
        // $path_path = config( 'settings.Resort_BudgetConfigFiles')."/".Auth::guard('resort-admin')->user()->resort->resort_id;

        try {
            //   $criteria = [];
            $attributes = [
                'xpat' => $request->xpat,
                'local' => $request->local,
                'consolidatdebudget' => null,
                'benifitgrid' => null,
            ];

            //   // Resort Id throw make folders
            //   if (isset($request->consolidatdebudget)) {
            //       $fileName = "Consolidation_Budget" . '.' . $request->consolidatdebudget->getClientOriginalExtension();
            //       Common::uploadFile($request->consolidatdebudget, $fileName, $path_path);
            //       $attributes['consolidatdebudget'] = $fileName;
            //   }

            // if (isset($request->benifitgrid))
            // {
            //     $fileName = "Benifit_Grid" . '.' . $request->benifitgrid->getClientOriginalExtension();
            //     Common::uploadFile($request->benifitgrid, $fileName, $path_path);
            //     $attributes['benifitgrid'] = $fileName;
            // }


            $configurationBudget = ManningandbudgetingConfigfiles::updateOrCreate(["resort_id"=> Auth::guard('resort-admin')->user()->resort_id], $attributes);
                //   $consolidatdebudget =   (isset($configurationBudget->consolidatdebudget))
                //                                 ? url($path_path.'/'.$configurationBudget->consolidatdebudget)
                //                                      :url(config('settings.default_picture'));
                // //   $benifitgrid = (isset($configurationBudget->benifitgrid))
                //                         ? url($path_path.'/'.$configurationBudget->benifitgrid)
                //                          :url(config('settings.default_picture'));

            if(isset( $consolidatedbudgetFile ))
            {
                $data = [
                    "Year"=>$request->consolidatdebudget_Year,
                    "Resort_id"=>$this->resort->resort_id,
                    "file"=>!empty($request->file('consolidatedbudget')) ? $request->file('consolidatedbudget')->getClientOriginalName() : ""
                ];
                try {
                    if(!empty($request->file('consolidatedbudget')))
                    {
                        $filePath = $request->file( 'consolidatedbudget')->store('imports');
                        $check =  ConsolidateBudgetImportJob::dispatch($filePath,$data);
                    }
                }
                catch (\Exception $e)
                {
                    $response['msg'] ="Something went wrong. Please check excel file format .";
                    $response['success'] = false;
                    return response()->json($response);
                }
            }

            $benifitgrid='';
            $consolidatdebudget='';
            $page_title = 'Configuration';
            $response['success'] = true;
            $response['data'] = [$consolidatdebudget,$benifitgrid];
            $response['msg'] ="Configuration saved successfully";
            return response()->json($response);
        }
        catch( \Exception $e ) {
          \Log::emergency("File: ".$e->getFile());
          \Log::emergency("Line: ".$e->getLine());
          \Log::emergency("Message: ".$e->getMessage());
          $response['success'] = false;
          $response['data'] = [];
          $response['msg'] ="Somthing Wrong";
          return response()->json($response);
        }
    }

    public function UpdateResortBudgetPositionWise(Request $request)
    {



        try
        {
            $ProposedBasicsalary= $request->ProposedBasicsalary;
            $basic_salary= $request->basic_salary;
            $monthdata= $request->month_data;
            $Total_Department_budget= $request->grand_total;
            $parent_id =0;

            foreach($basic_salary as $key=>$basic)
            {


                if(array_key_exists($key, $ProposedBasicsalary) && array_key_exists($basic['smrpChildId'], $monthdata))
                {
                    // echo  $ProposedBasicsalary[$key]['value'];
                    // echo "<pre>";
                        $StoreManningResponseChild = StoreManningResponseChild::where("id",$basic['smrpChildId'])->first();
                        $StoreManningResponseChild->Current_Basic_salary  =  $basic['value'];
                        $StoreManningResponseChild->Proposed_Basic_salary =  $ProposedBasicsalary[$key]['value'];
                        $StoreManningResponseChild->Months =  json_encode($monthdata[$basic['smrpChildId']]) ;// $monthdata[$basic['smrpChildId']];
                        $StoreManningResponseChild->save();

                        $parent_id = $StoreManningResponseChild->Parent_SMRP_id;
                    }
            }

            StoreManningResponseParent::where("id",$parent_id)->update(["Total_Department_budget"=>$Total_Department_budget]);
            return response()->json(['success' => true, 'message' => 'Budget updated successfully']);
        }
        catch   (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function approveBudget(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'budget_id' => 'required|integer',
                'department_id' => 'required|integer',
                'year' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
            }

            $budgetId = $request->input('budget_id');
            $departmentId = $request->input('department_id');
            $year = $request->input('year');

            $manningResponse = ManningResponse::where('id', $budgetId)
                                            ->where('dept_id', $departmentId)
                                            ->where('year', $year)
                                            ->first();

            if (!$manningResponse) {
                return response()->json(['success' => false, 'message' => 'Budget not found.'], 404);
            }

            // Update the budget status to 'Approved'
            $manningResponse->budget_process_status = 'Approved';
            $manningResponse->save();

            // You might also want to log this action or create a BudgetStatus entry
            BudgetStatus::create([
                'Budget_id' => $budgetId,
                'status' => 'Approved',
                'message' => 'Budget approved by GM.',
                'user_id' => Auth::guard('resort-admin')->user()->id, // Assuming authenticated resort admin
            ]);

            return response()->json(['success' => true, 'message' => 'Budget approved successfully!']);

        } catch (\Exception $e) {

            \Log::emergency("File: " . $e->getFile(). " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred while approving the budget.'], 500);
        }
    }

    /**
     * Save budget cost configuration via AJAX
     */
    public function saveBudgetCostAssignment(Request $request, $resortId)
    {
        try {
            // Validate the incoming request
            $validator = Validator::make($request->all(), [
                'department_id' => 'required|integer',
                'position_id' => 'required|integer',
                'table_type' => 'required|in:employee,vacant',
                'employee_id' => 'nullable|integer',
                'vacant_index' => 'nullable|integer',
                'basic_salary' => 'nullable|numeric|min:0',
                'current_salary' => 'nullable|numeric|min:0',
                'budget_costs' => 'required|array',
                'budget_costs.*.cost_id' => 'required|integer|exists:resort_budget_costs,id',
                'budget_costs.*.value' => 'required|numeric|min:0',
                'budget_costs.*.currency' => 'required|in:USD,MVR'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $departmentId = $request->input('department_id');
            $positionId = $request->input('position_id');
            $tableType = $request->input('table_type');
            $employeeId = $request->input('employee_id');
            $vacantIndex = $request->input('vacant_index', 1);
            $basicSalary = $request->input('basic_salary');
            $currentSalary = $request->input('current_salary');
            $budgetCosts = $request->input('budget_costs');
            $selectedYear = $request->input('year', Carbon::now()->year);

            // Get the actual department_id from manning_responses table
            $manningResponse = ManningResponse::find($departmentId);
            if (!$manningResponse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Department not found'
                ], 404);
            }

            $actualDepartmentId = $manningResponse->dept_id;

            // Get MVR to Dollar conversion rate
            $resortSettings = ResortSiteSettings::where('resort_id', $resortId)->first();
            $mvrToDollarRate = $resortSettings ? 1/$resortSettings->DollertoMVR : 15.42;

            DB::beginTransaction();

            if ($tableType === 'employee') {
                // Handle Employee Budget Cost Configuration
                if (!$employeeId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Employee ID is required for employee type'
                    ], 400);
                }

                // Delete existing configurations for this employee
                ResortEmployeeBudgetCostConfiguration::where('employee_id', $employeeId)
                    ->where('department_id', $actualDepartmentId)
                    ->where('position_id', $positionId)
                    ->where('resort_id', $resortId)
                    ->where('year', $selectedYear)
                    ->delete();

                // Check if "Overtime - Holiday" is selected
                $overtimeHolidayConfig = null;
                $overtimeHolidayCostId = null;
                foreach ($budgetCosts as $cost) {
                    $budgetCost = ResortBudgetCost::find($cost['cost_id']);
                    if ($budgetCost && $this->isOvertimeHoliday($budgetCost)) {
                        $overtimeHolidayConfig = $cost;
                        $overtimeHolidayCostId = $cost['cost_id'];
                        break;
                    }
                }

                // Insert new configurations
                foreach ($budgetCosts as $cost) {
                    $budgetCost = ResortBudgetCost::find($cost['cost_id']);
                    $isOvertimeHoliday = $budgetCost && $this->isOvertimeHoliday($budgetCost);

                    if ($isOvertimeHoliday) {
                        // For overtime holiday, create month-wise entries for all 12 months
                        for ($month = 1; $month <= 12; $month++) {
                            // Calculate holiday hours for this month
                            $holidayHours = $this->calculateHolidayHoursForMonth($selectedYear, $month);

                            // Calculate overtime holiday value based on basic salary and multiplier
                            // Formula: (Basic Salary ÷ Days in Month ÷ 8) × Multiplier × Hours
                            $daysInMonth = Carbon::create($selectedYear, $month, 1)->daysInMonth;
                            $dailySalary = $basicSalary / $daysInMonth;
                            $hourlyRate = $dailySalary / 8;
                            $multiplier = $budgetCost->amount ?? 1.5; // Default 1.5 for holiday OT
                            $overtimeHourlyRate = $hourlyRate * $multiplier;
                            $calculatedValue = $overtimeHourlyRate * $holidayHours;

                            ResortEmployeeBudgetCostConfiguration::create([
                                'employee_id' => $employeeId,
                                'resort_budget_cost_id' => $cost['cost_id'],
                                'value' => $calculatedValue,
                                'currency' => $cost['currency'],
                                'hours' => $holidayHours,
                                'department_id' => $actualDepartmentId,
                                'position_id' => $positionId,
                                'resort_id' => $resortId,
                                'year' => $selectedYear,
                                'month' => $month,
                                'basic_salary' => $basicSalary,
                                'current_salary' => $currentSalary
                            ]);
                        }
                    } else {
                        // For non-overtime-holiday items, create without month (legacy behavior)
                        ResortEmployeeBudgetCostConfiguration::create([
                            'employee_id' => $employeeId,
                            'resort_budget_cost_id' => $cost['cost_id'],
                            'value' => $cost['value'],
                            'currency' => $cost['currency'],
                            'department_id' => $actualDepartmentId,
                            'position_id' => $positionId,
                            'resort_id' => $resortId,
                            'year' => $selectedYear,
                            'basic_salary' => $basicSalary,
                            'current_salary' => $currentSalary
                        ]);
                    }
                }

                $savedConfigurations = ResortEmployeeBudgetCostConfiguration::where('employee_id', $employeeId)
                    ->where('resort_id', $resortId)
                    ->where('year', $selectedYear)
                    ->get();

            } else {
                // Handle Vacant Budget Cost Configuration

                // Get details from request
                $details = $request->input('details');

                // First, create or update the vacant budget cost record
                $vacantBudgetCost = ResortVacantBudgetCost::updateOrCreate(
                    [
                        'position_id' => $positionId,
                        'department_id' => $actualDepartmentId,
                        'resort_id' => $resortId,
                        'year' => $selectedYear,
                        'vacant_index' => $vacantIndex
                    ],
                    [
                        'basic_salary' => $basicSalary,
                        'current_salary' => $currentSalary,
                        'details' => $details
                    ]
                );

                // Delete existing configurations for this vacant position
                ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCost->id)
                    ->delete();

                // Check if "Overtime - Holiday" is selected
                $overtimeHolidayConfig = null;
                $overtimeHolidayCostId = null;
                foreach ($budgetCosts as $cost) {
                    $budgetCost = ResortBudgetCost::find($cost['cost_id']);
                    if ($budgetCost && $this->isOvertimeHoliday($budgetCost)) {
                        $overtimeHolidayConfig = $cost;
                        $overtimeHolidayCostId = $cost['cost_id'];
                        break;
                    }
                }

                // Insert new configurations
                foreach ($budgetCosts as $cost) {
                    $budgetCost = ResortBudgetCost::find($cost['cost_id']);
                    $isOvertimeHoliday = $budgetCost && $this->isOvertimeHoliday($budgetCost);

                    if ($isOvertimeHoliday) {
                        // For overtime holiday, create month-wise entries for all 12 months
                        for ($month = 1; $month <= 12; $month++) {
                            // Calculate holiday hours for this month
                            $holidayHours = $this->calculateHolidayHoursForMonth($selectedYear, $month);

                            // Calculate overtime holiday value based on basic salary and multiplier
                            // Formula: (Basic Salary ÷ Days in Month ÷ 8) × Multiplier × Hours
                            $daysInMonth = Carbon::create($selectedYear, $month, 1)->daysInMonth;
                            $dailySalary = $basicSalary / $daysInMonth;
                            $hourlyRate = $dailySalary / 8;
                            $multiplier = $budgetCost->amount ?? 1.5; // Default 1.5 for holiday OT
                            $overtimeHourlyRate = $hourlyRate * $multiplier;
                            $calculatedValue = $overtimeHourlyRate * $holidayHours;

                            ResortVacantBudgetCostConfiguration::create([
                                'vacant_budget_cost_id' => $vacantBudgetCost->id,
                                'resort_budget_cost_id' => $cost['cost_id'],
                                'value' => $calculatedValue,
                                'currency' => $cost['currency'],
                                'hours' => $holidayHours,
                                'department_id' => $actualDepartmentId,
                                'position_id' => $positionId,
                                'resort_id' => $resortId,
                                'year' => $selectedYear,
                                'month' => $month
                            ]);
                        }
                    } else {
                        // For non-overtime-holiday items, create without month (legacy behavior)
                        ResortVacantBudgetCostConfiguration::create([
                            'vacant_budget_cost_id' => $vacantBudgetCost->id,
                            'resort_budget_cost_id' => $cost['cost_id'],
                            'value' => $cost['value'],
                            'currency' => $cost['currency'],
                            'department_id' => $actualDepartmentId,
                            'position_id' => $positionId,
                            'resort_id' => $resortId,
                            'year' => $selectedYear
                        ]);
                    }
                }

                $savedConfigurations = ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCost->id)
                    ->get();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Budget cost configuration saved successfully!',
                'data' => [
                    'table_type' => $tableType,
                    'employee_id' => $employeeId,
                    'vacant_index' => $vacantIndex,
                    'position_id' => $positionId,
                    'basic_salary' => $basicSalary,
                    'current_salary' => $currentSalary,
                    'costs' => $savedConfigurations,
                    'mvr_to_dollar_rate' => $mvrToDollarRate
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the budget cost configuration.'
            ], 500);
        }
    }

    /**
     * Get existing budget cost configuration
     */
    public function getConfiguration(Request $request, $resortId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'department_id' => 'required|integer',
                'position_id' => 'required|integer',
                'table_type' => 'required|in:employee,vacant',
                'employee_id' => 'nullable|integer',
                'vacant_index' => 'nullable|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $departmentId = $request->input('department_id');
            $positionId = $request->input('position_id');
            $tableType = $request->input('table_type');
            $employeeId = $request->input('employee_id');
            $vacantIndex = $request->input('vacant_index', 1);
            $selectedYear = $request->input('year', Carbon::now()->year);

            // Get the actual department_id from manning_responses table
            $manningResponse = ManningResponse::find($departmentId);
            if (!$manningResponse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Department not found'
                ], 404);
            }

            $actualDepartmentId = $manningResponse->dept_id;

            $configuration = null;

            if ($tableType === 'employee' && $employeeId) {
                // Get employee configuration
                $configs = ResortEmployeeBudgetCostConfiguration::where('employee_id', $employeeId)
                    ->where('department_id', $actualDepartmentId)
                    ->where('position_id', $positionId)
                    ->where('resort_id', $resortId)
                    ->where('year', $selectedYear)
                    ->get();

                if ($configs->isNotEmpty()) {
                    $configuration = [
                        'basic_salary' => $configs->first()->basic_salary,
                        'current_salary' => $configs->first()->current_salary,
                        'costs' => $configs->map(function($config) {
                            return [
                                'resort_budget_cost_id' => $config->resort_budget_cost_id,
                                'value' => $config->value,
                                'currency' => $config->currency
                            ];
                        })
                    ];
                }
            } else {
                // Get vacant configuration
                $vacantBudgetCost = ResortVacantBudgetCost::where('position_id', $positionId)
                    ->where('department_id', $actualDepartmentId)
                    ->where('resort_id', $resortId)
                    ->where('year', $selectedYear)
                    ->where('vacant_index', $vacantIndex)
                    ->first();

                if ($vacantBudgetCost) {
                    $configs = ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCost->id)
                        ->get();

                    $configuration = [
                        'basic_salary' => $vacantBudgetCost->basic_salary,
                        'current_salary' => $vacantBudgetCost->current_salary,
                        'costs' => $configs->map(function($config) {
                            return [
                                'resort_budget_cost_id' => $config->resort_budget_cost_id,
                                'value' => $config->value,
                                'currency' => $config->currency
                            ];
                        })
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'configuration' => $configuration
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the configuration.'
            ], 500);
        }
    }

    /**
     * Get positions and sections for a department
     */
    public function getDepartmentHierarchy(Request $request)
    {
        try {
            $departmentId = $request->input('department_id');
            $year = $request->input('year', date('Y'));
            $resortId = auth()->guard('resort-admin')->user()->resort_id;

            // Get manning response for this department
            $manningResponse = ManningResponse::where('dept_id', $departmentId)
                ->where('year', $year)
                ->where('resort_id', $resortId)
                ->first();

            if (!$manningResponse) {
                return response()->json([
                    'success' => false,
                    'message' => 'No budget found for this department'
                ]);
            }

            // Get sections
            $sections = ResortSection::where('dept_id', $departmentId)
                ->where('resort_id', $resortId)
                ->where('status', 'active')
                ->get();

            // Get positions without section
            $positionsWithoutSection = ResortPosition::where('dept_id', $departmentId)
                ->where('resort_id', $resortId)
                ->whereNull('section_id')
                ->where('status', 'active')
                ->get();

            // Get positions grouped by section
            $positionsBySection = [];
            foreach ($sections as $section) {
                $positions = ResortPosition::where('dept_id', $departmentId)
                    ->where('section_id', $section->id)
                    ->where('resort_id', $resortId)
                    ->where('status', 'active')
                    ->get();

                $positionsBySection[$section->id] = $positions;
            }

            return response()->json([
                'success' => true,
                'sections' => $sections,
                'positions_without_section' => $positionsWithoutSection,
                'positions_by_section' => $positionsBySection,
                'manning_response' => $manningResponse
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching department hierarchy.'
            ], 500);
        }
    }

    /**
     * Get employees and vacancies for a position
     */
    public function getPositionEmployees(Request $request)
    {
        try {
            $positionId = $request->input('position_id');
            $year = $request->input('year', date('Y'));
            $resortId = auth()->guard('resort-admin')->user()->resort_id;

            $position = ResortPosition::find($positionId);
            if (!$position) {
                return response()->json(['success' => false, 'message' => 'Position not found']);
            }

            // Get manning response
            $manningResponse = ManningResponse::where('dept_id', $position->dept_id)
                ->where('year', $year)
                ->where('resort_id', $resortId)
                ->first();

            if (!$manningResponse) {
                return response()->json(['success' => false, 'message' => 'No budget found']);
            }

            // Get employees
            $employees = DB::table('employees as e')
                ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->where('e.Position_id', $positionId)
                ->where('e.Dept_id', $position->dept_id)
                ->where('e.status', 'Active')
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
                    'e.incremented_date'
                ]);

            // Get position monthly data
            $monthlyData = PositionMonthlyData::where('position_id', $positionId)
                ->where('manning_response_id', $manningResponse->id)
                ->get();

            // Get vacant position counts
            $vacantCounts = [];
            for ($i = 1; $i <= 12; $i++) {
                $monthData = $monthlyData->where('month', $i)->first();
                $vacantCounts[$i] = $monthData ? $monthData->vacantcount : 0;
            }

            // Process employee budget data
            foreach ($employees as $employee) {
                $smrp = StoreManningResponseParent::where('Resort_id', $resortId)
                    ->where('Department_id', $position->dept_id)
                    ->where('Budget_id', $manningResponse->id)
                    ->first();

                if ($smrp) {
                    $budgetChild = StoreManningResponseChild::where('Parent_SMRP_id', $smrp->id)
                        ->where('Emp_id', $employee->Empid)
                        ->first();

                    if ($budgetChild) {
                        $employee->smrp_child_id = $budgetChild->id;
                        $employee->proposed_basic_salary = $budgetChild->Proposed_Basic_salary ?? 0;
                        $employee->months_data = json_decode($budgetChild->Months, true) ?? [];
                    }
                }
            }

            // Get proper vacant count for this specific position using resorts_child_notifications
            // Join path: resorts_child_notifications -> budget_statuses -> manning_responses -> position_monthly_data
            $totalVacantPositions = 0;

            // Get budget status for this manning response
            $budgetStatus = BudgetStatus::where('Budget_id', $manningResponse->id)
                ->orderBy('id', 'desc')
                ->first();

            if ($budgetStatus) {
                // Check if this position is in resorts_child_notifications
                $childNotification = ResortsChildNotifications::where('Parent_msg_id', $budgetStatus->message_id)
                    ->where('Position_id', $positionId)
                    ->where('Department_id', $position->dept_id)
                    ->first();

                if ($childNotification) {
                    // Position is in manning request, get vacant count from position_monthly_data
                    // Filtered by position_id, manning_response_id (which already filters by dept_id, year, resort_id)
                    $positionMonthlyData = PositionMonthlyData::where('position_id', $positionId)
                        ->where('manning_response_id', $manningResponse->id)
                        ->get();

                    // Get maximum vacant count across all months for this position
                    $maxVacantCount = 0;
                    foreach ($positionMonthlyData as $monthlyDataItem) {
                        $vacantCount = $monthlyDataItem->vacantcount ?? 0;
                        $maxVacantCount = max($maxVacantCount, $vacantCount);
                    }


                    $totalVacantPositions = $maxVacantCount;

                } else {
                    // Position not in resorts_child_notifications, calculate from position_monthly_data
                    $maxVacantCount = 0;
                    foreach ($monthlyData as $monthlyDataItem) {
                        $vacantCount = $monthlyDataItem->vacantcount ?? 0;
                        $maxVacantCount = max($maxVacantCount, $vacantCount);
                    }
                    $totalVacantPositions = $maxVacantCount;
                }
            } else {
                // No budget status, calculate from position_monthly_data
                $maxVacantCount = 0;
                foreach ($monthlyData as $monthlyDataItem) {
                    $vacantCount = $monthlyDataItem->vacantcount ?? 0;
                    $maxVacantCount = max($maxVacantCount, $vacantCount);
                }
                $totalVacantPositions = $maxVacantCount;
            }

            // dd($vacantCounts, $totalVacantPositions, $manningResponse->id, $position);
            return response()->json([
                'success' => true,
                'employees' => $employees,
                'vacant_counts' => $vacantCounts,
                'total_vacant_positions' => $totalVacantPositions,
                'manning_response_id' => $manningResponse->id,
                'position' => $position
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching position employees.'
            ], 500);
        }
    }

    /**
     * Get employee monthly budget breakdown
     */
    public function getEmployeeMonthlyData(Request $request)
    {
        try {
            $employeeId = $request->input('employee_id');
            $positionId = $request->input('position_id');
            $year = $request->input('year', date('Y'));
            $resortId = auth()->guard('resort-admin')->user()->resort_id;

            // Get employee details
            $employee = DB::table('employees as e')
                ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->where('e.id', $employeeId)
                ->first([
                    'e.*',
                    'ra.first_name',
                    'ra.last_name'
                ]);

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee not found']);
            }

            $position = ResortPosition::find($positionId);
            if (!$position) {
                return response()->json(['success' => false, 'message' => 'Position not found']);
            }

            // Get all resort budget costs
            $resortCosts = ResortBudgetCost::where('resort_id', $resortId)
                ->where('status', 'active')
                ->orderBy('id')
                ->get();

            // Get employee budget cost configurations for all months
            $employeeBudgetConfigs = ResortEmployeeBudgetCostConfiguration::where('employee_id', $employeeId)
                ->where('resort_id', $resortId)
                ->where('year', $year)
                ->get();

            // Get salaries from employees table (same for all 12 months)
            $currentBasicSalary = $employee->basic_salary ?? 0;
            $proposedBasicSalary = $employee->proposed_salary ?? 0;

            // Get DollertoMVR conversion rate for converting USD to MVR
            $resortSettings = ResortSiteSettings::where('resort_id', $resortId)->first();
            $dollarToMvrRate = $resortSettings ? ($resortSettings->DollertoMVR ?? 15.42) : 15.42;

            // Create month-wise and cost-wise lookup array [month][cost_id] = config
            // If no data exists, this will be an empty array and will show 0 values in the table
            $monthCostLookup = [];
            if ($employeeBudgetConfigs->isNotEmpty()) {
                foreach ($employeeBudgetConfigs as $config) {
                    if (!isset($monthCostLookup[$config->month])) {
                        $monthCostLookup[$config->month] = [];
                    }

                    // Data from config tables is stored in USD
                    // If currency is MVR, convert USD value to MVR
                    $value = $config->value ?? 0;
                    $currency = $config->currency ?? 'USD';

                    // If currency is MVR, convert USD to MVR
                    if ($currency === 'MVR' && $value > 0) {
                        $value = $value * $dollarToMvrRate;
                    }

                    $monthCostLookup[$config->month][$config->resort_budget_cost_id] = [
                        'value' => $value,
                        'currency' => $currency,
                        'hours' => $config->hours ?? 0
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'employee' => $employee,
                'resort_costs' => $resortCosts,
                'month_cost_data' => $monthCostLookup,
                'employee_id' => $employeeId,
                'position_id' => $positionId,
                'department_id' => $position->dept_id,
                'year' => $year,
                'current_basic_salary' => $currentBasicSalary,
                'proposed_basic_salary' => $proposedBasicSalary
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching employee monthly data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vacant position monthly budget breakdown
     */
    public function getVacantMonthlyData(Request $request)
    {
        try {
            $vacantIndex = $request->input('vacant_index');
            $positionId = $request->input('position_id');
            $year = $request->input('year', date('Y'));
            $resortId = auth()->guard('resort-admin')->user()->resort_id;

            $position = ResortPosition::find($positionId);
            if (!$position) {
                return response()->json(['success' => false, 'message' => 'Position not found']);
            }

            // Get all resort budget costs
            $resortCosts = ResortBudgetCost::where('resort_id', $resortId)
                ->where('status', 'active')
                ->orderBy('id')
                ->get();

            // Get or create vacant budget cost record
            $vacantBudgetCost = ResortVacantBudgetCost::firstOrCreate(
                [
                    'position_id' => $positionId,
                    'department_id' => $position->dept_id,
                    'resort_id' => $resortId,
                    'year' => $year,
                    'vacant_index' => $vacantIndex
                ],
                [
                    'basic_salary' => 0,
                    'current_salary' => 0
                ]
            );

            // Get vacant budget cost configurations for all months
            $vacantBudgetConfigs = ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCost->id)
                ->where('resort_id', $resortId)
                ->where('year', $year)
                ->get();

            // Get salaries from resort_vacant_budget_costs table (same for all 12 months)
            $currentBasicSalary = $vacantBudgetCost->basic_salary ?? 0;
            $proposedBasicSalary = $vacantBudgetCost->current_salary ?? 0;

            // Get DollertoMVR conversion rate for converting USD to MVR
            $resortSettings = ResortSiteSettings::where('resort_id', $resortId)->first();
            $dollarToMvrRate = $resortSettings ? ($resortSettings->DollertoMVR ?? 15.42) : 15.42;

            // Create month-wise and cost-wise lookup array [month][cost_id] = config
            // If no data exists, this will be an empty array and will show 0 values in the table
            $monthCostLookup = [];
            if ($vacantBudgetConfigs->isNotEmpty()) {
                foreach ($vacantBudgetConfigs as $config) {
                    if (!isset($monthCostLookup[$config->month])) {
                        $monthCostLookup[$config->month] = [];
                    }

                    // Data from config tables is stored in USD
                    // If currency is MVR, convert USD value to MVR
                    $value = $config->value ?? 0;
                    $currency = $config->currency ?? 'USD';

                    // If currency is MVR, convert USD to MVR
                    if ($currency === 'MVR' && $value > 0) {
                        $value = $value * $dollarToMvrRate;
                    }

                    $monthCostLookup[$config->month][$config->resort_budget_cost_id] = [
                        'value' => $value,
                        'currency' => $currency,
                        'hours' => $config->hours ?? 0
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'vacant_index' => $vacantIndex,
                'vacant_budget_cost_id' => $vacantBudgetCost->id,
                'resort_costs' => $resortCosts,
                'month_cost_data' => $monthCostLookup,
                'position_id' => $positionId,
                'department_id' => $position->dept_id,
                'year' => $year,
                'details' => $vacantBudgetCost->details ?? null,
                'current_basic_salary' => $currentBasicSalary,
                'proposed_basic_salary' => $proposedBasicSalary
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching vacant position monthly data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update employee monthly budget configuration
     */
    public function updateEmployeeMonthlyBudget(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|integer',
                'position_id' => 'required|integer',
                'department_id' => 'required|integer',
                'year' => 'required|integer',
                'monthly_data' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $employeeId = $request->employee_id;
            $positionId = $request->position_id;
            $departmentId = $request->department_id;
            $year = $request->year;
            $resortId = auth()->guard('resort-admin')->user()->resort_id;

            // Update employee salaries in employees table (same for all 12 months)
            // Note: Only update if salaries are provided in the first month's data
            if (!empty($request->monthly_data) && isset($request->monthly_data[0])) {
                $firstMonthData = $request->monthly_data[0];
                $currentSalary = $firstMonthData['current_salary'] ?? null;
                $proposedSalary = $firstMonthData['proposed_salary'] ?? null;

                if ($currentSalary !== null || $proposedSalary !== null) {
                    $employee = DB::table('employees')->where('id', $employeeId)->first();
                    if ($employee) {
                        $updateData = [];
                        if ($currentSalary !== null) {
                            $updateData['basic_salary'] = $currentSalary;
                        }
                        if ($proposedSalary !== null) {
                            $updateData['proposed_salary'] = $proposedSalary;
                        }
                        if (!empty($updateData)) {
                            DB::table('employees')->where('id', $employeeId)->update($updateData);
                        }
                    }
                }
            }

            // Insert new month-wise cost configurations (without storing salary data)
            foreach ($request->monthly_data as $monthData) {
                $month = $monthData['month'];
                $costConfigurations = $monthData['cost_configurations'];

                // Delete existing configurations for this employee, year, and specific month
                ResortEmployeeBudgetCostConfiguration::where('employee_id', $employeeId)
                    ->where('resort_id', $resortId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->delete();

                // Check if "Overtime - Normal" is selected
                $overtimeNormalConfig = null;
                $overtimeHolidayConfig = null;
                $overtimeHolidayCostId = null;
                foreach ($costConfigurations as $costConfig) {
                    $budgetCost = ResortBudgetCost::find($costConfig['resort_budget_cost_id']);
                    if ($budgetCost && $this->isOvertimeNormal($budgetCost)) {
                        $overtimeNormalConfig = $costConfig;
                    }
                    if ($budgetCost && $this->isOvertimeHoliday($budgetCost)) {
                        $overtimeHolidayConfig = $costConfig;
                        $overtimeHolidayCostId = $costConfig['resort_budget_cost_id'];
                    }
                }

                // Insert configurations for this month (without salary fields)
                foreach ($costConfigurations as $costConfig) {
                    $budgetCost = ResortBudgetCost::find($costConfig['resort_budget_cost_id']);
                    $isOvertimeHoliday = $budgetCost && $this->isOvertimeHoliday($budgetCost);

                    // Skip overtime holiday here - we'll handle it separately for all 12 months
                    if (!$isOvertimeHoliday) {
                        ResortEmployeeBudgetCostConfiguration::create([
                            'employee_id' => $employeeId,
                            'resort_budget_cost_id' => $costConfig['resort_budget_cost_id'],
                            'value' => $costConfig['value'],
                            'currency' => $costConfig['currency'] ?? 'USD',
                            'hours' => $costConfig['hours'] ?? 0,
                            'department_id' => $departmentId,
                            'position_id' => $positionId,
                            'resort_id' => $resortId,
                            'year' => $year,
                            'month' => $month
                        ]);
                    }
                }

                // If "Overtime - Holiday" is selected, automatically calculate and add it for all 12 months
                if ($overtimeHolidayConfig && $overtimeHolidayCostId) {
                    // Get employee basic salary for calculation
                    $employee = DB::table('employees')->where('id', $employeeId)->first();
                    $employeeBasicSalary = $employee->basic_salary ?? 0;
                    $budgetCost = ResortBudgetCost::find($overtimeHolidayCostId);
                    $multiplier = $budgetCost->amount ?? 1.5; // Default 1.5 for holiday OT

                    // Delete existing overtime holiday configurations for all months
                    ResortEmployeeBudgetCostConfiguration::where('employee_id', $employeeId)
                        ->where('resort_id', $resortId)
                        ->where('year', $year)
                        ->where('resort_budget_cost_id', $overtimeHolidayCostId)
                        ->delete();

                    // Create overtime holiday entries for all 12 months
                    for ($targetMonth = 1; $targetMonth <= 12; $targetMonth++) {
                        // Calculate holiday hours for this month
                        $holidayHours = $this->calculateHolidayHoursForMonth($year, $targetMonth);

                        // Calculate overtime holiday value
                        $daysInMonth = Carbon::create($year, $targetMonth, 1)->daysInMonth;
                        $dailySalary = $employeeBasicSalary / $daysInMonth;
                        $hourlyRate = $dailySalary / 8;
                        $overtimeHourlyRate = $hourlyRate * $multiplier;
                        $calculatedValue = $overtimeHourlyRate * $holidayHours;

                        ResortEmployeeBudgetCostConfiguration::create([
                            'employee_id' => $employeeId,
                            'resort_budget_cost_id' => $overtimeHolidayCostId,
                            'value' => $calculatedValue,
                            'currency' => $overtimeHolidayConfig['currency'] ?? 'USD',
                            'hours' => $holidayHours,
                            'department_id' => $departmentId,
                            'position_id' => $positionId,
                            'resort_id' => $resortId,
                            'year' => $year,
                            'month' => $targetMonth
                        ]);
                    }
                }

                // If "Overtime - Normal" is selected, automatically add it for the next 6 months
                // (Current month is already added above, so we add the next 5 months to make 6 total)
                if ($overtimeNormalConfig) {
                    $startMonth = $month;
                    $endMonth = min(12, $startMonth + 5); // Add for next 5 months (total 6 months including current)

                    for ($targetMonth = $startMonth + 1; $targetMonth <= $endMonth; $targetMonth++) {
                        // Check if overtime normal configuration already exists for this month
                        $existingOvertimeNormal = ResortEmployeeBudgetCostConfiguration::where('employee_id', $employeeId)
                            ->where('resort_id', $resortId)
                            ->where('year', $year)
                            ->where('month', $targetMonth)
                            ->where('resort_budget_cost_id', $overtimeNormalConfig['resort_budget_cost_id'])
                            ->first();

                        // Only create if it doesn't already exist (to avoid overwriting manual entries)
                        if (!$existingOvertimeNormal) {
                            ResortEmployeeBudgetCostConfiguration::create([
                                'employee_id' => $employeeId,
                                'resort_budget_cost_id' => $overtimeNormalConfig['resort_budget_cost_id'],
                                'value' => $overtimeNormalConfig['value'],
                                'currency' => $overtimeNormalConfig['currency'] ?? 'USD',
                                'hours' => $overtimeNormalConfig['hours'] ?? 0,
                                'department_id' => $departmentId,
                                'position_id' => $positionId,
                                'resort_id' => $resortId,
                                'year' => $year,
                                'month' => $targetMonth
                            ]);
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Employee monthly budget updated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating employee budget: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update vacant position monthly budget configuration
     */
    public function updateVacantMonthlyBudget(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'vacant_budget_cost_id' => 'required|integer',
                'position_id' => 'required|integer',
                'department_id' => 'required|integer',
                'year' => 'required|integer',
                'monthly_data' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $vacantBudgetCostId = $request->vacant_budget_cost_id;
            $positionId = $request->position_id;
            $departmentId = $request->department_id;
            $year = $request->year;
            $resortId = auth()->guard('resort-admin')->user()->resort_id;
            $details = $request->input('details');

            // Update vacant budget cost record with details if provided
            if ($details) {
                $vacantBudgetCost = ResortVacantBudgetCost::find($vacantBudgetCostId);
                if ($vacantBudgetCost) {
                    $vacantBudgetCost->details = $details;
                    $vacantBudgetCost->save();
                }
            }

            // Update vacant salaries in resort_vacant_budget_costs table (same for all 12 months)
            // Note: Only update if salaries are provided in the first month's data
            if (!empty($request->monthly_data) && isset($request->monthly_data[0])) {
                $firstMonthData = $request->monthly_data[0];
                $proposedSalary = $firstMonthData['current_salary'] ?? null;
                $currentSalary = $firstMonthData['proposed_salary'] ?? null;

                if ($currentSalary !== null || $proposedSalary !== null) {
                    $updateData = [];
                    if ($currentSalary !== null) {
                        $updateData['basic_salary'] = $currentSalary;
                    }
                    if ($proposedSalary !== null) {
                        $updateData['current_salary'] = $proposedSalary;
                    }
                    if (!empty($updateData)) {
                        ResortVacantBudgetCost::where('id', $vacantBudgetCostId)->update($updateData);
                    }
                }
            }

            // Insert new month-wise cost configurations (without storing salary data)
            foreach ($request->monthly_data as $monthData) {
                $month = $monthData['month'];
                $costConfigurations = $monthData['cost_configurations'];

                // Delete existing configurations for this vacant position, year, and specific month
                ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCostId)
                    ->where('resort_id', $resortId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->delete();

                // Check if "Overtime - Normal" is selected
                $overtimeNormalConfig = null;
                $overtimeHolidayConfig = null;
                $overtimeHolidayCostId = null;
                foreach ($costConfigurations as $costConfig) {
                    $budgetCost = ResortBudgetCost::find($costConfig['resort_budget_cost_id']);
                    if ($budgetCost && $this->isOvertimeNormal($budgetCost)) {
                        $overtimeNormalConfig = $costConfig;
                    }
                    if ($budgetCost && $this->isOvertimeHoliday($budgetCost)) {
                        $overtimeHolidayConfig = $costConfig;
                        $overtimeHolidayCostId = $costConfig['resort_budget_cost_id'];
                    }
                }

                // Insert configurations for this month (without salary fields)
                foreach ($costConfigurations as $costConfig) {
                    $budgetCost = ResortBudgetCost::find($costConfig['resort_budget_cost_id']);
                    $isOvertimeHoliday = $budgetCost && $this->isOvertimeHoliday($budgetCost);

                    // Skip overtime holiday here - we'll handle it separately for all 12 months
                    if (!$isOvertimeHoliday) {
                        ResortVacantBudgetCostConfiguration::create([
                            'vacant_budget_cost_id' => $vacantBudgetCostId,
                            'resort_budget_cost_id' => $costConfig['resort_budget_cost_id'],
                            'value' => $costConfig['value'],
                            'currency' => $costConfig['currency'] ?? 'USD',
                            'hours' => $costConfig['hours'] ?? 0,
                            'department_id' => $departmentId,
                            'position_id' => $positionId,
                            'resort_id' => $resortId,
                            'year' => $year,
                            'month' => $month
                        ]);
                    }
                }

                // If "Overtime - Holiday" is selected, automatically calculate and add it for all 12 months
                if ($overtimeHolidayConfig && $overtimeHolidayCostId) {
                    // Get vacant budget cost basic salary for calculation
                    $vacantBudgetCost = ResortVacantBudgetCost::find($vacantBudgetCostId);
                    $vacantBasicSalary = $vacantBudgetCost->basic_salary ?? 0;
                    $budgetCost = ResortBudgetCost::find($overtimeHolidayCostId);
                    $multiplier = $budgetCost->amount ?? 1.5; // Default 1.5 for holiday OT

                    // Delete existing overtime holiday configurations for all months
                    ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCostId)
                        ->where('resort_id', $resortId)
                        ->where('year', $year)
                        ->where('resort_budget_cost_id', $overtimeHolidayCostId)
                        ->delete();

                    // Create overtime holiday entries for all 12 months
                    for ($targetMonth = 1; $targetMonth <= 12; $targetMonth++) {
                        // Calculate holiday hours for this month
                        $holidayHours = $this->calculateHolidayHoursForMonth($year, $targetMonth);

                        // Calculate overtime holiday value
                        $daysInMonth = Carbon::create($year, $targetMonth, 1)->daysInMonth;
                        $dailySalary = $vacantBasicSalary / $daysInMonth;
                        $hourlyRate = $dailySalary / 8;
                        $overtimeHourlyRate = $hourlyRate * $multiplier;
                        $calculatedValue = $overtimeHourlyRate * $holidayHours;

                        ResortVacantBudgetCostConfiguration::create([
                            'vacant_budget_cost_id' => $vacantBudgetCostId,
                            'resort_budget_cost_id' => $overtimeHolidayCostId,
                            'value' => $calculatedValue,
                            'currency' => $overtimeHolidayConfig['currency'] ?? 'USD',
                            'hours' => $holidayHours,
                            'department_id' => $departmentId,
                            'position_id' => $positionId,
                            'resort_id' => $resortId,
                            'year' => $year,
                            'month' => $targetMonth
                        ]);
                    }
                }

                // If "Overtime - Normal" is selected, automatically add it for the next 6 months
                // (Current month is already added above, so we add the next 5 months to make 6 total)
                if ($overtimeNormalConfig) {
                    $startMonth = $month;
                    $endMonth = min(12, $startMonth + 5); // Add for next 5 months (total 6 months including current)

                    for ($targetMonth = $startMonth + 1; $targetMonth <= $endMonth; $targetMonth++) {
                        // Check if overtime normal configuration already exists for this month
                        $existingOvertimeNormal = ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCostId)
                            ->where('resort_id', $resortId)
                            ->where('year', $year)
                            ->where('month', $targetMonth)
                            ->where('resort_budget_cost_id', $overtimeNormalConfig['resort_budget_cost_id'])
                            ->first();

                        // Only create if it doesn't already exist (to avoid overwriting manual entries)
                        if (!$existingOvertimeNormal) {
                            ResortVacantBudgetCostConfiguration::create([
                                'vacant_budget_cost_id' => $vacantBudgetCostId,
                                'resort_budget_cost_id' => $overtimeNormalConfig['resort_budget_cost_id'],
                                'value' => $overtimeNormalConfig['value'],
                                'currency' => $overtimeNormalConfig['currency'] ?? 'USD',
                                'hours' => $overtimeNormalConfig['hours'] ?? 0,
                                'department_id' => $departmentId,
                                'position_id' => $positionId,
                                'resort_id' => $resortId,
                                'year' => $year,
                                'month' => $targetMonth
                            ]);
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Vacant position monthly budget updated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating vacant budget: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a budget cost is "Overtime - Normal"
     * Uses the same detection logic as in the modal blade file
     */
    private function isOvertimeNormal($budgetCost)
    {
        if (!$budgetCost) {
            return false;
        }

        $particularsOriginal = $budgetCost->particulars ?? '';
        $costTitle = $budgetCost->cost_title ?? '';
        $particularsLower = strtolower(trim($particularsOriginal));
        $costTitleLower = strtolower(trim($costTitle));
        $particularsClean = strtolower(preg_replace('/[\s\-_]+/', '', $particularsOriginal));
        $costTitleClean = strtolower(preg_replace('/[\s\-_]+/', '', $costTitle));

        // Known overtime normal names (excluding holiday)
        $knownOvertimeNormalNames = [
            'overtime - normal',
            'overtime-normal',
            'ot - normal',
            'ot-normal',
            'overtime normal'
        ];

        // Check for exact matches
        if (in_array($particularsLower, $knownOvertimeNormalNames) || in_array($costTitleLower, $knownOvertimeNormalNames)) {
            return true;
        }

        // Check if it contains "overtime" or "ot" AND "normal" but NOT "holiday"
        if ((strpos($particularsLower, 'overtime') !== false || strpos($particularsClean, 'overtime') !== false ||
             strpos($costTitleLower, 'overtime') !== false || strpos($costTitleClean, 'overtime') !== false ||
             strpos($particularsLower, ' ot ') !== false || strpos($costTitleLower, ' ot ') !== false) &&
            (strpos($particularsLower, 'normal') !== false || strpos($costTitleLower, 'normal') !== false) &&
            strpos($particularsLower, 'holiday') === false && strpos($costTitleLower, 'holiday') === false) {
            return true;
        }

        // Pattern matching for OT normal variations
        if (preg_match('/\b(ot|overtime)[\s\-_]*normal\b/i', $particularsOriginal) ||
            preg_match('/\b(ot|overtime)[\s\-_]*normal\b/i', $costTitle)) {
            // Make sure it's not holiday
            if (stripos($particularsOriginal, 'holiday') === false && stripos($costTitle, 'holiday') === false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a budget cost is "Overtime - Holiday"
     * Uses the same detection logic as in the modal blade file
     */
    private function isOvertimeHoliday($budgetCost)
    {
        if (!$budgetCost) {
            return false;
        }

        $particularsOriginal = $budgetCost->particulars ?? '';
        $costTitle = $budgetCost->cost_title ?? '';
        $particularsLower = strtolower(trim($particularsOriginal));
        $costTitleLower = strtolower(trim($costTitle));
        $particularsClean = strtolower(preg_replace('/[\s\-_]+/', '', $particularsOriginal));
        $costTitleClean = strtolower(preg_replace('/[\s\-_]+/', '', $costTitle));

        // Known overtime holiday names
        $knownOvertimeHolidayNames = [
            'overtime - holiday',
            'overtime-holiday',
            'ot - holiday',
            'ot-holiday',
            'overtime holiday'
        ];

        // Check for exact matches
        if (in_array($particularsLower, $knownOvertimeHolidayNames) || in_array($costTitleLower, $knownOvertimeHolidayNames)) {
            return true;
        }

        // Check if it contains "overtime" or "ot" AND "holiday"
        if ((strpos($particularsLower, 'overtime') !== false || strpos($particularsClean, 'overtime') !== false ||
             strpos($costTitleLower, 'overtime') !== false || strpos($costTitleClean, 'overtime') !== false ||
             strpos($particularsLower, ' ot ') !== false || strpos($costTitleLower, ' ot ') !== false) &&
            (strpos($particularsLower, 'holiday') !== false || strpos($costTitleLower, 'holiday') !== false)) {
            return true;
        }

        // Pattern matching for OT holiday variations
        if (preg_match('/\b(ot|overtime)[\s\-_]*holiday\b/i', $particularsOriginal) ||
            preg_match('/\b(ot|overtime)[\s\-_]*holiday\b/i', $costTitle)) {
            return true;
        }

        return false;
    }

    /**
     * Calculate holiday hours for a specific month
     * Formula: (Fridays + Public Holidays - Fridays that are also Public Holidays) × 10 hours
     *
     * @param int $year
     * @param int $month (1-12)
     * @return int Total holiday hours for the month
     */
    private function calculateHolidayHoursForMonth($year, $month)
    {
        // Create Carbon instance for the first day of the month
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        // Count Fridays in the month
        $fridays = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            if ($currentDate->dayOfWeek === Carbon::FRIDAY) {
                // Format as 'dd-mm-yyyy' to match database format
                $fridays[] = $currentDate->format('d-m-Y');
            }
            $currentDate->addDay();
        }

        $fridayCount = count($fridays);

        // Get public holidays for this month from database
        // Public holidays are stored in format 'dd-mm-yyyy'
        $allPublicHolidays = PublicHoliday::where('status', 'active')->get();

        \Log::info("Holiday Calculation Debug - Looking for holidays in {$year}-{$month}");
        \Log::info("Total active holidays in database: " . $allPublicHolidays->count());

        $publicHolidays = $allPublicHolidays->filter(function($holiday) use ($year, $month) {
                // Parse the holiday_date (format: dd-mm-yyyy)
                $holidayDateRaw = trim($holiday->holiday_date);

                // Try to parse the date - handle multiple formats
                $dateParts = [];
                if (strpos($holidayDateRaw, '-') !== false) {
                    // Format: dd-mm-yyyy or d-m-yyyy
                    $dateParts = explode('-', $holidayDateRaw);
                } elseif (strpos($holidayDateRaw, '/') !== false) {
                    // Format: dd/mm/yyyy or d/m/yyyy
                    $dateParts = explode('/', $holidayDateRaw);
                } else {
                    \Log::warning("Unrecognized date format for holiday ID {$holiday->id}: '{$holidayDateRaw}'");
                    return false;
                }

                \Log::info("Processing holiday: ID={$holiday->id}, Name={$holiday->name}, Date={$holidayDateRaw}, Parts=" . json_encode($dateParts));

                if (count($dateParts) === 3) {
                    // Remove any whitespace and convert to integers
                    $holidayDay = (int)trim($dateParts[0]);
                    $holidayMonth = (int)trim($dateParts[1]);
                    $holidayYear = (int)trim($dateParts[2]);

                    \Log::info("Parsed: Day={$holidayDay}, Month={$holidayMonth}, Year={$holidayYear} | Looking for: Month={$month}, Year={$year}");
                    \Log::info("Type check - HolidayMonth type: " . gettype($holidayMonth) . ", value: " . var_export($holidayMonth, true));
                    \Log::info("Type check - Month type: " . gettype($month) . ", value: " . var_export($month, true));
                    \Log::info("Type check - HolidayYear type: " . gettype($holidayYear) . ", value: " . var_export($holidayYear, true));
                    \Log::info("Type check - Year type: " . gettype($year) . ", value: " . var_export($year, true));

                    // Validate parsed values
                    if ($holidayDay < 1 || $holidayDay > 31 || $holidayMonth < 1 || $holidayMonth > 12 || $holidayYear < 2000 || $holidayYear > 2100) {
                        \Log::warning("Invalid date values for holiday ID {$holiday->id}: Day={$holidayDay}, Month={$holidayMonth}, Year={$holidayYear}");
                        return false;
                    }

                    // Check if this holiday falls in the specified month and year
                    // Use loose comparison first to debug, then strict
                    $monthMatch = ($holidayMonth == $month);
                    $yearMatch = ($holidayYear == $year);
                    $monthStrictMatch = ($holidayMonth === $month);
                    $yearStrictMatch = ($holidayYear === $year);

                    \Log::info("Comparison results - Month loose: " . ($monthMatch ? 'true' : 'false') . ", strict: " . ($monthStrictMatch ? 'true' : 'false'));
                    \Log::info("Comparison results - Year loose: " . ($yearMatch ? 'true' : 'false') . ", strict: " . ($yearStrictMatch ? 'true' : 'false'));

                    $matches = ($holidayMonth == $month && $holidayYear == $year);

                    if ($matches) {
                        \Log::info("✓ Holiday '{$holiday->name}' ({$holidayDateRaw}) MATCHES {$year}-{$month}");
                    } else {
                        \Log::info("✗ Holiday '{$holiday->name}' ({$holidayDateRaw}) does NOT match {$year}-{$month}");
                        \Log::info("  Month comparison: {$holidayMonth} " . ($monthMatch ? '==' : '!=') . " {$month}");
                        \Log::info("  Year comparison: {$holidayYear} " . ($yearMatch ? '==' : '!=') . " {$year}");
                    }

                    return $matches;
                } else {
                    \Log::warning("Invalid date format for holiday ID {$holiday->id}: '{$holidayDateRaw}' (expected dd-mm-yyyy or dd/mm/yyyy, got " . count($dateParts) . " parts)");
                }
                return false;
            })
            ->map(function($holiday) {
                // Normalize the date format to 'dd-mm-yyyy' for comparison
                $holidayDateRaw = trim($holiday->holiday_date);
                $dateParts = [];

                // Handle both - and / separators
                if (strpos($holidayDateRaw, '-') !== false) {
                    $dateParts = explode('-', $holidayDateRaw);
                } elseif (strpos($holidayDateRaw, '/') !== false) {
                    $dateParts = explode('/', $holidayDateRaw);
                }

                if (count($dateParts) === 3) {
                    // Ensure consistent format: dd-mm-yyyy
                    $normalized = sprintf('%02d-%02d-%04d', (int)trim($dateParts[0]), (int)trim($dateParts[1]), (int)trim($dateParts[2]));
                    \Log::info("Normalized holiday date: '{$holidayDateRaw}' -> '{$normalized}'");
                    return $normalized;
                }
                \Log::warning("Could not normalize holiday date: '{$holidayDateRaw}'");
                return $holidayDateRaw;
            })
            ->toArray();

        $publicHolidayCount = count($publicHolidays);

        \Log::info("Filtered public holidays for {$year}-{$month}: " . json_encode($publicHolidays));

        // Normalize Friday dates for comparison
        $normalizedFridays = array_map(function($friday) {
            $dateParts = explode('-', $friday);
            if (count($dateParts) === 3) {
                return sprintf('%02d-%02d-%04d', (int)$dateParts[0], (int)$dateParts[1], (int)$dateParts[2]);
            }
            return $friday;
        }, $fridays);

        // Count how many Fridays are also public holidays (to avoid double counting)
        $fridaysThatArePublicHolidays = 0;
        foreach ($normalizedFridays as $friday) {
            if (in_array($friday, $publicHolidays)) {
                $fridaysThatArePublicHolidays++;
            }
        }

        // Calculate total holiday days
        // Total = Fridays + Public Holidays - Fridays that are also Public Holidays
        $totalHolidayDays = $fridayCount + $publicHolidayCount - $fridaysThatArePublicHolidays;

        // Calculate total holiday hours (10 hours per day)
        $totalHolidayHours = $totalHolidayDays * 10;

        \Log::info("Holiday Calculation Summary for {$year}-{$month}:");
        \Log::info("  - Fridays: {$fridayCount}");
        \Log::info("  - Public Holidays: {$publicHolidayCount} " . json_encode($publicHolidays));
        \Log::info("  - Fridays that are also Public Holidays (Overlap): {$fridaysThatArePublicHolidays}");
        \Log::info("  - Total Holiday Days: {$totalHolidayDays}");
        \Log::info("  - Total Holiday Hours: {$totalHolidayHours}");

        return $totalHolidayHours;
    }

    /**
     * Get holiday hours for a specific month
     * Used by frontend to auto-populate overtime holiday hours
     */
    public function getHolidayHoursForMonth(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'year' => 'required|integer|min:2000|max:2100',
                'month' => 'required|integer|min:1|max:12'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $year = $request->input('year');
            $month = $request->input('month');

            // Get all holidays for debugging
            $allHolidays = PublicHoliday::where('status', 'active')
                ->get()
                ->map(function($holiday) {
                    return [
                        'id' => $holiday->id,
                        'name' => $holiday->name,
                        'date' => $holiday->holiday_date,
                        'status' => $holiday->status
                    ];
                });

            $holidayHours = $this->calculateHolidayHoursForMonth($year, $month);

            return response()->json([
                'success' => true,
                'year' => $year,
                'month' => $month,
                'holiday_hours' => $holidayHours,
                'debug' => [
                    'all_active_holidays' => $allHolidays,
                    'check_logs' => 'Check Laravel logs for detailed calculation breakdown'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while calculating holiday hours: ' . $e->getMessage()
            ], 500);
        }
    }
}
