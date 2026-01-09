<?php
namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use App\Models\ResortRole;
use App\Models\ResortModule;
use App\Models\ResortPermission;
use App\Models\ResortModulePermission;
use App\Models\ResortRoleModulePermission;
use App\Models\ResortBudgetCost;
use App\Models\Division;
use App\Models\Department;
use App\Models\Section;
use App\Models\Position;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortSection;
use App\Models\ResortPosition;
use App\Helpers\Common;
use App\Models\ResortInteralPagesPermission;
use App\Models\ResortsChildNotifications;
use App\Models\Employee;
use App\Models\ManningResponse;
use App\Models\PositionMonthlyData;
use App\Models\BudgetStatus;
use Carbon\Carbon;
use  DB;
use App\Events\ResortNotificationEvent;
use App\Services\BudgetCalculationService;
use App\Models\StoreManningResponseParent;
use App\Models\StoreManningResponseChild;
class ManningResponseController extends Controller
{
    protected $budgetCalculationService;

    public function __construct(BudgetCalculationService $budgetCalculationService)
    {
        $this->budgetCalculationService = $budgetCalculationService;
    }

    public function fetchEmployees(Request $request)
    {
        // Get the position ID and count (number of employees to fetch)
        $positionId = $request->input('position_id');
        $count = $request->input('count', 1); // Default to 1 if not provided
        $resort_id = Auth::guard('resort-admin')->user()->resort_id; // Authenticated resort ID

        // Fetch active employees for the given position in the specific resort
        $employees = Employee::with('resortAdmin') // Eager load the resortAdmin relationship
                    ->where('Position_id', $positionId)
                    ->where('resort_id', $resort_id)
                    ->where('status', 'Active')
                    ->limit($count)
                    ->get();

        // Check if employees are found, if not mark as "Vacant"
        $response = [];
        if ($employees->isEmpty()) {
            // No employees found, mark all positions as "Vacant"
            for ($i = 0; $i < $count; $i++) {

                $response[] = [
                    'name' =>'Vacant' , // Mark position as vacant
                ];
            }

        } else {
            // If employees are found, add them to the response
            foreach ($employees as $employee) {
                // dd($employee->resortAdmin->first_name);
                $response[] = [
                    'name' => $employee->resortAdmin->first_name. " " .$employee->resortAdmin->last_name,
                ];
            }

            // If fewer employees are found than the requested count, fill the rest with "Vacant"
            if (count($response) < $count) {
                for ($i = count($response); $i < $count; $i++) {
                    $response[] = [
                        'name' => 'Vacant', // Mark additional positions as vacant
                    ];
                }
            }
        }
        return response()->json($response);
    }

