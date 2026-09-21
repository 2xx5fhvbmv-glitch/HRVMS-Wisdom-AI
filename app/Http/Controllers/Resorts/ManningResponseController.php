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
        // Which category's grid this is — same 'Permanent' default as every
        // other manning endpoint, so a caller that predates Casual/Intern
        // manning (or simply doesn't send this) behaves exactly as before.
        $employmentType = $request->input('employment_type', 'Permanent');

        // Fetch active employees for the given position in the specific
        // resort AND category — without this, typing a headcount on the
        // Casual tab could show Permanent employees (or vice versa)
        // already filling that position title, since a position can be
        // shared across categories.
        $employees = Employee::with('resortAdmin') // Eager load the resortAdmin relationship
                    ->where('Position_id', $positionId)
                    ->where('resort_id', $resort_id)
                    ->where('status', 'Active')
                    ->whereIn('employment_type', Common::manningCategoryEmploymentTypes($employmentType))
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
        // resort_id was accepted as plain client input — always derive it
        // from the authenticated resort-admin instead.
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        // Fetch manning response for the current year and position
        // Use first() to get a single record
        // employment_type defaults to Permanent — every existing caller
        // (before Casual/Intern manning existed) only ever meant the
        // permanent submission, so no behavior change for them.
        $employmentType = $request->input('employment_type', 'Permanent');

        $manningResponse = ManningResponse::where('dept_id', $dept_id)
            ->where('resort_id', $resort_id)
            ->where('year', $currentYear)
            ->where('employment_type', $employmentType)
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
                'employment_type' => 'nullable|string|in:Permanent,Casual,Intern',
                'monthly_data' => 'required|array',
                'total_headcount' => 'required|integer',
                'total_filled_headcount' => 'required|integer',
                'total_vacant_headcount' =>'required|integer',
            ]);

            // resort_id was accepted as plain client input and used directly
            // everywhere below — a hostile request from Resort A could wipe
            // and rewrite Resort B's workforce-planning numbers. Always
            // derive it from the authenticated resort-admin instead.
            $validated['resort_id'] = Auth::guard('resort-admin')->user()->resort_id;
            // Permanent/Casual/Intern are independent submissions — an HOD
            // picks which one they're filling in, nothing forces all three
            // together. Defaults to Permanent so every existing caller
            // (predating Casual/Intern manning) behaves exactly as before.
            $validated['employment_type'] = $validated['employment_type'] ?? 'Permanent';

            // Casual/Intern submissions need HR to have configured at
            // least one active cost rate first — otherwise the budget
            // this submission drives (Common::computeBudgetCostMonthlyValue()
            // reading resort_nonpermanent_budget_costs) would compute as
            // $0 for every cost line, silently. Permanent is unaffected —
            // it already has its own long-established cost configuration.
            if (in_array($validated['employment_type'], ['Casual', 'Intern'], true)) {
                $hasCostConfig = \App\Models\ResortNonpermanentBudgetCost::where('resort_id', $validated['resort_id'])
                    ->where('status', 'active')
                    ->where(function ($q) use ($validated) {
                        $q->where('applies_to', $validated['employment_type'])
                          ->orWhere('applies_to', 'Both');
                    })
                    ->exists();
                if (!$hasCostConfig) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'msg' => 'HR needs to configure Casual/Intern cost rates before this can be submitted — see Cost Configuration for Casuals & Interns.',
                    ]);
                }
            }

            // Check if a ManningResponse already exists for the given resort, department, year AND category
            $manningResponse = ManningResponse::where('resort_id', $validated['resort_id'])
                ->where('dept_id', $validated['dept_id'])
                ->where('year', $validated['year'])
                ->where('employment_type', $validated['employment_type'])
                ->first();

            if ($manningResponse) {
                // If a record exists, delete the existing PositionMonthlyData
                PositionMonthlyData::where('manning_response_id', $manningResponse->id)->delete();

                // Update the existing ManningResponse
                $manningResponse->update([
                    'total_headcount' => $validated['total_headcount'],
                    'total_filled_positions' => $validated['total_filled_headcount'] ?? 0,
                    'total_vacant_positions' => $validated['total_vacant_headcount'] ?? 0,
                    // WP4 — was never set, so a real submission and a draft
                    // were indistinguishable (both '' — see the model's
                    // $fillable fix). This is the actual "submit" moment.
                    'status' => 'submitted',
                ]);
            } else {
                // Create a new ManningResponse if no record exists
                $manningResponse = ManningResponse::create([
                    'resort_id' => $validated['resort_id'],
                    'dept_id' => $validated['dept_id'],
                    'year' => $validated['year'],
                    'employment_type' => $validated['employment_type'],
                    'total_headcount' => $validated['total_headcount'],
                    'total_filled_positions' => $validated['total_filled_headcount'] ?? 0,
                    'total_vacant_positions' => $validated['total_vacant_headcount'] ?? 0,
                    'status' => 'submitted',
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

            // WP4 — a multi-category submit calls store() once per category
            // sequentially; closing the notification on every individual
            // success meant one category succeeding could mark the HOD's
            // WHOLE request "answered" even if a sibling category in the
            // same batch failed (they'd have no way to resend it — the
            // dashboard would already show "No Requests"). The multi-
            // category JS (submitMultipleCategories()) sets this true for
            // every call in the batch and only closes the notification
            // itself, once, after confirming every category succeeded
            // (finishMultiSubmit() -> closeManningRequestNotification()).
            // A single-category submit never sets it, so today's behavior
            // (close immediately) is unchanged for the common case.
            if (!$request->boolean('skip_notification_close')) {
                ResortsChildNotifications::where('Parent_msg_id', $request['message_id'])
                    ->where('Department_id', $request['dept_id'])
                    ->update(['response' => 'yes']);
            }

            $Year = $validated['year'];

            // HR raised the manning request; this department's response
            // needs to reach them so they're not left waiting/chasing it up
            // manually. Was commented out — Common::notifyEmployees() is the
            // current recommended helper (one DB row + one push per
            // recipient), matching the pattern already used elsewhere in
            // this module for the reminder path.
            try {
                $deptName = ResortDepartment::where('id', $validated['dept_id'])->value('name');
                $hrEmployeeIds = Common::getResortHrEmployeeIds($validated['resort_id']);

                Common::notifyEmployees(
                    $validated['resort_id'],
                    $hrEmployeeIds,
                    'Manning Response Submitted',
                    ($deptName ?: 'A department') . ' has submitted its manning response for ' . $Year . '.',
                    'WorkForce Planning',
                    $manningResponse->id
                );
            } catch (\Exception $notifErr) {
                \Log::warning('Manning response notification to HR failed: ' . $notifErr->getMessage());
            }

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
                'employment_type' => 'nullable|string|in:Permanent,Casual,Intern',
                'monthly_data' => 'required|array',
                'vacant_positions' => 'required|array',
                'filled_positions' => 'required|array',
                'total_headcount' => 'required|integer',
                'total_filled_headcount' => 'required|integer',
                'total_vacant_headcount' =>'required|integer',
            ]);

            // resort_id was accepted as plain client input — a hostile
            // request from Resort A could wipe and rewrite Resort B's
            // workforce-planning draft. Always derive it from the
            // authenticated resort-admin instead.
            $validated['resort_id'] = Auth::guard('resort-admin')->user()->resort_id;
            $validated['employment_type'] = $validated['employment_type'] ?? 'Permanent';

            // WP4 — same rule getPositionsByCategory()/ShowDepartmentWiseBudgetData()
            // already apply: dept_id is client-supplied, an HOD could
            // otherwise auto-save a draft into another department's slot.
            $scopedDeptIds = Common::getScopedDepartmentIds();
            if (is_array($scopedDeptIds) && !in_array((int) $validated['dept_id'], $scopedDeptIds, true)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'You do not have access to this department.'], 403);
            }

            $existing = ManningResponse::where([
                'resort_id' => $validated['resort_id'],
                'dept_id' => $validated['dept_id'],
                'year' => $validated['year'],
                'employment_type' => $validated['employment_type'],
            ])->first();

            // WP4 — a tab switch away from an ALREADY-SUBMITTED category
            // must never silently demote it back to 'draft'. Revision
            // (HR sending a submitted category back) is a separate, HR-
            // initiated flow (WP5) that flips status itself — this method
            // only ever writes a fresh, still-open draft.
            // A2 — legacy rows submitted before `status` was tracked are
            // '' (empty string, or null), not 'submitted'; a strict
            // `=== 'submitted'` check let those fall through to the
            // updateOrCreate below, silently demoting an already-submitted
            // legacy row to draft and wiping its position_monthly_data.
            // Treat anything that isn't explicitly 'draft' as already
            // submitted — belt-and-braces on top of the backfill migration.
            if ($existing && $existing->status !== 'draft') {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'skipped' => true,
                    'msg' => 'Already submitted — not modified by the auto-save.',
                ]);
            }

            // Save or update the ManningResponse
            $manningResponse = ManningResponse::updateOrCreate(
                [
                    'resort_id' => $validated['resort_id'],
                    'dept_id' => $validated['dept_id'],
                    'year' => $validated['year'],
                    'employment_type' => $validated['employment_type'],
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

    /**
     * Which of Permanent/Casual/Intern already have an UNSUBMITTED draft
     * for this dept+year — powers §29 (submit several categories
     * together). WP4 — status='draft' only: an already-submitted category
     * must never appear here, or the multi-category recap would resubmit
     * it (duplicate BudgetStatus row, duplicate HR notification). A row
     * only exists at all because the tab-switch auto-save gates on
     * total_headcount > 0, so existence is a reliable "has real data"
     * signal on top of the status filter.
     */
    public function getCategoriesWithData($deptId, $year)
    {
        $resortId = Auth::guard('resort-admin')->user()->resort_id;

        $scopedDeptIds = Common::getScopedDepartmentIds();
        if (is_array($scopedDeptIds) && !in_array((int) $deptId, $scopedDeptIds, true)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this department.'], 403);
        }

        $rows = ManningResponse::where('resort_id', $resortId)
            ->where('dept_id', $deptId)
            ->where('year', $year)
            ->where('status', 'draft')
            ->whereIn('employment_type', ['Permanent', 'Casual', 'Intern'])
            ->get(['employment_type', 'total_headcount', 'total_filled_positions', 'total_vacant_positions']);

        $result = [];
        foreach ($rows as $row) {
            $result[$row->employment_type] = [
                'total_headcount' => $row->total_headcount,
                'total_filled_positions' => $row->total_filled_positions,
                'total_vacant_positions' => $row->total_vacant_positions,
            ];
        }

        return response()->json(['success' => true, 'categories' => $result]);
    }

    /**
     * WP4 — closes the HOD's pending manning request, called once by the
     * multi-category submit flow (finishMultiSubmit()) only after every
     * intended category's store() call has succeeded. Never called by a
     * single-category submit — store() closes it directly in that case,
     * unchanged from before this fix.
     */
    public function closeManningRequestNotification(Request $request)
    {
        $resortId = Auth::guard('resort-admin')->user()->resort_id;
        $deptId = (int) $request->input('dept_id');

        $scopedDeptIds = Common::getScopedDepartmentIds();
        if (is_array($scopedDeptIds) && !in_array($deptId, $scopedDeptIds, true)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this department.'], 403);
        }

        // C1 — resorts_child_notifications has no resort_id column of its
        // own; without this, a caller whose dept_id legitimately belongs to
        // their own resort could still pass ANOTHER resort's message_id
        // (format is guessable — "DR" + digits) and mark that resort's
        // notification answered. Verify the message_id's own parent row
        // actually belongs to this resort first.
        $messageId = $request->input('message_id');
        $belongsToResort = \App\Models\ResortsParentNotifications::where('message_id', $messageId)
            ->where('resort_id', $resortId)
            ->exists();
        if (!$belongsToResort) {
            return response()->json(['success' => false, 'message' => 'Invalid request.'], 403);
        }

        ResortsChildNotifications::where('Parent_msg_id', $messageId)
            ->where('Department_id', $deptId)
            ->update(['response' => 'yes']);

        return response()->json(['success' => true]);
    }

    public function getDraft($resortId, $deptId, $year, $employmentType = 'Permanent')
    {
        // {resortId} is a client-supplied URL segment — never trust it.
        // Always resolve the draft against the authenticated resort-admin's
        // own resort so a crafted URL can't read another resort's draft.
        $resortId = Auth::guard('resort-admin')->user()->resort_id;

        // Prepare the manning response query
        $manningResponseQuery = ManningResponse::where('resort_id', $resortId)
            ->where('dept_id', $deptId)
            ->where('year', $year)
            ->where('employment_type', $employmentType);

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

    /**
     * Position rows for the manning grid, scoped to department + category —
     * called on every Permanent/Casual/Intern tab switch so the grid shows
     * that category's own positions instead of one shared static list
     * (Permanent = employee_category NULL, matching every position that
     * existed before Casual/Intern positions could be created at all).
     */
    public function getPositionsByCategory($deptId, $employmentType = 'Permanent')
    {
        $resortId = Auth::guard('resort-admin')->user()->resort_id;

        // Same rule ShowDepartmentWiseBudgetData (below) already applies:
        // dept_id is client-supplied, an HOD could otherwise pass another
        // department's id and list its positions.
        $scopedDeptIds = Common::getScopedDepartmentIds();
        if (is_array($scopedDeptIds) && !in_array((int) $deptId, $scopedDeptIds, true)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this department.'], 403);
        }

        $positions = ResortPosition::where('resort_id', $resortId)
            ->where('dept_id', $deptId)
            ->where('status', 'active')
            ->when($employmentType === 'Permanent', function ($q) {
                $q->whereNull('employee_category');
            }, function ($q) use ($employmentType) {
                $q->where('employee_category', $employmentType);
            })
            ->orderBy('position_title')
            ->get(['id', 'position_title', 'no_of_positions']);

        return response()->json(['success' => true, 'positions' => $positions]);
    }

    public function ShowDepartmentWiseBudgetData(Request $request)
    {
        $data = json_decode($request->data[0], true);

        $year = date('Y') + 1;
        $resortId = auth()->guard('resort-admin')->user()->resort_id;


        if (isset($data['dept_id']) && !empty($data))
        {
            $dept_id = $data['dept_id'];

            // dept_id came straight from the client payload with no check —
            // any HOD/XCOM could request another department's full
            // budget/position/salary breakdown by passing an arbitrary
            // dept_id (IDOR). Enforce the same department scope every other
            // list in this app uses.
            $scopedDeptIds = Common::getScopedDepartmentIds();
            if (is_array($scopedDeptIds) && !in_array((int) $dept_id, $scopedDeptIds, true)) {
                return response()->json(['success' => false, 'message' => 'You do not have access to this department.'], 403);
            }

            $positionMonthlyDataIds = $data['position_monthly_data_id'];
            $Budget_id = $data['manning_response_id'];
            $Message_id = $data['Message_id'];
               $rank = config('settings.Position_Rank');
            $current_rank = auth()->guard('resort-admin')->user()->getEmployee->rank ?? null;
            $available_rank = $rank[$current_rank] ?? '';
            // dd($available_rank);

            // WP6(D6) — $Budget_id names one specific manning_response (one
            // category, one year) but was never actually used to scope the
            // query below: the mr join only checked resort_id, so this
            // blended every year/category's positions+headcounts together
            // regardless of which budget the caller asked about.
            $manningResponseForBudget = $Budget_id ? \App\Models\ManningResponse::find($Budget_id) : null;
            $scopeEmploymentType = $manningResponseForBudget->employment_type ?? 'Permanent';
            $scopeYear = $manningResponseForBudget->year ?? $year;

            $getPositions = DB::table('resort_positions as p')
                // Employees join now scopes to THIS resort. Without it
                // the downstream HAVING COUNT(e.id) > 0 could fire on the
                // back of an employee from another resort that happens
                // to share the same Position_id, surfacing that resort's
                // headcount as ours. (Reported live data-difference
                // between the WP HR dashboard and this view.)
                // Raw DB::table query — Eloquent's soft-delete scope and any
                // status filter do NOT apply automatically here, so a
                // terminated/inactive employee (deleted_at set or
                // status != Active) still counted as "filling" the seat,
                // showing e.g. 2 employee names against "No. of Position: 1".
                ->leftJoin('employees as e', function ($j) use ($resortId) {
                    $j->on('p.id', '=', 'e.Position_id')
                      ->where('e.resort_id', '=', $resortId)
                      ->where('e.status', '=', 'Active')
                      ->whereNull('e.deleted_at');
                })
                ->leftJoin('position_monthly_data as pmd', 'p.id', '=', 'pmd.position_id')
                // manning_responses also scoped to THIS resort. The
                // outer `use ($resortId, ...)` was importing $resortId
                // into the closure but never applying it, so any other
                // resort's manning_response sharing a pmd row leaked in.
                ->leftJoin('manning_responses as mr', function ($join) use ($resortId, $scopeYear, $scopeEmploymentType) {
                    $join->on('pmd.manning_response_id', '=', 'mr.id')
                         ->where('mr.resort_id', '=', $resortId)
                         ->where('mr.year', '=', $scopeYear)
                         ->where('mr.employment_type', '=', $scopeEmploymentType);
                })
                ->leftJoin('budget_statuses as bs', function($join) {
                    $join->on('mr.id', '=', 'bs.Budget_id')
                        ->whereRaw('bs.id = (SELECT MAX(id) FROM budget_statuses WHERE Budget_id = mr.id)');
                })
                ->where('p.resort_id', '=', $resortId)
                ->where('p.dept_id', '=', $dept_id)
                ->where(function ($q) use ($scopeEmploymentType) {
                    if ($scopeEmploymentType === 'Permanent') {
                        $q->whereNull('p.employee_category');
                    } else {
                        $q->where('p.employee_category', $scopeEmploymentType);
                    }
                })
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
                            // CRITICAL: scope employees by resort_id. Without
                            // this, the foreach loop hydrates each position
                            // with employees from ANY resort that shares the
                            // same Position_id + Dept_id — directly the
                            // "data difference vs WP HR dashboard" the user
                            // reported.
                            $employees = DB::table('employees as e')
                            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                            ->where('e.resort_id', $resortId)
                            ->where('position_id', $position->Position_id)
                            ->where('Dept_id', $position->dept_id)
                            ->where('e.status', 'Active')
                            ->whereNull('e.deleted_at')
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
                                    // manning_responses join now applies the $resortId
                                    // captured by the closure — previously the
                                    // outer `use ($resortId, $year)` imported it
                                    // but the join body never used it, letting
                                    // another resort's manning_response leak in
                                    // alongside ours via the same pmd row.
                                    ->leftJoin('manning_responses as mr', function ($join) use ($resortId) {
                                        $join->on('pmd.manning_response_id', '=', 'mr.id')
                                             ->where('mr.resort_id', '=', $resortId);
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
        // Scoped via the parent record's Resort_id — bare findOrFail($id)
        // let a caller load and overwrite any resort's salary/budget line
        // item.
        $resortId = Auth::guard('resort-admin')->user()->resort_id;
        $budget = StoreManningResponseChild::where('id', $id)
            ->whereIn('Parent_SMRP_id', function ($q) use ($resortId) {
                $q->select('id')->from('store_manning_response_parents')->where('Resort_id', $resortId);
            })
            ->first();

        if (!$budget) {
            return response()->json(['message' => 'Budget item not found.'], 404);
        }

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
            // Budget_id/Department_id alone don't prove the row belongs to
            // the caller's resort — scope by Resort_id too so a guessed
            // Budget_id+Department_id pair from another resort can't be
            // overwritten.
            $parent = StoreManningResponseParent::where('Budget_id', $request->Budget_id)
                ->where('Department_id', $request->Department_id)
                ->where('Resort_id', Auth::guard('resort-admin')->user()->resort_id)
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
