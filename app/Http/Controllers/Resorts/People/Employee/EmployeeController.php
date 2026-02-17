<?php

namespace App\Http\Controllers\Resorts\People\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Exports\SelectedEmployeesExport;
use Illuminate\Support\Facades\Session;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\EmployeeLanguage;
use App\Models\ResortBenifitGrid;
use App\Models\ResortPosition;
use App\Models\ResortSection;
use App\Models\EmployeeEducation;
use App\Models\EmployeeExperiance;
use App\Models\EmployeesDocument;
use App\Models\SOSTeamManagementModel;
use App\Models\SOSRolesAndPermission;
use App\Models\SOSTeamMemeberModel;
use App\Models\ResortBudgetCost;
use App\Services\EmployeeAllowanceService;
use App\Models\EmployeeAllowance;
use App\Models\FilemangementSystem;
use App\Models\Compliance;
use App\Models\EmployeeBankDetails;
use App\Events\ResortNotificationEvent;
use App\Models\ManningandbudgetingConfigfiles;
use App\Models\EmployeeManningAndBudgeting;
use App\Models\EmployeeManningAndBudgetingConfig;
use Auth;
use App\Models\ResortSiteSettings;
use Hash;
use Config;
use Common;
use DB;
use Carbon\Carbon;
class EmployeeController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index()
    {
        $page_title ='Employees';
        $resort_id = $this->resort->resort_id;
        $teams = SOSTeamManagementModel::where('resort_id',$resort_id)->get();
        $roles = SOSRolesAndPermission::where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('resort_id',$resort_id)->where('status','active')->get();
        $positions = ResortPosition::where('resort_id',$resort_id)->where('status','active')->get();
        $resort_divisions = ResortDivision::where('resort_id',$resort_id)->where('status','active')->get();
        $employees = Employee::with(['resortAdmin','position','department'])->where('resort_id',$resort_id)->latest()->get();
        return view('resorts.people.employee.list',compact('page_title','resort_id','resort_divisions','employees','departments','positions','teams','roles'));
    }


    public function fetchEmployeesGrid(Request $request)
    {
        $query = Employee::with(['resortAdmin', 'position', 'department','education','experiance'])->where('resort_id',$this->resort->resort_id);
        if($request->status == null)
        {
            $query->where('status','!=','Inactive');
        }

        if ($request->searchTerm) {
            $searchTerm = $request->searchTerm;
            $query->where(function ($q) use ($searchTerm) {
                $q->Where('status', 'LIKE', "%{$searchTerm}%")
                ->orWhere('Emp_id','LIKE',"%{$searchTerm}%");

                // Search employee name (first or last)
                $q->orWhereHas('resortAdmin', function ($adminQ) use ($searchTerm) {
                    $adminQ->where(function ($nameQ) use ($searchTerm) {
                        $nameQ->where('first_name', 'LIKE', "%{$searchTerm}%")
                              ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
                    });
                });

                $q->orWhereHas('position', function ($positionQ) use ($searchTerm) {
                    $positionQ->where('position_title', 'LIKE', "%{$searchTerm}%");
                });

                $q->orWhereHas('department', function ($deptQ) use ($searchTerm) {
                    $deptQ->where('name', 'LIKE', "%{$searchTerm}%");
                });
            });
        }
        // Apply filters if present
        if ($request->filled('department_id')) {
            $query->where('Dept_id', $request->department_id);
        }

        if ($request->filled('position_id')) {
            $query->where('Position_id', $request->position_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $pageSize = $request->input('pageSize', 10); // default to 10 if not sent
        $employees = $query->orderBy('created_by', 'desc')->paginate($pageSize);

        return response()->json([
            'html' => view('resorts.renderfiles.employee_grid', compact('employees'))->render(),
            'pagination' => (string) $employees->withQueryString()->links(),
        ]);
    }

    public function fetchEmployeesList(Request $request)
    {

        $query = Employee::with(['resortAdmin', 'position', 'department','education','experiance'])->where('resort_id',$this->resort->resort_id)->where('status','!=','Inactive');

        if ($request->searchTerm) {
            $searchTerm = $request->searchTerm;
            $query->where(function ($q) use ($searchTerm) {
                $q->Where('status', 'LIKE', "%{$searchTerm}%")
                ->orWhere('Emp_id','LIKE',"%{$searchTerm}%");

                // Search employee name (first or last)
                $q->orWhereHas('resortAdmin', function ($adminQ) use ($searchTerm) {
                    $adminQ->where(function ($nameQ) use ($searchTerm) {
                        $nameQ->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"])
                              ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                              ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
                    });
                });

                $q->orWhereHas('position', function ($positionQ) use ($searchTerm) {
                    $positionQ->where('position_title', 'LIKE', "%{$searchTerm}%");
                });

                $q->orWhereHas('department', function ($deptQ) use ($searchTerm) {
                    $deptQ->where('name', 'LIKE', "%{$searchTerm}%");
                });

            });
        }

        if ($request->department_id) {
            $query->where('Dept_id', $request->department_id);
        }

        if ($request->position_id) {
            $query->where('Position_id', $request->position_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

         // ✅ Sorting manually if needed (optional)
        if ($request->has('order')) {
            $columns = $request->input('columns');
            $order = $request->input('order')[0];
            $columnIndex = $order['column'];
            $columnName = $columns[$columnIndex]['data'] ?? 'created_at';
            $direction = $order['dir'] ?? 'desc';

            // Prevent sorting on custom columns like 'action' or 'checkbox'
            if (in_array($columnName, ['Emp_id','employment_type', 'status', 'created_at'])) {
                $query->orderBy($columnName, $direction);
            } else {
                // Fallback to default
                $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc'); // Default sort
        }

        return datatables()->of($query)
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="employee-checkbox" value="'.$row->id.'" />')
            ->addColumn('applicant', fn($row) => '
                <div class="tableUser-block">
                    <div class="img-circle">
                        <img src="'.Common::getResortUserPicture($row->Admin_Parent_id ?? null).'" alt="user">
                    </div>
                    <span class="userApplicants-btn">'.$row->resortAdmin->full_name.'</span>
                </div>')
            ->addColumn('position', fn($row) => $row->position->position_title ?? '')
            ->addColumn('department', fn($row) => $row->department->name ?? '')
            ->addColumn('status', fn($row) => '<span class="badge badge-themeSuccess">'.$row->status.'</span>')
            ->addColumn('employment_type', fn($row) => $row->employment_type) // Optional dynamic
            ->addColumn('action', function ($row) {
                return '
                    <div class="dropdown table-dropdown">
                        <button class="btn btn-secondary dropdown-toggle dots-link" type="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-ellipsis"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="' . route("people.employees.details", base64_encode($row->id)) . '">View Profile</a></li>
                            <li><a class="dropdown-item delete-employee" href="#" data-emp-id="'.$row->id.'">
                            Delete Employee</a></li>
                            <li><a class="dropdown-item add-to-team-btn" href="#" data-emp-id="'.$row->id.'">
                            Add to Team / Assign Role</a></li>
                            <li><a class="dropdown-item change-status" href="#"
                                data-id="'.$row->id.'"
                                data-status="'.$row->status.'">
                                Change Status
                            </a></li>
                        </ul>
                    </div>';
            })
            ->addColumn('created_at', fn($row) => $row->created_at) // Hidden column used for sorting
            ->rawColumns(['checkbox', 'applicant', 'status', 'action'])
            ->make(true);
    }

    public function getAllEmployeeIds(Request $request)
    {
        // Start with a proper query
        $query = Employee::where('resort_id', $this->resort->resort_id)
                        ->where('status', '!=', 'Inactive');

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->position_id) {
            $query->where('position_id', $request->position_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->searchTerm) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->searchTerm . '%')
                ->orWhere('last_name', 'like', '%' . $request->searchTerm . '%');
            });
        }

        $ids = $query->pluck('id')->toArray();

        return response()->json([
            'ids' => $ids,
            'total' => count($ids)
        ]);
    }

    public function exportSelected(Request $request)
    {
        $ids = $request->employee_ids;

        if (empty($ids)) {
            return response()->json(['error' => 'No employees selected'], 400);
        }

        return Excel::download(new SelectedEmployeesExport($ids), 'selected_employees.xlsx');
    }

    public function create()
    {
        $page_title ='Create Employee';
        $resort_id = $this->resort->resort_id;
        $last_emp = Employee::orderBy('id', 'desc')->where('resort_id', $resort_id)->first();
        $resort_prefix = $this->resort->resort->resort_prefix;
        $last_emp ? $employee_id = $resort_prefix.'-'.$last_emp->id+ 1 : $employee_id = $resort_prefix.'-'. 1;
        $resort_divisions = ResortDivision::where('resort_id',$resort_id)->where('status','active')->get();
        $departments = ResortDepartment::where('resort_id',$resort_id)->where('status','active')->get();
        $positions = ResortPosition::where('resort_id',$resort_id)->where('status','active')->get();
        $sections = ResortSection::where('resort_id',$resort_id)->where('status','active')->get();
        $payrollAllowance = ResortBudgetCost::where('resort_id', $resort_id)
            ->where('is_payroll_allowance', '1')
            ->get();
        $nationalitys = config('settings.nationalities');
        $countries = config('settings.countries');

        return view('resorts.people.employee.create',compact('page_title','resort_id','resort_divisions','departments','employee_id','positions','sections','payrollAllowance','nationalitys','countries'));
    }

    public function store(Request $request)
    {

        try{
            $check_admin = ResortAdmin::where('resort_id', $this->resort->resort_id)
                ->where('email', $request->email_address)
                ->where('status', 'active')
                ->first();

            if ($check_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email address already exists for another employee. Please use a different email address.'

                ]);
            }

           DB::beginTransaction();
            $resortAdmin = ResortAdmin::create([
                'resort_id' => $this->resort->resort_id,
                'first_name' => $request->employeeF_name,
                'middle_name' => $request->employeeM_name,
                'last_name' => $request->employeeL_name,
                'email' => $request->email_address,
                'personal_phone' => $request->mobile_num,
                'gender' => $request->gender,
                'address_line_1' => $request->permanent_addLine1,
                'address_line_2'=> $request->parmanent_addline2,
                'city' => $request->parmanent_city,
                'state' => $request->parmanent_state,
                'zip' => $request->parmanent_postal_code,
                'country' => $request->parmanent_country,
            ]);

            $dob = Carbon::createFromFormat('d/m/Y', $request->date_birth)->format('Y-m-d');
            $joining_date = Carbon::createFromFormat('d/m/Y', $request->joining_date)->format('Y-m-d');
            $probation_end_date = null;
            if (!empty($request->probation_end_date)) {
                    $probation_end_date = Carbon::createFromFormat('d/m/Y', $request->probation_end_date)->format('Y-m-d');
            }

            $employee = Employee::create([
                'resort_id' => $this->resort->resort_id,
                'Emp_id' => $request->employee_id,
                'Admin_Parent_id' => $resortAdmin->id,
                'title' => $request->gender =='male'? 'Mr.' : 'Ms.',
                'Dept_id'=> $request->department,
                'Section_id' => $request->section,
                'Position_id' => $request->position,
                'division_id'=> $request->division,
                'reporting_to'=> $request->reporting_person,
                'is_employee' => 1,
                'rank'=> $request->position_rank,
                'status' => 'Active',
                'dob' =>$dob ,
                'marital_status' => $request->marital_status,
                'nationality'=> $request->nationality,
                'blood_group'=> $request->blood_group,
                'religion' => $request->religion,
                'joining_date' =>$joining_date ,
                'employment_type' => $request->employment_status,
                'passport_number' => $request->passport_numb,
                'nid' => $request->nid,
                'present_address' => $request->present_addLine1 . ', ' . $request->present_addLine2.','. $request->parmanent_city.','.$request->parmanent_state.','. $request->parmanent_postal_code.','. $request->parmanent_country,
                'tin' => $request->tin,
                'contract_type'=> $request->contract_type,
                'payment_mode' => $request->payment_mode,
                'probation_end_date' => $probation_end_date,
                'probation_status' => $request->probation_end_date ? 'Active' : 'Confirmed',
                'basic_salary' => $request->basic_salary,
                'basic_salary_currency'=> $request->basic_salary_currency,
                'emg_cont_first_name' => $request->emg_contact_fname,
                'emg_cont_last_name' => $request->emg_contact_lname,
                'emg_cont_email' => $request->emg_contact_email,
                'emg_cont_no' => $request->emg_contact_number,
                'emg_cont_relationship' => $request->emg_contact_relation,
                'emg_cont_nationality' => $request->emg_contact_nationalitys,
                'emg_cont_current_address' => $request->emg_contact_add_addLine1 . ', ' . $request->emg_add_line2 .',' . $request->emg_cont_city.','.$request->emg_cont_state.','.$request->emg_cont_postal_code.','.$request->emg_cont_country,
                'entitled_service_charge' => $request->entitle_service_charge ? 'yes' : 'no',
                'entitled_overtime' => $request->entitle_overtime ? 'yes' : 'no',
                'entitled_public_holiday' => $request->entitle_public_holiday ? 'yes' : 'no',
                'ewt_status' => $request->ewt_status ? 'yes' : 'no',
                'pension' => $request->pension ?? null
            ]);

            $fileManagement = FilemangementSystem::where('resort_id', $this->resort->resort_id)->where('Folder_Name',$employee->Emp_id)->where('Folder_Type', 'categorized')->first();

            if(!$fileManagement)
            {
                $fileManagement = Common::createFolderByName($resortAdmin->resort_id, $employee->Emp_id, 'categorized');
            }

            $folder_name = $fileManagement->Folder_Name;

            if($request->hasFile('cv')){
                $cv = $request->file('cv');
                $aws_cv = Common::AWSEmployeeFileUpload($this->resort->resort_id,$cv,$folder_name);

                if($aws_cv['status'] == 'success'){
                    EmployeesDocument::create([
                        'employee_id' => $employee->id,
                        'resort_id' => $this->resort->resort_id,
                        'document_title' => 'CV',
                        'document_path' => $aws_cv['path'],
                        'document_category' => 'Employement',
                        'document_file_size' => $cv->getSize(),
                        'created_by' => Auth::guard('resort-admin')->user()->id,
                        'modified_by' => Auth::guard('resort-admin')->user()->id,
                    ]);
                }
            }
            if($request->hasFile('full_length_photo')){
                $file_full_length = $request->file('full_length_photo');
                $picture = Common::AWSEmployeeFileUpload($this->resort->resort_id,$file_full_length,$folder_name);

                if($picture['status'] == 'success'){
                    $employee->selfie_image = $picture['path'];
                    $employee->save();
                }
            }
            if($request->hasFile('profile_picture')){
                $file = $request->file('profile_picture');
                $profilePicture = Common::AWSEmployeeFileUpload($this->resort->resort_id,$file,$folder_name);

                if($profilePicture['status'] == 'success'){
                    $resortAdmin->profile_picture = $profilePicture['path'];
                    $resortAdmin->save();
                }
            }


            if(!empty($request->language)){
                foreach ($request->language as $lang) {
                    $language = EmployeeLanguage::create([
                        'employee_id' => $employee->id,
                        'language' => $lang[0],
                        'proficiency_level' => $lang[1],
                    ]);
                }
            }

            if(!empty($request->allowance)){
                foreach ($request->allowance as $allowance) {

                    $employeeAllowance = EmployeeAllowance::create([
                        'employee_id' => $employee->id,
                        'allowance_id' => $allowance['type'],
                        'amount' => $allowance['amount'],
                        'amount_unit' => $allowance['currency'],
                    ]);
                }
            }

            if(!empty($request->bank)){
                foreach ($request->bank as $bank) {

                    $EmployeeBankDetails = EmployeeBankDetails::create([
                        'employee_id' => $employee->id,
                        'bank_name' => $bank['bank_name'],
                        'bank_branch' => $bank['bank_branch'],
                        'account_type' => $bank['account_type'],
                        'IFSC_BIC' => $bank['ifsc'],
                        'account_holder_name' => $bank['account_name'],
                        'account_no' => $bank['account_number'],
                        'currency' => $bank['currency'],
                        'IBAN' => $bank['iban'],
                    ]);
                }
            }

            if(!empty($request->education)){
                foreach ($request->education as $edu) {

                    if($edu['document']){
                        $file = $edu['document'];
                        $edu_uploadedFile = Common::AWSEmployeeFileUpload($this->resort->resort_id,$file,$folder_name);
                    }
                    EmployeeEducation::create([
                        'employee_id' => $employee->id,
                        'education_level' => $edu['education_level'],
                        'institution_name' => $edu['institutio_name'],
                        'field_of_study' => $edu['field_study'],
                        'degree' => $edu['degree_earned'],
                        'attendance_period' => $edu['attendance_period'],
                        'location' => $edu['location'],
                        'certification' => $uploadedFile['path'] ?? null,
                    ]);
                }
            }


            if(!empty($request->experience)){
                foreach ($request->experience as $exp) {

                    if( isset($exp['document']) && $exp['document']){
                        $file = $exp['document'];
                        $uploadedFile = Common::AWSEmployeeFileUpload($this->resort->resort_id,$file,$folder_name);
                    }

                    EmployeeExperiance::create([
                        'employee_id' => $employee->id,
                        'company_name' => $exp['company_name'],
                        'job_title' => $exp['job_title'],
                        'employment_type' => $exp['employment_type'],
                        'duration' => $exp['duration'],
                        'location' => $exp['location'],
                        'reason_for_leaving' => $exp['reason_for_leaving'],
                        'reference_name' => $exp['reference_name'],
                        'reference_contact' => $exp['reference_contact'],
                        'document' => $uploadedFile['path'] ?? null,
                    ]);
                }
            }

            // Check salary  Compliance

            $notify_person = Employee::where('resort_id', $this->resort->resort_id)->where('rank','3')->first();
            if (!$notify_person) {
                $notify_person = Employee::where('resort_id', $this->resort->resort_id)->where('rank','2')->first();
            }

            $minWageMVR = 8021; // Minimum wage in MVR
            $minWageUSD = 520; // Minimum wage in USD
                if($employee->basic_salary < $minWageMVR && $employee->basic_salary_currency == 'MVR' || $employee->basic_salary < $minWageUSD && $employee->basic_salary_currency == 'USD') {

                    event(new ResortNotificationEvent(Common::nofitication(
                        $this->resort->resort_id,
                        10,
                        'Workforce Planning Minimum Wage Compliance Breached',
                        "Employee {$employee->resortAdmin->full_name} has a basic salary {$employee->basic_salary} below the minimum wage.",
                        0,
                        $notify_person->id,
                        'Workforce Planning (Minimum Wage)'
                    )));

                    Compliance::firstOrCreate([
                        'resort_id' => $this->resort->resort_id,
                        'employee_id' => $employee->id,
                        'module_name' => 'Workforce Planning',
                        'compliance_breached_name' => 'Minimum Wage',
                        'description' => "Employee {$employee->resortAdmin->full_name} has a basic salary {$employee->basic_salary} below the minimum wage.",
                        'reported_on' => Carbon::now(),
                        'status' => 'Breached'
                    ]);
                }

               $ManningandbudgetingConfigfiles = ManningandbudgetingConfigfiles::where('resort_id', $this->resort->resort_id)->first();

                $xpat = $ManningandbudgetingConfigfiles->xpat;
                $local = $ManningandbudgetingConfigfiles->local;

                // Get counts
                $totalEmployees = Employee::where('resort_id', $this->resort->resort_id)->count();
                $expatCount = Employee::where('resort_id', $this->resort->resort_id)
                        ->where('nationality', '!=', 'Maldivian')
                        ->count();

                $localCount = Employee::where('resort_id', $this->resort->resort_id)
                        ->where('nationality', 'Maldivian')
                        ->count();

                $compliance = null;
                    // Expat-Local Ratio compliance check
                    if ($totalEmployees > 0 && $xpat > 0 && $local > 0) {
                            // Calculate the expected counts based on configured ratio
                            $total_ratio = $xpat + $local;
                            $expected_expat = ceil($totalEmployees * ($xpat / $total_ratio));
                            $expected_local = ceil($totalEmployees * ($local / $total_ratio));

                            // Check if the actual counts violate the expected ratio
                            if ($expatCount > $expected_expat || $localCount < $expected_local) {
                                // Send notification to resort admin
                                event(new ResortNotificationEvent(Common::nofitication(
                                        $this->resort->resort_id,
                                        10,
                                        'Workforce Planning Expat-Local Ratio Compliance Breached',
                                        "Expat count ({$expatCount}) exceeds expected ({$expected_expat}) or Local count ({$localCount}) is below expected ({$expected_local}).",
                                        0,
                                        $notify_person->id,
                                        'Workforce Planning (Expat-Local Ratio)'
                                )));

                                $compliance = Compliance::firstOrCreate([
                                        'resort_id' => $this->resort->resort_id,
                                        'employee_id' => null,
                                        'module_name' => 'Workforce Planning',
                                        'compliance_breached_name' => 'Expat-Local Ratio',
                                        'description' => "Expat count ({$expatCount}) exceeds expected ({$expected_expat}) or Local count ({$localCount}) is below expected ({$expected_local})",
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached'
                                ]);
                            }
                    }
                    // Expat-Local Ratio compliance End

                    if($employee->nationality != 'Maldivian'){
                        $ResortPosition = ResortPosition::where('resort_id', $this->resort->resort_id)
                            ->where('id', $employee->Position_id)
                            ->first();
                        if($ResortPosition->is_reserved == 'Yes'){
                            event(new ResortNotificationEvent(Common::nofitication(
                                    $this->resort->resort_id,
                                    10,
                                    'Workforce Planning Reserved Position Compliance Breached',
                                    "Position {$ResortPosition->position_title} is reserved for Local Candidate, but employee {$employee->resortAdmin->full_name} is not Maldivian.",
                                    0,
                                    $notify_person->id,
                                    'Workforce Planning (Reserved Position)'
                            )));

                            $compliance = Compliance::firstOrCreate([
                                    'resort_id' => $this->resort->resort_id,
                                    'employee_id' => null,
                                    'module_name' => 'Workforce Planning (Reserved Position)',
                                    'compliance_breached_name' => 'Reserved Position',
                                    'description' => "Position {$ResortPosition->position_title} is reserved for Local Candidate, but employee {$employee->resortAdmin->full_name} is not Maldivian.",
                                    'reported_on' => Carbon::now(),
                                    'status' => 'Breached'
                            ]);
                        }
                    }

                    $startDate = Carbon::createFromFormat('Y-m-d', $employee->joining_date);
                    $probationMonths = $startDate->diffInMonths($employee->probation_end_date);
                    // Check if probation period is more than 3 months
                    if ($probationMonths > 3 && $employee->employment_type =='Probationary') {
                        Compliance::create([
                            'resort_id' => $this->resort->resort_id,
                            'employee_id' => $employee->id,
                            'module_name' => 'Probation',
                            'compliance_breached_name' => 'Extended Probation Period',
                            'description' => "Probation period for " . $employee->resortAdmin->full_name . "(" . $employee->position->position_title . ') is set to ' . $probationMonths . ' months. Reduce to comply with the 3-month maximum',
                            'reported_on' => Carbon::now(),
                            'status' => 'Breached'
                        ]);

                        event(new ResortNotificationEvent(Common::nofitication(
                            $this->resort->resort_id,
                            10,
                            'Extended Probation Period',
                            "Probation period for " . $probation->resortAdmin->full_name . "(" . $probation->position->position_title . ") is set to " . $probationMonths . " months. Reduce to comply with the 3-month maximum",
                            0,
                            $notify_person->id,
                            'Probation'
                        )));

                    }


            DB::commit();
            Session::forget('employee_form');
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Employee created successfully!',
                'redirect' => route('people.employees')
            ]);
       }catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error creating employee: ' . $e->getMessage(),
            ]);
        }
    }

    public function details($id)
    {
        $page_title ='Employee Details';
        $resort_id = $this->resort->resort_id;
        $departments = ResortDepartment::where('resort_id',$resort_id)->where('status','active')->get();
        $positions = ResortPosition::where('resort_id',$resort_id)->where('status','active')->get();
        $sections = ResortSection::where('resort_id',$resort_id)->where('status','active')->get();
        $resort_divisions = ResortDivision::where('resort_id',$resort_id)->where('status','active')->get();
        $resort_allowances = ResortBudgetCost::where('resort_id', $resort_id)->where('is_payroll_allowance',1)->get();
        $employee = Employee::with(['resortAdmin','position','department','division','section','education','experiance','allowance','language','sosTeams','document','bankDetails','reportingTo.resortAdmin'])->where('id',base64_decode($id))->first();
        $emp_benigit_grid = Common::getBenefitGrid($employee->position->Rank,$this->resort->resort_id);
        // dd($employee);
        $benefit_grid = DB::table('resort_benifit_grid as rbg')
            ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
            ->where('rbg.emp_grade', $employee->position->Rank)
            ->where('rbg.status','active')
            ->get();
        $benefitGrids = ResortBenifitGrid::where('resort_id',$this->resort->resort_id)->where('status','active')->get();
        // Calculate total leaves taken by the employee for the current year
        $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
        $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');
        $leavesTaken = DB::table('employees_leaves')
            ->where('emp_id', base64_decode($id))
            ->where('status', 'Approved')
            ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                    ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
            })
            ->sum('total_days');

        // Total allocation (sum of all allocated days across leave categories)
        if($benefit_grid->isEmpty()) {
            $totalAllocation = 0; // No benefits found, set to 0
        } else {
            $totalAllocation = $benefit_grid->sum('allocated_days');
        }
        $remianing_leaves = $totalAllocation - $leavesTaken;
        $teams = SOSTeamManagementModel::where('resort_id',$resort_id)->get();
        $roles = SOSRolesAndPermission::where('resort_id',$resort_id)->get();
        $nationality = config('settings.nationalities');
        if($employee->nationality != "Maldivian")
        {
            $costs = ResortBudgetCost::where('resort_id', $resort_id)->where('cost_title','Operational Cost')->whereIn('details',['Both','Xpat Only'])->get();
        }
        else
        {
            $costs = ResortBudgetCost::where('resort_id', $resort_id)->where('cost_title','Operational Cost')->whereIn('details',['Both','Locals Only'])->get();
        }

        $ResortSiteSettings = ResortSiteSettings::where('resort_id', $resort_id)->first();

        // Convert basic salary and allowances to MVR
        $conversionRate = $ResortSiteSettings->DollertoMVR;
        $basicMvr = $employee->basic_salary_currency === 'USD' ? $employee->basic_salary * $conversionRate : $employee->basic_salary;
        $totalAllowanceMvr = 0;
        foreach ($employee->allowance as $allowance)
        {
            $amt = $allowance->amount ?? 0;
            $unit = $allowance->amount_unit ?? 'USD';
            $totalAllowanceMvr += $unit === 'USD' ? ($amt * $conversionRate) : $amt;
        }
            $totalMonthlyEarningMvr = $basicMvr + $totalAllowanceMvr;
            $tin = $employee->tin ?? null;

            $notify_person = Employee::where('resort_id', $resort_id)->where('rank','3')->first();
            if (!$notify_person)
            {
                $notify_person = Employee::where('resort_id', $resort_id)->where('rank','2')->first();
            }
            if($totalMonthlyEarningMvr >= 30000 && !$tin)
            {
                // event(new ResortNotificationEvent(Common::nofitication(
                //     $this->resort->resort_id,
                //     10,
                //     'TIN Required for Employee',
                //     "{$employee->resortAdmin->full_name} ({$employee->Emp_id} - {$employee->position->position_title}) (RSWT: MVR {$totalMonthlyEarningMvr}/month) not registered. Submit MIRA 118 form.",
                //     0,
                //     $notify_person->id,
                //     'People Management (TIN Requirement)'
                // )));

                Compliance::firstOrCreate([
                    'resort_id' => $this->resort->resort_id,
                    'employee_id' => $employee->id,
                    'module_name' => 'People Management',
                    'compliance_breached_name' => 'TIN Requirement',
                    'description' => "{$employee->resortAdmin->full_name} ({$employee->Emp_id} - {$employee->position->position_title}) (RSWT: MVR {$totalMonthlyEarningMvr}/month) not registered. Submit MIRA 118 form.",
                    'reported_on' => Carbon::now(),
                    'status' => 'Breached'
                ]);
            }

            $minWageMVR = 8021; // Minimum wage in MVR
            $minWageUSD = 520; // Minimum wage in MVR
            // Check if the current employee's salary is below minimum wage
            $isBelowMinWage = false;

            if ($employee->basic_salary_currency == 'MVR' && $employee->basic_salary < $minWageMVR) {
                $isBelowMinWage = true;
            } elseif
                ($employee->basic_salary_currency == 'USD' && $employee->basic_salary < $minWageUSD) {
                $isBelowMinWage = true;
            }

            if ($isBelowMinWage) {
                // Create compliance record if employee's salary is below minimum wage
                Compliance::firstOrCreate([
                    'resort_id' => $this->resort->resort_id,
                    'employee_id' => $employee->id,
                    'module_name' => 'People Management',
                    'compliance_breached_name' => 'Minimum Wage',
                    'description' => "Employee {$employee->resortAdmin->full_name} has a basic salary below the minimum wage.",
                    'reported_on' => Carbon::now(),
                    'status' => 'Breached'
                ]);

                // Send notification
                // event(new ResortNotificationEvent(Common::nofitication(
                //     $this->resort->resort_id,
                //     10,
                //     'People Management Minimum Wage Compliance Breached',
                //     "Employee {$employee->resortAdmin->full_name} has a basic salary {$employee->basic_salary} {$employee->basic_salary_currency} below the minimum wage.",
                //     0,
                //     $notify_person->id,
                //     'People Management (Minimum Wage)'
                // )));
            }

        return view('resorts.people.employee.detail',compact('page_title','conversionRate','teams','roles','resort_id','resort_divisions','employee','departments','positions','remianing_leaves','nationality','benefitGrids','sections','costs','emp_benigit_grid','resort_allowances'));
    }

    public function assignToTeam(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'emp_id' => 'required|exists:employees,id',
            'team_id' => 'required|exists:sos_teams,id',
            'role_id' => 'required|exists:sos_role_management,id'
        ]);

        $resortId = Auth::guard('resort-admin')->user()->resort_id;
        $employee = Employee::with('resortAdmin')
                            ->where('id', $request->emp_id)
                            ->where('resort_id', $resortId)
                            ->first();

        $alreadyExists = SOSTeamMemeberModel::where('emp_id', $employee->Admin_Parent_id)
                            ->where('team_id', $request->team_id)
                            ->where('resort_id', $resortId)
                            ->exists();

        if ($alreadyExists) {
            return response()->json(['status' => 'error', 'message' => 'This employee is already a member of the selected SOS team.']);
        }

        SOSTeamMemeberModel::create([
            'resort_id' => $resortId,
            'emp_id' => $employee->Admin_Parent_id,
            'team_id' => $request->team_id,
            'role_id' => $request->role_id
        ]);

        return response()->json(['status' => 'success', 'message' => 'Employee assigned to SOS Team successfully!']);
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            'emp_id' => 'required|exists:employees,id',
            'status' => 'required|in:Active,Inactive,Terminated,Resigned,On Leave,Suspended'
        ]);


        $employee = Employee::find($request->emp_id);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }
        ResortAdmin::where('id', $employee->Admin_Parent_id)
            ->update(['status' => $request->status]);
        $employee->status = $request->status;
        $employee->save();

        return response()->json(['success' => true]);
    }

    public function sendCredentials(Request $request)
    {
        try {

            $employee = Employee::with('resortAdmin')->findOrFail($request->employee_id);

            if (!$employee->resortAdmin || !$employee->resortAdmin->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee does not have a valid email address.'
                ]);
            }

            $plainPassword = Common::generateUniquePassword(8);
            $hashedPassword = Hash::make($plainPassword);

            $resortAdmin = $employee->resortAdmin;
            $resortAdmin->password = $hashedPassword;
            $resortAdmin->save();
            $resortAdmin->sendResortemployee($this->resort->resort, $resortAdmin, $plainPassword);

            return response()->json([
                'success' => true,
                'message' => 'Credentials sent successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send credentials: ' . $e->getMessage()
            ]);
        }
    }

    public function updatePersonal(Request $request)
    {
        $formattedDOB = \Carbon\Carbon::createFromFormat('d/m/Y', $request->dob)->format('Y-m-d');

        $employee = Employee::findOrFail($request->employee_id);
        $employee->title = $request->title;
        $employee->dob = $formattedDOB;
        $employee->marital_status = $request->marital_status;
        $employee->nationality = $request->nationality;
        $employee->religion = $request->religion;
        $employee->blood_group = $request->blood_group;
        $employee->passport_number = $request->passport_number;
        $employee->nid = $request->nid;
        $employee->save();

        // Update name and gender in resortAdmin
        $employee->resortAdmin->first_name = $request->first_name;
        $employee->resortAdmin->last_name = $request->last_name;
        $employee->resortAdmin->gender = $request->gender;
        $employee->resortAdmin->save();

        return response()->json(['success' => true ,'message' => "Personal Details Updated!"]);
    }

    public function updateContacts(Request $request)
    {

        $employee = Employee::findOrFail($request->employee_id);
        $employee->present_address = $request->present_address;
        $employee->save();
        // Update name and gender in resortAdmin
        $employee->resortAdmin->personal_phone = $request->personal_phone;
        $employee->resortAdmin->email = $request->email;
        $employee->resortAdmin->address_line_1 = $request->address_line_1;
        $employee->resortAdmin->address_line_2 = $request->address_line_2;
        $employee->resortAdmin->city = $request->city;
        $employee->resortAdmin->state = $request->state;
        $employee->resortAdmin->country = $request->country;
        $employee->resortAdmin->zip = $request->zip;

        $employee->resortAdmin->save();

        return response()->json(['success' => true ,'message' => "Contacts Details Updated!"]);
    }

    public function updateEmergencyContacts(Request $request)
    {

        $employee = Employee::findOrFail($request->employee_id);
        $employee->emg_cont_first_name = $request->emg_cont_first_name;
        $employee->emg_cont_last_name = $request->emg_cont_last_name;
        $employee->emg_cont_no = $request->emg_cont_no;
        $employee->emg_cont_relationship = $request->emg_cont_relationship;
        $employee->emg_cont_email = $request->emg_cont_email;
        $employee->emg_cont_current_address = $request->emg_cont_current_address;
        $employee->save();

        return response()->json(['success' => true ,'message' => "Emegency Contacts Details Updated!"]);
    }

    public function updateAdditionalInfo(Request $request)
    {
        $request->validate([
            'leave_destination' => 'nullable|string|max:255',
            'biometric_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'languages.*.language' => 'required|string',
            'languages.*.proficiency_level' => 'required|string',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        $employee->leave_destination = $request->leave_destination;

        $encodedEmployeeID = base64_encode($request->employee_id);
        // $baseUploadPath = config('settings.employee_biometrics');
        // $uploadPath = $baseUploadPath . '/' . $encodedEmployeeID;

        // if (!file_exists(public_path($uploadPath))) {
        //     mkdir(public_path($uploadPath), 0755, true);
        // }
        $folderName = FilemangementSystem::where('resort_id', $this->resort->resort_id)
            ->where('Folder_Name', $employee->Emp_id)
            ->where('Folder_Type', 'categorized')
            ->first();


        if ($request->hasFile('biometric_file')) {

            $file = $request->file('biometric_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $aws = Common::AWSEmployeeFileUpload($this->resort->resort_id, $file, $folderName->Folder_Name);
            if($aws['status'] == true){
                $employee->biometric_file = $aws['path'];
            }
        }

        $employee->save();

        // Save languages
        if ($request->has('languages')) {
            // Delete old ones first (optional, depending on logic)
            $employee->language()->delete();

            foreach ($request->languages as $lang) {
                $employee->language()->create([
                    'language' => $lang['language'],
                    'proficiency_level' => $lang['proficiency_level'],
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Additional Information updated successfully.',
        ]);
    }

    public function updateEmploymentData(Request $request)
    {
        $formattedJoinDate = $request->joining_date ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->joining_date)->format('Y-m-d') : null;
        $formattedProbationEndDate = $request->probation_end_date ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->probation_end_date)->format('Y-m-d') : null;
        $formattedTerminationDate = $request->termination_date ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->termination_date)->format('Y-m-d') : null;


        $position = ResortPosition::find($request->Position_id);
        $grade = Common::getEmpGrade($position->Rank);

        $benefitGrid = ResortBenifitGrid::where('resort_id', $this->resort->resort_id)
            ->where('emp_grade', $grade)
            ->where('status', 'active')
            ->first();

        $employee = Employee::findOrFail($request->employee_id);
        $employee->status = $request->status;
        $employee->joining_date = $formattedJoinDate;
        $employee->benefit_grid_level = $request->benefit_grid_level;
        $employee->tin = $request->tin;
        $employee->probation_end_date = $formattedProbationEndDate;
        $employee->contract_type = $request->contract_type;
        $employee->termination_date = $formattedTerminationDate;
        $employee->Position_id = $request->Position_id;
        $employee->Section_id = $request->Section_id;
        $employee->Dept_id = $request->Dept_id;
        $employee->division_id = $request->division_id;
        $employee->reporting_to = $request->reporting_to ? $request->reporting_to : null;

        if( $benefitGrid)
        {
            $employee->entitled_service_charge = $benefitGrid->service_charge == 1 ? 'yes' : 'no';
            $employee->entitled_overtime = $benefitGrid->overtime;
            $employee->entitled_public_holiday = $benefitGrid->paid_worked_public_holiday_and_friday == 1 ? 'yes' : 'no';
        }
        $employee->save();

        // Update name and gender in resortAdmin
        $employee->resortAdmin->email = $request->email;
        $employee->resortAdmin->personal_phone = $request->personal_phone;
        $employee->resortAdmin->save();

        return response()->json(['success' => true ,'message' => "Employment data Updated!"]);
    }

    public function updateSalary(Request $request)
    {
        DB::beginTransaction();

        try {
            $employeeId = $request->input('employee_id');
            $conversionRate = 15.42; // USD to MVR
            $basicSalary = floatval($request->input('basic_salary'));
            $basicSalaryCurrency = $request->input('basic_salary_currency', 'MVR');

            // Convert to MVR for pension & EWT check
            $basicSalaryInMVR = $basicSalaryCurrency === 'USD' ? $basicSalary * $conversionRate : $basicSalary;

            // Calculate total allowances in MVR
            $allowances = $request->input('allowances', []);
            $totalAllowanceMVR = 0;

            foreach ($allowances as $allowance) {
                $amount = floatval($allowance['amount']);
                $unit = $allowance['amount_unit'] ?? 'USD';
                $totalAllowanceMVR += $unit === 'USD' ? $amount * $conversionRate : $amount;
            }

            $totalEarningMVR = $basicSalaryInMVR + $totalAllowanceMVR;
            $ewtEligible = $totalEarningMVR >= 30000;

            // Calculate pension: 7% of basic salary (in MVR)
            $pensionFinal = round(($basicSalaryInMVR * 0.07), 2);

            // Update employee
            $employee = Employee::findOrFail($employeeId);
            $employee->basic_salary = $basicSalary;
            $employee->basic_salary_currency = $basicSalaryCurrency;
            $employee->payment_mode = $request->input('payment_mode');
            $employee->pension = $pensionFinal;
            $employee->ewt = $request->input('ewt');
            $employee->entitled_service_charge = $request->input('entitle_service_charge') ? 'yes' : 'no';
            $employee->entitled_overtime = $request->input('entitle_overtime') ? 'yes' : 'no';
            $employee->entitled_public_holiday = $request->input('entitle_public_holiday') ? 'yes' : 'no';
            $employee->ewt_status = $request->input('ewt_status') ? 'yes' : 'no';
            $employee->save();


            $notify_person = Employee::where('resort_id', $this->resort->resort_id)->where('rank','3')->first();
            if (!$notify_person) {
                $notify_person = Employee::where('resort_id', $this->resort->resort_id)->where('rank','2')->first();
            }




            // Save/update allowances
            foreach ($allowances as $allowance) {
                $allowanceId = $allowance['type'];
                $amount = $allowance['amount'];
                $amountUnit = $allowance['amount_unit'] ?? 'USD';

                $existing = EmployeeAllowance::where('employee_id', $employeeId)
                    ->where('allowance_id', $allowanceId)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'amount' => $amount,
                        'amount_unit' => $amountUnit,
                    ]);
                } else {
                    EmployeeAllowance::create([
                        'employee_id' => $employeeId,
                        'allowance_id' => $allowanceId,
                        'amount' => $amount,
                        'amount_unit' => $amountUnit,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'ewt_eligible' => $ewtEligible,
                'ewt_enrolled' => !empty($employee->tin), // TIN number is proof of enrollment
                'total_earning_mvr' => $totalEarningMVR,
                'tin_no' => $employee->tin,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function updateBankDetails(Request $request, $id)
    {
        // Find the education record by ID
        $bank_details = EmployeeBankDetails::find($id);

        if (!$bank_details) {
            return response()->json([
                'status' => false,
                'message' => 'Bank details not found.'
            ], 404);
        }

        // Update the education record
        $bank_details->bank_name = $request->input('bank_name');
        $bank_details->bank_branch = $request->input('bank_branch');
        $bank_details->account_type = $request->input('account_type');
        $bank_details->IFSC_BIC = $request->input('IFSC_BIC');
        $bank_details->account_holder_name = $request->input('account_holder_name');
        $bank_details->account_no = $request->input('account_no');
        $bank_details->currency = $request->input('currency');
        $bank_details->IBAN = $request->input('IBAN');

        $bank_details->save();

        // Return a success JSON response
        return response()->json([
            'status' => true,
            'message' => 'Bank details updated successfully.'
        ]);
    }

    public function addBankDetails(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'bank_branch' => 'required|string|max:255',
            'account_type' => 'nullable|string|max:255',
            'IFSC_BIC' => 'nullable|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
            'account_no' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:255',
            'IBAN' => 'nullable|string|max:255',

        ]);

        $bank_details = new EmployeeBankDetails();
        $bank_details->bank_name = $request->bank_name;
        $bank_details->employee_id = $request->employee_id;
        $bank_details->bank_branch = $request->bank_branch;
        $bank_details->account_type = $request->account_type;
        $bank_details->IFSC_BIC = $request->IFSC_BIC;
        $bank_details->account_holder_name = $request->account_holder_name;
        $bank_details->account_no = $request->account_no;
        $bank_details->currency = $request->currency;
        $bank_details->IBAN = $request->IBAN;

        $bank_details->save();

        return response()->json([
            'status' => true,
            'message' => 'Bank details added successfully.'
        ]);
    }


    public function updateEducationDetails(Request $request, $id)
    {
        // Find the education record by ID
        $education = EmployeeEducation::find($id);

        if (!$education) {
            return response()->json([
                'status' => false,
                'message' => 'Education record not found.'
            ], 404);
        }

        // Update the education record
        $education->education_level = $request->input('education_level');
        $education->institution_name = $request->input('institution_name');
        $education->field_of_study = $request->input('field_of_study');
        $education->degree = $request->input('degree');
        $education->attendance_period = $request->input('attendance_period');
        $education->location = $request->input('location');

        $encodedEmployeeID = base64_encode($education->employee_id);
        // $baseUploadPath = config('settings.employee_certificates');
        // $uploadPath = $baseUploadPath . '/' . $encodedEmployeeID;

        // if (!file_exists(public_path($uploadPath))) {
        //     mkdir(public_path($uploadPath), 0755, true);
        // }

        // if ($request->hasFile('certification')) {
        //     $file = $request->file('certification'); // ✅ Fix: define $file
        //     $fileName = time() . '_' . $file->getClientOriginalName();
        //     $filePath = Common::uploadFile($file, $fileName, $uploadPath);
        //     $education->certification = $filePath;
        // }
        $folderName = FilemangementSystem::where('resort_id', $this->resort->resort_id)
            ->where('Folder_Name', $education->employee_id)
            ->where('Folder_Type', 'categorized')
            ->first();

        if ($request->hasFile('certification')) {
            $file = $request->file('certification'); // ✅ Fix: define $file
            $fileName = time() . '_' . $file->getClientOriginalName();
            $aws = Common::AWSEmployeeFileUpload($this->resort->resort_id, $file, $folderName->Folder_Name);
            if($aws['status'] == true){
                $education->certification = $filePath;
            }
        }

        $education->save();

        // Return a success JSON response
        return response()->json([
            'status' => true,
            'message' => 'Education details updated successfully.'
        ]);
    }

    public function addEducationDetails(Request $request)
    {
        $request->validate([
            'education_level' => 'required|string|max:255',
            'institution_name' => 'required|string|max:255',
            'field_of_study' => 'nullable|string|max:255',
            'degree' => 'nullable|string|max:255',
            'attendance_period' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $education = new EmployeeEducation();
        $education->employee_id = $request->employee_id;
        $education->education_level = $request->education_level;
        $education->institution_name = $request->institution_name;
        $education->field_of_study = $request->field_of_study;
        $education->degree = $request->degree;
        $education->attendance_period = $request->attendance_period;
        $education->location = $request->location;

        // Handle certificate file upload
        // $encodedEmployeeID = base64_encode($request->employee_id);
        // $baseUploadPath = config('settings.employee_certificates');
        // $uploadPath = $baseUploadPath . '/' . $encodedEmployeeID;

        // if (!file_exists(public_path($uploadPath))) {
        //     mkdir(public_path($uploadPath), 0755, true);
        // }

        // if ($request->hasFile('certification')) {
        //     $file = $request->file('certification'); // ✅ Fix: define $file
        //     $fileName = time() . '_' . $file->getClientOriginalName();
        //     $filePath = Common::uploadFile($file, $fileName, $uploadPath);
        //     $education->certification = $filePath;
        // }

        $folderName = FilemangementSystem::where('resort_id', $this->resort->resort_id)
            ->where('Folder_Name', $education->employee_id)
            ->where('Folder_Type', 'categorized')
            ->first();

        if ($request->hasFile('certification')) {
            $file = $request->file('certification'); // ✅ Fix: define $file
            $fileName = time() . '_' . $file->getClientOriginalName();

            $aws = Common::AWSEmployeeFileUpload($this->resort->resort_id, $file, $folderName->Folder_Name);
            if($aws['status'] == true){
                $education->certification = $aws['path'];
            }
        }

        $education->save();

        return response()->json([
            'status' => true,
            'message' => 'Education details added successfully.'
        ]);
    }

    public function updateExperianceDetails(Request $request, $id)
    {
        // Find the education record by ID
        $exp = EmployeeExperiance::find($id);

        if (!$exp) {
            return response()->json([
                'status' => false,
                'message' => 'Experiance record not found.'
            ], 404);
        }

        // Update the education record
        $exp->company_name = $request->input('company_name');
        $exp->job_title = $request->input('job_title');
        $exp->employment_type = $request->input('employment_type');
        $exp->duration = $request->input('duration');
        $exp->location = $request->input('location');
        $exp->reason_for_leaving = $request->input('reason_for_leaving');
        $exp->reference_name = $request->input('reference_name');
        $exp->reference_contact = $request->input('reference_contact');

        $exp->save();

        // Return a success JSON response
        return response()->json([
            'status' => true,
            'message' => 'Experiance details updated successfully.'
        ]);
    }

    public function addExperianceDetails(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'employment_type' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:255',
            'reason_for_leaving' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'reference_name' => 'nullable|string|max:255',
            'reference_contact' => 'nullable|string|max:255',
        ]);

        $exp = new EmployeeExperiance();
        $exp->employee_id = $request->employee_id;
        $exp->company_name = $request->company_name;
        $exp->job_title = $request->job_title;
        $exp->employment_type = $request->employment_type;
        $exp->duration = $request->duration;
        $exp->reason_for_leaving = $request->reason_for_leaving;
        $exp->location = $request->location;
        $exp->reference_name = $request->reference_name;
        $exp->reference_contact = $request->reference_contact;
        $exp->save();

        return response()->json([
            'status' => true,
            'message' => 'Experiance details added successfully.'
        ]);
    }

    public function updateExpiryDocuments(Request $request)
    {
        $documentIds = $request->document_ids;
        if (empty($documentIds)) {
            return response()->json(['success' => false, 'message' => 'No documents provided.'], 501);
        }
        $documentTitles = $request->document_titles;
        $expiryDates = $request->expiry_dates;

        foreach ($documentIds as $index => $docId) {
            $document = EmployeesDocument::find($docId);
            if ($document) {
                $document->document_title = $documentTitles[$index];
                $document->expiry_date = \Carbon\Carbon::createFromFormat('d/m/Y', $expiryDates[$index])->format('Y-m-d');
                $document->save();
            }
        }

        return response()->json(['success' => true, 'message' => 'Documents updated successfully.']);
    }


    public function extractDetails(Request $request){

        $request->validate([
            'document' => 'required|mimes:pdf,jpg,jpeg,png|max:20480',
        ]);

        $file = $request->file('document');
        if (!$file->isValid()) {
            return response()->json(['success' => false, 'message' => 'The document failed to upload.'], 400);
        }
        $flag = $request->doc_type;
        $url = env('AI_URL').'extract_education_exp_details?doc_type='.$flag;
        if($flag)
        {
            $curl = curl_init();
            $postFields = [
                'file' => new \CURLFile($file->getRealPath(), $file->getMimeType(), $file->getClientOriginalName()),
                'doc_type' => $flag,
            ];
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                ],
            ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if($err)
            {
                return response()->json(['success' => false, 'message' =>  $err]);
            }
            $AI_Data = json_decode($response, true);
        }
        else
        {
            $AI_Data = $response;
        }
        return response()->json(['success' => true, 'data' => $AI_Data ?? null]);
    }


    public function saveStep(Request $request)
    {
        try {

            $step = $request->step;
            if($step == 1){
                Session::forget('employee_form');
            }
            $sessionData = $request->except('step', '_token');

            $existingData = Session::get('employee_form', []);
            $existingData[$step] = $sessionData;

            Session::put('employee_form', $existingData);

            return response()->json(['success' => true, 'message' => 'Step data saved successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDraft(Request $request){
        $step = $request->step;
        $sessionData = Session::get('employee_form', []);

        if (isset($sessionData[$step])) {
            return response()->json(['success' => true, 'data' => $sessionData[$step]]);
        }
        return response()->json(['success' => false, 'message' => 'No data found for this step.']);
    }

    public function getDepartmentByDivision(Request $request){
        $divisionId = $request->division_id;
        $departments = ResortDepartment::where('resort_id', $this->resort->resort_id)
            ->where('division_id', $divisionId)
            ->where('status', 'active')
            ->get();
        return response()->json(['success' => true, 'departments' => $departments]);
    }

    public function getSectionByDepartment(Request $request){

        $departmentId = $request->department_id;
        $sections = ResortSection::where('resort_id', $this->resort->resort_id)
            ->where('dept_id', $departmentId)
            ->where('status', 'active')
            ->get();
        return response()->json(['success' => true, 'sections' => $sections]);
    }

    public function getPositionBySection(Request $request){

        if(!$request->has('section_id') || empty($request->section_id)){
            $departmentId = $request->department_id;
            $positions = ResortPosition::where('resort_id', $this->resort->resort_id)
                ->where('dept_id', $departmentId)
                ->where('status', 'active')
                ->get();
            return response()->json(['success' => true, 'positions' => $positions]);
        }else{
            $sectionId = $request->section_id;
            $positions = ResortPosition::where('resort_id', $this->resort->resort_id)
                ->where('section_id', $sectionId)
                ->where('status', 'active')
                ->get();
            return response()->json(['success' => true, 'positions' => $positions]);
        }
    }

    public function getBenefitGridByPosition(Request $request){

        $positionId = $request->position_id;
        $position = ResortPosition::find($positionId);
        $grade = Common::getEmpGrade($position->Rank);

        $benefitGrid = ResortBenifitGrid::where('resort_id', $this->resort->resort_id)
            ->where('status', 'active')
            ->where('emp_grade', $grade)
            ->first();

        if (!$benefitGrid) {
            return response()->json(['success' => false, 'message' => 'No benefit grid found for this position.'], 404);
        }

        return response()->json([
            'success' => true,
            'benfitGrid_emp_id' => $benefitGrid->emp_grade,
            'position_rank' => $position->Rank,
            'emp_grade_name' => config('settings.Position_Rank')[$benefitGrid->emp_grade] ?? null,
            'service' => $benefitGrid->service_charge == 1 ? 'yes' : 'no',
            'overtime' => $benefitGrid->overtime,
            'holiday_overtime' => $benefitGrid->paid_worked_public_holiday_and_friday == 1 ? 'yes' : 'no',
        ]);
    }


    public function getReportingPerson(Request $request){

        $Dept_id = $request->department_id;
        $targetRanks = [
            array_search('HOD', config('settings.Position_Rank')),
            array_search('MGR', config('settings.Position_Rank')),
            array_search('GM', config('settings.Position_Rank')),
            array_search('SUP', config('settings.Position_Rank')),
            array_search('EXCOM', config('settings.Position_Rank'))
        ];

        // Get all employees with reporting ranks (all HODs regardless of department, plus other ranks)
        $reportingEmployees = DB::table('employees')
            ->join('resort_admins', 'employees.Admin_Parent_id', '=', 'resort_admins.id')
            ->where('employees.resort_id', $this->resort->resort_id)
            ->where('employees.status', '!=', 'Inactive')
            ->whereIn('employees.rank', $targetRanks)
            ->select(
                'employees.*',
                'resort_admins.first_name as first_name',
                'resort_admins.last_name as last_name',
                'resort_admins.email as admin_email'
            )
            ->orderBy('employees.rank', 'asc')
            ->orderBy('resort_admins.first_name', 'asc')
            ->get();
        return response()->json(['success' => true, 'data' => $reportingEmployees]);

    }

    public function delete(Request $request)
    {
        $employee = Employee::find($request->id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        // Update status to inactive instead of deleting, and set deleted_at timestamp
        $employee->status = 'Inactive';
        $employee->deleted_at = now();
        $employee->save();

        return response()->json(['message' => 'Employee deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['message' => 'No employees selected.'], 400);
        }

        DB::beginTransaction();
        try {
            $employees = Employee::whereIn('id', $ids)->get();

            foreach ($employees as $employee) {
                $employee->status = 'Inactive';
                $employee->save();
            }

            DB::commit();
            return response()->json(['message' => 'Selected employees deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete employees.'], 500);
        }
    }

}

