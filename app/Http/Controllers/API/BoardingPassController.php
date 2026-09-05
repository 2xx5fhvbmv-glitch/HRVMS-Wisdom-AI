<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveStatus;
use App\Models\EmployeeTravelPass;
use App\Models\EmployeeTravelPassStatus;
use App\Models\ResortTransportation;
use App\Models\ResortPosition;
use App\Models\Manifest;
use App\Models\ManifestEmployee;
use App\Models\ManifestVisitor;
use App\Models\EmployeeTravelPassAssign;
use App\Notifications\AlternativeDateSuggestedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Options;
use Validator;
use Auth;
use File;
use DB;
use Common;
use Config;
use Carbon\Carbon;

class BoardingPassController extends Controller
{
    protected $user;
    protected $resort_id;
    protected $underEmp_id = [];

    public function __construct()
    {
        if (Auth::guard('api')->check()) {
            $this->user = Auth::guard('api')->user();
            $this->resort_id = $this->user->resort_id;
            $this->reporting_to                     =   $this->user->GetEmployee->id;
            $this->underEmp_id                      =   Common::getSubordinates($this->reporting_to);
        }
    }

    public function boardingEmpDashboard()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employeeId                             =   $this->user->GetEmployee->id;

            $baseQuery                              =   EmployeeTravelPass::where('employee_id', $employeeId)
                                                            ->where('resort_id', $this->resort_id)
                                                            ->where('status', 'Approved');

            $EmployeeTravelDepartedCount            =   (clone $baseQuery)
                                                            ->where('employee_departure_status', 'departed')
                                                            ->count();

            $EmployeeTravelArrivedCount             =   (clone $baseQuery)
                                                            ->where('employee_arrival_status', 'arrived')
                                                            ->count();

            $EmployeeCurrentYearDepartedPass       =   (clone $baseQuery)
                                                            ->where('employee_departure_status', 'departed')
                                                            ->whereBetween('departure_date', [
                                                                now()->startOfYear()->toDateString(),
                                                                now()->endOfYear()->toDateString()
                                                            ])
                                                            ->count();

            $EmployeeCurrentYearArrivedPass        = (clone $baseQuery)
                                                        ->where('employee_arrival_status', 'arrived')
                                                        ->whereBetween('arrival_date', [
                                                            now()->startOfYear()->toDateString(),
                                                            now()->endOfYear()->toDateString()
                                                        ])
                                                        ->count();

            $EmployeeTravelPass                     =   EmployeeTravelPass::with([
                                                          'employeeTravelPassStatusData' => function($query) {
                                                                    $query->orderBy('id', 'desc');
                                                                    // $query->where('emergency_cancel_status', '=', 'Cancel');
                                                                },
                                                            'DepartureResortTransportation:id,resort_id,transportation_option',
                                                            'ArrivalResortTransportation:id,resort_id,transportation_option',
                                                            // Sibling of boardingPassView's manifest_confirmation
                                                            // below — this listing endpoint was missing it entirely.
                                                            'manifest:id,transportation_mode,transportation_name,manifest_type,date,time',
                                                            ])
                                                            ->where('employee_id',$employeeId)
                                                            ->where('resort_id', $this->resort_id)
                                                            ->where(function($query) {
                                                                $query->where('status', '!=', 'Cancel')  // Include non-cancelled passes
                                                                    ->orWhereHas('employeeTravelPassStatusData', function($subquery) {
                                                                        $subquery->where('status', '!=', 'Cancel'); // Include if any status is not cancelled
                                                                    })
                                                                    ->orWhereHas('employeeTravelPassStatusData', function($subquery) {
                                                                        $subquery->where('emergency_cancel_status', '=', 'Cancel'); // Include emergency cancelled passes
                                                                    });
                                                            })
                                                            ->orderBy('created_at', 'desc')
                                                            ->get();

            $rankConfig                             =   config('settings.Position_Rank');

