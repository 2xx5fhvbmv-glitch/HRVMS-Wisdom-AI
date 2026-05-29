<?php
namespace App\Http\Controllers\Resorts\People\ExitClearance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Models\ExitClearanceFormResponse;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\resortAdmin;
use App\Models\ResortDepartment;
use App\Models\ResortPosition;
use App\Models\ExitClearanceForm;
use App\Models\EmployeeResignation;
use App\Models\ExitClearanceFormAssignment;
use App\Models\ProbationLetterTemplate;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Events\ExitClearanceNotificationEvent;
use App\Mail\EmployementCertificateMail;
use Illuminate\Support\Facades\Mail;
use Auth;
use Config;
use Common;
use DB;
use Carbon\Carbon;

class ExitClearanceController extends Controller 
{
    public $resort;
    
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    /**
     * Fire a People-module bell notification to one Employee. Mirrors the
     * shape used by sendReminder() and the salary-increment notification
     * fan-out so the bell list stays consistent.
     *
     * Silently no-ops when the recipient id is empty — callers don't have
     * to null-check before invoking.
     */
    private function notifyExit($recipientEmployeeId, string $title, string $message): void
    {
        if (empty($recipientEmployeeId)) return;
        try {
            $notificationHtml = \App\Helpers\Common::nofitication(
                $this->resort->resort_id,
                10,
                $title,
                $message,
                0,
                (int) $recipientEmployeeId,
                'People'
            );
            event(new \App\Events\ResortNotificationEvent($notificationHtml));
        } catch (\Throwable $e) {
            \Log::warning('Exit clearance notify failed: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        
        $page_title = 'Exit clearance';
        $resort_id = $this->resort->resort_id;  
        $departments = ResortDepartment::where('resort_id', $resort_id)->where('status','active')->get();
        $positions = ResortPosition::where('resort_id', $resort_id)->where('status','active')->get();
        $templates = ExitClearanceForm::where('resort_id', $resort_id)
            ->where('form_type', 'department')
            ->get();

        if ($request->ajax()) {
            // Honour the dropdown filters posted by the index page. The old
            // controller dropped them on the floor — department_id /
            // position_id / status were sent but never read, which is why
            // "other filters not working" was reported.
            //
            // Status default: when the filter dropdown is left blank we
            // keep the page's original behaviour and only surface
            // resignations that have been Approved (the rows that actually
            // need clearance). Picking a value from the dropdown REPLACES
            // that default so HR can audit other states.
            $deptId     = $request->filled('department_id') ? (int) $request->department_id : null;
            $positionId = $request->filled('position_id')   ? (int) $request->position_id   : null;
            $statusVal  = $request->filled('status')        ? trim((string) $request->status) : null;

            // Per-employee filter — used when the Employee Detail page
            // "Clearance" tab forwards ?empId=<base64>. When scoped to one
            // employee we drop the default status='Approved' filter so HR
            // sees that employee's clearance regardless of resignation state.
            $empIdFilter = null;
            if ($request->filled('empId')) {
                $decoded = base64_decode((string) $request->empId, true);
                if ($decoded !== false && ctype_digit((string) $decoded)) {
                    $empIdFilter = (int) $decoded;
                }
            }

            $employeeResignations = EmployeeResignation::with(['employee.resortAdmin', 'employee.department', 'employee.position'])
                ->where('resort_id', $resort_id)
                ->when($empIdFilter, function ($q) use ($empIdFilter) {
                    $q->where('employee_id', $empIdFilter);
                }, function ($q) use ($statusVal) {
                    $q->when($statusVal, fn($qq) => $qq->where('status', $statusVal),
                                         fn($qq) => $qq->where('status', 'Approved'));
                })
                ->when($deptId, function ($q) use ($deptId) {
                    $q->whereHas('employee', fn($eq) => $eq->where('Dept_id', $deptId));
                })
                ->when($positionId, function ($q) use ($positionId) {
                    $q->whereHas('employee', fn($eq) => $eq->where('Position_id', $positionId));
                })
                ->get();

            $edit_class = '';
            if(Common::checkRouteWisePermission('people.exit-clearance',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
           
            return datatables()->of($employeeResignations)
                ->addColumn('Emp_id', function ($employeeResignation) {
                    return $employeeResignation->employee ? $employeeResignation->employee->Emp_id : 'N/A';
                })
                ->addColumn('employee_name', function ($employeeResignation) {
                    $image = Common::getResortUserPicture($employeeResignation->employee->Admin_Parent_id ?? null);
                    $name = optional(@$employeeResignation->employee->resortAdmin)->full_name;
                   
                    return '<div class="tableUser-block">
                                <div class="img-circle"><img src="' . $image . '" alt="user"></div>
                                <span class="userApplicants-btn">' . ($name ? ucwords($name) : 'N/A') . '</span>
                            </div>';
                })
                ->addColumn('position', function ($employeeResignation) {
                    return $employeeResignation->employee && $employeeResignation->employee->position
                        ? $employeeResignation->employee->position->position_title
                        : 'N/A';
                })
                ->addColumn('department', function ($employeeResignation) {
                    return $employeeResignation->employee && $employeeResignation->employee->department
                        ? $employeeResignation->employee->department->name
                        : 'N/A';
                })
                ->addColumn('resignation_date', function ($employeeResignation) {
                    return $employeeResignation->resignation_date
                        ? \Carbon\Carbon::parse($employeeResignation->resignation_date)->format('d M Y')
                        : 'N/A';
                })
                ->addColumn('last_working_day', function ($employeeResignation) {
                    return $employeeResignation->last_working_day
                        ? \Carbon\Carbon::parse($employeeResignation->last_working_day)->format('d M Y')
                        : 'N/A';
                })
                ->addColumn('status', function ($employeeResignation) {
                    $statusBadge = match ($employeeResignation->status) {
                        'Completed' => '<span class="badge badge-themeSuccess">Completed</span>',
                        'Approved' => '<span class="badge badge-themeSuccess">Approved</span>',
                        'Rejected' => '<span class="badge badge-themeDanger">Rejected</span>',
                        'On Hold'  => '<span class="badge badge-themeSkyblue">On Hold</span>',
                        'In Progress' => '<span class="badge badge-themePrimary">In Progress</span>',
                        default    => '<span class="badge badge-themeWarning">Pending</span>',
                    };

                    // Trigger the Exit Clearance Status modal. The previous
                    // markup used `href="#statusModal" data-bs-toggle="modal"`
                    // — but the actual modal id is `#exitClear-modal`, so
                    // Bootstrap intercepted the click, looked up a target
                    // that didn't exist, and the jQuery handler below
                    // never got a chance to run. Use javascript:void(0)
                    // and let the delegated `.status-modal-trigger`
                    // handler in index.blade.php open the right modal.
                    //
                    // Adding a small history icon + cursor:pointer +
                    // title attribute so the badge clearly reads as
                    // clickable — HR couldn't tell it opened a modal.
                    return '<a href="javascript:void(0);" data-id="' . $employeeResignation->id . '" '
                         . 'class="status-modal-trigger" title="Click to view clearance status history" '
                         . 'style="text-decoration:none; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">'
                         . $statusBadge
                         . '<i class="fa fa-history" style="font-size:11px; color:#888;"></i>'
                         . '</a>';
                })
                ->addColumn('action', function ($employeeResignation) use ($edit_class) {
                    return '
                        <a href="#listDep-modal" data-bs-toggle="modal" id="listDepModal" data-id="'.$employeeResignation->id.'" class="btn-lg-icon icon-bg-blue '.$edit_class.'" >
                            <i class="fa-regular fa-file"></i>
                        </a>
                        <a href="' . route('people.exit-clearance.viewDetails', base64_encode($employeeResignation->id)) . '" class="btn-lg-icon icon-bg-skyblue ">
                            <i class="fa-regular fa-eye"></i>
                        </a>';
                })
                ->rawColumns(['employee_name', 'status', 'action'])
                ->make(true);
        }

        return view('resorts.people.exit-clearance.index', compact(
            'page_title', 
            'resort_id', 
            'departments',
            'positions',
            'templates'
        ));
    }

    public function viewDetails($id)
    {
        if(Common::checkRouteWisePermission('people.exit-clearance',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
           
        $id = base64_decode($id);
        $page_title = 'Exit Clearance Details';
        $resort_id = $this->resort->resort_id;

        $exit_clearance = EmployeeResignation::with([
                'employee.resortAdmin',
                'employee.department',
                'employee.position',
                // Reporting supervisor — Employee.reporting_to → Employee → resortAdmin
                'employee.reportingTo:id,Admin_Parent_id,Position_id,Dept_id',
                'employee.reportingTo.resortAdmin:id,first_name,last_name',
                'employee.reportingTo.position:id,position_title',
                // HOD + HR who own this resignation (used by the People Details card)
                'hod:id,Admin_Parent_id,Position_id',
                'hod.resortAdmin:id,first_name,last_name',
                'hod.position:id,position_title',
                'hr:id,Admin_Parent_id,Position_id',
                'hr.resortAdmin:id,first_name,last_name',
                'hr.position:id,position_title',
                'reason_title',
            ])
            ->where('id', $id)
            ->where('resort_id', $resort_id)
            ->firstOrFail();

        $user = $this->resort->GetEmployee;
       
        $is_assigned = false;
        $is_hr = false;

        if ($user->rank == 3) {
            $is_hr = true;
        }
        if($is_hr == true){
            $exitClearanceFormAssignments = ExitClearanceFormAssignment::with('exitClearanceForm')->where('resort_id', $resort_id)
                    ->where('emp_resignation_id', $id)
                    ->get();
        }elseif($is_hr == false){

            $exitClearanceFormAssignments = ExitClearanceFormAssignment::where('resort_id', $resort_id)
                ->where('emp_resignation_id', $id)
                ->where('assigned_to_id', $user->id)
                ->get();
        }
      
        $exitClearanceFormAssign =  ExitClearanceFormAssignment::where('resort_id', $resort_id)
                                    ->where('emp_resignation_id', $id)
                                    ->where('assigned_to_id', $user->id)
                                    ->where('assigned_to_type', 'department')
                                    ->first();

        if ($exitClearanceFormAssign) {
            $is_assigned = true;
        }
        // dd($exit_clearance);

            if($is_hr == true){
                return view('resorts.people.exit-clearance.view-details', compact(
                    'page_title',
                    'exit_clearance',
                    'exitClearanceFormAssignments',
                    'is_hr',
                    'is_assigned'
                ));
            }else{
                return view('resorts.people.exit-clearance.department-view-details', compact(
                    'page_title',
                    'exit_clearance',
                    'exitClearanceFormAssignments',
                    'is_hr',
                    'is_assigned'
                ));
            }
        
    }

    public function assignmentSubmitDepartment(Request $request){

        $resort_id = $this->resort->resort_id;
        $have_template = false;
        
        foreach ($request->department_id as $department_id) {
            $employee = Employee::where('resort_id', $resort_id)->where('Dept_id',$department_id)
            ->where('rank',2)
            ->first();

            if (!$employee) {
                $employee = Employee::where('resort_id', $resort_id)->where('Dept_id',$department_id)
                ->where('rank',3)->orWhere('rank',4)
                ->first();
            }

            $deadLineDate = Carbon::createFromFormat('d/m/Y', $request->input('deadline_date'))->format('Y-m-d');

            $template = ExitClearanceForm::where('resort_id', $resort_id)
                ->where('department_id', $department_id)
                ->where('form_type', 'department')->first();


            if ($template != null) {
                $have_template = true;
                $chkExitClearanceFormAssignment = ExitClearanceFormAssignment::where('resort_id', $resort_id)
                    ->where('department_id', $department_id)
                    ->where('emp_resignation_id', $request->employee_resignation_id)
                    ->where('form_id', $template->id)
                    ->where('assigned_to_type', 'department')
                    ->first();

                if (!$chkExitClearanceFormAssignment) {

                    $exitClearanceFormAssignment = ExitClearanceFormAssignment::create([
                        'resort_id' => $resort_id,
                        'department_id' => $department_id,
                        'emp_resignation_id' => $request->employee_resignation_id,
                        'form_id'=> $template->id,
                        'assigned_to_type' => 'department',
                        'assigned_to_id' => $employee ? $employee->id : null,
                        'assigned_by' => Auth::guard('resort-admin')->user()->id,
                        'assigned_date' => Carbon::now(),
                        'deadline_date' => $deadLineDate,
                        'status' => 'Pending',
                    ]);

                    // Notify the department's HOD (or fallback rank we
                    // picked above) — they need to know a clearance form
                    // landed in their queue. Was silently created
                    // before; HR had to chase by hand.
                    if ($employee) {
                        $resignation = EmployeeResignation::with('employee.resortAdmin')
                            ->find($request->employee_resignation_id);
                        $empName = $resignation
                            ? (optional(optional($resignation->employee)->resortAdmin)->full_name ?: 'employee')
                            : 'employee';
                        $this->notifyExit(
                            $employee->id,
                            'New Exit Clearance Form Assigned',
                            "📋 You have been assigned an exit clearance form for {$empName}."
                            . " Deadline: " . Carbon::parse($deadLineDate)->format('d M Y') . "."
                        );
                    }

                }else{
                    
                    $chkExitClearanceFormAssignment->update([
                            'deadline_date' => $deadLineDate,
                        ]);
                }
            }
        }

        if($have_template == false){
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Create Department form and assign.',
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Exit clearance form assignment created successfully.',
        ]);

    }

    public function employeeFormAssignment(Request $request,$id)
    {
        if(Common::checkRouteWisePermission('people.exit-clearance',config('settings.resort_permissions.edit')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title = 'Exit Clearance Form';
        $resort_id = $this->resort->resort_id;
        $employee_id = $request->input('employee_id');
        $departments = ResortDepartment::where('resort_id', $resort_id)->where('status', 'active')->get();
        $exit_clearance_employee_template = ExitClearanceForm::where('resort_id', $resort_id)
            ->where('form_type', 'employee')
            ->get();

        $id = base64_decode($id);

        $employeeResignation = EmployeeResignation::where('id', $id)
            ->where('resort_id', $resort_id)
            ->firstOrFail();

        $employee = Employee::where('id', $employeeResignation->employee_id)
                    ->where('resort_id', $resort_id)        
                    ->first();

        return view('resorts.people.exit-clearance.exit-clearance-form', compact(
            'page_title',
            'resort_id',
            'employee_id',
            'departments','exit_clearance_employee_template','employeeResignation','employee'
        ));
    }

    public function assignmentSubmitEmployee(Request $request){

        $resort_id = $this->resort->resort_id;
   
        $employee = $this->resort->GetEmployee;
           
        $deadLineDate = Carbon::createFromFormat('d/m/Y', $request->input('deadline_date'))->format('Y-m-d');

        $template = ExitClearanceForm::where('resort_id', $resort_id)
            ->where('id', $request->template_id)
            ->where('form_type', 'employee')->first();

        if ($template != null) {
            $chkExitClearanceFormAssignment = ExitClearanceFormAssignment::where('resort_id', $resort_id)
                ->where('emp_resignation_id', $request->employee_resignation_id)
                ->where('form_id', $template->id)
                ->where('assigned_to_type', 'employee')
                ->first();

            if (!$chkExitClearanceFormAssignment) {

                $exitClearanceFormAssignment = ExitClearanceFormAssignment::create([
                    'resort_id' => $resort_id,
                    'emp_resignation_id' => $request->employee_resignation_id,
                    'form_id'=> $template->id,
                    'assigned_to_type' => 'employee',
                    'assigned_to_id' => $employee ? $employee->id : null,
                    'assigned_by' => Auth::guard('resort-admin')->user()->id,
                    'assigned_date' => Carbon::now(),
                    'deadline_date' => $deadLineDate,
                    'status' => 'Pending',
                    'reminder_frequency' => $request->reminder_frequency ?? null,
                ]);

                // Notify the resigning employee that an exit interview
                // form is now waiting for them to fill in.
                $resignation = EmployeeResignation::with('employee.resortAdmin')
                    ->find($request->employee_resignation_id);
                if ($resignation && $resignation->employee_id) {
                    $this->notifyExit(
                        $resignation->employee_id,
                        'Exit Interview Form Assigned',
                        "📋 An exit interview form has been assigned to you ({$template->form_name})."
                        . " Please complete it by " . Carbon::parse($deadLineDate)->format('d M Y') . "."
                    );
                }

            }else{
                $chkExitClearanceFormAssignment->update([
                    'deadline_date' => $deadLineDate,
                    'reminder_frequency' => $request->reminder_frequency ?? null,
                ]);
            }
        }

        return redirect()->route('people.exit-clearance.viewDetails', base64_encode($request->employee_resignation_id))->with('success', 'Exit clearance form assignment created successfully.');

    }

    public function employeeFormAssignmentShow(Request $request, $id)
    {
        $id = base64_decode($id);
        $page_title = 'Exit Clearance Form Response';
        $response_has = false;
        // NB: variable name is misleading — in the blade `$is_submitted`
        // actually means "form is editable / Submit button visible".
        // Treat it as: this view is editable when the assignment is
        // still Pending. Once status is Completed it falls back to
        // read-only (existing behaviour).
        $is_submitted = false;

        $resort_id = $this->resort->resort_id;
        $exitClearanceFormAssignment = ExitClearanceFormAssignment::where('id', $id)
            ->where('resort_id', $resort_id)
            ->firstOrFail();

        if (trim((string) $exitClearanceFormAssignment->status) === 'Pending') {
            $is_submitted = true;
        }

        $employeeResignation = EmployeeResignation::where('id', $exitClearanceFormAssignment->emp_resignation_id)
            ->where('resort_id', $resort_id)
            ->firstOrFail();

        $employee = Employee::where('id', $exitClearanceFormAssignment->assigned_to_id)->where('resort_id', $resort_id)->first();    

        $exitClearanceForm = ExitClearanceForm::where('id', $exitClearanceFormAssignment->form_id)
            ->where('resort_id', $resort_id)
            
            ->firstOrFail();
          

        $exitClearanceFormResponse = ExitClearanceFormResponse::where('assignment_id', $exitClearanceFormAssignment->id)->first();
        $formStructure = json_decode($exitClearanceForm->form_structure, true); 
        
        if ($exitClearanceFormResponse) {
            $responses = json_decode($exitClearanceFormResponse->response_data, true);
            $response_has = true;
        }else {
            $responses = json_decode($exitClearanceFormAssignment->form_structure, true);
        }
       
        return view('resorts.people.exit-clearance.exit-clearance-form-view', compact(
            'page_title',
            'exitClearanceFormAssignment',
            'exitClearanceForm',
            'formStructure',
            'responses',
            'exitClearanceFormResponse',
            'response_has',
            'is_submitted',
            'employee',
            'employeeResignation'
        ));
    }


   

     public function departmentForm(Request $request,$id){
         
        $page_title = 'Exit Clearance Form Response';
        $response_has = false;
        $is_submitted = true;
        $id = base64_decode($id);
        $resort_id = $this->resort->resort_id; 

        $employeeResignation =  EmployeeResignation::where('id', $id)
            ->where('resort_id', $resort_id)
            ->firstOrFail();

        if (!$employeeResignation) {
            return redirect()->route('people.exit-clearance')->with('error', 'Employee resignation not found.');
        }

        $user = $this->resort->GetEmployee; 
        
        
        $exitClearanceFormAssignment = ExitClearanceFormAssignment::where('emp_resignation_id', $id)
        ->where('resort_id', $resort_id)
        ->where('assigned_to_id', $user->id)
        ->where('assigned_to_type', 'department')
        ->first();

        if (!$exitClearanceFormAssignment) {
            return redirect()->route('people.exit-clearance.viewDetails', base64_encode($id))
                ->with('error', 'You are not assigned to this exit clearance form.');
        }

        if($exitClearanceFormAssignment->status == 'Completed'){
            $is_submitted = false;  
        }

        $employee = Employee::where('id', $exitClearanceFormAssignment->assigned_to_id)->where('resort_id', $resort_id)->first();    

        $exitClearanceForm = ExitClearanceForm::where('id', $exitClearanceFormAssignment->form_id)
            ->where('resort_id', $resort_id)
            ->firstOrFail();

        $exitClearanceFormResponse = ExitClearanceFormResponse::where('assignment_id', $exitClearanceFormAssignment->id)->first();
        $formStructure = json_decode($exitClearanceForm->form_structure, true);
        $responses = json_decode($exitClearanceFormResponse->response_data, true);

        
        return view('resorts.people.exit-clearance.exit-clearance-form-view', compact(
            'page_title',
            'exitClearanceFormAssignment',
            'exitClearanceForm',
            'formStructure',
            'responses',
            'exitClearanceFormResponse',
            'response_has',
            'is_submitted',
            'employee',
            'employeeResignation'
        ));
    }

    public function departmentFormResponseStore(Request $request)
    {
        $resort_id = $this->resort->resort_id;

        $exitClearanceFormAssignment = ExitClearanceFormAssignment::where('id', $request->exit_clearance_assignment_id)
            ->where('resort_id', $resort_id)
            ->where('assigned_to_type', 'department')
            ->firstOrFail();

        $exitClearanceForm = ExitClearanceForm::where('id', $exitClearanceFormAssignment->form_id)
            ->where('resort_id', $resort_id)
            ->firstOrFail();

        $formStructure = json_decode($exitClearanceForm->form_structure, true);

        $responseData = [];

        foreach ($formStructure as $field) {
            $fieldName = $field['name'] ?? null;
            $fieldType = $field['type'] ?? null;

            if (!$fieldName) continue;

            // Handle file upload
            if ($fieldType === 'file' && $request->hasFile($fieldName)) {
                $uploadedFiles = $request->file($fieldName);
                $path = config('settings.ExitClearanceAttachments');
                $filePaths = [];

                if (is_array($uploadedFiles)) {
                    foreach ($uploadedFiles as $uploadedFile) {
                        $fileName = time() . '_' . $uploadedFile->getClientOriginalName();
                        $destinationPath = $path . '/' . $exitClearanceFormAssignment->id . '/' . $fieldName;
                        $fullDestinationPath = public_path($destinationPath);
                        if (!file_exists($fullDestinationPath)) {
                            mkdir($fullDestinationPath, 0777, true);
                        }
                        $uploadedFile->move($fullDestinationPath, $fileName);
                        $filePaths[] = $destinationPath . '/' . $fileName;
                    }
                } elseif ($uploadedFiles) {
                    $fileName = time() . '_' . $uploadedFiles->getClientOriginalName();
                    $destinationPath = $path . '/' . $exitClearanceFormAssignment->id . '/' . $fieldName;
                    $fullDestinationPath = public_path($destinationPath);
                    if (!file_exists($fullDestinationPath)) {
                        mkdir($fullDestinationPath, 0777, true);
                    }
                    $uploadedFiles->move($fullDestinationPath, $fileName);
                    $filePaths[] = $destinationPath . '/' . $fileName;
                }

                $responseData[$fieldName] = $filePaths;
            } else {
                $responseData[$fieldName] = $request->input($fieldName, null);
            }
        }
        $chkExitClearanceFormResponse = ExitClearanceFormResponse::where('assignment_id', $exitClearanceFormAssignment->id)->first();
        if($chkExitClearanceFormResponse){
            $chkExitClearanceFormResponse->update([
                    'response_data' => json_encode($responseData),
                ]);
        }else{
            $exitClearanceFormResponse = ExitClearanceFormResponse::create([
                    'assignment_id' => $exitClearanceFormAssignment->id,
                    'response_data' => json_encode($responseData),
                    'submitted_by' => Auth::guard('resort-admin')->user()->id,
                    'submitted_date' => Carbon::now(),
                ]);
        }

        $exitClearanceFormAssignment->update([
            'status' => 'Completed',
            'completed_date' => Carbon::now(),

        ]);

        // Notify HR (the resignation's hr_id, or the resort HR fallback)
        // that a department has completed their clearance form so HR can
        // verify and progress the offboarding.
        $resignation = EmployeeResignation::with('employee.resortAdmin')
            ->find($exitClearanceFormAssignment->emp_resignation_id);
        if ($resignation) {
            $hrId = $resignation->hr_id;
            if (empty($hrId)) {
                $hrFallback = \App\Helpers\Common::FindResortHR($this->resort);
                $hrId = optional($hrFallback)->id;
            }
            $empName = optional(optional($resignation->employee)->resortAdmin)->full_name ?: 'employee';
            $this->notifyExit(
                $hrId,
                'Exit Clearance Form Completed',
                "✅ A department clearance form has been completed for {$empName}."
            );
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Form response stored successfully.',
            'data' => $responseData,
        ]);
    }


    /**
     * Web-side submit for an EMPLOYEE-assigned exit interview form. Mirrors
     * departmentFormResponseStore() but scoped to assigned_to_type='employee'
     * and notifies HR instead of relying on the department-completed ping.
     *
     * The web exit-clearance-form-view used to POST to the department
     * endpoint, which silently rejected employee assignments — leaving HR
     * with no way to capture exit interview responses from the website.
     */
    public function employeeFormResponseStore(Request $request)
    {
        $resort_id = $this->resort->resort_id;

        $exitClearanceFormAssignment = ExitClearanceFormAssignment::where('id', $request->exit_clearance_assignment_id)
            ->where('resort_id', $resort_id)
            ->where('assigned_to_type', 'employee')
            ->firstOrFail();

        $exitClearanceForm = ExitClearanceForm::where('id', $exitClearanceFormAssignment->form_id)
            ->where('resort_id', $resort_id)
            ->firstOrFail();

        $formStructure = json_decode($exitClearanceForm->form_structure, true);
        $responseData = [];

        foreach ($formStructure as $field) {
            $fieldName = $field['name'] ?? null;
            $fieldType = $field['type'] ?? null;
            if (!$fieldName) continue;

            if ($fieldType === 'file' && $request->hasFile($fieldName)) {
                $uploadedFiles = $request->file($fieldName);
                $path = config('settings.ExitClearanceAttachments');
                $filePaths = [];
                $rows = is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles];
                foreach ($rows as $uploadedFile) {
                    if (!$uploadedFile) continue;
                    $fileName = time() . '_' . $uploadedFile->getClientOriginalName();
                    $destinationPath = $path . '/' . $exitClearanceFormAssignment->id . '/' . $fieldName;
                    $fullDestinationPath = public_path($destinationPath);
                    if (!file_exists($fullDestinationPath)) {
                        mkdir($fullDestinationPath, 0777, true);
                    }
                    $uploadedFile->move($fullDestinationPath, $fileName);
                    $filePaths[] = $destinationPath . '/' . $fileName;
                }
                $responseData[$fieldName] = $filePaths;
            } else {
                $responseData[$fieldName] = $request->input($fieldName, null);
            }
        }

        $existing = ExitClearanceFormResponse::where('assignment_id', $exitClearanceFormAssignment->id)->first();
        if ($existing) {
            $existing->update(['response_data' => json_encode($responseData)]);
        } else {
            ExitClearanceFormResponse::create([
                'assignment_id'  => $exitClearanceFormAssignment->id,
                'response_data'  => json_encode($responseData),
                'submitted_by'   => Auth::guard('resort-admin')->user()->id,
                'submitted_date' => Carbon::now(),
            ]);
        }

        $exitClearanceFormAssignment->update([
            'status'         => 'Completed',
            'completed_date' => Carbon::now(),
        ]);

        // Notify HR — same shape as the API formSubmit + the department
        // completion notification, so the bell log reads consistently.
        $resignation = EmployeeResignation::with('employee.resortAdmin')
            ->find($exitClearanceFormAssignment->emp_resignation_id);
        if ($resignation) {
            $hrId = $resignation->hr_id;
            if (empty($hrId)) {
                $hrFallback = \App\Helpers\Common::FindResortHR($this->resort);
                $hrId = optional($hrFallback)->id;
            }
            $empName = optional(optional($resignation->employee)->resortAdmin)->full_name ?: 'employee';
            $this->notifyExit(
                $hrId,
                'Exit Interview Form Submitted',
                "📋 {$empName} has submitted their exit interview form. Please review the responses."
            );
        }

        return response()->json([
            'success' => true,
            'status'  => 'success',
            'message' => 'Exit interview form submitted successfully.',
            'data'    => $responseData,
        ]);
    }

    // mark as Complete by hr
    public function markAsComplete(Request $request, $id)
    {
        $id = base64_decode($id);
        $resort_id = $this->resort->resort_id;

        $employeeResignation =  EmployeeResignation::where('id', $id)
            ->where('resort_id', $resort_id)
            ->firstOrFail();

        $exitClearanceFormAssignments = ExitClearanceFormAssignment::where('emp_resignation_id', $id)
            ->where('resort_id', $resort_id)
            ->where('status','!=','Pending')
            ->get();

        if (!$exitClearanceFormAssignments) {
            $employeeResignation->update([
                'status' => 'Completed',
            ]);

            // Now that exit clearance is signed off, transition the
            // employee from 'Offboarding' to 'Terminated'. ProbationController
            // (and any future resignation-approve flow) should set the
            // employee to 'Offboarding' on initiation — this is the
            // counterpart that finalises the lifecycle.
            $employee = Employee::find($employeeResignation->employee_id);
            if ($employee && $employee->status === 'Offboarding') {
                $employee->update(['status' => 'Terminated']);
            }

            // Notify the employee + HR that offboarding is officially
            // closed out. Final-state event in the lifecycle.
            $empName = $employee && $employee->resortAdmin
                ? $employee->resortAdmin->full_name
                : 'employee';
            $this->notifyExit(
                $employeeResignation->employee_id,
                'Exit Clearance Completed',
                "🎉 Your exit clearance has been finalised. All the best for your next role."
            );
            $hrId = $employeeResignation->hr_id;
            if (empty($hrId)) {
                $hrFallback = \App\Helpers\Common::FindResortHR($this->resort);
                $hrId = optional($hrFallback)->id;
            }
            $this->notifyExit(
                $hrId,
                'Exit Clearance Marked Complete',
                "✅ Exit clearance for {$empName} has been marked complete."
            );
        }else{
            return back()->with('error', 'Please complete all exit clearance forms before mark as completed.');
        }

        return redirect()->route('people.exit-clearance')->with('success', 'Exit clearance marked as completed successfully.');
    }

    public function employementCertificate(Request $request, $id)
    {
        $id = base64_decode($id);
       
        $page_title = 'Employement Certificate';

        $resort_id = $this->resort->resort_id;
        // $resort_id = $this->resort->resort->resort_id;
        $resort = Resort::findOrFail($resort_id);
        
        $employeeResignation = EmployeeResignation::where('id', $id)
            ->where('resort_id', $resort_id)
            ->firstOrFail();
           
        $probationLetterTemplate = ProbationLetterTemplate::where('resort_id', $resort_id)
            ->where('type', 'experience')
            ->first();

        if (!$probationLetterTemplate) {
            return response()->json([
                'success' => false,
                'message' => 'Experience letter template not found for this resort. Please Create a template in experience type.',
            ]);
        }

        $employee = Employee::where('id', $employeeResignation->employee_id)
            ->where('resort_id', $resort_id)
            ->first();
       
        
        $type = $probationLetterTemplate->type;
        $placeholders = [
            '{{date}}'                => now()->format('d M Y'),
            '{{resort_name}}'         => (string) $resort->resort_name,
            '{{employee_name}}'       => (string) optional($employee->resortAdmin)->full_name,
            '{{position_title}}'      => (string) optional($employee->position)->position_title,
            '{{Department_title}}'   => (string) optional($employee->department)->name,
            '{{joining_date}}' => (string) Carbon::parse($employee->joining_date)->format('d M Y'),
            '{{last_working_day}}' => (string) Carbon::parse($employeeResignation->last_working_day)->format('d M Y'),
        ];


        $letterContent = strtr($probationLetterTemplate->content, $placeholders);
      
        $pdf = Pdf::loadHTML($letterContent);
        
        $directory = public_path(config('settings.experienceLetters')).'/' . $this->resort->resort->resort_id.'/'.$employee->Emp_id;
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $pdfPath = $directory . '/' . $employee->Emp_id . '.pdf';
       
        file_put_contents($pdfPath, $pdf->output());

        $employeeResignation->certificate_issue = 'yes';
        $employeeResignation->save();
        // Send email
        Mail::to($employee->resortAdmin->email)->send(new EmployementCertificateMail($employee, $pdfPath, $type, $resort,$employeeResignation));

        return response()->json([
            'success' => true,
            'message' => 'Employment certificate sent to ' . $employee->resortAdmin->email . ' successfully.',
            'pdf_path' => $pdfPath,
        ]);
    }

    public function sendReminder(Request $request, $id)
    {
        $id = base64_decode($id);
        $resort_id = $this->resort->resort_id;  
        $employeeResignation = EmployeeResignation::where('id', $id)
            ->where('resort_id', $resort_id)    
            ->firstOrFail();

        $exitClearanceFormAssignments = ExitClearanceFormAssignment::where('emp_resignation_id', $employeeResignation->id)
            ->where('resort_id', $resort_id)
            ->where('status','Pending')
            ->get();

        foreach ($exitClearanceFormAssignments as $assignment) {
            $assignedEmployee = \App\Models\Employee::find($assignment->assigned_to_id);

            $message = "📢 Exit Clearance Reminder: Please fill up the exit clearance form for " 
                . optional($employeeResignation->employee->resortAdmin)->full_name . ".";

            $notificationHtml = Common::nofitication(
                $this->resort->resort_id,
                10,
                'Employee Exit Clearance Form Fillup Reminder',
                $message,
                0,
                $assignment->assigned_to_id, 
                'People'
            );

            event(new \App\Events\ResortNotificationEvent($notificationHtml));
        }

        return back()->with('success', 'Reminders sent to all pending assignees.');

        return response()->json([
            'success' => true,
            'message' => 'Reminders sent to all pending assignees.'
        ]);
    }

    public function employeeDepartureArrangement(Request $request, $id){
        $id = base64_decode($id);
        $page_title = 'Departure Arrangements';
        $resort_id = $this->resort->resort_id;

        $exit_clearance = EmployeeResignation::where('id', $id)
            ->where('resort_id', $resort_id)
            ->firstOrFail();

        $request_departure_arrangements = $request->arrangements;
        
        $departure_arrangements = [
            'international_flight' => $request_departure_arrangements['international_flight'] ?? 0,
            'transportation_arranged' => $request_departure_arrangements['transportation_arranged'] ?? 0,
            'passport_validity' => $request_departure_arrangements['passport_validity'] ?? 0,
            'accommodation_arranged' => $request_departure_arrangements['accommodation_arranged'] ?? 0,
            'documentVerifed' => $request_departure_arrangements['documentVerifed'] ?? 0,
        ];

        $exit_clearance->update([
            'departure_arrangements' => $departure_arrangements,
        ]);
        // dd($exit_clearance);
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Departure arrangements updated successfully.',
        ]);
    }

    public function getStatus(Request $request)
    {
        $id = $request->resignation_id;
        $resort_id = $this->resort->resort_id;

        $employeeResignation = EmployeeResignation::where('id', $id)
            ->where('resort_id', $resort_id)
            ->firstOrFail();

        $emp_resignation_assignment_form = ExitClearanceFormAssignment::with('exitClearanceForm')
            ->where('emp_resignation_id', $id)
            ->where('resort_id', $resort_id)
            ->orderBy('updated_at', 'desc')
            ->get();
        return response()->json([
            'success' => true,
            'status' => $employeeResignation->status,
            'message' => 'Status retrieved successfully.',
            'data'=>$emp_resignation_assignment_form,
        ]);
    }
}
