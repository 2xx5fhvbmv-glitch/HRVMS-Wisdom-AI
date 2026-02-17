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
            $employeeResignations = EmployeeResignation::with(['employee.resortAdmin', 'employee.department', 'employee.position'])
                ->where('resort_id', $resort_id)
                ->where('status', '=', 'Approved')
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

                    return '<a href="#statusModal" data-bs-toggle="modal" data-id="' . $employeeResignation->id . '" class="status-modal-trigger">' . $statusBadge . '</a>';
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

        $exit_clearance = EmployeeResignation::with(['employee.resortAdmin', 'employee.department', 'employee.position','reason_title'])
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
        $is_submitted = false;

        $resort_id = $this->resort->resort_id;
        $exitClearanceFormAssignment = ExitClearanceFormAssignment::where('id', $id)
            ->where('resort_id', $resort_id)
            ->firstOrFail();

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

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Form response stored successfully.',
            'data' => $responseData,
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
