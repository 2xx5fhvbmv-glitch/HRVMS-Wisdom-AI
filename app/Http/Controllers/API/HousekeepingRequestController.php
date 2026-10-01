<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\AssingAccommodation;
use App\Models\HousekeepingServiceCatalog;
use App\Models\BenefitGradeHousekeepingService;
use App\Models\HousekeepingRequest;
use App\Helpers\Common;
use Illuminate\Support\Str;
use Validator;
use Auth;
use DB;

/**
 * Card 4 (Trello: "Housekeeping Request – Benefit Grid and HR Request
 * Integration Missing"). resort_benifit_grid.housekeeping was a single
 * free-text frequency string with no concept of predefined, per-grade
 * services — this controller is the new catalog-driven replacement:
 * HR picks an employee, sees only the services their Benefit Grid grade is
 * eligible for, raises a request, and tracks it to completion.
 *
 * Deliberately separate from AccommodationController's existing
 * houseKeeping*() methods — those back housekeeping_schedules/
 * child_housekeeping_schedules, an unrelated pre-existing cleaning-
 * schedule/assignment system tied to available_accommodation_models, not
 * to Benefit Grid eligibility. Conflating the two would change behavior of
 * a live feature already in production use.
 */
class HousekeepingRequestController extends Controller
{
    protected $user;
    protected $resort_id;

    public function __construct()
    {
        if (Auth::guard('api')->check()) {
            $this->user      = Auth::guard('api')->user();
            $this->resort_id = $this->user->resort_id;
        }
    }

    public function servicesByGrade($emp_id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee = Employee::where('id', $emp_id)->where('resort_id', $this->resort_id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        $gradeLevelId = Common::resolveEmpGrade($this->resort_id, $employee->rank, $employee->benefit_grid_level, $employee->Position_id);

        if (empty($gradeLevelId)) {
            return response()->json([
                'success' => true,
                'message' => 'This employee has no Benefit Grid grade mapped',
                'data'    => [],
            ]);
        }

        $services = BenefitGradeHousekeepingService::join('housekeeping_service_catalog as hsc', 'hsc.id', '=', 'benefit_grade_housekeeping_services.housekeeping_service_id')
            ->where('benefit_grade_housekeeping_services.resort_id', $this->resort_id)
            ->where('benefit_grade_housekeeping_services.grade_level_id', $gradeLevelId)
            ->where('hsc.status', 'active')
            ->select('hsc.id', 'hsc.name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Eligible housekeeping services retrieved successfully',
            'data'    => $services,
        ]);
    }

