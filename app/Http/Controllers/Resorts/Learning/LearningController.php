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
        $isGM  = ($available_rank === "GM");

        // Position titles with full resort-wide access (L&D leadership, HR leadership, GM).
        $emp = $this->resort->getEmployee;
        $currentPositionTitle = optional(optional($emp)->position)->position_title;
        $isFullAccessTitle = in_array($currentPositionTitle, Common::fullAccessPositionTitles(), true);

        // Anyone in the L&D department gets full module visibility — title is often
        // misconfigured, dept is the reliable signal.
        $isInLDDept = $emp && Common::isLDDepartment($emp->Dept_id ?? null);

        // HR identity = rank 3 (HR) or any rank in [1,2] inside the HR department —
        // these users get DEPARTMENT-only scope (unless their title is in the full-access list).
        $isHrIdentity = ($available_rank === "HR")
            || ($emp && in_array((int) $emp->rank, [1, 2], true) && Common::isHRDepartment($emp->Dept_id ?? null));

        $isAdmin = (($this->resort->type ?? null) === 'super') || ($this->resort->is_master_admin ?? 0);

        // Resort-wide: GM rank, full-access titles, L&D-department members, or super/master admin.
        $hasResortWideAccess = $isAdmin || $isGM || $isFullAccessTitle || $isInLDDept;

        $employees_query = Employee::with(['resortAdmin','department','position'])
            ->where('resort_id', $resort_id)
            ->whereIn('status', ['Active', 'Probationary']);

        if ($hasResortWideAccess) {
            // Exclude self when the auth user has an employee record.
            $authEmpId = optional($emp)->id;
            if ($authEmpId) {
                $employees_query->where('employees.id', '!=', $authEmpId);
            }
        } elseif ($isHrIdentity && optional($emp)->Dept_id) {
            // HR sees their own department's employees (excluding self).
            $employees_query->where('Dept_id', $emp->Dept_id);
            $employees_query->where('employees.id', '!=', $emp->id);
        } else {
            // Everyone else (regular Manager rank, etc.) sees their reporting tree.
            $scopedDeptIds = Common::getScopedDepartmentIds();
            $employees_query->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds));
            $employees_query->where('employees.reporting_to', $this->reporting_to);
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
            // End date temporarily optional — UI field is hidden; column is nullable.
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $employeeIds = json_decode($request->employee_ids, true);
        $learningManagerId = $request->learning_manager;

        if (empty($employeeIds)) {
            return response()->json(['success' => false, 'msg' => 'Please select at least one employee.']);
        }

        // ✅ Fetch Learning Program Name for Better Notification
        $learningProgram = LearningProgram::find($request->suggested_Learning);
        $learningProgramName = $learningProgram ? $learningProgram->name : "Learning Program";

        // L&D Managers / HR / GM raising requests for their own team don't need
        // an approval round-trip — auto-approve their submission.
        $rankConfig = config('settings.Position_Rank');
        $currentRank = $this->resort->getEmployee->rank ?? null;
        $availableRank = $rankConfig[$currentRank] ?? '';
        $ldManagerTitles = ['Training Director', 'L&D Manager', 'Learning & Development Head'];
        $currentPositionTitle = optional(optional($this->resort->getEmployee)->position)->position_title;
        $autoApprove = ($availableRank === 'HR'
            || $availableRank === 'GM'
            || in_array($currentPositionTitle, $ldManagerTitles, true));

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
                'end_date' => $request->filled('end_date') ? Carbon::parse($request->end_date)->format('Y-m-d') : null,
                'status' => $autoApprove ? 'Approved' : 'Pending',
                'created_by' => $this->resort->id,
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

        // Notification — wrap in try/catch so a notify failure can't kill the submit.
        // (Earlier code passed the literal string 'Learning' as request_id; the column is
        //  int(11), so MySQL strict mode rejected the insert and 500'd the entire request.)
        try {
            $title = 'New Learning Request';
            $datesText = $request->filled('end_date')
                ? "Dates: {$request->start_date} to {$request->end_date}."
                : "Expected start: {$request->start_date}.";
            $message = "A new learning request for '{$learningProgramName}' has been submitted for review. "
                . $datesText
                . " Employees: " . count($employeeIds) . " participants.";
            Common::notifyEmployees(
                $this->resort->resort_id,
                [(int) $learningManagerId],
                $title,
                $message,
                'Learning',
                $learningRequest->id
            );
        } catch (\Exception $ne) {
            \Log::warning('Learning request notification failed: ' . $ne->getMessage());
        }

        return response()->json([
            'success' => true,
            'msg' => 'Learning request submitted successfully!',
            // url() doesn't resolve route names — use route() so the redirect actually lands on the requests list.
            'redirect_url' => route('learning.request.index'),
        ]);
    }

    public function request(){

        if(Common::checkRouteWisePermission('learning.request.add',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title = "Learning Requests";
        $emp = $this->resort->getEmployee;
        $ldManagerTitles = ['Training Director', 'L&D Manager', 'Learning & Development Head'];
        $currentPositionTitle = optional(optional($emp)->position)->position_title;
        $isLdManager = in_array($currentPositionTitle, $ldManagerTitles, true)
            || ($emp && Common::isLDDepartment($emp->Dept_id ?? null));
        $isAdmin = (($this->resort->type ?? null) === 'super') || ($this->resort->is_master_admin ?? 0);
        // Approve / On Hold / Deny actions are available ONLY to L&D Manager (or
        // super/master admin). HR and GM can SEE the requests but not act on them.
        $isManager = ($isLdManager || $isAdmin);
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
            $emp = $this->resort->getEmployee;
            $ldManagerTitles = ['Training Director', 'L&D Manager', 'Learning & Development Head'];
            $currentPositionTitle = optional(optional($emp)->position)->position_title;
            $isLdManager = in_array($currentPositionTitle, $ldManagerTitles, true)
                || ($emp && Common::isLDDepartment($emp->Dept_id ?? null));
            $isAdmin = (($this->resort->type ?? null) === 'super') || ($this->resort->is_master_admin ?? 0);
            // Only L&D Manager (or super/master admin) can Approve / Hold / Deny.
            // Visibility scope is separate — getPerformanceScopedEmpIds() filters
            // which requests the user can see.
            $isManager = ($isLdManager || $isAdmin);
            $scopedEmpIds = Common::getPerformanceScopedEmpIds();
            // Fetch Learning Requests
            $query = LearningRequest::select(
                'learning_requests.id',
                'learning_requests.learning_id',
                'learning_requests.reason',
                'learning_requests.rejection_reason',
                'learning_requests.start_date',
                'learning_requests.end_date',
                'learning_requests.status',
                'learning_requests.created_at',
                'learning_programs.name as learning_name',

                // Creator (resort_admin who submitted the request) — second join below.
                DB::raw("CONCAT(creator.first_name, ' ', creator.last_name) as requested_by"),

                DB::raw("GROUP_CONCAT(CONCAT(resort_admins.first_name, ' ', resort_admins.last_name) SEPARATOR ', ') as employee_names")
            )
            ->leftJoin('learning_programs', 'learning_requests.learning_id', '=', 'learning_programs.id')
            ->leftJoin('learning_requests_employees', 'learning_requests.id', '=', 'learning_requests_employees.learning_request_id')
            ->leftJoin('employees', 'learning_requests_employees.employee_id', '=', 'employees.id')
            ->leftJoin('resort_admins', 'resort_admins.id', '=', 'employees.Admin_Parent_id')
            // Resolve the creator's display name from resort_admins (created_by stores resort_admins.id).
            ->leftJoin('resort_admins as creator', 'creator.id', '=', 'learning_requests.created_by')
            ->where('learning_requests.resort_id', $resort_id);

            if ($isManager) {
                // HR / GM / L&D Manager: can act on every request — but HR is now
                // department-scoped (getPerformanceScopedEmpIds returns dept emp ids
                // for HR, null for GM / L&D Manager / admin).
                $query->when(is_array($scopedEmpIds), function ($q) use ($scopedEmpIds) {
                    $q->whereExists(function ($sub) use ($scopedEmpIds) {
                        $sub->selectRaw(1)
                            ->from('learning_requests_employees as lre_scope')
                            ->whereColumn('lre_scope.learning_request_id', 'learning_requests.id')
                            ->whereIn('lre_scope.employee_id', $scopedEmpIds);
                    });
                });
            } elseif ($isHOD) {
                // HODs see the requests they created.
                $query->where('learning_requests.created_by', $this->resort->GetEmployee->Admin_Parent_id);
            } else {
                // Everyone else (regular Manager rank) sees requests assigned to them.
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
                ->addColumn('requested_by', fn($row) => trim($row->requested_by ?? '') !== '' ? $row->requested_by : 'N/A')
                ->addColumn('reason', fn($row) => $row->reason ?? 'N/A')
                ->addColumn('start_date', fn($row) => $row->start_date ?? 'N/A')
                ->addColumn('end_date', fn($row) => $row->end_date ?? 'N/A')

                // Status badge — for Denied / On Hold, attach the rejection_reason as a
                // hover tooltip so reviewers can see WHY without opening the detail page.
                ->addColumn('status', function ($row) {
                    $badgeClass = ($row->status == 'Approved') ? 'success' : (($row->status == 'Denied') ? 'danger' : 'warning');
                    $tooltipAttrs = '';
                    if (in_array($row->status, ['Denied', 'On Hold'], true) && !empty($row->rejection_reason)) {
                        $tooltipAttrs = ' data-bs-toggle="tooltip" data-bs-placement="top"'
                            . ' title="' . htmlspecialchars($row->rejection_reason, ENT_QUOTES, 'UTF-8') . '"'
                            . ' style="cursor: help;"';
                    }
                    return '<span class="badge badge-' . $badgeClass . '"' . $tooltipAttrs . '>' . ucfirst($row->status) . '</span>';
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
            // Server-side authorisation — only L&D Manager (or super/master admin)
            // can change a learning-request status. Mirrors the UI gate in list().
            $emp = $this->resort->getEmployee;
            $ldManagerTitles = ['Training Director', 'L&D Manager', 'Learning & Development Head'];
            $currentPositionTitle = optional(optional($emp)->position)->position_title;
            $isLdManager = in_array($currentPositionTitle, $ldManagerTitles, true)
                || ($emp && Common::isLDDepartment($emp->Dept_id ?? null));
            $isAdmin = (($this->resort->type ?? null) === 'super') || ($this->resort->is_master_admin ?? 0);
            if (!($isLdManager || $isAdmin)) {
                return response()->json(['error' => 'Only the L&D Manager can update a request status.'], 403);
            }

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
                'Approved' => "<strong>Good news!</strong> Your learning request for <strong>'{$trainingName}'</strong> has been <strong>approved</strong>. "
                    . "<strong>Training Dates:</strong> {$learningRequest->start_date} - {$learningRequest->end_date}. "
                    . "Check your schedule for details.",
                'Denied' => "Your learning request for <strong>'{$trainingName}'</strong> has been <strong>denied</strong>. "
                    . "<strong>Reason:</strong> {$request->reason}",
                'On Hold' => "Your learning request for <strong>'{$trainingName}'</strong> is <strong>on hold</strong>. "
                    . "<strong>Reason:</strong> {$request->reason}",
                default => "Your learning request for <strong>'{$trainingName}'</strong> has been updated."
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
                    $notificationTitle = 'New Learning Assignment';
                    $notificationMessage = "<strong>Congratulations!</strong> "
                        . "You are selected for <strong>'{$trainingName}'</strong>. "
                        . "<strong>Training Dates:</strong> {$learningRequest->start_date} - {$learningRequest->end_date}. "
                        . "<strong>Check your schedule and be prepared.</strong>";

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