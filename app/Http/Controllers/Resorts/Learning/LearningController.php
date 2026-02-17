<?php
namespace App\Http\Controllers\Resorts\Learning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\ResortPosition;
use App\Models\LearningProgram;
use Illuminate\Validation\Rule;
use App\Models\LearningCategory;
use App\Models\ResortDepartment;
use App\Models\LearningMaterials;
use App\Models\LearningRequest;
use App\Models\LearningRequestEmployee;
use App\Models\LearningCalendarSession;
use App\Events\ResortNotificationEvent;
use Illuminate\Support\Facades\Validator;
use DB;
use Auth;
use Common;
use DateTime;
use Carbon\Carbon;

class LearningController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            $this->reporting_to = $this->resort->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($this->reporting_to);
        }
    }

    public function index()
    {
        if(Common::checkRouteWisePermission('learning.request.add',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
        }

        $resort_id = $this->resort->resort_id;
        $page_title ='Add Learning Request';
        $programs= LearningProgram::where('resort_id',$resort_id)->get();
        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        $isHOD = ($available_rank === "HOD");
        $isHR = ($available_rank === "HR");
        $employees_query = Employee::with(['resortAdmin','department','position'])->where('resort_id',$resort_id)->whereIn('status', ['Active', 'Probationary']);

        if ($isHR) {
            $employees_query->where('employees.id', '!=', $this->resort->getEmployee->id);
        }
        else{
            $employees_query->where('employees.reporting_to',$this->reporting_to );
        }
        $employees = $employees_query->get();
        // dd($employees);

        $trainingManagerTitles = ['Training Director', 'L&D Manager', 'Learning & Development Head'];

        // Get position IDs that match the titles in the current resort
        $positionIds = ResortPosition::where('resort_id', $resort_id)
                        ->whereIn('position_title', $trainingManagerTitles)
                        ->pluck('id'); // Get the position IDs

        // Get employees who hold these positions in the current resort
        $learningManagers = Employee::with(['resortAdmin','position'])->whereIn('Position_id', $positionIds)
                        ->where('resort_id', $resort_id)
                        ->get();
        // dd($learningManagers);
        return view('resorts.learning.request.add',compact('page_title','programs','employees','learningManagers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|json',
            'suggested_Learning' => 'required|exists:learning_programs,id',
            'reason' => 'required|string|max:255',
            'learning_manager' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $employeeIds = json_decode($request->employee_ids, true);
        $learningManagerId = $request->learning_manager;

        if (empty($employeeIds)) {
            return response()->json(['success' => false, 'msg' => 'Please select at least one employee.']);
        }

        // ✅ Fetch Learning Program Name for Better Notification
        $learningProgram = LearningProgram::find($request->suggested_Learning);
        $learningProgramName = $learningProgram ? $learningProgram->name : "Learning Program";

        // ✅ Ensure no duplicate learning request exists
        $learningRequest = LearningRequest::updateOrCreate(
            [
                'resort_id' => $this->resort->resort_id,
                'learning_id' => $request->suggested_Learning,
                'learning_manager_id' => $learningManagerId,
            ],
            [
                'reason' => $request->reason,
                'start_date' => Carbon::parse($request->start_date)->format('Y-m-d'),
                'end_date' => Carbon::parse($request->end_date)->format('Y-m-d'),
                'status' => 'pending',
            ]
        );

        // ✅ Remove old employees & insert new ones to avoid duplicates
        LearningRequestEmployee::where('learning_request_id', $learningRequest->id)->delete();
        
        foreach ($employeeIds as $employeeId) {
            LearningRequestEmployee::create([
                'learning_request_id' => $learningRequest->id,
                'employee_id' => $employeeId,
            ]);
        }

        // ✅ Improved Notification Message
        $notificationTitle = 'New Learning Request';
        $notificationMessage = "A new learning request for **'{$learningProgramName}'** has been submitted for review.  
                                **Dates:** {$request->start_date} to {$request->end_date}  
                                **Employees:** " . count($employeeIds) . " participants.";

        $moduleName = "Learning";

        event(new ResortNotificationEvent(Common::nofitication(
            $this->resort->resort_id, 
            10, 
            $notificationTitle, 
            $notificationMessage, 
            'Learning', 
            $learningManagerId, 
            $moduleName
        )));

        return response()->json([
            'success' => true, 
            'msg' => 'Learning request submitted successfully!',
            'redirect_url' => url('learning.request.index')
        ]);
    }

    public function request(){
        
        if(Common::checkRouteWisePermission('learning.request.add',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title = "Learning Requests";
        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        $isHOD = ($available_rank === "HOD");
        $isHR = ($available_rank === "HR");
        $isManager = (!$isHR && !$isHOD); // If not HR or HOD, assume it's the Learning Manager
        // dd($isManager);
        return view('resorts.learning.request.index',compact('page_title','isManager'));
    }

    public function list(Request $request)
    {
        try {
            $resort_id = $this->resort->resort_id;
            $loginEmployee = $this->resort->GetEmployee->id;

            $rank = config('settings.Position_Rank');
            $current_rank = $this->resort->getEmployee->rank ?? null;
            $available_rank = $rank[$current_rank] ?? '';
            $isHOD = ($available_rank === "HOD");
            $isHR = ($available_rank === "HR");
            $isManager = (!$isHR && !$isHOD); // If not HR or HOD, assume it's the Learning Manager
            // dd($isManager);
            // Fetch Learning Requests
            $query = LearningRequest::select(
                'learning_requests.id',
                'learning_requests.learning_id',
                'learning_requests.reason',
                'learning_requests.start_date',
                'learning_requests.end_date',
                'learning_requests.status',
                'learning_requests.created_at',
                'learning_programs.name as learning_name',

                DB::raw("GROUP_CONCAT(CONCAT(resort_admins.first_name, ' ', resort_admins.last_name) SEPARATOR ', ') as employee_names")
            )
            ->leftJoin('learning_programs', 'learning_requests.learning_id', '=', 'learning_programs.id')
            ->leftJoin('learning_requests_employees', 'learning_requests.id', '=', 'learning_requests_employees.learning_request_id')
            ->leftJoin('employees', 'learning_requests_employees.employee_id', '=', 'employees.id')
            ->leftJoin('resort_admins', 'resort_admins.id', '=', 'employees.Admin_Parent_id')
            ->where('learning_requests.resort_id', $resort_id);

            if ($isHR || $isHOD) {
                $query->where('learning_requests.created_by', $this->resort->GetEmployee->Admin_Parent_id);
            } else {
                $query->where('learning_requests.learning_manager_id', $loginEmployee);
            }

            $query->groupBy('learning_requests.id');

            // Apply search
            if ($request->searchTerm) {
                $searchTerm = $request->searchTerm;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('learning_programs.name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('learning_requests.reason', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('learning_requests.status', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('learning_requests.start_date', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('learning_requests.end_date', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('resort_admins.first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('resort_admins.last_name', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Filter by status
            if ($request->status) {
                $query->where('learning_requests.status', $request->status);
            }

            $requests = $query->get();

            return datatables()->of($requests)
                ->addColumn('learning_name', fn($row) => $row->learning_name ?? 'N/A')
                ->addColumn('employees', fn($row) => $row->employee_names ?? 'N/A')
                ->addColumn('reason', fn($row) => $row->reason ?? 'N/A')
                ->addColumn('start_date', fn($row) => $row->start_date ?? 'N/A')
                ->addColumn('end_date', fn($row) => $row->end_date ?? 'N/A')

                // Display Status Column (Always Visible)
                ->addColumn('status', function ($row) {
                    $badgeClass = ($row->status == 'Approved') ? 'success' : (($row->status == 'Denied') ? 'danger' : 'warning');
                    return '<span class="badge badge-' . $badgeClass . '">' . ucfirst($row->status) . '</span>';
                })

                // Conditionally Display Action Buttons (Only for Managers)
                ->addColumn('action', function ($row) use ($isManager) {
                    if (!$isManager) return ''; // Hide actions for HR & HOD

                    $approveBtn = '<button class="btn btn-themeBlue btn-sm" onclick="updateLearningRequestStatus(' . $row->id . ', \'Approved\')">Approve</button>';
                    $onHoldBtn = '<button class="btn btn-sm btn-warning" onclick="updateLearningRequestStatus(' . $row->id . ', \'On Hold\')">On Hold</button>';
                    $rejectBtn = '<button class="btn btn-danger btn-sm" onclick="rejectLearningRequest(' . $row->id . ')">Deny</button>';

                    return ($row->status == 'Pending' || $row->status == 'On Hold') ? $approveBtn . ' ' . $onHoldBtn . ' ' . $rejectBtn : '';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            \Log::error("Error fetching Learning Requests: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
    }

    public function updateStatus(Request $request)
    {
        try {
            $request->validate([
                'request_id' => 'required|exists:learning_requests,id',
                'status' => 'required|in:Approved,Denied,On Hold',
                'reason' => 'nullable|string'
            ]);

            $learningRequest = LearningRequest::find($request->request_id);

            // ✅ Ensure the request exists
            if (!$learningRequest) {
                \Log::error("Learning Request not found: ID " . $request->request_id);
                return response()->json(['error' => 'Learning request not found.'], 404);
            }

            $learningRequest->status = $request->status;

            // ✅ Fetch the Sender (Who Created the Learning Request)
            $sender = DB::table('resort_admins')
                ->where('id', $learningRequest->created_by)
                ->select('id')
                ->first();

            if (!$sender) {
                \Log::error("Sender not found for request ID " . $request->request_id);
                return response()->json(['error' => 'Sender not found.'], 404);
            }

            // ✅ Save rejection reason if Denied or On Hold
            if ($request->status === 'Denied' || $request->status === 'On Hold') {
                $learningRequest->rejection_reason = $request->reason;
            } else {
                $learningRequest->rejection_reason = null; // Clear reason if approved
            }

            $learningRequest->save();

            // ✅ Fetch Learning Program Name
            $learningProgram = LearningProgram::find($learningRequest->learning_id);
            $trainingName = $learningProgram ? $learningProgram->name : "Learning Program";

            // ✅ Notify Request Creator (Sender)
            $notificationTitle = 'Learning Request Update';
            $notificationMessage = match ($request->status) {
                'Approved' => "✅ **Good news!** Your learning request for **'{$trainingName}'** has been **approved!**  
                            📅 **Training Dates:** {$learningRequest->start_date} - {$learningRequest->end_date}  
                            📍 **Check your schedule for details.**",
                'Denied' => "❌ Your learning request for **'{$trainingName}'** has been **denied.**  
                            📌 **Reason:** {$request->reason}",
                'On Hold' => "⏳ Your learning request for **'{$trainingName}'** is **on hold.**  
                            📌 **Reason:** {$request->reason}",
                default => "Your learning request for **'{$trainingName}'** has been updated."
            };

            $moduleName = "Learning";

            event(new ResortNotificationEvent(Common::nofitication(
                $this->resort->resort_id, 
                10, 
                $notificationTitle, 
                $notificationMessage, 
                'Learning', 
                $sender->id, 
                $moduleName
            )));

            // ✅ Notify Selected Employees (Only If Approved)
            if ($request->status === 'Approved') {
                $employees = DB::table('learning_requests_employees')
                    ->join('employees', 'learning_requests_employees.employee_id', '=', 'employees.id')
                    ->where('learning_requests_employees.learning_request_id', $learningRequest->id)
                    ->select('employees.id')
                    ->get();

                foreach ($employees as $emp) {
                    $notificationTitle = '🎉 New Learning Assignment!';
                    $notificationMessage = "🎉 **Congratulations!** 🎉  
                                            You are selected for **'{$trainingName}'**.  
                                            📅 **Training Dates:** {$learningRequest->start_date} - {$learningRequest->end_date}  
                                            📍 **Check your schedule and be prepared!**";

                    event(new ResortNotificationEvent(Common::nofitication(
                        $this->resort->resort_id, 
                        10, 
                        $notificationTitle, 
                        $notificationMessage, 
                        'Learning', 
                        $emp->id, 
                        $moduleName
                    )));
                }
            }

            return response()->json(['message' => 'Status updated and notifications sent successfully.']);

        } catch (\Exception $e) {
            \Log::error("Error updating status: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update status: ' . $e->getMessage()], 500);
        }
    }

    public function schedule() {
        $page_title = "Learning Schedule";
        return view('resorts.learning.schedule.index',compact('page_title'));
    }

}
?>