            foreach ($EmployeeTravelPass as $pass) {
                foreach ($pass->employeeTravelPassStatusData as $item) {
                    // approver_role (the actual HOD/HR/SM stage) takes
                    // precedence — falls back to the rank-derived label
                    // only for rows created before this column existed.
                    if ($item->approver_role) {
                        $item->rank_type            =   $item->approver_role;
                    } else {
                        $role                       =   ucfirst(strtolower($item->approver_rank ?? ''));
                        $item->rank_type            =   $rankConfig[$role] ?? '';
                    }
                }

                // Parent status only flips once every stage is Approved (or
                // immediately to Rejected), so it stays "Pending" through the
                // whole HOD -> HR -> Security chain. Derive which stage is
                // actually pending right now from the per-stage rows, ordered
                // by submission order (ascending id), not the desc-ordered
                // relation above.
                $pass->current_status_label        =   $pass->status;
                if ($pass->status === 'Pending') {
                    $pendingStage                   =   $pass->employeeTravelPassStatusData
                                                            ->sortBy('id')
                                                            ->first(function ($item) {
                                                                return $item->status === 'Pending';
                                                            });
                    if ($pendingStage) {
                        $pass->current_status_label =   'Pending by ' . ($pendingStage->rank_type ?: $pendingStage->approver_rank);
                    }
                }

                foreach (Common::buildIslandPassApprovalFlow($pass->id, $employeeId) as $key => $value) {
                    $pass->{$key} = $value;
                }

                // Nothing told the mobile client when Modify/Cancel should be
                // hidden — it kept showing both after approval or
                // cancellation. Both actions are only valid while the pass
                // is still fully Pending (matches boardingPassUpdate's
                // hasApproved/Cancel guard and boardingPassCancel's matching
                // guard above).
                $pass->can_modify                  =   ($pass->overall_status === 'Pending');
                $pass->can_cancel                  =   ($pass->overall_status === 'Pending');

                // Once HR/Security have confirmed this pass on a manifest,
                // surface the confirmed time + mode of transportation + boat/
                // vessel name here too — matches boardingPassView's shape.
                $manifest                           =   $pass->manifest;
                $pass->manifest_confirmation        =   $manifest ? [
                    'manifest_id'                   =>  $manifest->id,
                    'manifest_type'                 =>  $manifest->manifest_type,
                    'confirmed_departure_time'      =>  $manifest->manifest_type === 'departure' ? $pass->departure_time : null,
                    'confirmed_arrival_time'        =>  $manifest->manifest_type === 'arrival' ? $pass->arrival_time : null,
                    'transportation_mode'           =>  $manifest->transportation_mode,
                    'vessel_name'                   =>  $manifest->transportation_name,
                ] : null;
            }
            $dahsboardArr                           =   [
                'departed_count'                    =>  $EmployeeTravelDepartedCount,
                'arrived_count'                     =>  $EmployeeTravelArrivedCount,
                'current_year_departed_count'       =>  $EmployeeCurrentYearDepartedPass,
                'current_year_arrived_count'        =>  $EmployeeCurrentYearArrivedPass,
                'emp_req_data'                      =>  $EmployeeTravelPass,
            ];

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Employee boarding pass dashboard data fetched Successfully',
                'emp_boarding_data'                 =>  $dahsboardArr
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }

    }

    public function boardingPassAdd(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'dept_date'                             => 'nullable|date_format:Y-m-d',
            'dept_time'                             => 'nullable',
            'dept_transportation'                   => 'nullable|string',
            'dept_reason'                           => 'nullable|string|max:255',
            'arrival_date'                          => 'nullable|date_format:Y-m-d',
            'arrival_time'                          => 'nullable',
            'arrival_transportation'                => 'nullable|string',
            'arrival_reason'                        => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        $data                                       = $validator->validated();

        $departureValid                             = isset($data['dept_date'],      $data['dept_transportation']);
        $arrivalValid                               = isset($data['arrival_date'],   $data['arrival_transportation']);
        // $departureValid                          = isset($data['dept_date'],     $data['dept_time'],     $data['dept_transportation']);
        // $arrivalValid                            = isset($data['arrival_date'],  $data['arrival_time'],  $data['arrival_transportation']);

        if (!$departureValid && !$arrivalValid) {
            return response()->json([ 'status' => false,'message'  => 'Please provide either departure or arrival details with transportation.'], 422);
        }

        DB::beginTransaction();

        try {
            $user                                   =   Auth::guard('api')->user();
            $employee                               =   $user->GetEmployee;
            $arrivalDate                            =   $data['arrival_date'] ?? null;
            $arrivalMode                            =   $data['arrival_transportation'] ?? null;
            $departureDate                          =   !empty($data['dept_date']) ? Carbon::createFromFormat('Y-m-d', $data['dept_date'])->format('Y-m-d') : null;
            $departureMode                          =   $data['dept_transportation'] ?? null;

            // Check for duplicate arrival or departure entry. Was matching
            // against every past pass regardless of status, so a Rejected
            // or Cancel(led) pass on some unrelated date could still block
            // a brand-new request — only Pending/Approved passes represent
            // a genuine standing conflict.
            $existingPass                           =   EmployeeTravelPass::where('employee_id', $employee->id)
                                                            ->whereIn('status', ['Pending', 'Approved'])
                                                            ->where(function ($q) use ($arrivalDate, $arrivalMode, $departureDate, $departureMode) {
                                                                $q->where(function ($q1) use ($arrivalDate, $arrivalMode) {
                                                                    $q1->whereDate('arrival_date', $arrivalDate)
                                                                    ->where('arrival_mode', $arrivalMode);
                                                                })
                                                                ->orWhere(function ($q2) use ($departureDate, $departureMode) {
                                                                    $q2->whereDate('departure_date', $departureDate)
                                                                    ->where('departure_mode', $departureMode);
                                                                });
                                                            })
                                                            ->first();
            if ($existingPass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Boarding pass already exists.',
                ], 200);
            } else {
                // Create the Boarding Pass (Entry or Exit)
                $boardingPass                           =   EmployeeTravelPass::create([
                    'resort_id'                         =>  $user->resort_id,
                    'employee_id'                       =>  $employee->id,
                    'leave_request_id'                  =>  null,
                    'arrival_date'                      =>  $data['arrival_date'] ??  null,
                    'arrival_time'                      =>  $data['arrival_time'] ?? null,
                    'arrival_mode'                      =>  $data['arrival_transportation'] ?? null,
                    'arrival_reason'                    =>  $data['arrival_reason'] ?? null,
                    'departure_date'                    =>  $data['dept_date'] ??  null,
                    'departure_time'                    =>  $data['dept_time'] ?? null,
                    'departure_mode'                    =>  $data['dept_transportation'] ?? null,
                    'departure_reason'                  =>  $data['dept_reason'] ?? null,
                    'status'                            =>  'Pending',
                ]);

                // Determine approval flow for both Entry and Exit passes
                $passApprovalFlow                       =   collect();

                // Add Security Manager (SM) to the approval flow (rank 4)
                $securityManagerTitles                  =   ['Security Manager', 'SM'];

                // Get position IDs that match the titles in the current resort
                $positionIds                            =   ResortPosition::where('resort_id', $this->resort_id)
                                                                ->whereIn('position_title', $securityManagerTitles)
                                                                ->pluck('id'); // Get the position IDs

                // Get employees who hold these positions in the current resort
                $SMApprover                             =   Employee::with(['resortAdmin','position'])->whereIn('Position_id', $positionIds)
                                                                ->where('resort_id', $this->resort_id)->where('status', 'Active')
                                                                ->select('id', 'rank')
                                                                ->orderBy('id')
                                                                ->first();

                if ($SMApprover) {
                    // Tag the FUNCTIONAL stage this approver fills — not
                    // their personal rank. An HR head or Security Manager
                    // can personally hold rank=2 ("HOD") same as a real
                    // department HOD, which made every rank_type lookup
                    // derived from approver_rank mislabel their rows "HOD"
                    // too (see approver_role column comment).
                    $SMApprover->approver_role          =   'SM';
                    $passApprovalFlow->push($SMApprover); // Fourth approver: Security Officer
                }

                // Add HR to the approval flow. Raw rank=3 excluded any
                // resort whose real HR employee isn't literally rank 3
                // (e.g. an HR-department employee ranked HOD/EXCOM),
                // silently dropping HR from the whole approval chain.
                $hrApprover                             =   Employee::select('id', 'rank')->whereIn('id', Common::getResortHrEmployeeIds($this->resort_id))->where('status', 'Active')->orderBy('id')->first();
                if ($hrApprover) {
                    $hrApprover->approver_role           =   'HR';
                    $passApprovalFlow->push($hrApprover); // Third approver: HR
                }

                // Add department head to the approval flow — HOD (rank 2),
                // falls back to EXCOM (rank 1) via FindResortHODDepartment()
                // for departments with no rank-2 employee. That helper also
                // already excludes inactive placeholder rows (onboarding
                // records can carry a valid rank/dept before onboarding
                // completes — confirmed against real data where a
                // department had 3 rank=2 employees, only one Active).
                $hodApprover                             =   Common::FindResortHODDepartment($this->resort_id, $employee->Dept_id);

                // FindResortHODDepartment() has no self-exclusion — an HOD
                // (or EXCOM, via its rank-1 fallback) requesting their own
                // Island Pass got themselves back as "the department HOD"
                // and could approve their own request. Route this stage to
                // their reporting manager instead when that happens.
                if ($hodApprover && (int) $hodApprover->id === (int) $employee->id) {
                    $hodApprover                         =   $employee->reporting_to
                                                                ? Employee::select('id', 'rank')
                                                                    ->where('id', $employee->reporting_to)
                                                                    ->where('resort_id', $this->resort_id)
                                                                    ->where('status', 'Active')
                                                                    ->first()
                                                                : null;
                }

                if ($hodApprover ) {
                    $hodApprover->approver_role          =   'HOD';
                    $passApprovalFlow->push($hodApprover); // Second approver: HOD
                }

                // Add the same approval flow for Exit Pass as well
                $passApprovalFlow->each(function($approver) use ($boardingPass) {
                    EmployeeTravelPassStatus::create([
                        'travel_pass_id'                =>  $boardingPass->id,
                        'approver_id'                   =>  $approver->id,
                        'approver_rank'                 =>  $approver->rank,
                        'approver_role'                 =>  $approver->approver_role,
                        'status'                        =>  'Pending',
                    ]);
                });

                // Was commented out — approvers (SM/HR/HOD) never got a
                // mobile push when a new boarding pass request was
                // submitted, so nobody knew to open the app and act on
                // it. Since the web list at /leaves/boarding-pass-requests
                // only shows a pass once the HOD's approval row is
                // already Approved, a silent HOD (never notified, never
                // opened the app) meant the request could never progress
                // far enough to appear on the web page either. Shared with
                // LeaveController::leaveAdd(), which builds the identical
                // $passApprovalFlow for a pass created alongside a leave
                // request but never notified anyone.
                Common::notifyBoardingPassApprovalFlow(
                    $this->resort_id,
                    $passApprovalFlow,
                    $boardingPass,
                    $employee,
                    $this->user->first_name . ' ' . $this->user->last_name
                );

                DB::commit();

                $response['status']                     =   true;
                $response['message']                    =   'Pass submitted successfully';
                // The submit response returned nothing about the pass
                // itself — mobile had no transportation/mode data to show
                // immediately after submitting without a second fetch.
                $response['data']                       =   $boardingPass->fresh()->load([
                                                                    'DepartureResortTransportation:id,resort_id,transportation_option',
                                                                    'ArrivalResortTransportation:id,resort_id,transportation_option',
                                                                ]);

                return response()->json($response);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);

        } catch (\Exception $e) {
            \Log::error("Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    public function bordingPassApprovedList()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employeeId                             =   $this->user->GetEmployee->id;

            $EmployeeTravelApprovePass                 =   EmployeeTravelPass::with([
                                                               'employeeTravelPassStatusData' => function($query) {
                                                                    $query->orderBy('id', 'desc');
                                                                },
                                                                'DepartureResortTransportation:id,resort_id,transportation_option',
                                                                'ArrivalResortTransportation:id,resort_id,transportation_option'
                                                            ])
                                                            ->where('employee_id',$employeeId)
                                                            ->where('resort_id', $this->resort_id)
                                                            ->orderBy('created_at', 'desc')
                                                            ->get();

            $rankConfig                             =   config('settings.Position_Rank');

            foreach ($EmployeeTravelApprovePass as $pass) {
                foreach ($pass->employeeTravelPassStatusData as $item) {
                    if ($item->approver_role) {
                        $item->rank_type            =   $item->approver_role;
                    } else {
                        $role                       =   ucfirst(strtolower($item->approver_rank ?? ''));
                        $item->rank_type            =   $rankConfig[$role] ?? '';
                    }
                }
            }
            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Employee Approved boarding pass data fetched Successfully',
                'emp_boarding_approved_list'        =>  $EmployeeTravelApprovePass
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }

    }

    public function boardingHODDashboard()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $resortId                               =   $this->resort_id;
            $today                                  =   Carbon::today()->toDateString();
            $startOfMonth                           =   Carbon::now()->startOfMonth(); // Get the first day of the month
            $year                                   =   now()->year; // or Carbon::now()->year
            $currentRank                            =   $this->user->GetEmployee->rank;

            // underEmp_id is the logged-in user's OWN org-chart subordinates
            // (Common::getSubordinates($this->user->GetEmployee->id)) — right
            // for a plain department HOD approving their own team, but wrong
            // for a cross-department functional approver (HR resort-wide, or
            // Security Manager as the final approval stage): the requesting
            // employee belongs to some OTHER department, not the approver's
            // own reporting tree, so this silently zeroed out every pending
            // request for those roles. etps.approver_id already precisely
            // scopes to exactly this approver's rows — that's the real
            // authorization signal (matches boardingSecurityManagerDashboard(),
            // which never had this extra restriction).
            $EmployeeTravelPass                     =  EmployeeTravelPass::join('employee_travel_pass_status as etps', 'etps.travel_pass_id', '=', 'employee_travel_passes.id')
                                                            ->where('employee_travel_passes.resort_id', $this->resort_id)
                                                            ->where('etps.approver_id', $this->user->GetEmployee->id)
                                                            ->where('etps.approver_rank', $currentRank)
                                                            ->selectRaw("
                                                                SUM(CASE WHEN etps.status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                                                                SUM(CASE WHEN etps.status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
                                                                SUM(CASE WHEN etps.status = 'Rejected' THEN 1 ELSE 0 END) as rejected_count
                                                            ")
                                                            ->first();

            $emergencyCancelCount                   =   EmployeeTravelPass::join('employee_travel_pass_status as etps', 'etps.travel_pass_id', '=', 'employee_travel_passes.id')
                                                            ->where('employee_travel_passes.resort_id', $this->resort_id)
                                                            ->where('etps.approver_id', $this->user->GetEmployee->id)
                                                            ->where('etps.emergency_cancel_status', 'Cancel')
                                                            ->where('employee_travel_passes.status', 'Cancel')
                                                            ->count();

            $EmployeeTravelDepartedCount            =   EmployeeTravelPass::whereIn('employee_id', $this->underEmp_id)
                                                            ->where('resort_id', $this->resort_id)
                                                            ->where('employee_departure_status', 'departed')
                                                            ->whereNull('employee_arrival_status')
                                                            ->where('status', 'Approved')
                                                            ->count();

            $EmployeeTravelArrivedCount            =   EmployeeTravelPass::whereIn('employee_id', $this->underEmp_id)
                                                            ->where('resort_id', $this->resort_id)
                                                            ->where('employee_departure_status', 'departed')
                                                            ->whereNull('employee_arrival_status') // Still not arrived
                                                            ->whereDate('arrival_date', '>=', Carbon::today())
                                                            ->where('status', 'Approved')
                                                            ->count();

            $totalExitEntryCurrentYear              =   DB::table('employee_travel_passes')
                                                            ->join('employees as e','e.id','=','employee_travel_passes.employee_id')
                                                            ->join('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
                                                            ->join('resort_positions as rp','rp.id','=','e.Position_id')
                                                            ->join('resort_departments as rd','rd.id','=','e.Dept_id')
                                                            ->select(
                                                                'employee_id',
                                                                'e.Admin_Parent_id',
                                                                'ra.first_name', 'ra.last_name', 'ra.profile_picture','rp.position_title as position', 'rd.name as department',
                                                                DB::raw("SUM(CASE WHEN YEAR(departure_date) = $year AND employee_departure_status = 'departed' THEN 1 ELSE 0 END) as departures"),
                                                                DB::raw("SUM(CASE WHEN YEAR(arrival_date) = $year AND employee_arrival_status = 'arrived' THEN 1 ELSE 0 END) as arrivals")
                                                            )
                                                            ->whereIn('employee_travel_passes.employee_id', $this->underEmp_id)
                                                            ->where('employee_travel_passes.resort_id', $this->resort_id)
                                                            ->where('employee_travel_passes.status', 'Approved')
                                                            ->groupBy('employee_travel_passes.employee_id', 'e.Admin_Parent_id')
                                                            ->get()
                                                            ->map(function($row) {
                                                                $row->profile_picture = Common::getResortUserPicture($row->Admin_Parent_id);
                                                                $row->current_year = Carbon::now()->year;
                                                                return $row;
                                                            });

            // Same underEmp_id problem as the count query above — replaced
            // with the actual authorization signal (approver_id, not org
            // hierarchy) so a cross-department approver (HR/SM) sees every
            // request actually routed to them, not just ones from their own
            // reporting tree.
            $EmployeeTravelPassReq                  =   EmployeeTravelPass::with([
                                                            'employeeTravelPassStatusData' => function($query) {
                                                                    $query->orderBy('id', 'desc');
                                                                },
                                                            'employee:id,Admin_Parent_id',
                                                            'employee.resortAdmin:id,first_name,last_name,profile_picture',
                                                            // Was never loaded here (other methods in this file do
                                                            // load it) — the card had no way to show the selected
                                                            // transportation, and arrival_mode/departure_mode went
                                                            // out as raw numeric ids with no name attached.
                                                            'DepartureResortTransportation:id,resort_id,transportation_option',
                                                            'ArrivalResortTransportation:id,resort_id,transportation_option',
                                                            ])
                                                            ->where('resort_id', $this->resort_id)
                                                            ->whereHas('employeeTravelPassStatusData', function($q) use ($currentRank) {
                                                                    $q->where('approver_id', $this->user->GetEmployee->id)
                                                                    ->where('approver_rank', $currentRank)
                                                                    ->where('status', 'Pending');
                                                                })
                                                            ->where('status', 'Pending')
                                                            ->orderBy('created_at', 'desc')
                                                            ->get();

            $rankConfig                             =   config('settings.Position_Rank');

            foreach ($EmployeeTravelPassReq as $pass) {
                foreach ($pass->employeeTravelPassStatusData as $item) {
                    if ($item->approver_role) {
                        $item->rank_type            =   $item->approver_role;
                    } else {
                        $role                       =   ucfirst(strtolower($item->approver_rank ?? ''));
                        $item->rank_type            =   $rankConfig[$role] ?? '';
                    }
                }
                // 'transportation' column on the pass itself is never set
                // (boardingPassAdd only stores arrival_mode/departure_mode
                // ids) — resolve the actual selected transportation name
                // from whichever leg is applicable so the card has
                // something real to show instead of null.
                $pass->transportation              =   $pass->DepartureResortTransportation->transportation_option
                                                        ?? $pass->ArrivalResortTransportation->transportation_option
                                                        ?? null;

                foreach (Common::buildIslandPassApprovalFlow($pass->id, $this->user->GetEmployee->id) as $key => $value) {
                    $pass->{$key} = $value;
                }
            }

            // Replace profile_picture with url
            $EmployeeTravelPassReq->each(function ($pass) {
                $resortAdmin                        = $pass->employee->resortAdmin ?? null;
                if ($resortAdmin) {
                    $resortAdmin->profile_picture   = Common::getResortUserPicture($resortAdmin->id);
                }
            });

            $dahsboardArr                           =   [
                'pending_count'                     =>  $EmployeeTravelPass->pending_count,
                'approved_count'                    =>  $EmployeeTravelPass->approved_count,
                'rejected_count'                    =>  $EmployeeTravelPass->rejected_count,
                'emergency_cancel_count'            =>   (string)$emergencyCancelCount,
                'employees_outside'                 =>  $EmployeeTravelDepartedCount,
                'scheduled_arrivals'                =>  $EmployeeTravelArrivedCount,
                'total_exit_entry_current_year'     =>  $totalExitEntryCurrentYear,
                'pass_request'                      =>  $EmployeeTravelPassReq,
            ];

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Employee boarding pass dashboard data fetched Successfully',
                'emp_boarding_data'                 =>  $dahsboardArr
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * HOD-requested filterable list (all/pending/approved/rejected) — none
     * of the existing endpoints covered this: boardingHODDashboard's
     * pass_request is hardcoded to the pending queue only, and there was no
     * HOD-facing history view at all, so a cancelled pass had nowhere to
     * show up once it left the pending queue.
     */
    public function boardingHODListByStatus(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $status = strtolower($request->input('status', 'all'));
        $statusMap = [
            'pending'   => 'Pending',
            'approved'  => 'Approved',
            'rejected'  => 'Rejected',
            'cancelled' => 'Cancel',
            'canceled'  => 'Cancel',
        ];
        if ($status !== 'all' && !isset($statusMap[$status])) {
            return response()->json(['success' => false, 'message' => 'Invalid status filter. Use all, pending, approved, rejected, or cancelled.'], 400);
        }

        try {
            $employeeId = $this->user->GetEmployee->id;
            $currentRank = $this->user->GetEmployee->rank;

            // Was whereIn('employee_id', $this->underEmp_id) — the caller's
            // own org-chart subordinates. Correct for a plain department
            // HOD, but a cross-department approver (HR, or the Security
            // Manager's final stage) never has the requesting employee in
            // their own reporting tree, so this silently returned zero rows
            // for those roles (reported: SM's queue always empty despite
            // real pending approvals). Same fix boardingHODDashboard()
            // already applies a few methods up — approver_id/approver_rank
            // on employee_travel_pass_status is the real authorization
            // signal, not the org hierarchy.
            $query = EmployeeTravelPass::with([
                    'employeeTravelPassStatusData' => function ($q) {
                        $q->orderBy('id', 'desc');
                    },
                    'employee:id,Admin_Parent_id',
                    'employee.resortAdmin:id,first_name,last_name,profile_picture',
                    'DepartureResortTransportation:id,resort_id,transportation_option',
                    'ArrivalResortTransportation:id,resort_id,transportation_option',
                ])
                ->where('resort_id', $this->resort_id)
                ->whereHas('employeeTravelPassStatusData', function ($q) use ($employeeId, $currentRank) {
                    $q->where('approver_id', $employeeId)->where('approver_rank', $currentRank);
                });

            if ($status !== 'all') {
                $query->where('status', $statusMap[$status]);
            }

            $passes = $query->orderBy('created_at', 'desc')->get();

            $rankConfig = config('settings.Position_Rank');
            foreach ($passes as $pass) {
                foreach ($pass->employeeTravelPassStatusData as $item) {
                    if ($item->approver_role) {
                        $item->rank_type = $item->approver_role;
                    } else {
                        $role = ucfirst(strtolower($item->approver_rank ?? ''));
                        $item->rank_type = $rankConfig[$role] ?? '';
                    }
                }
                $pass->transportation = $pass->DepartureResortTransportation->transportation_option
                    ?? $pass->ArrivalResortTransportation->transportation_option
                    ?? null;

                foreach (Common::buildIslandPassApprovalFlow($pass->id, $employeeId) as $key => $value) {
                    $pass->{$key} = $value;
                }

                $resortAdmin = $pass->employee->resortAdmin ?? null;
                if ($resortAdmin) {
                    $resortAdmin->profile_picture = Common::getResortUserPicture($resortAdmin->id);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'HOD Island Pass list fetched successfully',
                'status_filter' => $status,
                'data' => $passes,
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function boardingHRDashboard()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $resortId                               =   $this->resort_id;
            $today                                  =   Carbon::today()->toDateString();
            $startOfMonth                           =   Carbon::now()->startOfMonth(); // Get the first day of the month
            $year                                   =   now()->year; // or Carbon::now()->year
            $currentRank                            =   $this->user->GetEmployee->rank;

            $EmployeeTravelPass                     =   EmployeeTravelPass::join('employee_travel_pass_status as etps', 'etps.travel_pass_id', '=', 'employee_travel_passes.id')
                                                            ->where('employee_travel_passes.resort_id', $this->resort_id)
                                                            ->where('etps.approver_id', $this->user->GetEmployee->id)
                                                            ->where('etps.approver_rank', $currentRank)
                                                            ->selectRaw("
                                                                SUM(CASE WHEN etps.status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                                                                SUM(CASE WHEN etps.status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
                                                                SUM(CASE WHEN etps.status = 'Rejected' THEN 1 ELSE 0 END) as rejected_count
                                                            ")
                                                            ->first();

            $emergencyCancelCount                   =   EmployeeTravelPass::join('employee_travel_pass_status as etps', 'etps.travel_pass_id', '=', 'employee_travel_passes.id')
                                                            ->where('employee_travel_passes.resort_id', $this->resort_id)
                                                            ->where('etps.approver_id', $this->user->GetEmployee->id)
                                                            ->where('etps.emergency_cancel_status', 'Cancel')
                                                            ->where('employee_travel_passes.status', 'Cancel')
                                                            ->count();

            $EmployeeTravelDepartedCount            =   EmployeeTravelPass::where('resort_id', $this->resort_id)
                                                            ->where('employee_departure_status', 'departed')
                                                            ->whereNull('employee_arrival_status')
                                                            ->where('status', 'Approved')
                                                            ->count();

            $EmployeeTravelArrivedCount            =   EmployeeTravelPass::where('resort_id', $this->resort_id)
                                                            ->where('employee_departure_status', 'departed')
                                                            ->whereNull('employee_arrival_status') // Still not arrived
                                                            ->whereDate('arrival_date', '>=', Carbon::today())
                                                            ->where('status', 'Approved')
                                                            ->count();

            $year                                   =   now()->year; // or Carbon::now()->year
            $totalExitEntryCurrentYear              =   DB::table('employee_travel_passes')
                                                            ->join('employees as e','e.id','=','employee_travel_passes.employee_id')
                                                            ->join('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
                                                            ->join('resort_positions as rp','rp.id','=','e.Position_id')
                                                            ->join('resort_departments as rd','rd.id','=','e.Dept_id')
                                                            ->select(
                                                                'employee_id','e.Admin_Parent_id','ra.first_name', 'ra.last_name', 'ra.profile_picture','rp.position_title as position', 'rd.name as department',
                                                                DB::raw("SUM(CASE WHEN YEAR(departure_date) = $year AND employee_departure_status = 'departed' THEN 1 ELSE 0 END) as departures"),
                                                                DB::raw("SUM(CASE WHEN YEAR(arrival_date) = $year AND employee_arrival_status = 'arrived' THEN 1 ELSE 0 END) as arrivals")
                                                            )
                                                            ->where('employee_travel_passes.resort_id', $this->resort_id)
                                                            ->where('employee_travel_passes.status', 'Approved')
                                                            ->groupBy('employee_travel_passes.employee_id', 'e.Admin_Parent_id')
                                                            ->get()
                                                            ->map(function($row) {
                                                                $row->profile_picture = Common::getResortUserPicture($row->Admin_Parent_id);
                                                                $row->current_year = Carbon::now()->year;
                                                                return $row;
                                                            })->filter(function ($row) {
                                                                return $row->departures > 0 || $row->arrivals > 0;
                                                            })
                                                            ->values();

            $EmployeeTravelPassReq                  =   EmployeeTravelPass::with([
                                                            'employeeTravelPassStatusData' => function($query) {
                                                                    $query->orderBy('id', 'desc');
                                                                },
                                                            'employee:id,Admin_Parent_id',
                                                            'employee.resortAdmin:id,first_name,last_name,profile_picture',
                                                            // Was never loaded here (other methods in this file do
                                                            // load it) — the card had no way to show the selected
                                                            // transportation, and arrival_mode/departure_mode went
                                                            // out as raw numeric ids with no name attached.
                                                            'DepartureResortTransportation:id,resort_id,transportation_option',
                                                            'ArrivalResortTransportation:id,resort_id,transportation_option',
                                                            ])
                                                            ->where('status', 'Pending')
                                                            ->whereHas('employeeTravelPassStatusData', function($q) use ($currentRank) {
                                                                    $q->where('approver_rank', $currentRank)
                                                                    ->where('status', 'Pending');
                                                                })
                                                            ->where('resort_id', $this->resort_id)
                                                            ->orderBy('created_at', 'desc')
                                                            ->get();

            $rankConfig                             =   config('settings.Position_Rank');

            foreach ($EmployeeTravelPassReq as $pass) {
                foreach ($pass->employeeTravelPassStatusData as $item) {
                    if ($item->approver_role) {
                        $item->rank_type            =   $item->approver_role;
                    } else {
                        $role                       =   ucfirst(strtolower($item->approver_rank ?? ''));
                        $item->rank_type            =   $rankConfig[$role] ?? '';
                    }
                }
                // 'transportation' column on the pass itself is never set
                // (boardingPassAdd only stores arrival_mode/departure_mode
                // ids) — resolve the actual selected transportation name
                // from whichever leg is applicable so the card has
                // something real to show instead of null.
                $pass->transportation              =   $pass->DepartureResortTransportation->transportation_option
                                                        ?? $pass->ArrivalResortTransportation->transportation_option
                                                        ?? null;

                foreach (Common::buildIslandPassApprovalFlow($pass->id, $this->user->GetEmployee->id) as $key => $value) {
                    $pass->{$key} = $value;
                }
            }

            // Replace profile_picture with url
            $EmployeeTravelPassReq->each(function ($pass) {
                $resortAdmin = $pass->employee->resortAdmin ?? null;
                if ($resortAdmin) {
                    $resortAdmin->profile_picture = Common::getResortUserPicture($resortAdmin->id);
                }
            });

            $dahsboardArr                           =   [
                'pending_count'                     =>  $EmployeeTravelPass->pending_count,
                'approved_count'                    =>  $EmployeeTravelPass->approved_count,
                'rejected_count'                    =>  $EmployeeTravelPass->rejected_count,
                'emergency_cancel_count'            =>  (string)$emergencyCancelCount,
                'employees_outside'                 =>  $EmployeeTravelDepartedCount,
                'scheduled_arrivals'                =>  $EmployeeTravelArrivedCount,
                'total_exit_entry_current_year'     =>  $totalExitEntryCurrentYear,
                'pass_request'                      =>  $EmployeeTravelPassReq,
            ];

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Employee boarding pass dashboard data fetched Successfully',
                'emp_boarding_data'                 =>  $dahsboardArr
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function boardingSecurityManagerDashboard()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $empEntryExitCount                      =   EmployeeTravelPass::where('status', 'Approved')->where('resort_id', $this->resort_id)->count();
            $year                                   =   now()->year; // or Carbon::now()->year
            $today                                  =   Carbon::today()->toDateString();
            $currentRank                            =   $this->user->GetEmployee->rank;

            $empLeavingArriveCount                  =   EmployeeTravelPass::where('status', 'Approved')
                                                                ->where('employee_departure_status', 'departed')
                                                                ->where('employee_arrival_status', null)
                                                                ->where(function ($q) use ($today) {
                                                                    $q->whereDate('arrival_date', $today)
                                                                      ->orWhereDate('departure_date', $today);
                                                                })->count();

            $emergencyCancelCount                   =   EmployeeTravelPass::join('employee_travel_pass_status as etps', 'etps.travel_pass_id', '=', 'employee_travel_passes.id')
                                                            ->where('employee_travel_passes.resort_id', $this->resort_id)
                                                            ->where('etps.approver_id', $this->user->GetEmployee->id)
                                                            ->where('etps.emergency_cancel_status', 'Cancel')
                                                            ->where('employee_travel_passes.status', 'Cancel')
                                                            ->count();


            $totalExitEntryCurrentYear              =   DB::table('employee_travel_passes')
                                                            ->join('employees as e','e.id','=','employee_travel_passes.employee_id')
                                                            ->join('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
                                                            ->join('resort_positions as rp','rp.id','=','e.Position_id')
                                                            ->join('resort_departments as rd','rd.id','=','e.Dept_id')
                                                            ->select(
                                                                'employee_id','e.Admin_Parent_id','ra.first_name', 'ra.last_name', 'ra.profile_picture','rp.position_title as position', 'rd.name as department',
                                                                DB::raw("SUM(CASE WHEN YEAR(departure_date) = $year AND employee_departure_status = 'departed' THEN 1 ELSE 0 END) as departures"),
                                                                DB::raw("SUM(CASE WHEN YEAR(arrival_date) = $year AND employee_arrival_status = 'arrived' THEN 1 ELSE 0 END) as arrivals")
                                                            )
                                                            ->where('employee_travel_passes.resort_id', $this->resort_id)
                                                            ->where('employee_travel_passes.status', 'Approved')
                                                            ->groupBy('employee_travel_passes.employee_id', 'e.Admin_Parent_id')
                                                            ->get()
                                                            ->map(function($row) {
                                                                $row->profile_picture = Common::getResortUserPicture($row->Admin_Parent_id);
                                                                $row->current_year = Carbon::now()->year;
                                                                return $row;
                                                            })->filter(function ($row) {
                                                                return $row->departures > 0 || $row->arrivals > 0;
                                                            })
                                                            ->values();
            $EmployeeTravelPassReq                  =   EmployeeTravelPass::with([
                                                            'employeeTravelPassStatusData' => function($query) {
                                                                    $query->orderBy('id', 'desc');
                                                                },
                                                            'employee:id,Admin_Parent_id',
                                                            'employee.resortAdmin:id,first_name,last_name,profile_picture',
                                                            // Was never loaded here (other methods in this file do
                                                            // load it) — the card had no way to show the selected
                                                            // transportation, and arrival_mode/departure_mode went
                                                            // out as raw numeric ids with no name attached.
                                                            'DepartureResortTransportation:id,resort_id,transportation_option',
                                                            'ArrivalResortTransportation:id,resort_id,transportation_option',
                                                            ])
                                                            ->where('status', 'Pending')
                                                            ->where('resort_id', $this->resort_id)
                                                            ->whereHas('employeeTravelPassStatusData', function($q) use ($currentRank) {
                                                                    $q->where('approver_rank', $currentRank)
                                                                    ->where('status', 'Pending');
                                                                })
                                                            ->orderBy('created_at', 'desc')
                                                            ->get();

            $rankConfig                             =   config('settings.Position_Rank');

            foreach ($EmployeeTravelPassReq as $pass) {
                foreach ($pass->employeeTravelPassStatusData as $item) {
                    if ($item->approver_role) {
                        $item->rank_type            =   $item->approver_role;
                    } else {
                        $role                       =   ucfirst(strtolower($item->approver_rank ?? ''));
                        $item->rank_type            =   $rankConfig[$role] ?? '';
                    }
                }
                // 'transportation' column on the pass itself is never set
                // (boardingPassAdd only stores arrival_mode/departure_mode
                // ids) — resolve the actual selected transportation name
                // from whichever leg is applicable so the card has
                // something real to show instead of null.
                $pass->transportation              =   $pass->DepartureResortTransportation->transportation_option
                                                        ?? $pass->ArrivalResortTransportation->transportation_option
                                                        ?? null;

                foreach (Common::buildIslandPassApprovalFlow($pass->id, $this->user->GetEmployee->id) as $key => $value) {
                    $pass->{$key} = $value;
                }
            }

            // Replace profile_picture with url
            $EmployeeTravelPassReq->each(function ($pass) {
                $resortAdmin = $pass->employee->resortAdmin ?? null;
                if ($resortAdmin) {
                    $resortAdmin->profile_picture = Common::getResortUserPicture($resortAdmin->id);
                }
            });

            $dahsboardArr                           =   [
                'total_entry_exit_pass'             =>  $empEntryExitCount,
                'assigned'                          =>  '',
                'pending_assignment'                =>  '',
                'employees_scheduled_leave_arrive'  =>  $empLeavingArriveCount,
                'total_exit_entry_current_year'     =>  $totalExitEntryCurrentYear,
                'emergency_cancel_count'            =>  (string)$emergencyCancelCount,
                'pass_request'                      =>  $EmployeeTravelPassReq,
            ];

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Employee boarding pass dashboard data fetched Successfully',
                'emp_boarding_data'                 =>  $dahsboardArr
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * GET /api/boarding/boarding-pass-view/{pass_id}
     * Returns single boarding pass details. Only applicant or approver can view.
     * pass_id: numeric (e.g. 480) or base64-encoded id.
     */
    public function boardingPassView($passId)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $employee = $this->user->GetEmployee ?? null;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee record not found for this user.'], 403);
        }

        try {
            if (is_numeric($passId) || ctype_digit((string) $passId)) {
                $passId = (int) $passId;
            } else {
                $decoded = base64_decode($passId, true);
                $passId = ($decoded !== false && is_numeric($decoded)) ? (int) $decoded : (int) $passId;
            }
            if ($passId <= 0) {
                return response()->json(['success' => false, 'message' => 'Invalid pass id.'], 400);
            }

            $EmployeeTravelPassView = EmployeeTravelPass::with([
                'employeeTravelPassStatusData',
                'employee:id,Admin_Parent_id,Position_id',
                'employee.resortAdmin:id,first_name,last_name,profile_picture',
                'employee.position:id,position_title',
                'DepartureResortTransportation:id,resort_id,transportation_option',
                'ArrivalResortTransportation:id,resort_id,transportation_option',
                // Assigned via manifestStore() — arrival_time/departure_time
                // get copied onto this row directly, but transportation_mode/
                // transportation_name only ever lived on the manifest row
                // itself, linked via manifest_id. This view never loaded that
                // relation, so a confirmed manifest's boat/vessel details
                // never reached the employee's own pass detail screen even
                // though the notification (and the time) went out correctly.
                'manifest:id,transportation_mode,transportation_name,manifest_type,date,time',
            ])
                ->where('resort_id', $this->resort_id)
                ->where('id', $passId)
                ->first();

            if (!$EmployeeTravelPassView) {
                return response()->json(['success' => false, 'message' => 'Boarding pass not found.'], 404);
            }

            $currentEmployeeId = $employee->id;
            $isApplicant = (int) $EmployeeTravelPassView->employee_id === (int) $currentEmployeeId;
            $isApprover = EmployeeTravelPassStatus::where('travel_pass_id', $passId)
                ->where('approver_id', $currentEmployeeId)
                ->exists();

            if (!$isApplicant && !$isApprover) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this boarding pass.',
                ], 403);
            }

            $rankConfig = config('settings.Position_Rank', []);
            foreach ($EmployeeTravelPassView->employeeTravelPassStatusData ?? [] as $item) {
                if ($item->approver_role) {
                    $item->rank_type = $item->approver_role;
                } else {
                    $role = ucfirst(strtolower($item->approver_rank ?? ''));
                    $item->rank_type = $rankConfig[$role] ?? '';
                }
            }

            $approvalFlowData = Common::buildIslandPassApprovalFlow($passId, $currentEmployeeId);
            foreach ($approvalFlowData as $key => $value) {
                $EmployeeTravelPassView->{$key} = $value;
            }

            $emp = $EmployeeTravelPassView->employee;
            $resortAdmin = $emp ? $emp->resortAdmin : null;
            $profilePicture = '';
            if ($resortAdmin) {
                $profilePicture = Common::getResortUserPicture($resortAdmin->id);
                $resortAdmin->profile_picture = $profilePicture;
            }

            $employeeName = $resortAdmin
                ? trim(($resortAdmin->first_name ?? '') . ' ' . ($resortAdmin->last_name ?? ''))
                : '';
            $designation = ($emp && $emp->position) ? ($emp->position->position_title ?? '') : '';

            // Manifest confirmation (boat/vessel name + mode) — only ever
            // stored on the manifest row, surfaced here as its own object
            // plus merged into the matching travel segment below so both a
            // top-level lookup and the existing segment shape work.
            $manifest = $EmployeeTravelPassView->manifest;
            $manifestConfirmation = $manifest ? [
                'manifest_id' => $manifest->id,
                'manifest_type' => $manifest->manifest_type,
                'transportation_mode' => $manifest->transportation_mode,
                'transportation_name' => $manifest->transportation_name,
                'date' => $manifest->date,
                'time' => $manifest->time,
            ] : null;

            $travelSegments = [];
            if ($EmployeeTravelPassView->departure_date && $EmployeeTravelPassView->DepartureResortTransportation) {
                $travelSegments[] = [
                    'segment_type' => 'departure',
                    'label' => $EmployeeTravelPassView->DepartureResortTransportation->transportation_option ?? 'Departure',
                    'departure_date' => $EmployeeTravelPassView->departure_date,
                    'departure_time' => $EmployeeTravelPassView->departure_time,
                    'departure_date_display' => Carbon::parse($EmployeeTravelPassView->departure_date)->format('d M Y, D'),
                    'departure_time_display' => $EmployeeTravelPassView->departure_time ?? '—',
                    'manifest_confirmation' => ($manifest && $manifest->manifest_type === 'departure') ? $manifestConfirmation : null,
                ];
            }
            if ($EmployeeTravelPassView->arrival_date && $EmployeeTravelPassView->ArrivalResortTransportation) {
                $travelSegments[] = [
                    'segment_type' => 'arrival',
                    'label' => $EmployeeTravelPassView->ArrivalResortTransportation->transportation_option ?? 'Arrival',
                    'arrival_date' => $EmployeeTravelPassView->arrival_date,
                    'arrival_time' => $EmployeeTravelPassView->arrival_time,
                    'departure_date_display' => Carbon::parse($EmployeeTravelPassView->arrival_date)->format('d M Y, D'),
                    'departure_time_display' => $EmployeeTravelPassView->arrival_time ?? '—',
                    'manifest_confirmation' => ($manifest && $manifest->manifest_type === 'arrival') ? $manifestConfirmation : null,
                ];
            }

            $reason = trim(($EmployeeTravelPassView->departure_reason ?? '') . ' ' . ($EmployeeTravelPassView->arrival_reason ?? ''));
            if ($reason === '') {
                $reason = null;
            }

            $viewDisplay = array_merge([
                'employee' => [
                    'name' => $employeeName,
                    'designation' => $designation,
                    'profile_picture' => $profilePicture,
                    'status' => $EmployeeTravelPassView->status ?? 'Pending',
                ],
                'travel_segments' => $travelSegments,
                'reason' => $reason,
                'manifest_confirmation' => $manifestConfirmation,
            ], $approvalFlowData, [
                // Same Pending-only rule as boardingEmpDashboard's can_modify/
                // can_cancel — Modify/Cancel must disappear once approved or
                // cancelled.
                'can_modify' => (($approvalFlowData['overall_status'] ?? null) === 'Pending'),
                'can_cancel' => (($approvalFlowData['overall_status'] ?? null) === 'Pending'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Employee boarding pass details fetched successfully',
                'borading_pass_details' => $EmployeeTravelPassView,
                'view_display' => $viewDisplay,
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function boardingPassApprovedAction(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'pass_id'                              =>  'required',
            // Was 'required' only, no enum check — employee_travel_pass_status.status
            // and employee_travel_passes.status are both DB-level ENUMs
            // ('Pending','Approved','Rejected','Cancel'). Sending anything
            // else (e.g. "Reject" instead of "Rejected") locally just
            // silently truncates to '' (confirmed: lenient sql_mode), but
            // a stricter production MySQL (STRICT_TRANS_TABLES) throws a
            // real SQL error on that same write, caught by the generic
            // catch block below as a raw 500 with no useful message.
            'action'                                =>  'required|in:Approved,Rejected',
            'reason'                                =>  'required_if:action,Rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $passId                                 =   $request->input('pass_id');
            $action                                 =   $request->input('action'); // Approve or Reject
            $comments                               =   $request->input('reason', null); // Optional comments
            $employee                               =   $this->user->GetEmployee;
            $currentApproverId                      =   $employee->id; // Assuming the logged-in user is the approver
            $employeeTravelPasses                   =   EmployeeTravelPass::find($passId);

            if (!$employeeTravelPasses) {
                return response()->json([
                    'status'                        =>  false,
                    'message'                       =>  'Boarding pass request not found.',
                ], 200);
            }

            $employeeTravelPassStatus               =   EmployeeTravelPassStatus::where('travel_pass_id', $passId)
                                                            ->where('status', 'Pending')
                                                            ->orderBy('id', 'desc')
                                                            ->first();

                if (!$employeeTravelPassStatus) {
                    // All approvals are completed
                    return response()->json(array_merge([
                        'success'                   =>  true,
                        'message'                   =>  'This travel pass has already been fully approved.',
                    ], Common::buildIslandPassApprovalFlow((int) $passId, $currentApproverId)), 200);
                }

            $rankConfig                             =   config('settings.Position_Rank');
            $currentApproverRank                    =   array_key_exists($employee->rank, $rankConfig) ? $rankConfig[$employee->rank] : '';
            $lastApproverRank                       =   array_key_exists($employeeTravelPassStatus->approver_rank, $rankConfig) ? $rankConfig[$employeeTravelPassStatus->approver_rank] : '';

            $actionname                             =   ($action == "Rejected") ? "reject" : "approve";

            // If the logged-in approver already actioned their own step,
            // say so instead of the misleading "must first be approved by
            // the X" message (the pending row at this point belongs to the
            // NEXT approver in the chain, not a missing earlier one).
            $ownStatusRow                           =   EmployeeTravelPassStatus::where('travel_pass_id', $passId)
                                                            ->where('approver_id', $currentApproverId)
                                                            ->orderBy('id', 'desc')
                                                            ->first();
            if ($ownStatusRow && $ownStatusRow->status !== 'Pending') {
                return response()->json(array_merge([
                    'status'                        =>  false,
                    'already_actioned'              =>  true,
                    'pass_status'                   =>  $employeeTravelPasses->status,
                    'message'                       =>  'You have already ' . strtolower($ownStatusRow->status) . ' this request. It is now awaiting action from the ' . $lastApproverRank . '.',
                ], Common::buildIslandPassApprovalFlow((int) $passId, $currentApproverId)), 200);
            }

            if ($employeeTravelPassStatus && $employeeTravelPassStatus->approver_id != $currentApproverId) {
                return response()->json(array_merge([
                    'status'                        =>  false,
                    'message'                       =>  "You cannot $actionname this request. It is currently awaiting action from the $lastApproverRank.",
                ], Common::buildIslandPassApprovalFlow((int) $passId, $currentApproverId)), 200);
            }

            if($request->arrival_time || $request->departure_time) {
                EmployeeTravelPass::where('id', $employeeTravelPasses->id)->update([
                    'arrival_time' => $request->arrival_time,
                    'departure_time' => $request->departure_time
                ]);
            }

            EmployeeTravelPassStatus::where('travel_pass_id', $employeeTravelPasses->id)->where('approver_id', $currentApproverId)->update([
                'approver_id'                       =>  $currentApproverId,
                'status'                            =>  $action,
                'comments'                          =>  $comments, // Save comments if provided
                'approved_at'                       =>  now(),
            ]);

            $allApproved                            =   EmployeeTravelPassStatus::where('travel_pass_id', $employeeTravelPasses->id)
                                                            ->where('status', '!=', 'Approved')
                                                            ->doesntExist();

            if ($allApproved) {
                EmployeeTravelPass::where('id', $employeeTravelPasses->id)->update([
                    'status' => $action,
                    ]);
            }
                Common::sendMobileNotification(
                    $this->resort_id,
                    2,
                    null,
                    null,
                    'Boarding Pass ' . $action,
                    'A boarding pass request has been ' . $action . ' by ' . $this->user->first_name . ' ' . $this->user->last_name . '.',
                    'Boarding Pass',
                    [$employeeTravelPasses->employee_id],
                    $employeeTravelPasses->id,
                    false,
                    'boarding-pass-' . strtolower($action),
                );

            if ($action == 'Approved') {

                if ($employeeTravelPassStatus->approver_rank == 8) {
                    $employeeTravelPasses->status   =   "Approved";
                    $employeeTravelPasses->save();
                }

                // Tell the next approver in the chain their action is now due.
                $nextPendingStatus                  =   EmployeeTravelPassStatus::where('travel_pass_id', $employeeTravelPasses->id)
                                                            ->where('status', 'Pending')
                                                            ->orderBy('id', 'desc')
                                                            ->first();
                if ($nextPendingStatus && $nextPendingStatus->approver_id) {
                    Common::sendMobileNotification(
                        $this->resort_id,
                        2,
                        null,
                        null,
                        'Boarding Pass Approval Required',
                        'A boarding pass request is awaiting your approval.',
                        'Boarding Pass',
                        [$nextPendingStatus->approver_id],
                        $employeeTravelPasses->id,
                        false,
                        'boarding-pass-approval-required',
                    );
                }

                return response()->json(array_merge([
                    'status'                        =>  true,
                    'isAssigned'                    =>  true,
                    'pass_status'                   =>  $employeeTravelPasses->fresh()->status,
                    'all_approved'                  =>  (bool) $allApproved,
                    'message'                       =>  'Boarding pass approved successfully.',
                ], Common::buildIslandPassApprovalFlow((int) $passId, $currentApproverId)));
            } elseif ($action === 'Rejected') {
                $employeeTravelPasses->status       =   "Rejected";
                $employeeTravelPasses->save();

                return response()->json(array_merge([
                    'status'                        =>  true,
                    'message'                       =>  'Boarding Pass Rejected.',
                ], Common::buildIslandPassApprovalFlow((int) $passId, $currentApproverId)), 200);
            } else {
                return response()->json(array_merge([
                    'status'                        =>  false,
                    'message'                       =>  'Invalid action.',
                ], Common::buildIslandPassApprovalFlow((int) $passId, $currentApproverId)), 200);
            }

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function employeeLeavingOrArriving(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee                                   =   $this->user->GetEmployee;

        try {
            $resortId                               =   $this->resort_id;
            $filter                                 =   $request->get('filter', 'today'); // either 'today' or 'week'
            $startDate                              =   Carbon::today();
            $endDate                                =   $filter === 'week' ? Carbon::today()->endOfWeek() : Carbon::today();

            // Build query based on user rank — HOD (2) and EXCOM (1) get the
            // same department-scoped view; was HOD-only, so EXCOM fell into
            // the narrower branch below.
            if(in_array((int) $employee->rank, [1, 2])) {
                $query                              =   EmployeeTravelPass::with([
                                                            'employee:id,Admin_Parent_id,Position_id',
                                                            'employee.resortAdmin:id,first_name,last_name,profile_picture',
                                                            'employee.position:id,position_title',
                                                            ])
                                                            ->where('status', 'Approved')
                                                            ->whereIn('employee_id', $this->underEmp_id)
                                                            ->where(function ($q) use ($startDate, $endDate) {
                                                                // Filter by date ranges
                                                                $q->whereBetween('departure_date', [$startDate, $endDate])
                                                                ->orWhereBetween('arrival_date', [$startDate, $endDate]);
                                                            });
            } else {
                $query                              =   EmployeeTravelPass::with([
                                                            'employee:id,Admin_Parent_id,Position_id',
                                                            'employee.resortAdmin:id,first_name,last_name,profile_picture',
                                                            'employee.position:id,position_title',
                                                            ])
                                                            ->where('status', 'Approved')
                                                            ->where(function ($q) use ($startDate, $endDate) {
                                                                // Filter by date ranges
                                                                $q->whereBetween('departure_date', [$startDate, $endDate])
                                                                ->orWhereBetween('arrival_date', [$startDate, $endDate]);
                                                            });
            }

            $employeeTravelPasses                   =   $query->get();
            $formattedPasses                        =   [];

            foreach ($employeeTravelPasses as $pass) {
                if ($pass->employee && $pass->employee->resortAdmin) {
                    $pass->employee->resortAdmin->profile_picture = Common::getResortUserPicture($pass->employee->Admin_Parent_id);
                }

                // For departures
                if ($pass->departure_date && Carbon::parse($pass->departure_date)->between($startDate, $endDate)) {
                    $departedPass                   =   clone $pass;
                    $departedPass->pass_status      =   'departure';
                    $formattedPasses[]              =   $departedPass;
                }

                // For arrivals
                if ($pass->arrival_date && Carbon::parse($pass->arrival_date)->between($startDate, $endDate)) {
                    $arrivedPass                    =   clone $pass;
                    $arrivedPass->pass_status       =   'arrival';
                    $formattedPasses[]              =   $arrivedPass;
                }
            }

            $employeeTravelPasses                   =   collect($formattedPasses);

            return response()->json([
                'success'                           => true,
                'message'                           => 'Employee boarding pass details fetched successfully',
                'employee_leave_arriving'           => $employeeTravelPasses
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function emergencyCancelBoardingPass(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'pass_id'                               =>  'required',
            'action'                                =>  'required|in:Cancel',
            'comments'                                =>  'required_if:action,Cancel',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $employee                                   =   $this->user->GetEmployee;
        $passId                                     =   $request->input('pass_id');
        $action                                     =   $request->input('action', 'Cancel'); // Default to 'Cancel' if not provided
        $comments                                   =   $request->input('comments'); // Optional
        try {

            // $employeeTravelPasses                   =   EmployeeTravelPass::find($passId);
            $employeeTravelPasses                   =   EmployeeTravelPass::where('id',$passId)->where('resort_id', $this->resort_id)->first();

            if (!$employeeTravelPasses) {
                return response()->json([
                    'status'                        =>  false,
                    'message'                       =>  'Boarding pass request not found.',
                ], 200);
            }

            if($action == 'Cancel') {

                EmployeeTravelPassStatus::where('travel_pass_id', $employeeTravelPasses->id)->where('approver_id', $employee->id)->update([
                    'emergency_cancel_status'           =>  $action,
                    'comments'                          =>  $comments, // Save comments if provided
                    'approved_at'                       =>  now(),
                ]);

                $employeeTravelPasses->status           =   $action;
                $employeeTravelPasses->save();

                // Employee only ever got the approval notification, never
                // one when an already-approved pass was cancelled — they'd
                // have no idea until they happened to check the app.
                Common::sendMobileNotification(
                    $this->resort_id,
                    2,
                    null,
                    null,
                    'Boarding Pass Cancelled',
                    'Your approved boarding pass has been cancelled by ' . $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name . ($comments ? (': ' . $comments) : '.'),
                    'Boarding Pass',
                    [$employeeTravelPasses->employee_id],
                    $employeeTravelPasses->id,
                    false,
                    'boarding-pass-cancelled'
                );

                return response()->json([
                    'success'                           =>  true,
                    'message'                           =>  'Cancelled the Employee boarding pass.',
                ], 200);
            }
            // Check if the boarding pass is already cancelled

                return response()->json([
                    'success'                           =>  false,
                    'message'                           =>  'This boarding pass has not been cancelled.',
                ], 200);


        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());

            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Manifest create/view is restricted to HR HOD/EXCOM and any Security
     * department employee (department+rank spec from the Island Pass
     * Manifest ticket) — everyone else gets a 403.
     */
    private function userCanManageManifests(): bool
    {
        $employee = $this->user->GetEmployee ?? null;
        if (!$employee) return false;

        $rank    = $employee->rank ?? null;
        $isHOD   = ($rank == 2 || $rank === '2');
        $isEXCOM = ($rank == 1 || $rank === '1');

        $isHRDept       = Common::isHRDepartment($employee->Dept_id);
        $isSecurityDept = Common::isSecurityDepartment($employee->Dept_id);

        return ($isHRDept && ($isHOD || $isEXCOM)) || $isSecurityDept;
    }

    public function transportationDateBasedEmp(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->userCanManageManifests()) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to manage manifests.'], 403);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'transportation_id'                     => 'required',
            'date'                                  => 'required|date_format:Y-m-d',
            'type'                                  => 'required|in:arrival,departure',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $query                                  =   EmployeeTravelPass::with([
                                                            'employee:id,Admin_Parent_id',
                                                            'employee.resortAdmin:id,first_name,last_name'
                                                        ])
                                                        ->where('resort_id', $this->resort_id)
                                                        ->where('status', 'Approved')
                                                        // Don't trust the parent's denormalized status alone —
                                                        // re-verify against the real per-step approval rows
                                                        // (HOD -> HR -> SM) so a stale/short-circuited status
                                                        // can't surface a not-actually-fully-approved pass.
                                                        ->whereHas('employeeTravelPassStatusData')
                                                        ->whereDoesntHave('employeeTravelPassStatusData', function ($q) {
                                                            $q->where('status', '!=', 'Approved');
                                                        });
            // Filter by arrival or departure
            if ($request->type === 'arrival') {
                $query->where('arrival_date', $request->date)->where('arrival_mode', $request->transportation_id);
            } else {
                $query->where('departure_date', $request->date)->where('departure_mode', $request->transportation_id);
            }

            $employeeTravelPasses                   =   $query->get();

            // Return photo/pass-type/date alongside name so the manifest
            // employee-picker can display them, not just the id/name.
            $employees                              =   $employeeTravelPasses->map(function ($pass) use ($request) {
                $employee = $pass->employee;
                if ($employee && $employee->resortAdmin) {
                    return [
                        'id'                        =>  $employee->id,
                        'first_name'                =>  $employee->resortAdmin->first_name,
                        'last_name'                 =>  $employee->resortAdmin->last_name,
                        'full_name'                 =>  $employee->resortAdmin->full_name,
                        'profile_picture'           =>  Common::getResortUserPicture($employee->Admin_Parent_id),
                        'pass_type'                 =>  $request->type,
                        'date'                      =>  $request->type === 'arrival' ? $pass->arrival_date : $pass->departure_date,
                    ];
                }
                return null;
            })->filter()->values();

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Fetched the transportation and date-based ' . $request->type . ' employee list successfully',
                'emp_list'                          =>  $employees
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());

            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function manifestStore(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->userCanManageManifests()) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to manage manifests.'], 403);
        }

         // Validate request data
         $validator = Validator::make($request->all(), [
            'manifest_type'                     => 'required|in:arrival,departure',
            'transportation_mode'               => 'required|string',
            'transportation_name'               => 'required|string',
            'date'                              => 'required|date',
            'time'                              => 'required',
            // Mobile sends a fixed-size employee_ids[] with empty slots when
            // no employee is picked for that row (e.g. a visitors-only
            // manifest) — was 'required|array' with no per-element rule, so
            // an empty string ("employee_ids[0]: ''") sailed through
            // validation and crashed the FK constraint on manifest_employees
            // (employee_id references employees.id) with a raw 500 instead
            // of a clean validation error.
            'employee_ids'                      => 'nullable|array',
            'employee_ids.*'                    => 'nullable|integer|exists:employees,id',
            'visitors'                          => 'array',
            'visitors.*'                        => 'string',
        ]);

        // Nothing stopped a manifest being saved with every employee_ids[]
        // slot blank and no visitors either — a completely empty manifest,
        // which is exactly what happened for two manifests reported as
        // "No travel pass found for this manifest" (nobody was ever
        // actually on them to have a pass). Blocking on employee_ids alone
        // would break the legitimate visitors-only manifest case (see
        // comment above), so this only rejects the case where BOTH are
        // empty.
        $validator->after(function ($validator) use ($request) {
            $hasEmployees = collect((array) $request->employee_ids)->filter(fn ($id) => $id !== null && $id !== '')->isNotEmpty();
            $hasVisitors = collect((array) $request->visitors)->filter(fn ($v) => $v !== null && trim((string) $v) !== '')->isNotEmpty();
            if (!$hasEmployees && !$hasVisitors) {
                $validator->errors()->add('employee_ids', 'Add at least one employee or visitor before saving the manifest.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            // Check for existing manifest
            $existingManifest                   =   Manifest::where('resort_id', $this->resort_id)
                                                        ->where('manifest_type', $request->manifest_type)
                                                        ->where('transportation_mode', $request->transportation_mode)
                                                        ->where('transportation_name', $request->transportation_name)
                                                        ->where('date', $request->date)
                                                        ->first();

            if ($existingManifest) {
                return response()->json([
                    'success'                   =>  false,
                    'message'                   =>  'Manifest already exists for this transportation and date.'
                ], 200);
            }

            // Attach employees — filter out empty slots (mobile sends a
            // fixed-size employee_ids[] and leaves unpicked rows blank).
            // Computed before the manifest itself so the passes update
            // below can be scoped to exactly these employees.
            $validEmployeeIds = array_values(array_filter(
                (array) $request->employee_ids,
                fn ($id) => $id !== null && $id !== ''
            ));

            if($request->manifest_type == 'arrival') {

                // Set status for arrival
                $status = in_array($request->status, ['draft', 'confirmed']) ? $request->status : 'draft';

                 // Create manifest
                $manifest = Manifest::create([
                    'resort_id'                         =>  $this->resort_id,
                    'manifest_type'                     =>  $request->manifest_type,
                    'transportation_mode'               =>  $request->transportation_mode,
                    'transportation_name'               =>  $request->transportation_name,
                    'date'                              =>  $request->date,
                    'time'                              =>  $request->time,
                    'status'                            =>  'saved',
                ]);

                // Scoped to this resort + the employees actually on this
                // manifest (was previously any pass anywhere with a
                // matching arrival_date, regardless of resort or whether
                // the employee was even on this manifest), and stamps
                // manifest_id so the pass detail reflects the assignment.
                if (!empty($validEmployeeIds)) {
                    EmployeeTravelPass::where('resort_id', $this->resort_id)
                        ->where('arrival_date', $request->date)
                        ->whereIn('employee_id', $validEmployeeIds)
                        ->update([
                            'arrival_time'               =>  $request->time,
                            'manifest_id'                =>  $manifest->id,
                        ]);
                }
            } else {

                // Set status for departure
                $status = in_array($request->status, ['saved', 'closed']) ? $request->status : 'saved';

                 // Create manifest
                $manifest = Manifest::create([
                    'resort_id'                         =>  $this->resort_id,
                    'manifest_type'                     =>  $request->manifest_type,
                    'transportation_mode'               =>  $request->transportation_mode,
                    'transportation_name'               =>  $request->transportation_name,
                    'date'                              =>  $request->date,
                    'time'                              =>  $request->time,
                    'status'                            =>  'saved',
                ]);

                if (!empty($validEmployeeIds)) {
                    EmployeeTravelPass::where('resort_id', $this->resort_id)
                        ->where('departure_date', $request->date)
                        ->whereIn('employee_id', $validEmployeeIds)
                        ->update([
                            'departure_time'             =>  $request->time,
                            'manifest_id'                =>  $manifest->id,
                        ]);
                }
            }

            foreach ($validEmployeeIds as $empId) {
                ManifestEmployee::create([
                    'manifest_id'               =>  $manifest->id,
                    'employee_id'               =>  $empId,
                ]);
            }

            // Notify once per manifest, not once per employee (previous code
            // fired this inside the loop with the full list as `sendto`
            // every time — N employees meant N duplicate notifications to
            // everyone).
            if (!empty($validEmployeeIds)) {
                Common::sendMobileNotification(
                    $this->resort_id,
                    2,
                    null,
                    null,
                    $request->transportation_mode . ' ' . $request->manifest_type,
                    $request->transportation_mode . ' '  . $request->date . ' at ' . $request->time . ' has been ' . $request->manifest_type . '.',
                    'Boarding Pass',
                    $validEmployeeIds,
                    null,
                    false,
                    'boarding-pass-detail',
                );
            }

            // Also notify HR and Security — the ticket's spec is that
            // manifest creation notifies the included employees AND these
            // two groups, not just the employees. Both fan-outs reuse the
            // same Common:: helper pattern (alias-matching department
            // lookup + active-employee filter) instead of an inline query
            // that only matched a department literally named "Security".
            $hrEmployeeIds = Common::getResortHrEmployeeIds($this->resort_id);
            $securityEmployeeIds = Common::getResortSecurityEmployeeIds($this->resort_id);
            $staffNotifyIds = array_values(array_unique(array_merge($hrEmployeeIds, $securityEmployeeIds)));

            if (!empty($staffNotifyIds)) {
                Common::sendMobileNotification(
                    $this->resort_id,
                    2,
                    null,
                    null,
                    'Manifest ' . ucfirst($request->manifest_type) . ' Created',
                    ucfirst($request->manifest_type) . ' manifest for ' . $request->transportation_mode . ' (' . $request->transportation_name . ') on ' . $request->date . ' at ' . $request->time . ' created with ' . count($validEmployeeIds) . ' employee(s).',
                    'Boarding Pass',
                    $staffNotifyIds,
                    null,
                    false,
                    'boarding-pass-manifest-created',
                );
            }

            // Attach visitors
            if ($request->has('visitors')) {
                foreach ($request->visitors as $visitorName) {
                    ManifestVisitor::create([
                        'manifest_id'               =>  $manifest->id,
                        'visitor_name'              =>  $visitorName,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Manifest created successfully',
                'data'                              =>  $manifest->load('employees.employee:id,Admin_Parent_id', 'visitors')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());

            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function SOEmployeeList()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employeeId                             =   $this->user->GetEmployee->id;
            $securityOfficerTitles                  =   ['Security Officer', 'SO'];

            $SOEmployeeList                         =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                            ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                                                            ->where("t1.resort_id", $this->resort_id)
                                                            ->where('employees.reporting_to',$employeeId)
                                                            ->whereIn('t2.position_title',$securityOfficerTitles)
                                                            ->where('employees.status', 'Active')
                                                            ->select('employees.id','t1.first_name','t1.last_name')
                                                            ->get();
            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Security Officer employee list fetched successfully.',
                'so_employee_list'                  =>  $SOEmployeeList
            ]);
         } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());

            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }

    }

    public function passTimeupdateHRAndSM(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        // Name says HR/SM-only, but had no actual role check — any
        // authenticated user could edit any pass's arrival/departure time.
        if (!$this->userCanManageManifests()) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to manage manifests.'], 403);
        }

         // Validate request data
         $validator = Validator::make($request->all(), [
            'pass_id'                               =>  'required',
            'departure_time'                        =>  'nullable|date_format:H:i',
            'arrival_time'                          =>  'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

         // At least one time must be present
        if (!$request->departure_time && !$request->arrival_time) {
            return response()->json([
                'success'                           => false,
                'message'                           => 'Either departure_time or arrival_time is required.'
            ], 422);
        }

        DB::beginTransaction();
        try {

            $pass                                   = EmployeeTravelPass::findOrFail($request->pass_id);

            // Update times conditionally
            if ($request->has('departure_time')) {
                $pass->departure_time               = $request->departure_time;
            }

            if ($request->has('arrival_time')) {
                $pass->arrival_time                 = $request->arrival_time;
            }

            $pass->save();

            // Employee whose travel time this is had no way to find out HR/SM
            // moved it under them.
            Common::notifyEmployees(
                $this->resort_id,
                [$pass->employee_id],
                'Boarding Pass Time Updated',
                'Your boarding pass travel time was updated by ' . $this->user->first_name . ' ' . $this->user->last_name . '.',
                'Boarding Pass',
                $pass->id
            );

            DB::commit();
            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Time updated successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());

            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function SOPassAssign(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'pass_id'                           => 'required',
            'employee_ids'                      => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            foreach ($request->employee_ids as $employeeId) {
                $exists = EmployeeTravelPassAssign::where([
                    ['resort_id', $this->resort_id],
                    ['travel_pass_id', $request->pass_id],
                    ['employee_id', $employeeId]
                ])->exists();

                if (!$exists) {
                    EmployeeTravelPassAssign::create([
                        'resort_id'        => $this->resort_id,
                        'travel_pass_id'   => $request->pass_id,
                        'employee_id'      => $employeeId,
                    ]);
                }

                Common::sendMobileNotification(
                    $this->resort_id,
                    2,
                    null,
                    null,
                    'Boarding Pass Request',
                    'A boarding pass assigned to you by ' . $this->user->first_name . ' ' . $this->user->last_name . '.',
                    'Boarding Pass',
                    $request->employee_ids,
                    null,
                    false,
                    'security-officer-details',
                );
            }

            DB::commit();

            $response['status']                     =   true;
            $response['message']                    =   'The travel pass has been successfully assigned to the selected Security Officers.';

            return response()->json($response);
         } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());

            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }

    }

    public function SODashboard(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $resortId                                   =   $this->resort_id;
        $employee                                   =   $this->user->GetEmployee;
        $SOId                                       =   $employee->id; // Assuming the logged-in user is the approver

        if ($request->filter === 'weekly' ) {
            // Default to weekly if filter is 'weekly' or no filter is provided
            $startDate                              =   Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $endDate                                =   Carbon::now()->endOfWeek(Carbon::SATURDAY)->format('Y-m-d');
        } elseif ($request->filter  === 'monthly') {
            // For monthly filter
            $startDate                              =   Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate                                =   Carbon::now()->endOfMonth()->format('Y-m-d');
        } elseif ($request->filter  === 'day' || !$request->has('filter')) {
            // For day filter
            $startDate                              =   $endDate = Carbon::now()->format('Y-m-d');
        }

        try {

                $departedEmployeeCount              =   EmployeeTravelPassAssign::whereHas('employeeTravelPasses', function ($query) {
                                                                $query->whereDate('departure_date', Carbon::now()->format('Y-m-d'));
                                                            })->where('employee_id', $SOId)
                                                            ->where('resort_id', $this->resort_id)
                                                            ->count();

                $arrivedEmployeeCount               =   EmployeeTravelPassAssign::whereHas('employeeTravelPasses', function ($query) {
                                                                $query->whereDate('arrival_date', Carbon::now()->format('Y-m-d'));
                                                            })->where('employee_id', $SOId)
                                                            ->where('resort_id', $this->resort_id)
                                                            ->count();


                $employeeList                       =   EmployeeTravelPassAssign::whereHas('employeeTravelPasses', function ($query) use ($startDate, $endDate) {
                                                            $query->whereBetween('departure_date', [$startDate, $endDate])
                                                                    ->orWhereBetween('arrival_date', [$startDate, $endDate]);
                                                            })->with([
                                                                'employeeTravelPasses:id,status,departure_date,departure_time,arrival_date,arrival_time,employee_id,employee_departure_status,employee_arrival_status',
                                                                'employeeTravelPasses.employee:id,Admin_Parent_id',
                                                                'employeeTravelPasses.employee.resortAdmin:id,first_name,last_name,profile_picture',
                                                            ])->where('employee_id',$SOId)->get()->map( function($row){
                                                                $row->employeeTravelPasses->employee->resortAdmin->profile_picture = Common::getResortUserPicture($row->employeeTravelPasses->employee->Admin_Parent_id);
                                                                return $row;
                                                            });

            $SOdahsboardArr                           =   [
                'depature_count'                    =>  $departedEmployeeCount,
                'arrival_count'                     =>  $arrivedEmployeeCount,
                'today_departures_arrivals'         =>  $employeeList,
            ];

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Employee Approved boarding pass data fetched Successfully',
                'employee_data'                     =>  $SOdahsboardArr,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function SOConfirmArrivalDept(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'pass_id'                           => 'required',
            'status'                            => 'required|in:departed,arrived',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }


        try {
             $passId                                =   $request->pass_id;

                $employeeTravelPass                 =   EmployeeTravelPass::find($passId);
                if (!$employeeTravelPass) {
                    return response()->json([
                        'success'                   =>  false,
                        'message'                   =>  'Travel pass not found'
                    ], 200);
                }
                if ($request->status == 'departed') {
                    $employeeTravelPass->employee_departure_status      = 'departed';
                } elseif ($request->status == 'arrival') {
                    $employeeTravelPass->employee_arrival_status        = 'arrived'; // Example new field
                }

                $employeeTravelPass->save();

            // Neither the traveller nor HR previously learned that Security
            // had confirmed their departure/arrival.
            $travellerAdmin                         =   optional($employeeTravelPass->employee)->resortAdmin;
            $travellerName                          =   $travellerAdmin ? trim(($travellerAdmin->first_name ?? '') . ' ' . ($travellerAdmin->last_name ?? '')) : '';

            Common::notifyEmployees(
                $employeeTravelPass->resort_id,
                [$employeeTravelPass->employee_id],
                'Boarding Pass ' . ucfirst($request->status),
                'Your boarding pass has been marked as ' . $request->status . ' by Security.',
                'Boarding Pass',
                $employeeTravelPass->id
            );

            Common::notifyEmployees(
                $employeeTravelPass->resort_id,
                Common::getResortHrEmployeeIds($employeeTravelPass->resort_id),
                'Employee ' . ucfirst($request->status),
                ($travellerName !== '' ? $travellerName : 'An employee') . ' has been marked as ' . $request->status . ' by Security.',
                'Boarding Pass',
                $employeeTravelPass->id
            );

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Employee ' . $request->status . ' status updated successfully',
                'employee_data'                     =>  $employeeTravelPass,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function boardingPassUpdate(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'pass_id'                               =>  'required',
            'dept_date'                             =>  'nullable|date_format:Y-m-d',
            'dept_time'                             =>  'nullable',
            'dept_transportation'                   =>  'nullable|string',
            'dept_reason'                           =>  'nullable|string|max:255',
            'arrival_date'                          =>  'nullable|date_format:Y-m-d',
            'arrival_time'                          =>  'nullable',
            'arrival_transportation'                =>  'nullable|string',
            'arrival_reason'                        =>  'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        $data                                       =   $validator->validated();

        $departureValid                             =   isset($data['dept_date'],      $data['dept_transportation']);
        $arrivalValid                               =   isset($data['arrival_date'],   $data['arrival_transportation']);

        if (!$departureValid && !$arrivalValid) {
            return response()->json([
                'status'                            =>  false,
                'message'                           =>  'Please provide either departure or arrival details with transportation.'
            ], 200);
        }

        DB::beginTransaction();

        try {
            $user                                   =   Auth::guard('api')->user();
            $employee                               =   $user->GetEmployee;

            // Only checked per-stage approval status before — a pass that was
            // emergency-cancelled while still fully Pending (no stage ever
            // reached Approved) had nothing here blocking it from still being
            // modified.
            $travelPass                             =   EmployeeTravelPass::find($data['pass_id']);
            if (!$travelPass) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Travel pass not found.'], 200);
            }
            if ($travelPass->status === 'Cancel') {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Update not allowed. This travel pass has been cancelled.'], 200);
            }

            $EmployeeTravelPassStatus               =   EmployeeTravelPassStatus::where('travel_pass_id',$data['pass_id'])->get();
            $hasApproved                            =   $EmployeeTravelPassStatus->contains('status', 'Approved');

            if ($hasApproved) {
                DB::rollBack();
                return response()->json([
                    "status"                        =>  false,
                    'message'                       =>  'Update not allowed. This travel pass has already been approved by an approver.'
                ], 200);
            }

            $boardingData                           =   [
                'arrival_date'                      =>  !empty($data['arrival_date']) ? \Carbon\Carbon::createFromFormat('Y-m-d', $data['arrival_date']) : null,
                'arrival_time'                      =>  $data['arrival_time'] ?? null,
                'arrival_mode'                      =>  $data['arrival_transportation'] ?? null,
                'arrival_reason'                    =>  $data['arrival_reason'] ?? null,
                'departure_date'                    =>  !empty($data['dept_date']) ? \Carbon\Carbon::createFromFormat('Y-m-d', $data['dept_date']) : null,
                'departure_time'                    =>  $data['dept_time'] ?? null,
                'departure_mode'                    =>  $data['dept_transportation'] ?? null,
                'departure_reason'                  =>  $data['dept_reason'] ?? null,
            ];

            EmployeeTravelPass::where('id', $data['pass_id'])->update($boardingData);

            // Approvers still holding a Pending stage on this pass were
            // never told the details they're about to act on just changed.
            $pendingApproverIds                     =   $EmployeeTravelPassStatus->where('status', 'Pending')->pluck('approver_id')->unique()->values()->all();
            if (!empty($pendingApproverIds)) {
                Common::notifyEmployees(
                    $this->resort_id,
                    $pendingApproverIds,
                    'Boarding Pass Updated',
                    $user->first_name . ' ' . $user->last_name . ' updated a pending boarding pass request awaiting your review.',
                    'Boarding Pass',
                    $travelPass->id
                );
            }

            DB::commit();

            return response()->json([
                'status'                            =>  true,
                'message'                           =>  'Travel pass updated successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function boardingPassCancel(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'pass_id'                               =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        $passId                                     =   $request->pass_id;
        DB::beginTransaction();

        try {
            // Check if the pass is already cancelled
            $travelPass                             =   EmployeeTravelPass::where('id', $passId)->first();

            if (!$travelPass) {
                return response()->json([
                    'status'                        =>  false,
                    'message'                       =>  'Travel pass not found.'
                ], 200);
            }

            if ($travelPass->status === 'Cancel') {
                return response()->json([
                    'status'                        =>  false,
                    'message'                       =>  'This travel pass has already been cancelled.'
                ], 200);
            }

            // Employee self-cancel is only meant for a still-Pending request —
            // once any stage has approved it, cancellation must go through
            // the HOD's Emergency Cancel flow instead. Previously nothing
            // here stopped a plain self-cancel on an already-approved pass.
            $alreadyApproved                            =   EmployeeTravelPassStatus::where('travel_pass_id', $passId)
                                                                ->where('status', 'Approved')
                                                                ->exists();
            if ($alreadyApproved) {
                DB::rollBack();
                return response()->json([
                    'status'                        =>  false,
                    'message'                       =>  'This travel pass has already been approved and can no longer be self-cancelled.'
                ], 200);
            }

            // Approvers still holding a Pending stage need to know before
            // their status rows are overwritten below.
            $pendingApproverIds                         =   EmployeeTravelPassStatus::where('travel_pass_id', $passId)
                                                                ->where('status', 'Pending')
                                                                ->pluck('approver_id')
                                                                ->unique()
                                                                ->values()
                                                                ->all();

            // Cancel the travel pass and all its approver statuses
            $travelPass->update(['status' => 'Cancel']);
            EmployeeTravelPassStatus::where('travel_pass_id',$passId)->update(['status' => 'Cancel']);

            if (!empty($pendingApproverIds)) {
                Common::notifyEmployees(
                    $this->resort_id,
                    $pendingApproverIds,
                    'Boarding Pass Cancelled',
                    $this->user->first_name . ' ' . $this->user->last_name . ' withdrew a boarding pass request awaiting your review.',
                    'Boarding Pass',
                    $travelPass->id
                );
            }

            DB::commit();

            return response()->json([
                'status'                            =>  true,
                'message'                           =>  'Travel pass cancelled successfully.'
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
     * Scheduled departure/arrival time for a given transport mode + date,
     * looked up from the manifest HR/Admin already created — for the mobile
     * "Apply Leave" / Departure Pass screen to auto-populate Departure Time
     * / Arrival Time instead of letting the employee type an arbitrary
     * value. manifestListing() returns every manifest for a type with no
     * mode/date filter, so it isn't a precise fit for this.
     */
    public function manifestScheduleLookup(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'manifest_type'                          => 'required|in:arrival,departure',
            'transportation_mode'                    => 'required|string',
            'date'                                    => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $manifests                               =   Manifest::where('resort_id', $this->resort_id)
                                                            ->where('manifest_type', $request->manifest_type)
                                                            ->where('transportation_mode', $request->transportation_mode)
                                                            ->whereDate('date', $request->date)
                                                            ->where('status', 'saved')
                                                            ->orderBy('time', 'asc')
                                                            ->get(['id', 'transportation_mode', 'transportation_name', 'date', 'time']);

            return response()->json([
                'success'                            =>  true,
                'message'                            =>  'Manifest schedule fetched successfully.',
                'data'                               =>  $manifests,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function manifestListing(Request $request)
    {

        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->userCanManageManifests()) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to manage manifests.'], 403);
        }
         $validator = Validator::make($request->all(), [
            'manifest_type'                     => 'required|in:arrival,departure',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {

            $ManifestListing                            =   Manifest::where('resort_id',$this->resort_id)
                                                            ->where('manifest_type',$request->manifest_type)
                                                            ->where('status','saved')
                                                            ->get();

            return response()->json([
                'success' => true,
                'message' => $request->manifest_type.' Manifest listing fetched successfully',
                'data' => $ManifestListing
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());

            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function manifestDetails($manifestId)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->userCanManageManifests()) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to manage manifests.'], 403);
        }

        $manifestId = base64_decode($manifestId);
        try {

            $ManifestListing                            =   Manifest::with([
                                                                 'employees.employee:id,Admin_Parent_id,Position_id,Emp_id',
                                                                'employees.employee.resortAdmin:id,first_name,last_name,profile_picture',
                                                                'visitors',
                                                                'transportationMode:id,transportation_option',
                                                                'employees.employee.position:id,position_title',
                                                            ])->where('resort_id',$this->resort_id)
                                                                ->where('id',$manifestId)
                                                                ->first();

         if ($ManifestListing) {
            foreach ($ManifestListing->employees as $employeeRelation) {
                if ($employeeRelation->employee && $employeeRelation->employee->resortAdmin) {
                    $employeeRelation->employee->resortAdmin->profile_picture = Common::getResortUserPicture($employeeRelation->employee->Admin_Parent_id);
                }
            }
        }

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Manifest details fetched successfully',
                'data'                                  =>  $ManifestListing
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());

            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function manifestDetailsPDFWithEmployees($manifestId)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->userCanManageManifests()) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to manage manifests.'], 403);
        }

        $manifestId = base64_decode($manifestId);
        try {

            $ManifestListing                            =   Manifest::with([
                                                                'employees.employee:id,Admin_Parent_id,Position_id,Emp_id',
                                                                'employees.employee.resortAdmin:id,first_name,last_name,profile_picture',
                                                                'visitors',
                                                                'transportationMode:id,transportation_option',
                                                                'employees.employee.position:id,position_title',
                                                            ])->where('resort_id',$this->resort_id)
                                                                ->where('id',$manifestId)
                                                                ->first();
            if ($ManifestListing) {
                foreach ($ManifestListing->employees as $employeeRelation) {
                    if ($employeeRelation->employee && $employeeRelation->employee->resortAdmin) {

                        if( isset($employeeRelation->employee->resortAdmin->profile_picture) && $employeeRelation->employee->resortAdmin->profile_picture != null)
                    {
                        $profilePicturePath = public_path(config('settings.ResortProfile_folder') . '/' . $employeeRelation->employee->resortAdmin->profile_picture);

                        if (file_exists($profilePicturePath))
                        {
                           $profilePicture = $profilePicturePath;
                        }
                        else
                        {
                            $profilePicture = public_path(config('settings.default_picture'));
                        }
                    } else {
                        $profilePicture = public_path(config('settings.default_picture'));
                    }

                    }

            $type = pathinfo($profilePicture, PATHINFO_EXTENSION);
            $data = file_get_contents($profilePicture);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            $employeeRelation->employee->resortAdmin->profile_picture = $base64;
                }
            }

            $options                                    =   new Options();
            $options                                    ->set('isRemoteEnabled', true);
            $options                                    ->set('defaultFont', 'Poppins');

            // Convert Options Object to Array
            // $optionsArray                               =   [
            //     'isRemoteEnabled'                       =>  true,
            //     'defaultFont'                           =>  'Poppins'
            // ];
            // $pdf                                        =   Pdf::loadView('pdf.manifestdetailspdf', compact('ManifestListing'));
            // $pdf                                        ->setOptions($optionsArray);
            // $folderPath                                 =   public_path(config('settings.Manifestdetailspdf'));

            // if (!File::exists($folderPath)) {
            //     File::makeDirectory($folderPath, 0777, true, true);
            // }

            // $filePath                                   =   public_path(config('settings.Manifestdetailspdf').'/'. time() . '_manifests.pdf');
            //                                                 file_put_contents($filePath, $pdf->output());

            // $pdfUrl                                     =   asset(config('settings.Manifestdetailspdf').'/'. basename($filePath));

            return response()->json([
                'success'                               => true,
                // 'pdf_url'                               => $pdfUrl,
                'pdf_url'                               => '',
            ]);


        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());

            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function boardingPassStatusUpdate(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'manifest_type' => 'required|in:arrival,departure',
            'manifest_id'   => 'required',
            'status'        => 'required|in:departed,arrived',
            'date'          => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $manifestType = $request->manifest_type;
            $date = $request->date;
            $status = $request->status;
            $manifestId = $request->manifest_id;

            // Set query parameters based on manifest type
            $isDeparture = $manifestType === 'departure';
            // Same bug already fixed in the manifest-CREATE path (see the
            // comment there: "was previously any pass anywhere with a
            // matching arrival_date... now scoped to manifest_id") — this
            // approve step never got the same fix. Matching purely by
            // resort_id + date meant approving one manifest could silently
            // touch passes belonging to a DIFFERENT manifest sharing the
            // same date/type, and (as reported) failed entirely whenever a
            // manifest's own date happened not to exactly equal every
            // linked pass's date field. manifest_id is the actual FK
            // stamped onto the pass at creation for exactly this lookup.
            $travelPassQuery = EmployeeTravelPass::where('resort_id', $this->resort_id)
                ->where('manifest_id', $manifestId)
                ->where('status', 'Approved')
                ->where($isDeparture ? 'employee_departure_status' : 'employee_arrival_status', null);

            $EmployeeTravelPass = $travelPassQuery->get();

            if ($EmployeeTravelPass->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No travel pass found for this manifest.'
                ], 200);
            }

            // Manifest status to check and update
            $manifestStatus = $isDeparture ? 'saved' : 'confirmed';
            // dd($manifestStatus);
            $Manifest = Manifest::where('resort_id', $this->resort_id)
                ->where('manifest_type', $manifestType)
                ->where('status', 'saved')
                ->where('date', $date)
                ->where('id', $manifestId)
                ->first();

            if (empty($Manifest)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No manifest found for this date.'
                ], 200);
            }
            if($manifestType == 'departure'){
                $Manifest->status = 'closed';
            }

            if($manifestType == 'arrival'){
                $Manifest->status = 'confirmed';
            }
            $Manifest->save();

            // Update travel pass status
            foreach ($EmployeeTravelPass as $pass) {
                if ($isDeparture) {
                    $pass->employee_departure_status = $status;
                } else {
                    $pass->employee_arrival_status = $status;
                }
                $pass->save();
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ucfirst($manifestType) . ' manifest updated and passes marked as ' . $status . ' successfully.',
                'data' => $EmployeeTravelPass
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
     * Single Security-Officer assignment detail — which travel pass this
     * officer was assigned to escort/process, and who's travelling.
     * SODashboard() only returns the officer's aggregate list; the mobile
     * "Security Officer Details" screen needs one assignment in full.
     */
    public function SOAssignmentDetails($assignId)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $assignId                                   =   base64_decode($assignId);

        try {
            $assignment                              =   \App\Models\EmployeeTravelPassAssign::with([
                                                                'employeeTravelPasses:id,status,departure_date,departure_time,arrival_date,arrival_time,employee_id,employee_departure_status,employee_arrival_status',
                                                                'employeeTravelPasses.employee:id,Admin_Parent_id,Position_id',
                                                                'employeeTravelPasses.employee.resortAdmin:id,first_name,last_name,profile_picture',
                                                                'employeeTravelPasses.employee.position:id,position_title',
                                                            ])
                                                            ->where('resort_id', $this->resort_id)
                                                            ->where('id', $assignId)
                                                            ->first();

            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'Assignment not found.'], 200);
            }

            $officer                                 =   Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                                            ->where('employees.id', $assignment->employee_id)
                                                            ->select('employees.id', 'ra.id as Admin_Parent_id', 'ra.first_name', 'ra.last_name')
                                                            ->first();

            if ($officer) {
                $officer->profile_picture = Common::getResortUserPicture($officer->Admin_Parent_id);
            }

            $pass = $assignment->employeeTravelPasses;
            if ($pass && $pass->employee && $pass->employee->resortAdmin) {
                $pass->employee->resortAdmin->profile_picture = Common::getResortUserPicture($pass->employee->Admin_Parent_id);
            }

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Security officer assignment details fetched successfully.',
                'data'                               =>  [
                    'officer'                        =>  $officer,
                    'travel_pass'                    =>  $pass,
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}