    public function fetchCurrentYearData(Request $request)
    {
        $currentYear = Carbon::now()->year; // Get the current year
        $dept_id = $request->input('dept_id');
        $resort_id = $request->input('resort_id');  // Assuming you have a resort ID to filter

        // Fetch manning response for the current year and position
        // Use first() to get a single record
        $manningResponse = ManningResponse::where('dept_id', $dept_id)
            ->where('resort_id', $resort_id)
            ->where('year', $currentYear)
            ->first(); // Use first() to get one record instead of a collection

        // Check if the manning response exists
        if (!$manningResponse) {
            return response()->json(['message' => 'No data found for the current year.'], 404);
        }

        // Fetch monthly position data based on the manning response
        $monthlyDataQuery = PositionMonthlyData::where('manning_response_id', $manningResponse->id)
            ->select('month', 'position_id', 'headcount', 'vacantcount', 'filledcount');

        // Print the raw SQL query and bindings
        // dd($monthlyDataQuery->toSql(), $monthlyDataQuery->getBindings());

        // Execute the query
        $monthlyData = $monthlyDataQuery->get();

        // Structure the data in a format suitable for the front-end
        $headcountData = [];
        foreach ($monthlyData as $data) {
            $headcountData[$data->position_id][$data->month] = [
                'headcount' => $data->headcount,
                'vacantcount' => $data->vacantcount,
                'filledcount' => $data->filledcount,
            ];
        }
        return response()->json($headcountData);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try{
            $type = config('settings.Notifications');

            $validated = $request->validate([
                'resort_id' => 'required|integer',
                'dept_id' => 'required|integer',
                'year' => 'required|integer',
                'monthly_data' => 'required|array',
                'total_headcount' => 'required|integer',
                'total_filled_headcount' => 'required|integer',
                'total_vacant_headcount' =>'required|integer',
            ]);

            // Check if a ManningResponse already exists for the given resort, department, and year
            $manningResponse = ManningResponse::where('resort_id', $validated['resort_id'])
                ->where('dept_id', $validated['dept_id'])
                ->where('year', $validated['year'])
                ->first();

            if ($manningResponse) {
                // If a record exists, delete the existing PositionMonthlyData
                PositionMonthlyData::where('manning_response_id', $manningResponse->id)->delete();

                // Update the existing ManningResponse
                $manningResponse->update([
                    'total_headcount' => $validated['total_headcount'],
                    'total_filled_positions' => $validated['total_filled_headcount'] ?? 0,
                    'total_vacant_positions' => $validated['total_vacant_headcount'] ?? 0,
                ]);
            } else {
                // Create a new ManningResponse if no record exists
                $manningResponse = ManningResponse::create([
                    'resort_id' => $validated['resort_id'],
                    'dept_id' => $validated['dept_id'],
                    'year' => $validated['year'],
                    'total_headcount' => $validated['total_headcount'],
                    'total_filled_positions' => $validated['total_filled_headcount'] ?? 0,
                    'total_vacant_positions' => $validated['total_vacant_headcount'] ?? 0,
                ]);
            }

            // Loop through positions to create new monthly data
            foreach ($validated['monthly_data'] as $positionId => $value) {
                if (isset($validated['monthly_data'][$positionId])) {
                    foreach ($validated['monthly_data'][$positionId] as $monthIndex => $headcount) {
                        // Create a new PositionMonthlyData record for each month
                        PositionMonthlyData::create([
                            'manning_response_id' => $manningResponse->id,
                            'position_id' => $positionId,
                            'month' => $monthIndex + 1,
                            'headcount' => $headcount,
                            'vacantcount' => $request['vacant_positions'][$positionId][$monthIndex],
                            'filledcount' => $request['filled_positions'][$positionId][$monthIndex],
                        ]);
                    }
                }
            }

            // Update the notification response
            ResortsChildNotifications::where('Parent_msg_id', $request['message_id'])
                ->where('Department_id', $request['dept_id'])
                ->update(['response' => 'yes']);

            $Year = $validated['year'];

// event(new ResortNotificationEvent(Common::nofitication($validated['resort_id'], $type[3], $request['message_id'])));

            BudgetStatus::create(
                // ['resort_id' => $manningResponse->resort_id, 'message_id' => $request['message_id']],
                [
                    'resort_id' => $manningResponse->resort_id,
                    'message_id' => $request['message_id'],
                    'Department_id'=>$request['dept_id'],
                    'Budget_id' => $manningResponse->id,
                    'status' => 'Genrated',
                    'comments' => 'Respond to HR',
                    'message_id' => $request['message_id']
                ]
            );

            DB::commit();
            $BudgetStatus =  BudgetStatus::where('resort_id', $manningResponse->resort_id)
                ->where( 'Department_id',$request['dept_id'])
                ->where( 'Budget_id', $manningResponse->id)
                ->get()
                ->toArray();
                $getNotifications['BudgetStatus'] =  $BudgetStatus;
            $view = view('resorts.renderfiles.manninglifecycle', compact( 'getNotifications','Year'))->render();
            return response()->json([
                'success' => true,
                'html' => $view,
                'msg' => 'Data saved successfully.',
                'nextYearHeadcount' => $validated['total_headcount'],
                'currentYearHeadcount' => $request['total_headcount_current_year']
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback the transaction if something goes wrong
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage(),
            ]);
        }
    }

