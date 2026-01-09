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
                                                            'ArrivalResortTransportation:id,resort_id,transportation_option'
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
                                                            ->take(2)
                                                            ->get();

            $rankConfig                             =   config('settings.Position_Rank');

            foreach ($EmployeeTravelPass as $pass) {
                foreach ($pass->employeeTravelPassStatusData as $item) {
                    $role                           =   ucfirst(strtolower($item->approver_rank ?? ''));
                    $item->rank_type                =   $rankConfig[$role] ?? '';
                }
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

            // Check for duplicate arrival or departure entry
            $existingPass                           =   EmployeeTravelPass::where('employee_id', $employee->id)
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
                                                                ->where('resort_id', $this->resort_id)->select('id', 'rank')
                                                                ->first();

                if ($SMApprover) {
                    $passApprovalFlow->push($SMApprover); // Fourth approver: Security Officer
                }

                // Add HR to the approval flow (rank 3)
                $hrApprover                             =   Employee::select('id', 'rank')->where('resort_id',$this->resort_id)->where('rank', 3)->first();
                if ($hrApprover) {
                    $passApprovalFlow->push($hrApprover); // Third approver: HR
                }

                // Add HOD to the approval flow (rank 2)
                $hodApprover                             =   Employee::select('id', 'rank')->where('rank', 2)->where('resort_id',$this->resort_id)->where('Dept_id', $employee->Dept_id)->first();
                if ($hodApprover ) {
                    $passApprovalFlow->push($hodApprover); // Second approver: HOD
                }

                // Add the same approval flow for Exit Pass as well
                $passApprovalFlow->each(function($approver) use ($boardingPass) {
                    EmployeeTravelPassStatus::create([
                        'travel_pass_id'                =>  $boardingPass->id,
                        'approver_id'                   =>  $approver->id,
                        'approver_rank'                 =>  $approver->rank,
                        'status'                        =>  'Pending',
                    ]);

                    // Common::sendMobileNotification(
                    //     $this->resort_id,
                    //     2,
                    //     null,
                    //     null,
                    //     'Boarding Pass Request',
                    //     'A boarding pass request has been submitted by ' . $this->user->first_name . ' ' . $this->user->last_name . '.',
                    //     'Boarding Pass',
                    //     [$approver->id],
                    //     null,
                    // );

                });

                DB::commit();

                $response['status']                     =   true;
                $response['message']                    =   'Pass submitted successfully';

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
                    $role                           =   ucfirst(strtolower($item->approver_rank ?? ''));
                    $item->rank_type                =   $rankConfig[$role] ?? '';
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

            $EmployeeTravelPass                     =  EmployeeTravelPass::join('employee_travel_pass_status as etps', 'etps.travel_pass_id', '=', 'employee_travel_passes.id')
                                                            ->whereIn('employee_travel_passes.employee_id', $this->underEmp_id)
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

            $EmployeeTravelPassReq                  =   EmployeeTravelPass::with([
                                                            'employeeTravelPassStatusData' => function($query) {
                                                                    $query->orderBy('id', 'desc');
                                                                },
                                                            'employee:id,Admin_Parent_id',
                                                            'employee.resortAdmin:id,first_name,last_name,profile_picture'
                                                            ])
                                                            ->whereIn('employee_id', $this->underEmp_id)
                                                            ->where('resort_id', $this->resort_id)
                                                            ->whereHas('employeeTravelPassStatusData', function($q) use ($currentRank) {
                                                                    $q->where('approver_rank', $currentRank)
                                                                    ->where('status', 'Pending');
                                                                })
                                                            ->where('status', 'Pending')
                                                            ->orderBy('created_at', 'desc')
                                                            ->get();

            $rankConfig                             =   config('settings.Position_Rank');

            foreach ($EmployeeTravelPassReq as $pass) {
                foreach ($pass->employeeTravelPassStatusData as $item) {
                    $role                           =   ucfirst(strtolower($item->approver_rank ?? ''));
                    $item->rank_type                =   $rankConfig[$role] ?? '';
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
                                                            'employee.resortAdmin:id,first_name,last_name,profile_picture'
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
                    $role                           =   ucfirst(strtolower($item->approver_rank ?? ''));
                    $item->rank_type                =   $rankConfig[$role] ?? '';
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
                                                            'employee.resortAdmin:id,first_name,last_name,profile_picture'
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
                    $role                           =   ucfirst(strtolower($item->approver_rank ?? ''));
                    $item->rank_type                =   $rankConfig[$role] ?? '';
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

    public function boardingPassView($passId)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $passId                                 =   base64_decode($passId, true);
            $EmployeeTravelPassView                 =   EmployeeTravelPass::with([
                                                            'employeeTravelPassStatusData',
                                                            'employee:id,Admin_Parent_id,Position_id',
                                                            'employee.resortAdmin:id,first_name,last_name,profile_picture',
                                                            'employee.position:id,position_title',
                                                            'DepartureResortTransportation:id,resort_id,transportation_option',
                                                            'ArrivalResortTransportation:id,resort_id,transportation_option'
                                                            ])
                                                            ->where('resort_id', $this->resort_id)
                                                            ->where('id', $passId)
                                                            ->first();


                $rankConfig                         =   config('settings.Position_Rank');
                foreach ($EmployeeTravelPassView->employeeTravelPassStatusData as $item) {
                    $role                           =   ucfirst(strtolower($item->approver_rank ?? ''));
                    $item->rank_type                =   $rankConfig[$role] ?? '';
                }


            // Replace profile_picture with url
            $EmployeeTravelPassView->each(function ($pass) {
                $resortAdmin                        =   $pass->employee->resortAdmin ?? null;
                if ($resortAdmin) {
                    $resortAdmin->profile_picture   =   Common::getResortUserPicture($resortAdmin->id);
                }
            });

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Employee boarding pass details fetched successfully',
                'borading_pass_details'             =>  $EmployeeTravelPassView
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
            'action'                                =>  'required',
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
                    return response()->json([
                        'success'                   =>  true,
                        'message'                   =>  'This travel pass has already been fully approved.',
                    ], 200);
                }

            $rankConfig                             =   config('settings.Position_Rank');
            $currentApproverRank                    =   array_key_exists($employee->rank, $rankConfig) ? $rankConfig[$employee->rank] : '';
            $lastApproverRank                       =   array_key_exists($employeeTravelPassStatus->approver_rank, $rankConfig) ? $rankConfig[$employeeTravelPassStatus->approver_rank] : '';

            $actionname                             =   ($action == "Rejected") ? "reject" : "approve";

            if ($employeeTravelPassStatus && $employeeTravelPassStatus->approver_id != $currentApproverId) {
                return response()->json([
                    'status'                        =>  false,
                    'message'                       =>  "You cannot $actionname this request. The request must first be approved by the $lastApproverRank.",
                ], 200);
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
                    null,
                );

            if ($action == 'Approved') {

                if ($employeeTravelPassStatus->approver_rank == 8) {
                    $employeeTravelPasses->status   =   "Approved";
                    $employeeTravelPasses->save();
                }
                return response()->json([
                    'status'                        =>  true,
                    'isAssigned'                    =>  true,
                    'message'                       =>  'Boarding pass approved successfully.',
                ]);
            } elseif ($action === 'Rejected') {
                $employeeTravelPasses->status       =   "Rejected";
                $employeeTravelPasses->save();

                return response()->json([
                    'status'                        =>  true,
                    'message'                       =>  'Boarding Pass Rejected.',
                ], 200);
            } else {
                return response()->json([
                    'status'                        =>  false,
                    'message'                       =>  'Invalid action.',
                ], 200);
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

            // Build query based on user rank
            if($employee->rank == 2) {
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

    public function transportationDateBasedEmp(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
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
                                                        ->where('status', 'Approved');
            // Filter by arrival or departure
            if ($request->type === 'arrival') {
                $query->where('arrival_date', $request->date)->where('arrival_mode', $request->transportation_id);
            } else {
                $query->where('departure_date', $request->date)->where('departure_mode', $request->transportation_id);
            }

            $employeeTravelPasses                   =   $query->get();

            $employees                              =   $employeeTravelPasses->pluck('employee')->map(function ($employee) {
                if ($employee && $employee->resortAdmin) {
                    return [
                        'id'                        =>  $employee->id,
                        'first_name'                =>  $employee->resortAdmin->first_name,
                        'last_name'                 =>  $employee->resortAdmin->last_name,
                        'full_name'                 =>  $employee->resortAdmin->full_name,
                    ];
                }
                return null;
            })->values();

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

         // Validate request data
         $validator = Validator::make($request->all(), [
            'manifest_type'                     => 'required|in:arrival,departure',
            'transportation_mode'               => 'required|string',
            'transportation_name'               => 'required|string',
            'date'                              => 'required|date',
            'time'                              => 'required',
            'employee_ids'                      => 'required|array',
            'visitors'                          => 'array',
            'visitors.*'                        => 'string',
        ]);

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

                EmployeeTravelPass::where('arrival_date',$request->date)->update([
                    'arrival_time'                      =>  $request->time,
                ]);
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

                EmployeeTravelPass::where('departure_date',$request->date)->update([
                    'departure_time'                    =>  $request->time,
                ]);
            }

            // Attach employees
            if ($request->has('employee_ids')) {
                foreach ($request->employee_ids as $empId) {
                    ManifestEmployee::create([
                        'manifest_id'               =>  $manifest->id,
                        'employee_id'               =>  $empId,
                    ]);

                    Common::sendMobileNotification(
                        $this->resort_id,
                        2,
                        null,
                        null,
                        $request->transportation_mode . ' ' . $request->manifest_type,
                        $request->transportation_mode . ' '  . $request->date . ' at ' . $request->time . ' has been ' . $request->manifest_type . '.',
                        'Boarding Pass',
                        $request->employee_ids,
                        null,
                    );
                }
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
            $EmployeeTravelPassStatus               =   EmployeeTravelPassStatus::where('travel_pass_id',$data['pass_id'])->get();
            $hasApproved                            =   $EmployeeTravelPassStatus->contains('status', 'Approved');

            if ($hasApproved) {
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

            // Cancel the travel pass and all its approver statuses
            $travelPass->update(['status' => 'Cancel']);
            EmployeeTravelPassStatus::where('travel_pass_id',$passId)->update(['status' => 'Cancel']);

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

    public function manifestListing(Request $request)
    {

        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
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
            $travelPassQuery = EmployeeTravelPass::where('resort_id', $this->resort_id)
                ->where($isDeparture ? 'departure_date' : 'arrival_date', $date)
                ->where('status', 'Approved')
                ->where($isDeparture ? 'employee_departure_status' : 'employee_arrival_status', null);

            $EmployeeTravelPass = $travelPassQuery->get();

            if ($EmployeeTravelPass->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No travel pass found for this date.'
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
}