    public function createRequest(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'emp_id'      => 'required|integer',
            'service_ids' => 'required|array|min:1',
            'remarks'     => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $employee = Employee::where('id', $request->emp_id)->where('resort_id', $this->resort_id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        // Same convention as StaffAccommodationController::createMaintenanceRequests():
        // building/floor/room are resolved server-side from the accommodation
        // actually assigned to the employee, never client-supplied.
        $assignment = AssingAccommodation::where('emp_id', $employee->id)
            ->join('available_accommodation_models as aam', 'aam.id', '=', 'assing_accommodations.available_a_id')
            ->select('aam.BuildingName as building_id', 'aam.Floor as FloorNo', 'aam.RoomNo')
            ->first();
        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'No accommodation is assigned to this employee.'], 200);
        }

        $gradeLevelId = Common::resolveEmpGrade($this->resort_id, $employee->rank, $employee->benefit_grid_level, $employee->Position_id);
        $eligibleServiceIds = BenefitGradeHousekeepingService::where('resort_id', $this->resort_id)
            ->where('grade_level_id', $gradeLevelId)
            ->whereIn('housekeeping_service_id', $request->service_ids)
            ->pluck('housekeeping_service_id')
            ->all();

        if (empty($eligibleServiceIds)) {
            return response()->json(['success' => false, 'message' => 'None of the selected services are eligible for this employee\'s Benefit Grid grade'], 200);
        }

        try {
            DB::beginTransaction();

            $raisedBy = $this->user->GetEmployee->id;
            $batchId  = (string) Str::uuid();
            $created  = [];

            foreach ($eligibleServiceIds as $serviceId) {
                $created[] = HousekeepingRequest::create([
                    'resort_id'               => $this->resort_id,
                    'batch_id'                => $batchId,
                    'employee_id'             => $employee->id,
                    'housekeeping_service_id' => $serviceId,
                    'raised_by'               => $raisedBy,
                    'BuildingName'            => $assignment->building_id,
                    'FloorNo'                 => $assignment->FloorNo,
                    'RoomNo'                  => $assignment->RoomNo,
                    'remarks'                 => $request->remarks,
                    'status'                  => 'Pending',
                ]);
            }

            DB::commit();

            try {
                Common::notifyEmployees(
                    $this->resort_id,
                    [$employee->id],
                    'Housekeeping Request Raised',
                    'A housekeeping request has been raised for your accommodation.',
                    'Housekeeping Request',
                    $created[0]->id
                );
            } catch (\Exception $e) {
                \Log::warning('HousekeepingRequestController::createRequest notify failed: ' . $e->getMessage());
            }

            // The Housekeeping HOD/XCOM was never notified at all — the
            // only notification this method sent was to the employee whose
            // accommodation the request is about, not to whoever needs to
            // actually action it.
            try {
                $hkHodXcomIds = Common::getResortHousekeepingHodXcomEmployeeIds($this->resort_id);
                if (!empty($hkHodXcomIds)) {
                    Common::notifyEmployees(
                        $this->resort_id,
                        $hkHodXcomIds,
                        'New Housekeeping Request',
                        $this->user->first_name . ' ' . $this->user->last_name . ' has raised a housekeeping request for ' . $employee->Emp_id . '.',
                        'Housekeeping Request',
                        $created[0]->id
                    );
                }
            } catch (\Exception $e) {
                \Log::warning('HousekeepingRequestController::createRequest HOD notify failed: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Housekeeping request(s) created successfully',
                'data'    => $created,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function requestList()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            // HR/GM (hasFullDataAccess) see every request resort-wide, same as
            // before. A HOD/EXCOM calling this same endpoint previously saw
            // that too — this was the "HOD can't tell which requests are
            // theirs" gap: nothing distinguished "everything" from "my
            // department's queue". Scope it the same way every other
            // department-keyed list in this app already does.
            // Housekeeping HOD/XCOM own this queue resort-wide — scoping by
            // the guest employee's department hid every request from them
            // unless it was for a Housekeeping employee.
            $scopedDeptIds = Common::isHousekeepingHodXcom($this->user->GetEmployee)
                ? null
                : Common::getScopedDepartmentIds($this->user->GetEmployee);

            // leftJoin: room-only requests (raised on web, no employee) were
            // silently dropped from this list by the inner join.
            $requests = HousekeepingRequest::leftJoin('employees as t1', 't1.id', '=', 'housekeeping_requests.employee_id')
                ->leftJoin('resort_admins as t2', 't2.id', '=', 't1.Admin_Parent_id')
                ->leftJoin('employees as t3', 't3.id', '=', 'housekeeping_requests.assigned_to_employee_id')
                ->leftJoin('resort_admins as t4', 't4.id', '=', 't3.Admin_Parent_id')
                ->join('housekeeping_service_catalog as hsc', 'hsc.id', '=', 'housekeeping_requests.housekeeping_service_id')
                ->leftJoin('building_models as bm', 'bm.id', '=', 'housekeeping_requests.BuildingName')
                ->where('housekeeping_requests.resort_id', $this->resort_id)
                ->when($scopedDeptIds !== null, function ($query) use ($scopedDeptIds) {
                    return $query->whereIn('t1.Dept_id', $scopedDeptIds);
                })
                ->select(
                    'housekeeping_requests.*',
                    't2.first_name', 't2.last_name',
                    't4.first_name as assigned_to_first_name', 't4.last_name as assigned_to_last_name',
                    'hsc.name as service_name',
                    'bm.BuildingName as building_name'
                )
                ->orderBy('housekeeping_requests.created_at', 'desc')
                ->get()
                ->map(function ($row) {
                    $row->photos_urls = $this->resolveRequestPhotoUrls($row->photos);
                    return $row;
                });

            return response()->json([
                'success' => true,
                'message' => 'Housekeeping requests retrieved successfully',
                'data'    => $requests,
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function requestView($id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $id = base64_decode($id, true);

            $requestRow = HousekeepingRequest::leftJoin('employees as t1', 't1.id', '=', 'housekeeping_requests.employee_id')
                ->leftJoin('resort_admins as t2', 't2.id', '=', 't1.Admin_Parent_id')
                ->leftJoin('employees as t3', 't3.id', '=', 'housekeeping_requests.assigned_to_employee_id')
                ->leftJoin('resort_admins as t4', 't4.id', '=', 't3.Admin_Parent_id')
                ->join('housekeeping_service_catalog as hsc', 'hsc.id', '=', 'housekeeping_requests.housekeeping_service_id')
                ->leftJoin('building_models as bm', 'bm.id', '=', 'housekeeping_requests.BuildingName')
                ->where('housekeeping_requests.resort_id', $this->resort_id)
                ->where('housekeeping_requests.id', $id)
                ->select(
                    'housekeeping_requests.*',
                    't2.first_name', 't2.last_name',
                    't4.first_name as assigned_to_first_name', 't4.last_name as assigned_to_last_name',
                    'hsc.name as service_name',
                    'bm.BuildingName as building_name'
                )
                ->first();

            if (!$requestRow) {
                return response()->json(['success' => false, 'message' => 'Housekeeping request not found'], 404);
            }

            $requestRow->photos_urls = $this->resolveRequestPhotoUrls($requestRow->photos);

            // Every other row sharing the same submission, so the app can
            // show "requested together" context on the detail screen.
            $batchSiblings = HousekeepingRequest::join('housekeeping_service_catalog as hsc', 'hsc.id', '=', 'housekeeping_requests.housekeeping_service_id')
                ->where('housekeeping_requests.resort_id', $this->resort_id)
                ->where('housekeeping_requests.batch_id', $requestRow->batch_id)
                ->where('housekeeping_requests.id', '!=', $id)
                ->select('housekeeping_requests.id', 'housekeeping_requests.status', 'hsc.name as service_name')
                ->get();

            return response()->json([
                'success'        => true,
                'message'        => 'Housekeeping request retrieved successfully',
                'data'           => $requestRow,
                'batch_siblings' => $batchSiblings,
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function updateStatus(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'id'      => 'required|integer',
            'status'  => 'required|in:Pending,Accepted,In-Progress,Completed,Not Completed',
            'remarks' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $housekeepingRequest = HousekeepingRequest::where('resort_id', $this->resort_id)
            ->where('id', $request->id)
            ->first();
        if (!$housekeepingRequest) {
            return response()->json(['success' => false, 'message' => 'Housekeeping request not found'], 404);
        }

        $housekeepingRequest->status = $request->status;
        if ($request->filled('remarks')) {
            $housekeepingRequest->remarks = $request->remarks;
        }
        if ($request->status === 'Completed') {
            $housekeepingRequest->completed_at = now();
        }
        $housekeepingRequest->save();

        // Decision side of the flow: tell whoever raised the request and the
        // employee it is for (the create side already notifies).
        try {
            $actorEmpId = $this->user->GetEmployee->id ?? null;
            Common::notifyEmployees(
                $this->resort_id,
                array_diff([$housekeepingRequest->raised_by, $housekeepingRequest->employee_id], [$actorEmpId]),
                'Housekeeping Request ' . $housekeepingRequest->status,
                'Your housekeeping request is now ' . $housekeepingRequest->status . '.',
                'Housekeeping Request',
                $housekeepingRequest->id
            );
        } catch (\Throwable $e) {
            \Log::warning('HousekeepingRequestController::updateStatus notify failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Housekeeping request status updated successfully',
            'data'    => $housekeepingRequest,
        ]);
    }

    /**
     * HOD/XCOM (or HR/GM) assigns a Housekeeping-department employee to
     * clean this request. Reported as a 404 in prod — the route/method
     * never existed for this table (only the unrelated legacy
     * housekeeping_schedules system has an assign flow).
     */
    public function assign(Request $request, $id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $housekeepingRequest = HousekeepingRequest::where('resort_id', $this->resort_id)
            ->where('id', $id)
            ->first();
        if (!$housekeepingRequest) {
            return response()->json(['success' => false, 'message' => 'Housekeeping request not found'], 404);
        }
        if ($housekeepingRequest->status === 'Completed') {
            return response()->json(['success' => false, 'message' => 'This request is already completed.'], 200);
        }

        $assignee = Employee::where('id', $request->employee_id)->where('resort_id', $this->resort_id)->first();
        if (!Common::isHousekeepingLineWorker($assignee)) {
            return response()->json(['success' => false, 'message' => 'Employee must be an active Housekeeping Line Worker (not HOD/EXCOM).'], 200);
        }

        $isReassignment = $housekeepingRequest->assigned_to_employee_id
            && (int) $housekeepingRequest->assigned_to_employee_id !== (int) $assignee->id;

        $housekeepingRequest->assigned_to_employee_id = $assignee->id;
        // A reassignment after the previous assignee already actioned it
        // needs to go back through Accept — the new assignee hasn't done
        // any of that yet.
        if ($isReassignment && $housekeepingRequest->status !== 'Pending') {
            $housekeepingRequest->status = 'Pending';
        }
        $housekeepingRequest->save();

        try {
            Common::notifyEmployees(
                $this->resort_id,
                [$assignee->id],
                'Housekeeping Task Assigned',
                'You have been assigned a housekeeping task (' . $housekeepingRequest->request_id . ').',
                'Housekeeping Request',
                $housekeepingRequest->id
            );
        } catch (\Throwable $e) {
            \Log::warning('HousekeepingRequestController::assign notify failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Housekeeping task assigned successfully',
            'data'    => $housekeepingRequest,
        ]);
    }

    /**
     * Housekeeping Employee Dashboard — every request assigned to the
     * caller, any status, most recent first.
     */
    public function myAssignedList()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $empId = $this->user->GetEmployee->id ?? null;

        $requests = HousekeepingRequest::leftJoin('employees as t1', 't1.id', '=', 'housekeeping_requests.employee_id')
            ->leftJoin('resort_admins as t2', 't2.id', '=', 't1.Admin_Parent_id')
            ->join('housekeeping_service_catalog as hsc', 'hsc.id', '=', 'housekeeping_requests.housekeeping_service_id')
            ->leftJoin('building_models as bm', 'bm.id', '=', 'housekeeping_requests.BuildingName')
            ->where('housekeeping_requests.resort_id', $this->resort_id)
            ->where('housekeeping_requests.assigned_to_employee_id', $empId)
            ->select(
                'housekeeping_requests.*',
                't2.first_name', 't2.last_name',
                'hsc.name as service_name',
                'bm.BuildingName as building_name'
            )
            ->orderBy('housekeeping_requests.created_at', 'desc')
            ->get()
            ->map(function ($row) {
                $row->photos_urls = $this->resolveRequestPhotoUrls($row->photos);
                return $row;
            });

        return response()->json([
            'success' => true,
            'message' => 'Assigned housekeeping tasks retrieved successfully',
            'data'    => $requests,
        ]);
    }

    /** Request Details screen for the assigned employee's own task. */
    public function myAssignedView($id)
    {
        $requestRow = $this->ownedAssignedRequest($id);
        if (!$requestRow) {
            return response()->json(['success' => false, 'message' => 'Housekeeping request not found'], 404);
        }

        $requestRow->photos_urls = $this->resolveRequestPhotoUrls($requestRow->photos);

        return response()->json([
            'success' => true,
            'message' => 'Housekeeping task retrieved successfully',
            'data'    => $requestRow,
        ]);
    }

    /** Pending -> Accepted. */
    public function accept($id)
    {
        return $this->transitionOwnTask($id, 'Pending', 'Accepted');
    }

    /** Accepted -> In-Progress (cleaning has actually started). */
    public function start($id)
    {
        return $this->transitionOwnTask($id, 'Accepted', 'In-Progress');
    }

    /**
     * In-Progress -> Completed (with 1-5 proof photos) or Not Completed
     * (with a required reason in remarks). Per the Trello card, this is the
     * "employee opens the same assigned task after cleaning" screen.
     */
    public function complete(Request $request, $id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'outcome'    => 'required|in:Completed,Not Completed',
            'photos'     => 'required_if:outcome,Completed|array|max:5',
            'photos.*'   => 'file|mimes:jpeg,png,jpg,heic,heif',
            'remarks'    => 'required_if:outcome,Not Completed|nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $housekeepingRequest = $this->ownedAssignedRequest($id);
        if (!$housekeepingRequest) {
            return response()->json(['success' => false, 'message' => 'Housekeeping request not found'], 404);
        }
        if ($housekeepingRequest->status !== 'In-Progress') {
            return response()->json(['success' => false, 'message' => 'This task must be In-Progress before it can be completed.'], 200);
        }

        if ($request->outcome === 'Completed') {
            $filenames = [];
            $basePath = config('settings.HousekeepingRequestPhotos') . '/' . $this->resort_id;
            foreach ($request->file('photos') as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                \App\Helpers\StorageHelper::put($basePath . '/' . $filename, file_get_contents($file->getRealPath()));
                $filenames[] = $filename;
            }
            $housekeepingRequest->photos = implode(',', $filenames);
            $housekeepingRequest->completed_at = now();
        }
        if ($request->filled('remarks')) {
            $housekeepingRequest->remarks = $request->remarks;
        }
        $housekeepingRequest->status = $request->outcome;
        $housekeepingRequest->save();

        try {
            $actorEmpId = $this->user->GetEmployee->id ?? null;
            $notifyIds = array_diff(
                array_merge([$housekeepingRequest->raised_by, $housekeepingRequest->employee_id], Common::getResortHousekeepingHodXcomEmployeeIds($this->resort_id)),
                [$actorEmpId]
            );
            Common::notifyEmployees(
                $this->resort_id,
                $notifyIds,
                'Housekeeping Task ' . $housekeepingRequest->status,
                'Housekeeping task ' . $housekeepingRequest->request_id . ' is now ' . $housekeepingRequest->status . '.',
                'Housekeeping Request',
                $housekeepingRequest->id
            );
        } catch (\Throwable $e) {
            \Log::warning('HousekeepingRequestController::complete notify failed: ' . $e->getMessage());
        }

        $housekeepingRequest->photos_urls = $this->resolveRequestPhotoUrls($housekeepingRequest->photos);

        return response()->json([
            'success' => true,
            'message' => 'Housekeeping task ' . $housekeepingRequest->status . ' successfully',
            'data'    => $housekeepingRequest,
        ]);
    }

    /** Shared Accept/Start ownership + single-step transition guard. */
    private function transitionOwnTask($id, string $fromStatus, string $toStatus)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $housekeepingRequest = $this->ownedAssignedRequest($id);
        if (!$housekeepingRequest) {
            return response()->json(['success' => false, 'message' => 'Housekeeping request not found'], 404);
        }
        if ($housekeepingRequest->status !== $fromStatus) {
            return response()->json(['success' => false, 'message' => "This task must be {$fromStatus} before it can be moved to {$toStatus}."], 200);
        }

        $housekeepingRequest->status = $toStatus;
        $housekeepingRequest->save();

        try {
            $actorEmpId = $this->user->GetEmployee->id ?? null;
            Common::notifyEmployees(
                $this->resort_id,
                array_diff(
                    array_merge([$housekeepingRequest->raised_by], Common::getResortHousekeepingHodXcomEmployeeIds($this->resort_id)),
                    [$actorEmpId]
                ),
                'Housekeeping Task ' . $toStatus,
                'Housekeeping task ' . $housekeepingRequest->request_id . ' is now ' . $toStatus . '.',
                'Housekeeping Request',
                $housekeepingRequest->id
            );
        } catch (\Throwable $e) {
            \Log::warning('HousekeepingRequestController::transitionOwnTask notify failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Housekeeping task updated successfully',
            'data'    => $housekeepingRequest,
        ]);
    }

    /** Resort- and ownership-scoped lookup shared by the employee actions. */
    private function ownedAssignedRequest($id)
    {
        if (!$this->user) {
            return null;
        }
        $empId = $this->user->GetEmployee->id ?? null;

        return HousekeepingRequest::leftJoin('building_models as bm', 'bm.id', '=', 'housekeeping_requests.BuildingName')
            ->where('housekeeping_requests.resort_id', $this->resort_id)
            ->where('housekeeping_requests.id', $id)
            ->where('housekeeping_requests.assigned_to_employee_id', $empId)
            ->select('housekeeping_requests.*', 'bm.BuildingName as building_name')
            ->first();
    }

    /** Same comma-separated-filename convention as GrievanceSubmissionWitness::Attachement. */
    private function resolveRequestPhotoUrls(?string $commaSeparated): array
    {
        if (empty($commaSeparated)) {
            return [];
        }
        $basePath = config('settings.HousekeepingRequestPhotos') . '/' . $this->resort_id;
        $urls = [];
        foreach (explode(',', $commaSeparated) as $filename) {
            $filename = trim($filename);
            if ($filename === '') {
                continue;
            }
            try {
                $path = $basePath . '/' . $filename;
                if (\App\Helpers\StorageHelper::disk()->exists($path)) {
                    $urls[] = ['filename' => $filename, 'url' => \App\Helpers\StorageHelper::temporaryUrl($path, 30)];
                }
            } catch (\Throwable $e) {
                // skip files that fail to resolve
            }
        }
        return $urls;
    }
}
