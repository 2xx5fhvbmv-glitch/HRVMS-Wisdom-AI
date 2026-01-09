<?php

namespace App\Http\Controllers\Resorts\Leave;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use App\Models\ResortDepartment;
use App\Models\EmployeeTravelPass;
use App\Models\EmployeeTravelPassStatus;
use Auth;
use DB;
use Common;
use Config;
use Carbon\Carbon;
use App\Events\ResortNotificationEvent;
class BoardingPassController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $this->reporting_to = $this->resort->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($this->reporting_to);
        }
    }
    
    public function index(Request $request)
    {
        $resort_id = $this->resort->resort_id;

        $searchInput = $request->searchInput;
        $datefilter= $request->datefilter;
        $query = EmployeeTravelPass::with([
            'employee',
            'ResortTransportation',
            'employeeTravelPassStatusData' => function ($q) {
                // We'll load all status data for use in display logic
                $q->orderBy('created_at', 'desc');
            }
        ])
        ->where('status', 'Approved')
        ->where('resort_id', $resort_id)
        ->whereHas('employeeTravelPassStatusData', function($q) {
            // Confirm rank 2 is approved
            $q->where('status', 'Approved')
              ->where('approver_rank', 2);
        });
        
        if (!empty($datefilter)) {
            $formattedDate = \Carbon\Carbon::createFromFormat('d/m/Y', $datefilter)->format('Y-m-d');
        
            $query->where(function ($q) use ($formattedDate) {
                $q->whereDate('arrival_date', $formattedDate)
                  ->orWhereDate('departure_date', $formattedDate);
            });
        }
        
        if (!empty($searchInput)) {
            $query->where(function ($q) use ($searchInput) {
                $q->whereHas('employee', function ($q2) use ($searchInput) {
                    $q2->where(function($subQ) use ($searchInput) {
                        $subQ->where('Emp_id', 'like', "%{$searchInput}%")
                            ->orWhereHas('resortAdmin', function ($adminQ) use ($searchInput) {
                                $adminQ->where(function($innerQ) use ($searchInput) {
                                    $innerQ->where('first_name', 'like', "%{$searchInput}%")
                                            ->orWhere('last_name', 'like', "%{$searchInput}%")
                                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchInput}%"]);
                                });
                            });
                    });
                })
                ->orWhereHas('ResortTransportation', function ($q3) use ($searchInput) {
                    $q3->where('transportation_option', 'like', "%{$searchInput}%");
                });
            });
        }
        
        // Group by travel_pass_id to get the latest status for each pass
        $query = $query->get()->map(function ($passData) {
            $Rank = config('settings.Position_Rank');
            
            // Group statuses by rank for easier access and get the latest record for each rank
            $statusByRank = collect($passData->employeeTravelPassStatusData)
                ->sortByDesc('created_at')
                ->groupBy('approver_rank');
            
            // Set approval information based on latest status for each rank
            $rank2Status = $statusByRank->get('2', collect())->first();
            $rank3Status = $statusByRank->get('3', collect())->first();
            $rank4Status = $statusByRank->get('4', collect())->first();
            
            // Determine the current highest approved rank
            $highestApprovedRank = null;
            if ($rank4Status && $rank4Status->status == 'Approved') {
                $highestApprovedRank = 4;
            } elseif ($rank3Status && $rank3Status->status == 'Approved') {
                $highestApprovedRank = 3;
            } elseif ($rank2Status && $rank2Status->status == 'Approved') {
                $highestApprovedRank = 2;
            }
            
            // Add approval information to the pass data
            $passData->isRank2Approved = $rank2Status && $rank2Status->status == 'Approved';
            $passData->isRank3ApprovedOrPending = $rank3Status && in_array($rank3Status->status, ['Approved', 'Pending']);
            $passData->isRank4Pending = $rank4Status && $rank4Status->status == 'Pending';
            $passData->isRank4Approved = $rank4Status && $rank4Status->status == 'Approved';
            $passData->highestApprovedRank = $highestApprovedRank;
            
            if ($highestApprovedRank && isset($Rank[$highestApprovedRank])) {
                $passData->ApprovedRank = $Rank[$highestApprovedRank];
            } else {
                $passData->ApprovedRank = '';
            }
        
            // Add other needed fields
            $passData->EmpId = $passData->employee->Emp_id;
            $passData->Transportation = isset($passData->ResortTransportation) ? $passData->ResortTransportation->transportation_option : ' - ';
            $passData->EmployeeName = $passData->employee->resortAdmin->first_name.' '.$passData->employee->resortAdmin->last_name;
            $passData->arrival_date = date('d-m-Y', strtotime($passData->arrival_date));
            $passData->departure_date = date('d-m-Y', strtotime($passData->departure_date));
            $passData->arrival_time1 = Carbon::parse($passData->arrival_time)->format('h:i A');
            $passData->departure_time1 = Carbon::parse($passData->departure_time)->format('h:i A');
            
            return $passData;
        });
        
        if($request->ajax()) {
            $edit_class = '';
            if(Common::checkRouteWisePermission('resort.boardingpass.list',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
            return datatables()->of($query)
                ->addColumn('EmpId', fn($row) => $row->EmpId)
                ->addColumn('EmployeeName', fn($row) => $row->EmployeeName)
                ->addColumn('Transportation', fn($row) => $row->Transportation)
                ->addColumn('ArrivalDate', fn($row) => $row->arrival_date)
                ->addColumn('ArrivalTime', fn($row) => $row->arrival_time1)
                ->addColumn('DepartureDate', fn($row) => $row->departure_date ?? 'N/A')
                ->addColumn('DepartureTime', fn($row) => $row->departure_time1 ?? 'N/A')
        
                // Display Status Column with the highest approved rank
                ->addColumn('Status', function ($row) {
                    $badgeClass = ($row->status == 'Approved') ? 'success' : (($row->status == 'Denied') ? 'danger' : 'warning');
                    return '<span class="badge badge-' . $badgeClass . '">' . ucfirst($row->status) . ' ' . $row->ApprovedRank . '</span>';
                })
        
                // Action buttons logic based on the approval flow
                ->addColumn('Action', function ($row) use ($edit_class) {
                    // Get specific rank statuses
                    $rank3Status = collect($row->employeeTravelPassStatusData)
                        ->where('approver_rank', 3)
                        ->sortByDesc('created_at')
                        ->first();
                        
                    $rank4Status = collect($row->employeeTravelPassStatusData)
                        ->where('approver_rank', 4)
                        ->sortByDesc('created_at')
                        ->first();
                    
                    // Show process completed if rank 4 is approved
                    if ($rank4Status && $rank4Status->status == 'Approved') {
                        return '<span class="badge badge-success">The pass process is complete.</span>';
                    }
                    
                    // For rank 3 pending/approved AND rank 4 pending - allow edit action
                    if ($rank3Status && in_array($rank3Status->status, ['Approved']) && 
                        $rank4Status && $rank4Status->status == 'Pending') {
                        return '<button class="btn btn-themeBlue btn-sm edit-row-btn '.$edit_class.'" data-flag="Approved" data-arrival_time="'.$row->arrival_time.'" data-departure_time="'.$row->departure_time.'" data-id="'.base64_encode($row->id).'">Edit</button>';
                    }


                    
                    // If rank 3 is pending, show only Approve and Reject buttons
                    if ($rank3Status && $rank3Status->status == 'Pending') {
                        return '<a href="javascript:void(0)" class="btn btn-sm btn-theme update-row-btn_agent '.$edit_class.' " data-flag="Approved" data-id="'.base64_encode($row->id).'">Approve</a>
                        <button class="btn btn-danger btn-sm SendPass '.$edit_class.'" data-flag="Rejected" data-id="'.base64_encode($row->id).'" >Reject</button>';
                    }
                    
                    // If rank 3 is approved but rank 4 is not pending, show only message
                    if ($rank3Status && $rank3Status->status == 'Approved' && 
                        (!$rank4Status || $rank4Status->status != 'Pending')) {
                        return '<span class="badge badge-warning">Waiting for next approval step.</span>';
                    }
                    
                    // For all other states, show appropriate message
                    return '<span class="badge badge-warning">Waiting for proper approval sequence.</span>';
                })
                ->rawColumns(['EmpId','EmployeeName','Transportation','ArrivalDate','ArrivalTime','DepartureDate','DepartureTime','Status','Action'])
                ->make(true);
        }
        $page_title = "Boarding Pass Requests";
        return view('resorts.leaves.boarding-pass.request',compact('page_title'));
       
       
                try 
                { } catch (\Exception $e) {
            \Log::error("Error fetching Learning Requests: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
    }

    public function BoardingPassStatusUpdate(Request $request)
    {
        $flag = $request->flag;

        
        $passId                                 =    base64_decode($request->id);

        $action                                 =   $request->flag;
        $comments                               =   $request->input('reason', null); // Optional comments
        $employee                               =   $this->resort->GetEmployee;
        $currentApproverId                      =   $employee->id; // Assuming the logged-in user is the approver
        $employeeTravelPasses                   =   EmployeeTravelPass::find($passId);
        $employeeTravelPassStatus               =   EmployeeTravelPassStatus::where('travel_pass_id', $passId)
                                                        ->where('status', 'Pending')
                                                        ->orderBy('created_at', 'desc')

                                                        ->first();
                                      
             
        // $rankConfig                             =   config('settings.Position_Rank');
        // $currentApproverRank                    =   array_key_exists($employee->rank, $rankConfig) ? $rankConfig[$employee->rank] : '';
        // $lastApproverRank                       =   array_key_exists($employeeTravelPassStatus->approver_rank, $rankConfig) ? $rankConfig[$employeeTravelPassStatus->approver_rank] : '';
        // $actionname                             =   ($action == "Approved") ?  "approve":"reject" ;
        // if ($employeeTravelPassStatus && $employeeTravelPassStatus->approver_id != $currentApproverId) 
        // {
        //     return response()->json([
        //         'status'                        =>  'error',
        //         'message'                       =>  "You cannot $actionname this request. The request must first be approved by the $lastApproverRank.",
        //     ], 403);
        // }

        EmployeeTravelPassStatus::where('travel_pass_id', $employeeTravelPasses->id)->where('approver_id', $currentApproverId)->update([
            'approver_id'                       =>  $currentApproverId,
            'status'                            =>  $action,
            'comments'                          =>  $comments, // Save comments if provided
            'approved_at'                       =>  now(),
        ]);

        $allApproved                            =   EmployeeTravelPassStatus::where('travel_pass_id', $employeeTravelPasses->id)
                                                        ->where('status', '!=', 'Approved')
                                                        ->doesntExist();

        if(isset($employeeTravelPasses->employee_id) && $action  == "Approved")
        {
            $msg = 'Your Boarding Pass has been Approved By HR';
            $title = ' Boarding Pass';
            $ModuleName = "Leave Management";
            event(new ResortNotificationEvent(Common::nofitication($this->resort->resort_id, 10,$title,$msg,0,$employeeTravelPasses->employee_id,$ModuleName)));
        }
        if ($allApproved) 
        {
            EmployeeTravelPass::where('id', $employeeTravelPasses->id)->update(['status' => $action]);
        }
        if($action  == "Approved")
        {
            $pass= EmployeeTravelPass::findOrFail($employeeTravelPasses->id);
            if ($request->has('d_time')) 
            {
               $pass->departure_time  = $request->d_time;
            }
            if ($request->has('a_time')) 
            {
                $pass->arrival_time = $request->a_time;
            }
            $pass->save();
        }
        if ($action == 'Approved') 
        {
            if ($employeeTravelPassStatus->approver_rank == 8) 
            {
                $employeeTravelPasses->status   =   "Approved";
                $employeeTravelPasses->save();
            }
            return response()->json([
                'success'                   =>  true,
                'message'                   =>  'Boarding pass approved successfully.',
            ], 200);
        } 
        elseif ($action === 'Rejected') 
        {
            $employeeTravelPasses->status       =   "Rejected";
            $employeeTravelPasses->save();


            $msg = 'Your Boarding Pass has been Rejected By HR';
            $title = ' Boarding Pass';
            $ModuleName = "Leave Management";
            event(new ResortNotificationEvent(Common::nofitication($this->resort->resort_id, 10,$title,$msg,0,$employeeTravelPasses->employee_id,$ModuleName)));
            return response()->json([
                'success'                   =>  true,
                'message'                   =>  'Boarding pass rejected successfully.',
            ], 200);
        } 
        else 
        {
            return response()->json([
                'status'                        =>  false,
                'message'                       =>  'Invalid action.',
            ], 200);
        }

    }
}
