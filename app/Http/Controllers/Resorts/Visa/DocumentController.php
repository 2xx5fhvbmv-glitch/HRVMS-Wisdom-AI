<?php

namespace App\Http\Controllers\Resorts\Visa;

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
use App\Models\EmployeeBankDetails;
use App\Events\ResortNotificationEvent;
use App\Models\Compliance;
use App\Models\ManningandbudgetingConfigfiles;
use Auth;
use Config;
use Common;
use DB;
use Carbon\Carbon;
use App\Models\VisaDocumentType;

class DocumentController extends Controller
{


     public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;

    }
    public function index()
    {

        $page_title ="Document Management";
        $documentTypes = VisaDocumentType::select('id', 'documentname')->get();

        $last_emp = Employee::orderBy('id', 'desc')->where('resort_id', $this->resort->resort_id)->first();
        $resort_prefix = $this->resort->resort->resort_prefix;
        
        $employee_id = $resort_prefix.'-'.$last_emp->id + 1;

        $nationalitys = config('settings.nationalities');
        $countries = config('settings.countries');
        $resort_id = $this->resort->resort_id;

        
        $resort_divisions    = ResortDivision::where('resort_id',$resort_id)->where('status','active')->get();
        $departments         = ResortDepartment::where('resort_id',$resort_id)->where('status','active')->get();
        $positions           = ResortPosition::where('resort_id',$resort_id)->where('status','active')->get();
        $sections            = ResortSection::where('resort_id',$resort_id)->where('status','active')->get();
        $payrollAllowance    = ResortBudgetCost::where('resort_id', $resort_id)->where('is_payroll_allowance', '1')->get();
        $nationalitys        = config('settings.nationalities');
        $countries           = config('settings.countries');

        return view('resorts.Visa.document.index',compact('page_title', 'documentTypes','nationalitys','countries','departments','positions',
        'sections','resort_divisions','payrollAllowance','employee_id'));
    }
    public function Xpatsync(Request $request)
    {
        $page_title ="Xpat Sync";
        return view('resorts.Visa.document.xpatsync',compact('page_title'));
    }

    public function FetchAithrowData(Request $request)
    { 
        $url  = env('AI_URL');
        dd($url );
    }
    public function CreateEmployee(Request $request)
    {
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
        try{
            $resortAdmin = ResortAdmin::create([
                'resort_id' => $this->resort->resort_id,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
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

            if(!$fileManagement){
                $fileManagement = Common::createFolderByName($resortAdmin->resort_id, $employee->Emp_id, 'categorized');    
            }
            
            $folder_name = $fileManagement->Folder_Name;
        
            if($request->hasFile('MergerFile'))
            {
                $MergerFile = $request->file('MergerFile');
                $aws_cv = Common::AWSEmployeeFileUpload($this->resort->resort_id,$MergerFile,$folder_name);
                
                if($aws_cv['status'] == 'success'){
                    EmployeesDocument::create([
                        'employee_id' => $employee->id,
                        'resort_id' => $this->resort->resort_id,
                        'document_title' => 'CV',
                        'document_path' => $aws_cv['path'],
                        'document_category' => 'Employement',
                        'document_file_size' => $MergerFile->getSize(),
                        'created_by' => Auth::guard('resort-admin')->user()->id,
                        'modified_by' => Auth::guard('resort-admin')->user()->id,
                    ]);
                }
            }

            if($request->hasFile('full_length_photo')){
                $file_full_length = $request->file('full_length_photo');
                $picture = Common::AWSEmployeeFileUpload($this->resort->resort_id,$file_full_length,$folder_name);
                
                if($picture['status'] == 'success')
                {
                    $employee->selfie_image = $picture['path'];
                    $employee->save();
                } 
            }

            if($request->hasFile('profile_picture'))
            {
                $file = $request->file('profile_picture');
                $profilePicture = Common::AWSEmployeeFileUpload($this->resort->resort_id,$file,$folder_name);
                
                if($profilePicture['status'] == 'success')
                {
                    $resortAdmin->profile_picture = $profilePicture['path'];
                    $resortAdmin->save();
                } 
            }
            

            if(!empty($request->language))
            {
                foreach ($request->language as $lang) {
                    $language = EmployeeLanguage::create([
                        'employee_id' => $employee->id,
                        'language' => $lang[0],
                        'proficiency_level' => $lang[1],
                    ]);
                }
            }

            if(!empty($request->allowance))
            {
                foreach ($request->allowance as $allowance)
                {
                    $employeeAllowance = EmployeeAllowance::create([
                        'employee_id' => $employee->id,
                        'allowance_id' => $allowance['type'],
                        'amount' => $allowance['amount'],
                        'amount_unit' => $allowance['currency'],
                    ]);
                }
            }

            if(!empty($request->bank))
            {
                foreach ($request->bank as $bank) 
                {
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

            if(!empty($request->education))
            {

                foreach ($request->education as $edu)
                {
                    if($edu['document'])
                    {
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
            if (!$notify_person) 
            {
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
                                        'resort_id' => $resort->resort_id,
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
                                    'resort_id' => $resort->resort_id,
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
}
