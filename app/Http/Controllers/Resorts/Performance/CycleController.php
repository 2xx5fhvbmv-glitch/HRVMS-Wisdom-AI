<?php

namespace App\Http\Controllers\Resorts\Performance;
use DB;
use Auth;
use Validator;
use Carbon\Carbon;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ResortDepartment;
use App\Models\ResortSection;
use App\Models\ResortPosition;
use App\Models\Employee;
use App\Models\PerformanceReviewType;
use App\Models\PerformanceTemplateForm;
use App\Models\NintyDayPeformanceForm;
use App\Models\Professionalform;
use App\Models\PerformaChildCycle;
use App\Models\PerformanceCycle;
use App\Events\ResortNotificationEvent;
class CycleController extends Controller
{

    public $resort='';
    protected $underEmp_id=[];

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
        $reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id : 3;
        $this->underEmp_id = Common::getSubordinates($reporting_to);
    }
    function index(Request $request)
    {

        if(Common::checkRouteWisePermission('Performance.cycle',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }

        $page_title = " Cycle";
        $selectedYear = $request->get('year');

        // Get distinct years from all cycles for dropdown
        $availableYears = PerformanceCycle::where('resort_id', $this->resort->resort_id)
            ->selectRaw('DISTINCT YEAR(Start_Date) as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        $query = PerformanceCycle::where('resort_id', $this->resort->resort_id);
        if (!empty($selectedYear)) {
            $query->where(function ($q) use ($selectedYear) {
                $q->whereYear('Start_Date', $selectedYear)
                  ->orWhereYear('End_Date', $selectedYear);
            });
        }

        // Department scoping — limit cycles to ones containing at least one scoped employee
        $scopedIds = Common::getPerformanceScopedEmpIds();
        if (is_array($scopedIds)) {
            $query->whereIn('id', function ($sub) use ($scopedIds) {
                $sub->from('performa_child_cycles')
                    ->select('Parent_cycle_id')
                    ->whereIn('Emp_main_id', $scopedIds);
            });
        }

        $PerformanceCycle = $query->orderByDesc('id')->get()
        ->map(function($p) use ($scopedIds) {
            $cq = PerformaChildCycle::where('Parent_cycle_id', $p->id);
            if (is_array($scopedIds)) {
                $cq->whereIn('Emp_main_id', $scopedIds);
            }
            $ChildCycle = $cq->get();

            $p->child_count = $ChildCycle->count();
            $p->ManagerReview = $ChildCycle->where('manager_review_status', 'completed')->count();
            $p->SelfReview = $ChildCycle->where('self_review_status', 'completed')->count();
            return $p;
        });
        // Only HR / GM / super-admin can create a new cycle.
        $canCreateCycle = Common::hasFullDataAccess();
        return view('resorts.Performance.Cycle.index', compact('page_title', 'PerformanceCycle', 'availableYears', 'selectedYear', 'canCreateCycle'));
    }
    function create()
    {
        if (!Common::hasFullDataAccess()) {
            return abort(403, 'Only HR and GM can create a performance cycle.');
        }
        if(Common::checkRouteWisePermission('Performance.cycle',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
        }
        $main_resort_id  = $this->resort->resort->resort_id;

        $page_title = "Create Cycle";
        $ResortDepartment = ResortDepartment::where('resort_id',$this->resort->resort_id)->get();
        $Location = collect(['Malé', 'Resorts']);
        $PerformanceReviewType = PerformanceReviewType::where('resort_id',$this->resort->resort_id)
                                        ->orderBy("category_title","DESC")
                                        ->get(['id','category_title']);
       $PerformanceTemplateForm =  PerformanceTemplateForm::where('resort_id',$this->resort->resort_id)->get();

                                                
        return view('resorts.Performance.Cycle.create',compact('page_title','main_resort_id','ResortDepartment','Location','PerformanceReviewType','PerformanceReviewType','PerformanceTemplateForm'));
    }
    function CycleFetchEmployees(Request $request)
    {
        $Department         =    $request->Department;
        $Position           =    $request->Position;
        $emp_status         =    $request->emp_status;
        $Location           =    $request->Location;
        $gender             =    $request->gender;
        $joining_date_from  =    $request->joining_date_from;
        $joining_date_to    =    $request->joining_date_to;
        $tenure_duration    =   (int)$request->tenure_duration;
        $CheckedAll         = $request->CheckedAll;
        
        $statusValues = ['Active','Inactive','Terminated','Resigned','On Leave','Suspended'];

        $employees = Employee::join('resort_admins as t3', 't3.id', '=', 'employees.Admin_Parent_id')
                                ->join('resort_departments as t1', 't1.id', '=', 'employees.Dept_id')
                                ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
                                ->where('employees.resort_id', $this->resort->resort_id);
                                // Default to Active employees only if no status-type filter applied
                                if (empty($emp_status) || !in_array($emp_status, $statusValues)) {
                                    $employees->where('employees.status', 'Active');
                                }
                                if(!empty($Department))
                                {
                                    $employees->where('employees.Dept_id', $Department);
                                }
                                if(!empty($Position))
                                {
                                    $employees->where('t2.id', $Position);
                                }
                                if(!empty($emp_status))
                                {
                                    if ($emp_status === 'Probationary') {
                                        // Probationary = joined within last 3 months (90 days)
                                        $employees->whereRaw('DATEDIFF(CURDATE(), employees.joining_date) <= 90');
                                    } elseif (in_array($emp_status, $statusValues)) {
                                        $employees->where('employees.status', $emp_status);
                                    } else {
                                        $employees->where('employees.employment_type', $emp_status);
                                    }
                                }
                                if(!empty($gender))
                                {
                                    $employees->where('t3.gender', strtolower($gender));
                                }
                                if(!empty($joining_date_from))
                                {
                                    $from = Carbon::createFromFormat('d/m/Y', $joining_date_from)->format('Y-m-d');
                                    $employees->whereDate('employees.joining_date', '>=', $from);
                                }
                                if(!empty($joining_date_to))
                                {
                                    $to = Carbon::createFromFormat('d/m/Y', $joining_date_to)->format('Y-m-d');
                                    $employees->whereDate('employees.joining_date', '<=', $to);
                                }
                                if(!empty($Location))
                                {
                                    if ($Location === 'Malé') {
                                        $employees->where('employees.nationality', 'Maldivian');
                                    } elseif ($Location === 'Resorts') {
                                        $employees->where('employees.nationality', '!=', 'Maldivian');
                                    }
                                }

                                if (!empty($tenure_duration) && $tenure_duration != 0)
                                {
                                    // Show employees who joined within the last X days
                                    $employees->whereRaw('DATEDIFF(CURDATE(), employees.joining_date) <= ?', [$tenure_duration]);
                                }
                                $employees=   $employees->get(['t3.id as Parentid','employees.Emp_id','employees.joining_date','t3.status','t3.gender','t3.first_name','t3.last_name','t1.name as DepartmentName', 't2.position_title as PositionTitle'])
                                ->map(function ($i)
                                {
                                    $i->EmployeeName = ucfirst($i->first_name . ' ' . $i->last_name);
                                    $i->JoiningDate = isset($i->joining_date) ? Carbon::parse($i->joining_date)->format('d/m/Y') : '-';
                                    $i->status = ucfirst($i->status);
                                    $i->gender = ucfirst($i->gender);
                                    $i->joining_date = Carbon::parse($i->joining_date)->format('d M Y');

                                    $string='';
                                    if($i->status =="Active")
                                    {
                                        $string = '<span class="badge badge-success">Active</span>';
                                    }
                                    else
                                    {
                                        $string =  '<span class="badge badge-themePrimary">'.$i->status.'</span>';
                                    }

                                    $i->status = $string;

                                    $i->profileImg = isset($i->Parentid) ?  Common::getResortUserPicture($i->Parentid) : '' ;

                                    return $i;
                                });

            if ($request->ajax()) {
                return datatables()->of($employees)
                    ->editColumn('id', function($row) use($CheckedAll) {
                        $string ='';
                        if($CheckedAll=="true")
                        {
                            $string="checked";
                        }
                        return'<div class="form-check no-label">
                            <input class="form-check-input SelectCycleEmp" type="checkbox" '.$string.' id="" name="Emp_main_id[]" value="'.e($row->Emp_id).'"  >
                                </div>';
                    })
                    ->editColumn('EmployeeName', function($row) {
                        return '<div class="tableUser-block">
                                    <div class="img-circle">
                                        <img src="' . e($row->profileImg) . '" alt="user">
                                    </div>
                                    <span class="userApplicants-btn">' . e($row->EmployeeName) . '</span>
                                </div>';
                    })
                    ->editColumn('DepartmentName', fn($row) => e($row->DepartmentName))
                    ->editColumn('PositionTitle', fn($row) => e($row->PositionTitle))
                    ->editColumn('JoiningDate', fn($row) => e($row->joining_date))
                    ->editColumn('status', fn($row) => $row->status) // Changed from Status to status, removed e() since it's already HTML
                    ->rawColumns(['id', 'EmployeeName', 'status']) // Added 'status' since it contains HTML
                    
                    ->make(true);
            }



    }

    function CycleFetchTemplate(Request $request)
    {
        $deptId = $request->deptId;
        $position = $request->position;
        $tenure_duration = $request->tenure_duration;
        $resort_id = $this->resort->resort_id;

        try
        {
            $allForms = collect();

            // 1. Form Templates (filtered by dept/position if provided, else all)
            $templateQuery = PerformanceTemplateForm::where("resort_id", $resort_id);
            if (!empty($deptId) && !empty($position)) {
                $matchedTemplates = (clone $templateQuery)
                    ->where("Department_id", $deptId)
                    ->where("Position_id", $position)
                    ->get();
                if ($matchedTemplates->isNotEmpty()) {
                    $templateQuery = $templateQuery->where("Department_id", $deptId)->where("Position_id", $position);
                }
            }
            // Use plain associative arrays for the response. Eloquent silently casts the
            // primary key column to int, which means assigning "ninty_2" to $model->id
            // collapses the value to 0 — the bug that left CycleTemplate=0 in the form post.
            $templates = $templateQuery->get(['id', 'FormName'])->map(function($t) {
                return ['id' => (string) $t->id, 'FormName' => $t->FormName . ' (Template)'];
            });
            $allForms = $allForms->merge($templates);

            // 2. 90 Day Appraisal Forms
            $nintyDayForms = NintyDayPeformanceForm::where("resort_id", $resort_id)
                ->get(['id', 'FormName'])
                ->map(function($f) {
                    return ['id' => 'ninty_' . $f->id, 'FormName' => $f->FormName . ' (90 Day)'];
                });
            $allForms = $allForms->merge($nintyDayForms);

            // 3. Professional Development Forms
            if (class_exists(\App\Models\Professionalform::class)) {
                $professionalForms = \App\Models\Professionalform::where("resort_id", $resort_id)
                    ->get(['id', 'FormName'])
                    ->map(function($f) {
                        return ['id' => 'prof_' . $f->id, 'FormName' => $f->FormName . ' (Professional)'];
                    });
                $allForms = $allForms->merge($professionalForms);
            }

            return response()->json([
                'success' => true,
                'message' => 'Templates fetched successfully',
                'data' => $allForms->values()
            ], 200);

        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Professional Form'], 500);
        }

    }

    public function CycleStore(Request $request)
    {
        if (!Common::hasFullDataAccess()) {
            return response()->json(['success' => false, 'message' => 'Only HR and GM can create a performance cycle.'], 403);
        }

        $cycle_name =  $request->cycle_name;
        $CycleStartDate = $request->Step_One_start_date;
        $Step_One_end_date = $request->Step_One_end_date;
        $CycleSummary = $request->CycleSummary;
        $Emp_main_id = $request->Emp_main_id;
        $cycleTemplate = $request->CycleTemplate;
        $activityStartDates = $request->ActivityStartDate ?? [];
        $activityEndDates = $request->ActivityEndDate ?? [];
        $CycleReminders = in_array(strtolower((string) $request->CycleReminders), ['on', '1', 'true', 'yes']) ? 'ON' : 'OFF';

        if (empty($CycleStartDate) || empty($Step_One_end_date)) {
            return response()->json(['success' => false, 'errors' => ['date' => ['Start date and end date are required']]], 422);
        }

        // Refuse to create a cycle without a usable template — otherwise reviewers
        // get a "No template found" page with no way out. Treat empty string AND "0"
        // as missing (a "0" used to leak through when option values were mis-cast to int).
        $cycleTemplateClean = is_string($cycleTemplate) ? trim($cycleTemplate) : $cycleTemplate;
        if (empty($cycleTemplateClean) || $cycleTemplateClean === '0' || $cycleTemplateClean === 0) {
            return response()->json([
                'success' => false,
                'errors' => ['CycleTemplate' => ['Please select a review template before creating the cycle.']],
            ], 422);
        }

        // Refuse to create a cycle with zero participants — the foreach below would
        // produce a 500 ("foreach() argument must be of type array|object, null given").
        if (!is_array($Emp_main_id) || count(array_filter($Emp_main_id)) === 0) {
            return response()->json([
                'success' => false,
                'errors' => ['Emp_main_id' => ['Please select at least one employee for this cycle.']],
            ], 422);
        }
        try {
            $CycleStartDate = Carbon::createFromFormat('d/m/Y', $CycleStartDate)->format('Y-m-d');
            $CycleEndDate = Carbon::createFromFormat('d/m/Y', $Step_One_end_date)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'errors' => ['date' => ['Invalid date format. Expected dd/mm/yyyy']]], 422);
        }

        $Self_Activity_Start_Date = null;
        $Self_Activity_End_Date = null;
        $Manager_Activity_Start_Date = null;
        $Manager_Activity_End_Date = null;

        try
        {
            DB::beginTransaction();

            // Parse Self Review activity dates
            if (!empty($activityStartDates['Self_Review'] ?? null)) {
                try {
                    $Self_Activity_Start_Date = Carbon::createFromFormat('d/m/Y', $activityStartDates['Self_Review'])->format('Y-m-d');
                } catch (\Exception $e) {}
            }
            if (!empty($activityEndDates['Self_Review'] ?? null)) {
                try {
                    $Self_Activity_End_Date = Carbon::createFromFormat('d/m/Y', $activityEndDates['Self_Review'])->format('Y-m-d');
                } catch (\Exception $e) {}
            }

            // Parse Manager Review activity dates
            if (!empty($activityStartDates['Manager_Review'] ?? null)) {
                try {
                    $Manager_Activity_Start_Date = Carbon::createFromFormat('d/m/Y', $activityStartDates['Manager_Review'])->format('Y-m-d');
                } catch (\Exception $e) {}
            }
            if (!empty($activityEndDates['Manager_Review'] ?? null)) {
                try {
                    $Manager_Activity_End_Date = Carbon::createFromFormat('d/m/Y', $activityEndDates['Manager_Review'])->format('Y-m-d');
                } catch (\Exception $e) {}
            }

            // Extract numeric template id for legacy int columns
            $legacyTemplateId = null;
            if (is_numeric($cycleTemplate)) {
                $legacyTemplateId = (int) $cycleTemplate;
            } elseif (preg_match('/(\d+)/', $cycleTemplate, $m)) {
                $legacyTemplateId = (int) $m[1];
            }
            $self_review = $legacyTemplateId;
            $manager_review = $legacyTemplateId;
            $Activity_manager_Start_date = $Manager_Activity_Start_Date;
            $Activivty_manager_End_date = $Manager_Activity_End_Date;
                $p_id = PerformanceCycle::create(['resort_id'=>$this->resort->resort_id,
                                            'Cycle_Name'=>$cycle_name,
                                            'Start_Date'=>$CycleStartDate,
                                            'End_Date'=>$CycleEndDate,
                                            'CycleSummary'=>$CycleSummary,
                                            'Self_Review_Templete'=>$self_review,
                                            'Manager_Review_Templete'=>$manager_review,
                                            'CycleReminders'=>$CycleReminders,
                                            'Self_Activity_Start_Date'=>$Self_Activity_Start_Date,
                                            'Self_Activity_End_Date'=>$Self_Activity_End_Date,
                                            'Manager_Activity_Start_Date'=>$Activity_manager_Start_date,
                                            'Manager_Activity_End_Date'=>$Activivty_manager_End_date,
                                        ]);

                if(isset($p_id->id))
                {
                    $selectedTemplate = $request->CycleTemplate;
                    $participantIds = [];
                    $managerIds = [];
                    foreach ((is_array($Emp_main_id) ? $Emp_main_id : []) as $key => $emp_id)
                    {
                        // Resolve employee — Emp_main_id could be numeric id, base64, or Emp_id string (e.g. DR-17)
                        $actualEmpId = null;
                        $employee = null;
                        if (is_numeric($emp_id)) {
                            $employee = Employee::find($emp_id);
                            $actualEmpId = $emp_id;
                        } else {
                            // Try base64 decode first
                            $decoded = base64_decode($emp_id, true);
                            if ($decoded && is_numeric($decoded)) {
                                $employee = Employee::find($decoded);
                                $actualEmpId = $decoded;
                            }
                            // Fallback: lookup by Emp_id string like "DR-17"
                            if (!$employee) {
                                $employee = Employee::where('Emp_id', $emp_id)
                                    ->where('resort_id', $this->resort->resort_id)
                                    ->first();
                                if ($employee) $actualEmpId = $employee->id;
                            }
                        }
                        if (!$employee) continue;

                        // Check if employee is GM (rank 8 or position contains "general manager")
                        $isGm = false;
                        if ($employee->rank == 8) {
                            $isGm = true;
                        } else {
                            $position = \App\Models\ResortPosition::find($employee->Position_id);
                            if ($position && stripos($position->position_title, 'general manager') !== false) {
                                $isGm = true;
                            }
                        }

                        // Find reporting manager from org hierarchy
                        $managerId = $employee->reporting_to ?: null;

                        PerformaChildCycle::create([
                            'Parent_cycle_id' => $p_id->id,
                            'Emp_main_id' => $actualEmpId,
                            'Manager_id' => $isGm ? null : $managerId,
                            'template_id' => $selectedTemplate,
                            'is_gm_review' => $isGm,
                            'self_review_status' => 'pending',
                            'manager_review_status' => $isGm ? 'not_applicable' : 'pending',
                            'Self_review_date' => null,
                            'Manager_review_date' => null,
                        ]);

                        $participantIds[] = (int) $actualEmpId;
                        if (!$isGm && $managerId) {
                            $managerIds[] = (int) $managerId;
                        }
                    }
                }
                DB::commit();

                // Notify each participant that an appraisal is now pending for them.
                try {
                    $resortId = $this->resort->resort_id;
                    Common::notifyEmployees(
                        $resortId,
                        $participantIds ?? [],
                        'New Performance Review Assigned',
                        'You have been added to the "' . $cycle_name . '" cycle. Please complete your self review.',
                        'Performance',
                        $p_id->id
                    );
                    Common::notifyEmployees(
                        $resortId,
                        $managerIds ?? [],
                        'Team Performance Review Assigned',
                        'One of your team members has been added to the "' . $cycle_name . '" cycle. A manager review will be required after they complete their self review.',
                        'Performance',
                        $p_id->id
                    );
                } catch (\Exception $ne) {
                    \Log::warning('Cycle create notifications failed: ' . $ne->getMessage());
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Cycle Created Successfully..',
                ], 200);
            
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to create Cycle'], 500);
        }
    }
    /**
     * HR/GM can attach (or replace) the template on an existing cycle.
     * Used to repair cycles that were created before template-required validation —
     * the symptom is a "No template was assigned" banner on the self-review page.
     */
    public function attachTemplate(Request $request, $id)
    {
        if (!Common::hasFullDataAccess()) {
            return response()->json(['success' => false, 'message' => 'Only HR and GM can change the cycle template.'], 403);
        }

        $request->validate([
            'template_id' => 'required|string|max:50',
        ]);
        $templateId = $request->template_id;

        $cycle = PerformanceCycle::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$cycle) {
            return response()->json(['success' => false, 'message' => 'Cycle not found.'], 404);
        }

        // Verify the template_id resolves to a real form
        if (!$this->resolveTemplateExists($templateId)) {
            return response()->json(['success' => false, 'message' => 'That template does not exist.'], 422);
        }

        // Mirror what CycleStore does: legacy int columns hold the numeric id;
        // child rows hold the prefixed string (e.g. "ninty_2").
        $legacyId = is_numeric($templateId)
            ? (int) $templateId
            : (preg_match('/(\d+)/', $templateId, $m) ? (int) $m[1] : 0);

        $cycle->update([
            'Self_Review_Templete'    => $legacyId,
            'Manager_Review_Templete' => $legacyId,
        ]);
        PerformaChildCycle::where('Parent_cycle_id', $cycle->id)
            ->update(['template_id' => $templateId]);

        return response()->json(['success' => true, 'message' => 'Template attached. Reviewers can now open the form.']);
    }

    private function resolveTemplateExists($templateId)
    {
        if (strpos($templateId, 'ninty_') === 0) {
            return \App\Models\NintyDayPeformanceForm::where('id', substr($templateId, 6))->exists();
        }
        if (strpos($templateId, 'prof_') === 0) {
            return \App\Models\Professionalform::where('id', substr($templateId, 5))->exists();
        }
        if (is_numeric($templateId)) {
            return \App\Models\PerformanceTemplateForm::where('id', $templateId)->exists();
        }
        return false;
    }

    /**
     * Resolve an Emp_main_id value (stored as numeric id, base64 id, or Emp_id
     * string like "DR-22") to an Employee model. Legacy cycle rows stored the
     * Emp_id string instead of the numeric key, so the lookup needs all three.
     */
    private function resolveEmployee($identifier, $resortId)
    {
        if ($identifier === null || $identifier === '') return null;

        if (is_numeric($identifier)) {
            return Employee::with(['resortAdmin', 'department', 'position'])->find($identifier);
        }

        $decoded = base64_decode($identifier, true);
        if ($decoded !== false && is_numeric($decoded)) {
            $emp = Employee::with(['resortAdmin', 'department', 'position'])->find($decoded);
            if ($emp) return $emp;
        }

        return Employee::with(['resortAdmin', 'department', 'position'])
            ->where('Emp_id', $identifier)
            ->where('resort_id', $resortId)
            ->first();
    }

    public function viewCycle($id)
    {
        if (Common::checkRouteWisePermission('Performance.cycle', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }

        $id = base64_decode($id);
        $cycle = PerformanceCycle::where('id', $id)
            ->where('resort_id', $this->resort->resort_id)
            ->first();

        if (!$cycle) {
            abort(404, 'Cycle not found');
        }

        $scopedIds = Common::getPerformanceScopedEmpIds();
        $childQuery = PerformaChildCycle::where('Parent_cycle_id', $id);
        if (is_array($scopedIds)) {
            $childQuery->whereIn('Emp_main_id', $scopedIds);
        }
        $children = $childQuery->get();
        $totalEmployees = $children->count();
        $selfCompleted = $children->where('self_review_status', 'completed')->count();
        $managerCompleted = $children->where('manager_review_status', 'completed')->count();
        $managerTotal = $children->where('is_gm_review', false)->count() ?: $totalEmployees;

        $selfPct = $totalEmployees > 0 ? round(($selfCompleted / $totalEmployees) * 100) : 0;
        $managerPct = $managerTotal > 0 ? round(($managerCompleted / $managerTotal) * 100) : 0;

        // Build participant list with review status
        $resortId = $this->resort->resort_id;
        $participants = $children->map(function ($child) use ($resortId) {
            $employee = $this->resolveEmployee($child->Emp_main_id, $resortId);
            if (!$employee) return null;

            return (object)[
                'child_id' => $child->id,
                'emp_id' => $employee->Emp_id,
                'name' => trim(optional($employee->resortAdmin)->first_name . ' ' . optional($employee->resortAdmin)->last_name),
                'profileImg' => Common::getResortUserPicture(optional($employee->resortAdmin)->id),
                'department' => optional($employee->department)->name ?? 'N/A',
                'position' => optional($employee->position)->position_title ?? 'N/A',
                'self_status' => $child->self_review_status,
                'self_date' => $child->Self_review_date,
                'manager_status' => $child->manager_review_status,
                'manager_date' => $child->Manager_review_date,
                'is_gm' => (bool) $child->is_gm_review,
            ];
        })->filter()->values();

        $page_title = "Cycle Details";

        // Template diagnostics — if the cycle has no usable template, surface a fixer for HR/GM.
        $sampleChild = PerformaChildCycle::where('Parent_cycle_id', $cycle->id)->first();
        $hasTemplate = ($sampleChild && !empty($sampleChild->template_id))
            || !empty($cycle->Self_Review_Templete) || !empty($cycle->Manager_Review_Templete);
        $canFixTemplate = !$hasTemplate && Common::hasFullDataAccess();
        $availableTemplates = [];
        if ($canFixTemplate) {
            $availableTemplates = $this->collectAvailableTemplates($cycle->resort_id);
        }

        return view('resorts.Performance.Cycle.view', compact('page_title', 'cycle', 'totalEmployees', 'selfCompleted', 'managerCompleted', 'selfPct', 'managerPct', 'participants', 'hasTemplate', 'canFixTemplate', 'availableTemplates'));
    }

    /**
     * Build the list of templates HR can pick when repairing a cycle.
     * Prefixes match the convention in getTemplate() / CycleStore().
     */
    private function collectAvailableTemplates($resortId)
    {
        $out = [];
        foreach (\App\Models\NintyDayPeformanceForm::where('resort_id', $resortId)->get(['id', 'FormName']) as $f) {
            $out[] = ['id' => 'ninty_' . $f->id, 'label' => $f->FormName . ' (90-Day)'];
        }
        if (class_exists(\App\Models\Professionalform::class)) {
            foreach (\App\Models\Professionalform::where('resort_id', $resortId)->get(['id', 'FormName']) as $f) {
                $out[] = ['id' => 'prof_' . $f->id, 'label' => $f->FormName . ' (Professional)'];
            }
        }
        foreach (\App\Models\PerformanceTemplateForm::where('resort_id', $resortId)->get(['id', 'FormName']) as $f) {
            $out[] = ['id' => (string) $f->id, 'label' => $f->FormName . ' (Template)'];
        }
        return $out;
    }

    /**
     * Cycle analytics — bucket employees into Does not Meet / Meets / Exceeds
     * based on extracted numeric ratings from manager_review_data.
     */
    public function cycleAnalytics(Request $request, $id)
    {
        if (Common::checkRouteWisePermission('Performance.cycle', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }

        $id = base64_decode($id);
        $cycle = PerformanceCycle::where('id', $id)
            ->where('resort_id', $this->resort->resort_id)
            ->first();

        if (!$cycle) abort(404, 'Cycle not found');

        $scopedIds = Common::getPerformanceScopedEmpIds();
        $childQ = PerformaChildCycle::where('Parent_cycle_id', $id);
        if (is_array($scopedIds)) {
            $childQ->whereIn('Emp_main_id', $scopedIds);
        }
        $children = $childQ->get();

        // Build employee list with optional rating from manager_review_data
        $resortId = $this->resort->resort_id;
        $rows = $children->map(function ($child) use ($resortId) {
            $employee = $this->resolveEmployee($child->Emp_main_id, $resortId);
            if (!$employee) return null;

            $rating = $this->extractRating($child->manager_review_data);

            return (object)[
                'emp_id'          => $employee->id,
                'emp_code'        => $employee->Emp_id,
                'name'            => trim(optional($employee->resortAdmin)->first_name.' '.optional($employee->resortAdmin)->last_name),
                'profileImg'      => Common::getResortUserPicture(optional($employee->resortAdmin)->id),
                'department_id'   => $employee->Dept_id,
                'department'      => optional($employee->department)->name ?? '-',
                'position_id'     => $employee->Position_id,
                'position'        => optional($employee->position)->position_title ?? '-',
                'employment_type' => $employee->employment_type ?? '-',
                'rating'          => $rating,
                'manager_status'  => $child->manager_review_status,
            ];
        })->filter()->values();

        // Apply filters
        if ($request->filled('department_id')) {
            $rows = $rows->where('department_id', $request->department_id)->values();
        }
        if ($request->filled('position_id')) {
            $rows = $rows->where('position_id', $request->position_id)->values();
        }
        if ($request->filled('employment_type')) {
            $rows = $rows->where('employment_type', $request->employment_type)->values();
        }

        // Bucket employees by rating (scale 1-5)
        // Does not Meet: rating < 2.5
        // Meets: 2.5 <= rating <= 4.0
        // Exceeds: rating > 4.0
        // Uncategorized: no rating yet (pending review)
        $doesNotMeet = $rows->filter(fn($r) => $r->rating !== null && $r->rating < 2.5)->values();
        $meets       = $rows->filter(fn($r) => $r->rating !== null && $r->rating >= 2.5 && $r->rating <= 4.0)->values();
        $exceeds     = $rows->filter(fn($r) => $r->rating !== null && $r->rating > 4.0)->values();
        $uncategorized = $rows->filter(fn($r) => $r->rating === null)->values();

        $total = $rows->count();
        $pct = function ($n) use ($total) {
            return $total > 0 ? round(($n / $total) * 100) : 0;
        };

        $buckets = [
            'does_not_meet' => [
                'label'     => 'Does not Meet',
                'count'     => $doesNotMeet->count(),
                'percent'   => $pct($doesNotMeet->count()),
                'employees' => $doesNotMeet,
            ],
            'meets' => [
                'label'     => 'Meets',
                'count'     => $meets->count(),
                'percent'   => $pct($meets->count()),
                'employees' => $meets,
            ],
            'exceeds' => [
                'label'     => 'Exceeds',
                'count'     => $exceeds->count(),
                'percent'   => $pct($exceeds->count()),
                'employees' => $exceeds,
            ],
            'uncategorized' => [
                'label'     => 'Pending Review',
                'count'     => $uncategorized->count(),
                'percent'   => $pct($uncategorized->count()),
                'employees' => $uncategorized,
            ],
        ];

        // Filter options
        $departments = ResortDepartment::where('resort_id', $this->resort->resort_id)
            ->where('status', 'active')->get();
        $positions = ResortPosition::where('resort_id', $this->resort->resort_id)
            ->where('status', 'active')->get();
        $employmentTypes = Employee::where('resort_id', $this->resort->resort_id)
            ->whereNotNull('employment_type')
            ->distinct()->pluck('employment_type');

        $page_title = 'Cycle Analytics';

        return view('resorts.Performance.Cycle.analytics', compact(
            'page_title', 'cycle', 'buckets', 'total',
            'departments', 'positions', 'employmentTypes'
        ));
    }

    /**
     * Extract a numeric rating (1-5 scale) from manager_review_data JSON.
     * Scans all numeric values, averages them, returns null if nothing found.
     */
    private function extractRating($json)
    {
        if (!$json) return null;
        $data = is_string($json) ? json_decode($json, true) : $json;
        if (!is_array($data)) return null;

        $nums = [];
        $walk = function ($arr) use (&$walk, &$nums) {
            foreach ($arr as $v) {
                if (is_array($v)) { $walk($v); continue; }
                if (is_numeric($v)) {
                    $n = (float) $v;
                    // Treat values between 1 and 5 as ratings
                    if ($n >= 1 && $n <= 5) $nums[] = $n;
                }
            }
        };
        $walk($data);

        if (empty($nums)) return null;
        return round(array_sum($nums) / count($nums), 2);
    }

    public function Destroy($id)
    {
        $id = base64_decode($id);

        $cycle = PerformanceCycle::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$cycle) {
            return response()->json([
                'success' => false,
                'message' => 'Cycle not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            PerformaChildCycle::where('Parent_cycle_id', $id)->delete();
            $cycle->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cycle deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('Cycle Destroy File: '.$e->getFile());
            \Log::emergency('Cycle Destroy Line: '.$e->getLine());
            \Log::emergency('Cycle Destroy Message: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cycle.',
            ], 500);
        }
    }
}
