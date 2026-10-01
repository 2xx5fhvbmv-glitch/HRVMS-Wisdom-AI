<?php

namespace App\Http\Controllers\Resorts\Accommodation;

use DB;
use Validator;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\BuildingModel;
use App\Models\AssingAccommodation;
use App\Models\HousekeepingRequest;
use App\Models\HousekeepingServiceCatalog;
use App\Models\BenefitGradeHousekeepingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

/**
 * Trello: "Housekeeping – Combine Housekeeping and Housekeeping Request".
 * The one unified web-portal request flow: HR picks an employee (room is
 * then server-resolved and eligible services come from that employee's
 * Benefit Grid grade, same rule as the mobile HousekeepingRequestController)
 * OR a Building/Room directly (no employee — every active catalog service
 * is offered, since there's no grade to check eligibility against).
 *
 * Writes to the same housekeeping_requests table the mobile app already
 * uses — this is not a parallel system, just the web-portal entry point
 * that table never had. Deliberately does not touch the old
 * housekeeping_schedules system (AccommodationController's houseKeeping*()
 * methods) — that's a separate, already-live feature.
 */
class HousekeepingRequestController extends Controller
{
    protected $resort;

    public function __construct()
    {
        $this->resort = auth()->guard('resort-admin')->user();
    }

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.accommodation.HousekeepingRequest', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }

        $page_title = 'Housekeeping Request';
        $employees = Employee::with('resortAdmin')
            ->where('resort_id', $this->resort->resort_id)
            ->where('status', 'Active')
            ->get();
        $buildings = BuildingModel::where('resort_id', $this->resort->resort_id)->get();
        $lineWorkers = Common::getResortHousekeepingLineWorkers($this->resort->resort_id);