    public function saveDraft(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request
            $validated = $request->validate([
                'resort_id' => 'required|integer',
                'dept_id' => 'required|integer',
                'year' => 'required|integer',
                'monthly_data' => 'required|array',
                'vacant_positions' => 'required|array',
                'filled_positions' => 'required|array',
                'total_headcount' => 'required|integer',
                'total_filled_headcount' => 'required|integer',
                'total_vacant_headcount' =>'required|integer',
            ]);

            // Save or update the ManningResponse
            $manningResponse = ManningResponse::updateOrCreate(
                [
                    'resort_id' => $validated['resort_id'],
                    'dept_id' => $validated['dept_id'],
                    'year' => $validated['year']
                ],
                [
                    'total_headcount' => $validated['total_headcount'],
                    'total_filled_positions' => $validated['total_filled_headcount'] ?? 0,
                    'total_vacant_positions' => $validated['total_vacant_headcount'] ?? 0,
                    'status' => 'draft' // Mark as draft
                ]
            );

            // Clear existing monthly data for the current response
            PositionMonthlyData::where('manning_response_id', $manningResponse->id)->delete();

            // Loop through monthly data to create new records
            foreach ($validated['monthly_data'] as $positionId => $value) {
                foreach ($validated['monthly_data'][$positionId] as $monthIndex => $headcount) {
                    // Create a new PositionMonthlyData record for each month
                    PositionMonthlyData::create([
                        'manning_response_id' => $manningResponse->id,
                        'position_id' => $positionId,
                        'month' => $monthIndex + 1, // Months are 1-12
                        'headcount' => $headcount,
                        'vacantcount' => $validated['vacant_positions'][$positionId][$monthIndex] ?? 0,
                        'filledcount' => $validated['filled_positions'][$positionId][$monthIndex] ?? 0,
                    ]);
                }
            }

            DB::commit(); // Commit the transaction

            return response()->json([
                'success' => true,
                'msg' => 'Draft saved successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback the transaction if something goes wrong
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage(),
            ]);
        }
    }

    public function getDraft($resortId, $deptId, $year)
    {
        // Prepare the manning response query
        $manningResponseQuery = ManningResponse::where('resort_id', $resortId)
            ->where('dept_id', $deptId)
            ->where('year', $year);

        // Debug SQL and bindings for the manning response query
        // dd($manningResponseQuery->toSql(), $manningResponseQuery->getBindings());

        // Execute the query to get the manning response
        $manningResponse = $manningResponseQuery->first();

        if (!$manningResponse) {
            return response()->json(['success' => false, 'msg' => 'No draft found.']);
        }

        // Prepare the monthly data query
        $monthlyDataQuery = PositionMonthlyData::where('manning_response_id', $manningResponse->id);

        // Debug SQL and bindings for the monthly data query
        // dd($monthlyDataQuery->toSql(), $monthlyDataQuery->getBindings());

        // Execute the query to get the monthly data
        $monthlyData = $monthlyDataQuery->get();

        // Process the monthly data into a structured array
        $headcountData = [];
        foreach ($monthlyData as $data) {
            $headcountData[$data->position_id][$data->month] = [
                'headcount' => $data->headcount,
                'vacantcount' => $data->vacantcount,
                'filledcount' => $data->filledcount,
            ];
        }
        // dd($headcountData);
        return response()->json($headcountData);
    }

    public function ShowDepartmentWiseBudgetData(Request $request)
    {
        $data = json_decode($request->data[0], true);

        $year = date('Y') + 1;
        $resortId = auth()->guard('resort-admin')->user()->resort_id;


        if (isset($data['dept_id']) && !empty($data))
        {
            $dept_id = $data['dept_id'];
            $positionMonthlyDataIds = $data['position_monthly_data_id'];
            $Budget_id = $data['manning_response_id'];
            $Message_id = $data['Message_id'];
               $rank = config('settings.Position_Rank');
            $current_rank = auth()->guard('resort-admin')->user()->getEmployee->rank ?? null;
            $available_rank = $rank[$current_rank] ?? '';
            // dd($available_rank);

            $getPositions = DB::table('resort_positions as p')
                ->leftJoin('employees as e', 'p.id', '=', 'e.Position_id')
                ->leftJoin('position_monthly_data as pmd', 'p.id', '=', 'pmd.position_id')
                ->leftJoin('manning_responses as mr', function($join) use ($resortId, $dept_id, $year) {
                    $join->on('pmd.manning_response_id', '=', 'mr.id');
                        // ->where('mr.year', '=', $year);
                })
                ->leftJoin('budget_statuses as bs', function($join) {
                    $join->on('mr.id', '=', 'bs.Budget_id')
                        ->whereRaw('bs.id = (SELECT MAX(id) FROM budget_statuses WHERE Budget_id = mr.id)');
                })
                ->where('p.resort_id', '=', $resortId)
                ->where('p.dept_id', '=', $dept_id)
                ->select(
                    'p.id as Position_id',
                    'mr.id as Budget_id',
                    'p.position_title',
                    'p.dept_id',
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

                                $smrp =  StoreManningResponseParent::updateOrCreate(
                                    [
                                        "Resort_id" => $resortId,
                                        "Department_id" =>$position->dept_id,
                                        "Budget_id" => $position->Budget_id
                                    ],
                                    [
                                        "Resort_id" => $resortId,
                                        "Department_id" => $position->dept_id,
                                        "Budget_id" => $position->Budget_id
                                    ]
                                );
                                foreach ($employees as $emp)
                                {
                                    StoreManningResponseChild::updateOrCreate(
                                        [
                                            "Parent_SMRP_id" => $smrp->id,
                                            'Emp_id' => $emp->Empid
                                        ],
                                        [
                                            "Parent_SMRP_id" => $smrp->id,
                                            'Emp_id' => $emp->Empid,
                                            'Current_Basic_salary' => $emp->basic_salary ?? 0,
                                        ]
                                    );

                                    $vacant_positions = DB::table('store_manning_response_parents as t1')
                                    ->join("store_manning_response_children as t2", "t2.Parent_SMRP_id", "=", "t1.id")
                                    ->join('employees as t3', 't3.id', "=", "t2.Emp_id")
                                    ->join('resort_positions as t4', 't4.id', "=", "t3.Position_id")
                                    ->leftJoin('position_monthly_data as pmd', 't4.id', '=', 'pmd.position_id')
                                    ->leftJoin('manning_responses as mr', function($join) use ($resortId, $year) {
                                        $join->on('pmd.manning_response_id', '=', 'mr.id');
                                            // ->where('mr.year', '=', $year);
                                    })
                                    ->where('t1.resort_id', '=', $resortId)
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
                                        't2.Proposed_Basic_salary',
                                        DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                                        DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
                                    )
                                    ->groupBy('t4.id', 't4.position_title')
                                    ->first();
                                    $emp->Proposed_Basic_salary  =   $vacant_positions->Proposed_Basic_salary;
                                    $emp->vacantData = $vacant_positions;
                                }

                                $position->employees = $employees;
                            }
                        }
                        // dd($position);

                            // dd($positionMonthlyDataIds);

                        // // Process each position_monthly_data_id if needed
                        // foreach ($positionMonthlyDataIds as $positionMonthId)
                        // {
                        //     dd($positionMonthId);
                        //     $budget = $this->budgetCalculationService->calculateBudgetForDepartment($dept_id, $positionMonthId);
                        // }


                }
                else{
                    $budget = (object)collect();
                    $getPositions = (object)collect();
                }
        }
        else
        {
            $budget = (object)collect();
            $getPositions = (object)collect();
        }
        $page_title = "Department Wise View Budget";

        $department = ResortDepartment::where('id',$dept_id)->first();
        return view('resorts.budget.view',compact('Budget_id','available_rank','Message_id','resortId','dept_id','getPositions','department','page_title'));
    }

    public function updateBudgetData(Request $request, $id){
        // dd($request);
        // Create a validator instance using the Validator facade
        $validator = Validator::make($request->all(), [
            'basic_salary' => 'required|numeric',
            'proposed_basic_salary' => 'required|numeric',
            'month_data' => 'required|array',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422); // Unprocessable entity
        }

        // If validation passes, continue with your update logic
        $budget = StoreManningResponseChild::findOrFail($id);
        $budget->Current_Basic_salary = $request->input('basic_salary');
        $budget->Proposed_Basic_salary = $request->input('proposed_basic_salary');
        $budget->Months = json_encode($request->input('month_data')); // Save months as JSON

        $budget->save();

        return response()->json([
            'message' => 'Budget updated successfully!'
        ]);

    }

    public function updateParentTotal(Request $request)
    {
        try {
            $parent = StoreManningResponseParent::where('Budget_id', $request->Budget_id)
                ->where('Department_id', $request->Department_id)
                ->first();

            if ($parent) {
                $parent->Total_Department_budget = $request->Total_Department_budget;
                $parent->save();

                return response()->json(['success' => true, 'message' => 'Total updated successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Parent record not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
