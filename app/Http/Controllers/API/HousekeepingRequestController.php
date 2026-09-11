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
            $requests = HousekeepingRequest::join('employees as t1', 't1.id', '=', 'housekeeping_requests.employee_id')
                ->join('resort_admins as t2', 't2.id', '=', 't1.Admin_Parent_id')
                ->join('housekeeping_service_catalog as hsc', 'hsc.id', '=', 'housekeeping_requests.housekeeping_service_id')
                ->where('housekeeping_requests.resort_id', $this->resort_id)
                ->select(
                    'housekeeping_requests.*',
                    't2.first_name', 't2.last_name',
                    'hsc.name as service_name'
                )
                ->orderBy('housekeeping_requests.created_at', 'desc')
                ->get();

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

            $requestRow = HousekeepingRequest::join('employees as t1', 't1.id', '=', 'housekeeping_requests.employee_id')
                ->join('resort_admins as t2', 't2.id', '=', 't1.Admin_Parent_id')
                ->join('housekeeping_service_catalog as hsc', 'hsc.id', '=', 'housekeeping_requests.housekeeping_service_id')
                ->where('housekeeping_requests.resort_id', $this->resort_id)
                ->where('housekeeping_requests.id', $id)
                ->select(
                    'housekeeping_requests.*',
                    't2.first_name', 't2.last_name',
                    'hsc.name as service_name'
                )
                ->first();

            if (!$requestRow) {
                return response()->json(['success' => false, 'message' => 'Housekeeping request not found'], 404);
            }

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
            'status'  => 'required|in:Pending,Approved,Rejected,In-Progress,Completed',
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

        return response()->json([
            'success' => true,
            'message' => 'Housekeeping request status updated successfully',
            'data'    => $housekeepingRequest,
        ]);
    }
}