        return view('resorts.Accommodation.HousekeepingRequest.index', compact('page_title', 'employees', 'buildings', 'lineWorkers'));
    }

    public function eligibleServices(Request $request)
    {
        $resortId = $this->resort->resort_id;

        if ($request->filled('emp_id')) {
            $employee = Employee::where('id', $request->emp_id)->where('resort_id', $resortId)->first();
            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
            }

            $gradeLevelId = Common::resolveEmpGrade($resortId, $employee->rank, $employee->benefit_grid_level, $employee->Position_id);
            if (empty($gradeLevelId)) {
                return response()->json(['success' => true, 'message' => 'This employee has no Benefit Grid grade mapped', 'data' => []]);
            }

            $services = BenefitGradeHousekeepingService::join('housekeeping_service_catalog as hsc', 'hsc.id', '=', 'benefit_grade_housekeeping_services.housekeeping_service_id')
                ->where('benefit_grade_housekeeping_services.resort_id', $resortId)
                ->where('benefit_grade_housekeeping_services.grade_level_id', $gradeLevelId)
                ->where('hsc.status', 'active')
                ->select('hsc.id', 'hsc.name')
                ->get();

            return response()->json(['success' => true, 'data' => $services]);
        }

        // Room-only path — no employee, so no Benefit Grid to check against;
        // offer every active catalog service (per Trello card clarification).
        $services = HousekeepingServiceCatalog::where('resort_id', $resortId)
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        return response()->json(['success' => true, 'data' => $services]);
    }

    public function store(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.accommodation.HousekeepingRequest', config('settings.resort_permissions.create')) == false) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $resortId = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'emp_id' => 'nullable|integer',
            'building_id' => 'required_without:emp_id',
            'FloorNo' => 'nullable',
            'RoomNo' => 'nullable',
            'service_ids' => 'required|array|min:1',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'remarks' => 'nullable|string',
            'assigned_to_employee_id' => 'nullable|integer',
            // frequency is the count of days HR picked; recurring_days is the
            // day-of-week set itself (ISO 1=Mon..7=Sun) — kept as two fields
            // instead of deriving one from the other so an inconsistent
            // submission (e.g. frequency=3 with 2 days) is rejected rather
            // than silently generating the wrong number of occurrences.
            'frequency' => 'nullable|integer|min:1|max:7',
            'recurring_days' => 'nullable|array',
            'recurring_days.*' => 'integer|min:1|max:7',
        ], [
            'building_id.required_without' => 'Please select an employee, or a building/room.',
            'service_ids.required' => 'Please select at least one service.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        if ($request->filled('recurring_days') && count($request->recurring_days) !== (int) $request->frequency) {
            return response()->json(['success' => false, 'message' => 'Selected days must match the chosen frequency.'], 422);
        }

        $assignee = null;
        if ($request->filled('assigned_to_employee_id')) {
            $assignee = Employee::where('id', $request->assigned_to_employee_id)->where('resort_id', $resortId)->first();
            if (!Common::isHousekeepingLineWorker($assignee)) {
                return response()->json(['success' => false, 'message' => 'Assignee must be an active Housekeeping Line Worker (not HOD/EXCOM).'], 422);
            }
        }

        $employee = null;
        $buildingId = $request->building_id;
        $floor = $request->FloorNo;
        $room = $request->RoomNo;
        $serviceIds = $request->service_ids;

        if ($request->filled('emp_id')) {
            $employee = Employee::where('id', $request->emp_id)->where('resort_id', $resortId)->first();
            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
            }

            // Room is always server-resolved from the employee's actual
            // accommodation assignment, never client-supplied — same
            // convention as the mobile HousekeepingRequestController.
            $assignment = AssingAccommodation::where('emp_id', $employee->id)
                ->join('available_accommodation_models as aam', 'aam.id', '=', 'assing_accommodations.available_a_id')
                ->select('aam.BuildingName as building_id', 'aam.Floor as FloorNo', 'aam.RoomNo')
                ->first();
            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'No accommodation is assigned to this employee.'], 200);
            }
            $buildingId = $assignment->building_id;
            $floor = $assignment->FloorNo;
            $room = $assignment->RoomNo;

            // Re-validate eligibility server-side — never trust the
            // submitted service_ids for the employee path.
            $gradeLevelId = Common::resolveEmpGrade($resortId, $employee->rank, $employee->benefit_grid_level, $employee->Position_id);
            $serviceIds = BenefitGradeHousekeepingService::where('resort_id', $resortId)
                ->where('grade_level_id', $gradeLevelId)
                ->whereIn('housekeeping_service_id', $serviceIds)
                ->pluck('housekeeping_service_id')
                ->all();

            if (empty($serviceIds)) {
                return response()->json(['success' => false, 'message' => 'None of the selected services are eligible for this employee\'s Benefit Grid grade'], 200);
            }
        } else {
            // Room-only path — no eligibility grade to check against, so
            // just confirm the submitted services are real, active catalog
            // entries for this resort.
            $serviceIds = HousekeepingServiceCatalog::where('resort_id', $resortId)
                ->where('status', 'active')
                ->whereIn('id', $serviceIds)
                ->pluck('id')
                ->all();

            if (empty($serviceIds)) {
                return response()->json(['success' => false, 'message' => 'None of the selected services are valid.'], 200);
            }
        }

        // Rolling-4-week generation: one HousekeepingRequest row per
        // (service x occurrence date), each independently trackable
        // (own status/assignee/photos) via the shared batch_id. A
        // non-recurring submission is just the single scheduled_date, same
        // as before.
        $recurringDays = $request->filled('recurring_days') ? array_map('intval', $request->recurring_days) : [];
        $occurrenceDates = empty($recurringDays)
            ? [$request->scheduled_date]
            : $this->buildRecurringOccurrenceDates($request->scheduled_date, $recurringDays, 28);

        try {
            DB::beginTransaction();

            $raisedBy = $this->resort->GetEmployee->id;
            $batchId = (string) Str::uuid();
            $created = [];

            foreach ($serviceIds as $serviceId) {
                foreach ($occurrenceDates as $occurrenceDate) {
                    $created[] = HousekeepingRequest::create([
                        'resort_id' => $resortId,
                        'batch_id' => $batchId,
                        'employee_id' => $employee->id ?? null,
                        'assigned_to_employee_id' => $assignee->id ?? null,
                        'housekeeping_service_id' => $serviceId,
                        'raised_by' => $raisedBy,
                        'BuildingName' => $buildingId,
                        'FloorNo' => $floor,
                        'RoomNo' => $room,
                        'remarks' => $request->remarks,
                        'scheduled_date' => $occurrenceDate,
                        'scheduled_time' => $request->scheduled_time,
                        'frequency' => $request->frequency,
                        'recurring_days' => empty($recurringDays) ? null : json_encode($recurringDays),
                        'status' => 'Pending',
                    ]);
                }
            }

            DB::commit();

            if ($employee) {
                try {
                    Common::notifyEmployees(
                        $resortId,
                        [$employee->id],
                        'Housekeeping Request Raised',
                        'A housekeeping request has been raised for your accommodation.',
                        'Housekeeping Request',
                        $created[0]->id
                    );
                } catch (\Exception $e) {
                    \Log::warning('HousekeepingRequestController::store employee notify failed: ' . $e->getMessage());
                }
            }

            try {
                $hkHodXcomIds = Common::getResortHousekeepingHodXcomEmployeeIds($resortId);
                if (!empty($hkHodXcomIds)) {
                    Common::notifyEmployees(
                        $resortId,
                        $hkHodXcomIds,
                        'New Housekeeping Request',
                        $this->resort->first_name . ' ' . $this->resort->last_name . ' has raised a housekeeping request' . ($employee ? ' for ' . $employee->Emp_id : '') . '.',
                        'Housekeeping Request',
                        $created[0]->id
                    );
                }
            } catch (\Exception $e) {
                \Log::warning('HousekeepingRequestController::store HOD notify failed: ' . $e->getMessage());
            }

            // One notification per submission, not one per generated
            // occurrence — the Line Worker sees every date on the task in
            // their mobile Housekeeping Services/Tasks list already; a
            // recurring "3x/week" schedule shouldn't spam 12 separate pushes.
            if ($assignee) {
                try {
                    Common::notifyEmployees(
                        $resortId,
                        [$assignee->id],
                        'Housekeeping Task Assigned',
                        count($occurrenceDates) > 1
                            ? 'You have ' . count($occurrenceDates) . ' housekeeping tasks scheduled, starting ' . \Carbon\Carbon::parse($occurrenceDates[0])->format('d M Y') . '.'
                            : 'You have been assigned a housekeeping task for ' . \Carbon\Carbon::parse($occurrenceDates[0])->format('d M Y') . '.',
                        'Housekeeping Request',
                        $created[0]->id
                    );
                } catch (\Exception $e) {
                    \Log::warning('HousekeepingRequestController::store assignee notify failed: ' . $e->getMessage());
                }
            }

            return response()->json(['success' => true, 'message' => 'Housekeeping request(s) created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File: ' . $e->getFile());
            \Log::emergency('Line: ' . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * ISO weekday (1=Mon..7=Sun) dates matching $days, from $startDate
     * through the next $windowDays. $startDate is the anchor/start of the
     * window, not necessarily an occurrence itself — e.g. HR picks a
     * Thursday start date with Mon/Tue/Wed selected; the first generated
     * occurrence is the next matching Monday, not the Thursday.
     */
    private function buildRecurringOccurrenceDates(string $startDate, array $days, int $windowDays): array
    {
        $start = \Carbon\Carbon::parse($startDate);
        $dates = [];
        for ($i = 0; $i < $windowDays; $i++) {
            $date = $start->copy()->addDays($i);
            if (in_array($date->dayOfWeekIso, $days, true)) {
                $dates[] = $date->toDateString();
            }
        }
        return $dates;
    }

    /** Eligible "Assign to" dropdown for the create form and any future re-assign action. */
    public function eligibleLineWorkers()
    {
        $lineWorkers = Common::getResortHousekeepingLineWorkers($this->resort->resort_id)
            ->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => trim(($emp->resortAdmin->first_name ?? '') . ' ' . ($emp->resortAdmin->last_name ?? '')) . ' (' . $emp->Emp_id . ')',
                ];
            })
            ->values();

        return response()->json(['success' => true, 'data' => $lineWorkers]);
    }

    public function list(Request $request)
    {
        $resortId = $this->resort->resort_id;
        // Housekeeping HOD/XCOM own this queue resort-wide (see
        // Common::isHousekeepingHodXcom); everyone else stays dept-scoped.
        $scopedDeptIds = Common::isHousekeepingHodXcom($this->resort->GetEmployee)
            ? null
            : Common::getScopedDepartmentIds($this->resort->GetEmployee);

        $rows = HousekeepingRequest::leftJoin('employees as emp', 'emp.id', '=', 'housekeeping_requests.employee_id')
            ->leftJoin('resort_admins as emp_admin', 'emp_admin.id', '=', 'emp.Admin_Parent_id')
            ->join('housekeeping_service_catalog as hsc', 'hsc.id', '=', 'housekeeping_requests.housekeeping_service_id')
            ->leftJoin('building_models as bm', 'bm.id', '=', 'housekeeping_requests.BuildingName')
            ->join('employees as raiser_emp', 'raiser_emp.id', '=', 'housekeeping_requests.raised_by')
            ->join('resort_admins as raiser', 'raiser.id', '=', 'raiser_emp.Admin_Parent_id')
            ->leftJoin('employees as assignee_emp', 'assignee_emp.id', '=', 'housekeeping_requests.assigned_to_employee_id')
            ->leftJoin('resort_admins as assignee', 'assignee.id', '=', 'assignee_emp.Admin_Parent_id')
            ->where('housekeeping_requests.resort_id', $resortId)
            ->when($scopedDeptIds !== null, function ($query) use ($scopedDeptIds) {
                // A restricted HOD/XCOM only ever sees their own department's
                // requests. A room-only request has no employee/department at
                // all, so — same default-restrictive rule as
                // Common::hasFullDataAccess() — it's HR/GM-only visibility,
                // not shown to a scoped user just because it isn't excluded.
                return $query->whereIn('emp.Dept_id', $scopedDeptIds);
            })
            ->select(
                'housekeeping_requests.*',
                'emp_admin.first_name as emp_first_name',
                'emp_admin.last_name as emp_last_name',
                'emp.Emp_id as emp_code',
                'hsc.name as service_name',
                'bm.BuildingName as building_name',
                'raiser.first_name as raiser_first_name',
                'raiser.last_name as raiser_last_name',
                'assignee.first_name as assignee_first_name',
                'assignee.last_name as assignee_last_name'
            )
            ->orderBy('housekeeping_requests.created_at', 'desc')
            ->get()
            ->map(function ($row) {
                $row->RequestedFor = $row->employee_id
                    ? trim($row->emp_first_name . ' ' . $row->emp_last_name) . ' (' . $row->emp_code . ')'
                    : trim(($row->building_name ?? 'Building') . (($row->FloorNo || $row->RoomNo) ? ', ' : '') . ($row->FloorNo ? 'Floor ' . $row->FloorNo : '') . ($row->RoomNo ? ' Room ' . $row->RoomNo : ''));
                $row->RaisedBy = trim($row->raiser_first_name . ' ' . $row->raiser_last_name);
                $row->AssignedTo = $row->assigned_to_employee_id ? trim($row->assignee_first_name . ' ' . $row->assignee_last_name) : '-';
                $row->ScheduledOn = $row->scheduled_date
                    ? \Carbon\Carbon::parse($row->scheduled_date)->format('d M Y') . ($row->scheduled_time ? ' ' . \Carbon\Carbon::parse($row->scheduled_time)->format('h:i A') : '')
                    : '-';
                return $row;
            });

        return datatables()->of($rows)
            ->editColumn('RequestedFor', fn($row) => e($row->RequestedFor))
            ->editColumn('service_name', fn($row) => e($row->service_name))
            ->editColumn('RaisedBy', fn($row) => e($row->RaisedBy))
            ->editColumn('AssignedTo', fn($row) => e($row->AssignedTo))
            ->editColumn('ScheduledOn', fn($row) => e($row->ScheduledOn))
            ->editColumn('status', function ($row) {
                $map = [
                    'Pending' => 'badge-themeWarning',
                    'Accepted' => 'badge-blueNew',
                    'In-Progress' => 'badge-blueNew',
                    'Completed' => 'badge-success',
                    'Not Completed' => 'badge-danger',
                ];
                $class = $map[$row->status] ?? 'badge-themeWarning';
                return '<span class="badge ' . $class . ' border-0">' . e($row->status) . '</span>';
            })
            ->editColumn('remarks', fn($row) => e($row->remarks))
            ->editColumn('photos', function ($row) {
                if (empty($row->photos)) {
                    return '-';
                }
                $basePath = config('settings.HousekeepingRequestPhotos') . '/' . $row->resort_id;
                $links = [];
                foreach (explode(',', $row->photos) as $i => $filename) {
                    $filename = trim($filename);
                    if ($filename === '') {
                        continue;
                    }
                    try {
                        $path = $basePath . '/' . $filename;
                        if (\App\Helpers\StorageHelper::disk()->exists($path)) {
                            $links[] = '<a href="' . e(\App\Helpers\StorageHelper::temporaryUrl($path, 30)) . '" target="_blank">Photo ' . ($i + 1) . '</a>';
                        }
                    } catch (\Throwable $e) {
                        // skip files that fail to resolve
                    }
                }
                return $links ? implode(' | ', $links) : '-';
            })
            ->rawColumns(['status', 'photos'])
            ->make(true);
    }
}
