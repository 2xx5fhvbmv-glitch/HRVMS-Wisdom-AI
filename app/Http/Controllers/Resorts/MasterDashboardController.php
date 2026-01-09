<?php

namespace App\Http\Controllers\Resorts;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortSection;
use App\Models\ResortPosition;
use App\Models\Employee;
use App\Helpers\Common;
use Carbon\Carbon;
use DB;
use App\Models\ResortLanguages;
use App\Models\ParentAttendace;
use App\Models\Applicant_form_data;
use App\Models\Incidents;
use App\Models\AvailableAccommodationModel;
use App\Models\EmployeeLeave;
use App\Models\ResortAdmin;
use App\Models\AssingAccommodation;
use App\Models\ServiceCharges;
use App\Models\VisaWallets;
use App\Models\Occuplany;
use App\Models\Vacancies;
use App\Models\TAnotificationChild;
use App\Models\TAnotificationParent;
use App\Models\ApplicationLink;
use App\Models\ApplicantInterViewDetails;
use App\Models\HiringSource;
use App\Models\TaEmailTemplate;
use App\Models\TrainingSchedule;
use App\Models\LearningRequest;
use App\Models\ParentSurvey;
use App\Models\GrivanceSubmissionModel;
use App\Models\disciplinarySubmit;
use App\Models\ChildFileManagement;
use App\Models\PublicHoliday;
use App\Models\Announcement;
use App\Models\EmployeePromotion;
use App\Models\EmployeeResignation;
use App\Models\EmployeeInfoUpdateRequest;
use App\Models\SOSHistoryModel;
use App\Models\BuildingModel;
use App\Models\ExitClearanceForm;
use App\Models\BulidngAndFloorAndRoom;
use App\Models\MonthlyCheckingModel;
use URL;
use Illuminate\Support\Facades\Route;
class MasterDashboardController extends Controller
{
    public $globalUser='';
    public function __construct()
    {
        $this->globalUser = Auth::guard('resort-admin')->user();
    }
    public function admin_dashboard()
    {

            $page_title ='Talent Acquisition Dashboard';
            $page_header = '<span class="arca-font">My</span> Dashboard';
            $currentYear = date('Y');
            $nextYear = $currentYear + 1;
            $resort_id = $this->globalUser->resort_id;
            $resort_divisions = ResortDivision::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
            $config = config('settings.Position_Rank');
            // $rank = $this->globalUser->GetEmployee->rank;
            $rank = 3;
            $Vacancies = Common::GetTheFreshVacancies($resort_id,"Active",$rank);

            $resort_id_decode =  base64_encode($resort_id);
            $applicant_link = route('resort.applicantForm',$resort_id_decode);
            $applicationUrlshow = substr($applicant_link, 0, 30).'...';
            $TodoData = Common::GmApprovedVacancy($resort_id,$rank);

            $NewVacancies = Vacancies::join("resort_departments as t1", "t1.id", "=", "vacancies.department")
                            ->join("resort_positions as t2", "t2.id", "=", "vacancies.position")
                            ->join("t_anotification_parents as t3", "t3.V_id", "=", "vacancies.id")
                            ->join("t_anotification_children as t4", "t4.Parent_ta_id", "=", "t3.id")
                            ->join("application_links as t5", "t5.ta_child_id", "=", "t4.id")
                            ->leftJoin("applicant_form_data as t6", "t6.Parent_v_id", "=", "vacancies.id")
                            ->join('job_advertisements as t7', 't7.Resort_id', '=', 'vacancies.Resort_id')
                            ->where("vacancies.resort_id", $resort_id)
                            ->where("t4.status", "ForwardedToNext")
                            ->where("t4.Approved_By", Common::TaFinalApproval($resort_id))
                            ->selectRaw("
                                vacancies.id AS vacancy_id,
                                vacancies.position,
                                t2.position_title AS positionTitle,
                                t2.id AS PositionID,
                                t2.code AS PositionCode,
                                t1.name AS Department,
                                t1.code AS DepartmentCode,
                                COUNT(t6.id) AS NoOfApplication, -- Total applications per vacancy
                                MAX(t5.link_Expiry_date) AS LinkExpiryDate, -- Latest link expiry date
                                MAX(t6.Application_date) AS LatestApplicationDate, -- Latest application date
                                vacancies.Total_position_required as NoOfVacnacy,
                                t7.Jobadvimg
                            ")
                            ->groupBy(
                                "vacancies.id",
                                "vacancies.position",
                                "t2.position_title",
                                "t2.id",
                                "t2.code",
                                "t1.name",
                                "t1.code",
                                "vacancies.Total_position_required",
                                "t7.Jobadvimg"
                            )
                            ->get();

                            foreach($NewVacancies  as $v)
                            {

                                $v->positionTitle;
                                $v->PositonCode;
                                $v->Department;
                                $v->DepartmentCode;
                                $v->NoOfVacnacy = $v->NoOfVacnacy;
                                $v->NoOfApplication =  (isset($v->NoOfApplication))  ? $v->NoOfApplication: 0;
                                $v->ApplicationDate =  Carbon::parse($v->Application_date)->format('d-m-Y');
                                $v->ExpiryDate = Carbon::parse($v->link_Expiry_date)->format('d-m-Y');
                                $v->ApplicationId= $v->application_id;
                            }
            $talentPool  = Applicant_form_data::join('applicant_wise_statuses as t1', 't1.Applicant_id', '=', 'applicant_form_data.id')
                            ->whereIn("t1.status", ["Rejected","Rejected By Wisdom AI"])
                            ->latest('t1.created_at')
                            ->take('10')
                            ->get(['applicant_form_data.id','email','passport_photo','first_name','last_name','Comments']);

            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();
            $TotalApplicants = Applicant_form_data::join('applicant_wise_statuses as t1', 't1.Applicant_id', '=', 'applicant_form_data.id')
                        ->where('t1.status', "!=",'Selected')
                        ->where('applicant_form_data.resort_id', $resort_id)
                        ->select(DB::raw('COUNT(DISTINCT t1.Applicant_id) as total_applicants') )
                        ->groupBy('t1.Applicant_id')
                        ->distinct()
                        ->get();
            $TotalApplicants = isset($TotalApplicants[0]) ? $TotalApplicants[0]->total_applicants : 0;
            $Interviews = ApplicantInterViewDetails::where('resort_id', $resort_id)->count();
            $Hired = DB::table('applicant_wise_statuses as t1')
                    ->join('applicant_form_data as t2', 't2.id', '=', 't1.Applicant_id')
                    ->where('t2.resort_id', $resort_id) // Reference resort_id from applicant_form_data
                    ->where("t1.As_ApprovedBy",8)
                    ->where("t1.status","Selected")
                    ->groupBy('t1.Applicant_id')
                    ->get()
                    ->count();
            // $positionId = $request->input('position_id'); // Fetch position filter from request
            $Resort_Position =  ResortPosition::where('Resort_id', $resort_id)->get();
            $HiringSource = HiringSource::where('Resort_id', $resort_id)
                        ->orderBy('id', 'desc')->get();
            $EmailTamplete = TaEmailTemplate::where('Resort_id',$resort_id)
                        ->orderByDesc("id")->get();


            return view('resorts.talentacquisition.dashboard.admindashboard',
                compact(
                    'applicationUrlshow',
                    'applicant_link',
                    'Hired',
                    'Interviews',
                    'resort_id_decode',
                    'TotalApplicants',
                    'resort_id',
                    'resort_divisions',
                    'resort_departments',
                    'resort_positions',
                    'Vacancies',
                    'TodoData',
                    'NewVacancies',
                    'talentPool',
                    'Resort_Position',
                    'HiringSource',
                    'EmailTamplete'
                )
            );
    //     } catch( \Exception $e ) {
    //         \Log::emergency("File: ".$e->getFile());
    //         \Log::emergency("Line: ".$e->getLine());
    //         \Log::emergency("Message: ".$e->getMessage());
    //         return view('resorts.talentacquisition.dashboard.admindashboard',compact('resort_id'));
    //     }
    }

    public function hr_dashboard()
    {
        // try {

            $page_title ='Talent Acquisition Dashboard';
            $page_header = '<span class="arca-font">My</span> Dashboard';
            $currentYear = date('Y');
            $nextYear = $currentYear + 1;
            $resort_id = $this->globalUser->resort_id;
            $rank = $this->globalUser->GetEmployee->rank ?? '';


            $resort_divisions = ResortDivision::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();

            $total_emp = ResortAdmin::where('resort_id', $resort_id)->count();
            $male_emp = ResortAdmin::where('resort_id', $resort_id)->where('gender', 'male')->count();
            $female_emp = ResortAdmin::where('resort_id', $resort_id)->where('gender', 'female')->count();

            $male_emp_percentage = $total_emp > 0 ? ($male_emp / $total_emp) * 100 : 0;
            $female_emp_percentage = $total_emp > 0 ? ($female_emp / $total_emp) * 100 : 0;

            $resort_divisions_count = $resort_divisions->count();

            $resort_departments_count = $resort_departments->count();
            $resort_positions_count = $resort_positions->count();

            $Employees = Employee::where('resort_id', $resort_id)->where('status','Active')->get();

            $new_joining = Employee::where('resort_id', $resort_id)->where('status','Active')->whereMonth('joining_date', now()->month)->count();

            $total_employees = $Employees->count();
            $resort_employee_ids = $Employees->pluck('id')->toArray();

            $present_employee_counts = ParentAttendace::where('resort_id', $resort_id)
                ->whereIn('Emp_id', $resort_employee_ids)
                ->whereDate('date', Carbon::today())
                ->where('Status', 'Present')
                ->count();
            $absent_employee_counts = ParentAttendace::where('resort_id', $resort_id)
                ->whereIn('Emp_id', $resort_employee_ids)
                ->whereDate('date', Carbon::today())
                ->where('Status', 'Absent')
                ->count();

            $leave_employee_counts = EmployeeLeave::where('resort_id', $resort_id)
                ->whereIn('Emp_id', $resort_employee_ids)
                ->whereDate('from_date', '<=', Carbon::today())
                ->whereDate('to_date', '>=', Carbon::today())
                ->where('status', 'Approved')
                ->count();

            $total_application_for_job_in_review = Applicant_form_data::where('resort_id', $resort_id)
                        ->whereHas('Application_wise_status', function ($query) {
                            $query->where('status', '!=', 'Selected');
                        })
                        ->count();
            $total_selected_applications_with_interviews = Applicant_form_data::whereHas('Application_wise_status', function ($query) {
                            $query->where('status', 'Round');
                        })
                        ->whereHas('ApplicantInterviewDetail')
                        ->where('resort_id', $resort_id)
                        ->count();

            $total_hired_candidates = Applicant_form_data::whereHas('Application_wise_status', function ($query) {
                            $query->where('status', 'Selected');
                        })
                        ->whereHas('ApplicantInterviewDetail')
                        ->where('resort_id', $resort_id)
                        ->count();

            $OccupiedBed=  AssingAccommodation::where("resort_id",$this->globalUser->resort_id)
                            ->where('emp_id','!=',0)->count();

            $total_beds = AssingAccommodation::where("resort_id",$this->globalUser->resort_id)->count();
            $total_available_beds =AssingAccommodation::where("resort_id",$this->globalUser->resort_id)->where('emp_id',0)->count();

            $completed_trainings_count = TrainingSchedule::where('status','Completed')->where('resort_id', $resort_id)->count();
            $pending_trainings_count = LearningRequest::where('status','Pending')->where('resort_id', $resort_id)->count();

            $total_survey_count = ParentSurvey::where('resort_id', $resort_id)->count();
            $open_survey_count = ParentSurvey::where('Status','OnGoing')->where('resort_id', $resort_id)->count();
            $pending_survey_count = ParentSurvey::whereIn('Status',['Publish','SaveAsDraft'])->where('resort_id', $resort_id)->count();
            $complete_survey_count = ParentSurvey::where('Status','Complete')->where('resort_id', $resort_id)->count();

            $grivanceSubmissionModel = GrivanceSubmissionModel::where('resort_id', $resort_id)
                                ->with('category')
                                ->selectRaw('Grivance_Cat_id, COUNT(*) as count')
                                ->groupBy('Grivance_Cat_id')
                                ->get()
                                ->map(function ($item) {
                                    $item->category_name = $item->category->Category_Name ?? 'Unknown';
                                    return $item;
                                });


            $grivance_data = GrivanceSubmissionModel::where('resort_id',$resort_id)->get();
            $disiplinary_data = disciplinarySubmit::where('resort_id',$resort_id)->get();


            $open_grivance_count = $grivance_data->where('status','in_review')->count();
            $pending_grivance_count = $grivance_data->where('status','pending')->count();
            $resolve_grivance_count  = $grivance_data->where('status','resolved')->count();

            $open_disciplinary_count = $disiplinary_data->where('status','In_Review')->count();
            $pending_disciplinary_count = $disiplinary_data->where('status','pending')->count();
            $resolve_disciplinary_count  = $disiplinary_data->where('status','resolved')->count();

            $TotalDocument = ChildFileManagement::
                                join("filemangement_systems as fp", "fp.id", "=", "child_file_management.Parent_File_ID")
                                ->where('fp.resort_id', $resort_id)
                                ->orderByDesc('fp.id')
                                ->count();
            $UnassignedDocumentsCounts = ChildFileManagement::join("filemangement_systems as fp", "fp.id", "=", "child_file_management.Parent_File_ID")
                                            ->leftJoin("file_permissions as perms", "perms.file_id", "=", "child_file_management.unique_id")
                                            ->where('fp.resort_id', $resort_id)
                                            ->where("fp.Folder_Type", "uncategorized")
                                            ->whereNull("perms.file_id")
                                            ->orderByDesc('fp.id')
                                            ->count();

            $total_applied_leave = EmployeeLeave::where('resort_id', $resort_id)
                            ->whereIn('Emp_id', $resort_employee_ids)
                            ->whereDate('from_date', '<=', Carbon::today())
                            ->whereDate('to_date', '>=', Carbon::today())
                            ->where('status', 'Pending')
                            ->count();
            $todayleaveUsers = EmployeeLeave::where('resort_id', $resort_id)
                            ->whereIn('Emp_id', $resort_employee_ids)
                            ->whereDate('from_date', '<=', Carbon::today())
                            ->whereDate('to_date', '>=', Carbon::today())
                            ->where('status', 'Approved')
                            ->with('employee')
                            ->get();

            $upcomingLeaveUsers = EmployeeLeave::where('resort_id', $resort_id)
                            ->whereIn('Emp_id', $resort_employee_ids)
                            ->whereDate('from_date', '>', Carbon::today())
                            ->where('status', 'Approved')
                            ->with('employee')
                            ->get();

            $upcommingPublicHoliday = PublicHoliday::whereMonth('holiday_date', Carbon::now()->month)
                ->whereYear('holiday_date', Carbon::now()->year)
                ->orderBy('holiday_date', 'asc')
                ->get();

            $todayBirthdays = Employee::where('resort_id', $resort_id)
                ->whereMonth('dob', Carbon::now()->month)
                ->whereDay('dob', Carbon::now()->day)
                ->get();


            $upcommingBirthdays = Employee::where('resort_id', $resort_id)
                ->whereMonth('dob', Carbon::now()->month)
                ->whereDay('dob', '>=', Carbon::now()->day)
                ->orderBy('dob', 'asc')
                ->get();

            $leaveRequests = EmployeeLeave::where('resort_id', $resort_id)
                ->whereIn('Emp_id', $resort_employee_ids)
                ->whereDate('from_date', '<=', Carbon::today())
                ->whereDate('to_date', '>=', Carbon::today())
                ->where('status', 'Pending')
                ->with('employee')
                ->get();
            $resort_departments = ResortDepartment::where('resort_id',$resort_id)->where('status','active')->get();

            $startDate = Carbon::now()->subDays(5)->toDateString(); // Five days before
            for ($i = 0; $i <= 5; $i++) {
                $nextDate[] = Carbon::now()->addDays($i)->toDateString();
            }
            for ($i = 5; $i >= 0; $i--) {
                $previousDate[] = Carbon::now()->subDay($i)->toDateString();
            }
            $newArray = array_merge($nextDate, $previousDate);

            $occupancies = Occuplany::where('resort_id', $resort_id)
                ->whereIn('occupancydate', $newArray)
                ->get(['occupancyinPer', 'occupancydate', 'occupancytotalRooms', 'occupancyOccupiedRooms']);

            $vacant_positions = DB::table('resort_positions as p')
                ->leftJoin('employees as e', 'e.Position_id', '=', 'p.id')
                ->where('p.resort_id', '=', $resort_id);

            $vacant_positions = $vacant_positions->select(
                DB::raw('COUNT(DISTINCT p.id) as total_positions_count'), // Total number of unique positions
                DB::raw('COUNT(DISTINCT CASE WHEN e.id IS NOT NULL THEN p.id END) as positions_with_employees'), // Positions with at least one employee
                DB::raw('COUNT(DISTINCT p.id) - COUNT(DISTINCT CASE WHEN e.id IS NOT NULL THEN p.id END) as vacant_positions'), // Positions with no employees
            DB::raw('COUNT(e.id) as TotalBudgtedemp')
                )->first();

                $manning_response = (object) [
                    "total_budgeted_employees" => $vacant_positions->total_positions_count, // Total unique positions
                    "total_filled_positions_count" => $vacant_positions->positions_with_employees, // Positions where employees exist
                    "total_vacant_count" => $vacant_positions->vacant_positions, // Positions with no employees
                    "TotalBudgtedemp"=>$vacant_positions->TotalBudgtedemp,
            ];

            $severityCounts = Incidents::where('resort_id', $resort_id)
                ->select('severity', \DB::raw('count(*) as total'))
                ->groupBy('severity')
                ->pluck('total', 'severity')
                ->toArray();

            // Ensure all severity types are present even if count is 0
            $allSeverities = ['Minor', 'Moderate', 'Severe'];
            foreach ($allSeverities as $severity) {
                if (!isset($severityCounts[$severity])) {
                    $severityCounts[$severity] = 0;
                }
            }

            $totalIncidentCounts = Incidents::where('resort_id', $resort_id)->count();
            $openIncidentCounts = Incidents::where('resort_id', $resort_id)->where('status','Reported')->count();
            $underInvestigationIncidentCounts = Incidents::where('resort_id', $resort_id)->where('status','Assigned To')->count();



              $OngoingSurvey = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                    ->where('parent_surveys.Status', 'OnGoing')
                                    ->where('parent_surveys.resort_id', $resort_id)
                                    ->select('parent_surveys.id','parent_surveys.Status','parent_surveys.Surevey_title as title','parent_surveys.Start_date','parent_surveys.End_date',
                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                                        DB::raw("COUNT(t1.id) as total_count")
                                    )
                                    ->groupBy('parent_surveys.id')->limit(7)->get();


                $SOSHistory = SOSHistoryModel::with(['getSos','employee','employee.resortAdmin'])->where('resort_id',$resort_id)->limit(5)->latest()->get();

                $totalPublished = Announcement::where('resort_id', $resort_id)->where('status', 'Published')->count();

            $employeeInfoUpdateRequest = EmployeeInfoUpdateRequest::where('resort_id',$resort_id)->with([
                'employee.resortAdmin','department','position'])->where('status','Pending')->wherehas('employee.resortAdmin')->latest()->limit(5)->get();

            $probationalEmployees = Employee::where('resort_id',$resort_id)->whereIn('probation_status',['Active','Extended'])->count();
            $activeProbationCount = Employee::where('resort_id',$resort_id)->where('probation_status', 'Active')->count();
            $failedProbationCount = Employee::where('resort_id',$resort_id)->where('probation_status', 'Failed')->count();
            $completedProbationCount = Employee::where('resort_id',$resort_id)->where('probation_status', 'Completed')->count();
            $total_promotions = EmployeePromotion::where('resort_id',$resort_id)->count();

            $recent_promotions = EmployeePromotion::with(
                ['employee.position',
                'employee.department',
                'employee.resortAdmin',
                'currentPosition',
                'newPosition',
                'approvals'
                ]
            )->where('resort_id',$resort_id)->orderBy('id','desc')->limit(5)->get();

            $average_salary_increase = EmployeePromotion::whereNotNull('current_salary')
                                    ->whereNotNull('new_salary')
                                    ->where('resort_id',$resort_id)
                                    ->get()
                                    ->map(function ($promo) {
                                        if ($promo->current_salary == 0) return 0; // Avoid division by zero
                                        return (($promo->new_salary - $promo->current_salary) / $promo->current_salary) * 100;
                                    })->avg();

            $employee_resignation_query = EmployeeResignation::where('resort_id', $resort_id);
            $total_resignations = $employee_resignation_query->count();
            $withdraw_resignation = $employee_resignation_query->where('status', 'Withdraw')->count();
            $pending_resignation = $employee_resignation_query->where('status', 'Pending')->count();

             $NewVacancies = Vacancies::join("resort_departments as t1", "t1.id", "=", "vacancies.department")
                            ->join("resort_positions as t2", "t2.id", "=", "vacancies.position")
                            ->join("t_anotification_parents as t3", "t3.V_id", "=", "vacancies.id")
                            ->join("t_anotification_children as t4", "t4.Parent_ta_id", "=", "t3.id")
                            ->join("application_links as t5", "t5.ta_child_id", "=", "t4.id")
                            ->leftJoin("applicant_form_data as t6", "t6.Parent_v_id", "=", "vacancies.id")
                            ->join('job_advertisements as t7', 't7.Resort_id', '=', 'vacancies.Resort_id')
                            ->where("vacancies.resort_id", $resort_id)
                            ->where("t4.status", "ForwardedToNext")
                            ->where("t4.Approved_By", Common::TaFinalApproval($resort_id))
                            ->selectRaw("
                                vacancies.id AS vacancy_id,
                                vacancies.position,
                                t2.position_title AS positionTitle,
                                t2.id AS PositionID,
                                t2.code AS PositionCode,
                                t1.name AS Department,
                                t1.code AS DepartmentCode,
                                COUNT(t6.id) AS NoOfApplication, -- Total applications per vacancy
                                MAX(t5.link_Expiry_date) AS LinkExpiryDate, -- Latest link expiry date
                                MAX(t6.Application_date) AS LatestApplicationDate, -- Latest application date
                                vacancies.Total_position_required as NoOfVacnacy,
                                t7.Jobadvimg
                            ")
                            ->groupBy(
                                "vacancies.id",
                                "vacancies.position",
                                "t2.position_title",
                                "t2.id",
                                "t2.code",
                                "t1.name",
                                "t1.code",
                                "vacancies.Total_position_required",
                                "t7.Jobadvimg"
                            )
                            ->get();
                            foreach($NewVacancies  as $v)
                            {
                                $v->positionTitle;
                                $v->PositonCode;
                                $v->Department;
                                $v->DepartmentCode;
                                $v->NoOfVacnacy = $v->NoOfVacnacy;
                                $v->NoOfApplication =  (isset($v->NoOfApplication))  ? $v->NoOfApplication: 0;
                                $v->ApplicationDate =  Carbon::parse($v->Application_date)->format('d-m-Y');
                                $v->ExpiryDate = Carbon::parse($v->link_Expiry_date)->format('d-m-Y');
                                $v->ApplicationId= $v->application_id;
                            }
             $TodoData = Common::GmApprovedVacancy($resort_id,$rank);
            $Vacancies = Common::GetTheFreshVacancies($resort_id,"Active",$rank);

            $buildings = BuildingModel::where("resort_id", $this->globalUser->resort_id)
                            ->get()
                            ->reduce(function ($result, $building) {
                                // Initialize the array for this building if not set
                                if (!isset($result[$building->BuildingName])) {
                                    $result[$building->BuildingName] = [];
                                }

                                // Fetch data for the current building
                                $data = AvailableAccommodationModel::join('assing_accommodations', 'assing_accommodations.available_a_id', '=', 'available_accommodation_models.id')
                                    ->where('available_accommodation_models.BuildingName', $building->id) // Filter by building ID
                                    ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                    ->selectRaw("
                                        available_accommodation_models.RoomType,
                                        available_accommodation_models.BuildingName,
                                        available_accommodation_models.RoomNo,
                                        available_accommodation_models.RoomStatus,
                                        assing_accommodations.emp_id,
                                        SUM(CASE WHEN assing_accommodations.emp_id != 0 THEN 1 ELSE 0 END) as OccupiedRooms,
                                        SUM(CASE WHEN assing_accommodations.emp_id = 0 THEN 1 ELSE 0 END) as AvailableRooms,
                                        SUM(CASE WHEN assing_accommodations.emp_id != 0 THEN 1 ELSE 0 END) as OccupiedRooms,
                                        SUM(CASE WHEN available_accommodation_models.RoomStatus = 'Available' THEN 1 ELSE 0 END) as MainAvailableRooms
                                    ")
                                    ->groupBy(
                                        'available_accommodation_models.RoomType',
                                        'available_accommodation_models.BuildingName'
                                    )
                                    ->get()
                                    ->map(function ($accommodation) use ($building, &$result) {
                                        // Get additional data for floors and rooms
                                        // dd($accommodation);
                                        $buildingData = BulidngAndFloorAndRoom::where("building_id", $building->id)
                                            ->selectRaw('COUNT(distinct(Floor)) as TotalFloors, COUNT(Room) as TotalRooms')
                                            ->where('resort_id', $this->globalUser->resort_id)
                                            ->groupBy('building_id')
                                            ->first();


                                            $a = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                            ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                            ->where("BuildingName", $building->id)
                                            ->where("t1.emp_id", "!=",0)
                                            // ->groupBy('t1.available_a_id')
                                            ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('COUNT(t1.id ) as OccupiedRooms')]);
                                            $AvailableRooms=0;

                                                if( isset($a->OccupiedRooms))
                                                {
                                                    if( $a->OccupiedRooms < $a->Capacity)
                                                    {
                                                        $AvailableRooms = 1;
                                                    }

                                                    else
                                                    {
                                                        $AvailableRooms = $a->OccupiedRooms;
                                                    }
                                                }

                                                $AvailableFloor=0;
                                                if( isset($a->OccupiedRooms))
                                                {
                                                    if( $a->OccupiedRooms < $a->Capacity)
                                                    {
                                                        $AvailableFloor = 1;
                                                    }
                                                    else
                                                    {
                                                        $AvailableFloor = $a->OccupiedRooms;
                                                    }
                                                }

                                                $MaleBeds = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                                ->where("BuildingName", $building->id)
                                                ->where("t1.emp_id", 0)
                                                ->where("available_accommodation_models.blockFor",'Male')
                                                // ->groupBy('t1.available_a_id')
                                                ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('COUNT(t1.id ) as AvailableMaleBeds')]);

                                                $AvailableMaleBeds=0;
                                                if( isset($MaleBeds->AvailableBeds))
                                                {
                                                        $AvailableMaleBeds = $MaleBeds->AvailableMaleBeds;
                                                }
                                                $FemaleBeds = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                                ->where("BuildingName", $building->id)
                                                ->where("t1.emp_id", 0)
                                                ->where("available_accommodation_models.blockFor",'Female')
                                                // ->groupBy('t1.available_a_id')
                                                ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('COUNT(t1.id ) as AvailableFemaleBeds')]);
                                                $AvailableFemaleBeds=0;
                                                if( isset($FemaleBeds->AvailableFemaleBeds))
                                                {
                                                        $AvailableFemaleBeds = $FemaleBeds->AvailableFemaleBeds;
                                                }


                                                $OccupiedMaleBeds = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                                ->where("BuildingName", $building->id)
                                                ->where("t1.emp_id","!=" ,0)
                                                ->where("available_accommodation_models.blockFor",'Male')
                                                // ->groupBy('t1.available_a_id')
                                                ->first([DB::raw('COUNT(t1.id ) as OccupiedMaleBeds')]);

                                                $OccupiedMaleBedsNew=0;
                                                if( isset($OccupiedMaleBeds->OccupiedMaleBeds))
                                                {
                                                        $OccupiedMaleBedsNew =$OccupiedMaleBeds->OccupiedMaleBeds;
                                                }

                                                $OccupiedFemaleBedsnew = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                                ->where("BuildingName", $building->id)
                                                ->where("t1.emp_id","!=" ,0)
                                                ->where("available_accommodation_models.blockFor",'Female')
                                                // ->groupBy('t1.available_a_id')
                                                ->first([DB::raw('COUNT(t1.id ) as OccupiedFemaleBeds')]);
                                                $OccupiedFemaleBeds=0;


                                                    if( isset($OccupiedFemaleBedsnew->OccupiedFemaleBeds))
                                                    {
                                                             $OccupiedFemaleBeds = $OccupiedFemaleBedsnew->OccupiedFemaleBeds;
                                                    }

                                    $Othercounts = AvailableAccommodationModel::where('BuildingName', $building->id)
                                            ->select(DB::RAW('SUM(CASE WHEN available_accommodation_models.blockFor = \'Female\' THEN available_accommodation_models.Capacity ELSE 0 END) as FemaleAvailableBeds'),DB::RAW('SUM(CASE WHEN available_accommodation_models.blockFor = \'Male\' THEN available_accommodation_models.Capacity ELSE 0 END) as MaleAvailableBeds'))
                                            ->where('resort_id', $this->globalUser->resort_id)
                                            ->first();


                                        if (empty($result[$building->BuildingName])) {
                                            $result[$building->BuildingName][] = [
                                                'Floor' => $AvailableFloor . '/' . ($buildingData->TotalFloors ?? 0), // Pending
                                                'Room' => $AvailableRooms . '/' . ($buildingData->TotalRooms ?? 0), // Done
                                                'Male Beds' =>  $OccupiedMaleBedsNew. '/' . ($Othercounts->MaleAvailableBeds ?? 0),
                                                'Female Beds' => $OccupiedFemaleBeds . '/' . ($Othercounts->FemaleAvailableBeds ?? 0),
                                            ];
                                        }

                                        // Update the existing array for this building
                                        $Rank = config('settings.eligibilty');
                                        if (isset($Rank[$accommodation->RoomType])) {
                                            $rankKey = $Rank[$accommodation->RoomType];

                                            $TotaData=AvailableAccommodationModel::where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                                                            ->where("BuildingName", $building->id)
                                                                            ->where("RoomType", $accommodation->RoomType)
                                                                            ->groupBy('RoomType')
                                                                            ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('SUM(available_accommodation_models.Capacity) as TotalCapacity')]);

                                            $a = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                            ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                            ->where("BuildingName", $building->id)
                                            ->where("t1.emp_id", "!=",0)
                                            ->where("available_accommodation_models.RoomType", $accommodation->RoomType)
                                            ->groupBy('t1.available_a_id')
                                            ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('SUM(available_accommodation_models.Capacity) as assignedCapacity')]);
                                            // dd( $a );
                                            $assignedCapacity = isset($a->assignedCapacity) ? $a->assignedCapacity : 0;
                                            $TotalCapacity  = isset($TotaData->TotalCapacity) ? $TotaData->TotalCapacity : 0;
                                            $result[$building->BuildingName][0][$rankKey] = $assignedCapacity  .'/' . $TotalCapacity;
                                        }

                                        return $accommodation;
                                    });

                                return $result;
                            }, []);


            $monthly = MonthlyCheckingModel::join("employees as t1", "t1.id", "=", "monthly_checking_models.emp_id")
                ->join("resort_admins as t2", "t2.id", "=", "t1.Admin_Parent_id")
                ->join("resort_positions as t3", "t3.id", "=", "t1.Position_id")
                ->leftjoin("learning_programs as t4", "t4.id", "=", "monthly_checking_models.tranining_id")
                ->where("t1.resort_id", $this->globalUser->resort_id)


                ->orderBy("monthly_checking_models.id", "desc")
                ->select([
                    't1.id as emp_orignal_id',
                    't4.name as traniningname',
                    't2.first_name',
                    't2.last_name',
                    't3.position_title as PositionName',
                    'monthly_checking_models.*'
                ]);

            // Get the collection after executing the query
            $monthlyCollection = $monthly->limit(5)->orderBy('monthly_checking_models.created_at','DESC')->get();

            // Process each record
            $monthlyCheckinPerformance = $monthlyCollection->map(function($ak) {
                if (isset($ak->tranining_id)) {
                    $l = LearningRequest::with('employees')
                        ->where("learning_id", $ak->tranining_id)
                        ->where("resort_id", $this->globalUser->resort_id)
                        ->whereHas('employees', function($q) use ($ak) {
                            $q->where('id', $ak->emp_orignal_id);
                        })
                        ->latest('id')
                        ->first();

                    $ak->duration = isset($l->start_date) && isset($l->end_date)
                        ? $l->start_date . '-' . $l->end_date
                        : '-';

                    $ak->status = isset($l->status) && $l->status == 'Approved'
                        ? 'In Progress'
                        : (isset($l->status) ? $l->status : 'Pending');
                } else {
                    $ak->duration = '-';
                    $ak->status = 'Pending';
                }
                $ak->new_date_of_dicussion = date("d M Y",strtotime($ak->date_discussion));
                return $ak;
            });

            $totalExitInitiated = EmployeeResignation::where('resort_id', $this->globalUser->resort_id)->whereNotIn('status',['Appoved','Rejected','Withdarw'])->count();
            $visa_reserved_total = VisaWallets::where('resort_id', $this->globalUser->resort_id)->where('WalletName', 'Reserved')->where('status','Active')->sum('Amt');
            $visa_available_total = VisaWallets::where('resort_id', $this->globalUser->resort_id)->where('WalletName', 'Available')->where('status','Active')->sum('Amt');
            $visa_deposited_total = VisaWallets::where('resort_id', $this->globalUser->resort_id)->where('WalletName', 'Deposited')->where('status','Active')->sum('Amt');
            $visa_withdraw_total = VisaWallets::where('resort_id', $this->globalUser->resort_id)->where('WalletName', 'Withdrawn')->where('status','Active')->sum('Amt');


            return view('resorts.master-dashboard.hrdashboard',
                compact('resort_divisions_count','resort_departments_count','resort_positions_count','total_employees','resort_id','resort_divisions','resort_departments','resort_positions','page_title','page_header','currentYear','nextYear','present_employee_counts','absent_employee_counts','leave_employee_counts','total_application_for_job_in_review','total_selected_applications_with_interviews','Employees','total_hired_candidates','total_beds' ,'OccupiedBed','total_available_beds','completed_trainings_count','pending_trainings_count','total_survey_count','open_survey_count','pending_survey_count','complete_survey_count','open_grivance_count','pending_grivance_count','resolve_grivance_count','open_disciplinary_count','pending_disciplinary_count','resolve_disciplinary_count','UnassignedDocumentsCounts','TotalDocument','total_applied_leave','new_joining','grivanceSubmissionModel','todayleaveUsers','upcomingLeaveUsers','upcommingPublicHoliday','todayBirthdays','upcommingBirthdays','leaveRequests','resort_departments','occupancies','manning_response','vacant_positions','severityCounts','OngoingSurvey','SOSHistory','male_emp','female_emp','male_emp_percentage','female_emp_percentage','totalPublished','employeeInfoUpdateRequest','probationalEmployees','activeProbationCount','failedProbationCount','completedProbationCount','total_promotions','recent_promotions','average_salary_increase','total_resignations','withdraw_resignation','pending_resignation','NewVacancies','TodoData','Vacancies','buildings','totalIncidentCounts','openIncidentCounts','underInvestigationIncidentCounts','monthlyCheckinPerformance','totalExitInitiated','visa_reserved_total','visa_available_total','visa_deposited_total','visa_withdraw_total')
            );

        // } catch( \Exception $e ) {
        //     \Log::emergency("File: ".$e->getFile());
        //     \Log::emergency("Line: ".$e->getLine());
        //     \Log::emergency("Message: ".$e->getMessage());
        //     return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        // }
    }

    public function hod_dashboard()
    {

        // try {
            $page_title = 'Talent Acquisition Dashboard';
            $page_header = '<span class="arca-font">My</span> Dashboard';
            $currentYear = date('Y');
            $nextYear = $currentYear + 1;
            $resort_id = $this->globalUser->resort_id;
            $Dept_id = $this->globalUser->GetEmployee->Dept_id;
            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();
            $config = config('settings.Position_Rank');

            // Fetch active divisions, departments, and positions for the resort
            $resort_divisions = ResortDivision::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_departments = ResortDepartment::where('status','active')->where('id',$Dept_id)->where('resort_id',$resort_id)->get();
            $resort_positions = ResortPosition::where('status','active')->where('dept_id',$Dept_id)->where('resort_id',$resort_id)->get();

            $Employees = Employee::where('resort_id', $resort_id)->where('Dept_id',$Dept_id)->where('status','Active')->get();

            $total_employees = $Employees->count();
            $resort_employee_ids = $Employees->pluck('id')->toArray();

            $present_employee_counts = ParentAttendace::where('resort_id', $resort_id)
                ->whereIn('Emp_id', $resort_employee_ids)
                ->whereDate('date', Carbon::today())
                ->where('Status', 'Present')
                ->count();

            $absent_employee_counts = ParentAttendace::where('resort_id', $resort_id)
                ->whereIn('Emp_id', $resort_employee_ids)
                ->whereDate('date', Carbon::today())
                ->where('Status', 'Absent')
                ->count();

            $leave_employee_counts = EmployeeLeave::where('resort_id', $resort_id)
                ->whereIn('Emp_id', $resort_employee_ids)
                ->whereDate('from_date', '<=', Carbon::today())
                ->whereDate('to_date', '>=', Carbon::today())
                ->where('status', 'Approved')
                ->count();

            // Fetch hiring requests with related notifications
            $hiring_request = Vacancies::with(['Getdepartment','Getposition',
                'TAnotificationParent.TAnotificationChildren' => function ($query) {
                    $query->where('status', '!=', ''); // Filter by valid status
                }
            ])
            ->whereHas('TAnotificationParent.TAnotificationChildren', function ($query) {
                $query->where('status', '!=', ''); // Ensure valid notifications exist
            })
            ->where('department', $Dept_id)
            ->where('resort_id', $resort_id)
            ->orderBy('vacancies.id', 'desc') // Order by ID in descending order
            ->limit(6)
            ->get();

            $vacancies = Vacancies::with([
                'Getdepartment',
                'Getposition',
                'TAnotificationParent.TAnotificationChildren' => function ($query) {
                    $query->where('status', 'Approved')  // Only include notifications that are approved
                          ->where('Approved_By', 8);     // Ensure the approval is from GM (Approved_By = 8)
                }
            ])
            ->where('resort_id', $resort_id)
            ->where('department', $Dept_id)
            ->whereHas('TAnotificationParent.TAnotificationChildren', function ($query) {
                $query->where('status', 'ForwardedToNext')  // Ensure only those with "Approved" status are included
                      ->where('Approved_By', 8);     // Ensure the approval is by GM
            })
            ->orderByDesc('id')
            ->limit(6)
            ->get();

            $TotalApplicants = Applicant_form_data::join('applicant_wise_statuses as t1', 't1.Applicant_id', '=', 'applicant_form_data.id')
                ->join('vacancies as t2', 't2.id', '=', 'applicant_form_data.Parent_v_id')
                ->where('t1.status', '!=', 'Selected')
                ->where('applicant_form_data.resort_id', $resort_id)
                ->where('t2.department', $Dept_id)
                ->select(DB::raw('COUNT(DISTINCT t1.Applicant_id) as total_applicants'))
                ->first();

            $TotalApplicantCounts = $TotalApplicants->total_applicants ?? 0;

            $Interviews = DB::table('applicant_inter_view_details as t1')
                ->join('applicant_form_data as t2', 't2.id', '=', 't1.Applicant_id') // Linking to applicant_form_data
                ->join('vacancies as t3', 't3.id', '=', 't2.Parent_v_id') // Linking to vacancies for department
                ->where('t2.resort_id', $resort_id)
                ->where('t3.department', $Dept_id)
                ->count();

            $Hired = DB::table('applicant_wise_statuses as t1')
                ->join('applicant_form_data as t2', 't2.id', '=', 't1.Applicant_id')
                ->join('vacancies as t3', 't3.id', '=', 't2.Parent_v_id') // Correct alias for vacancies
                ->where('t2.resort_id', $resort_id)
                ->where('t1.As_ApprovedBy', 8)
                ->where('t1.status', 'Selected')
                ->where('t3.department', $Dept_id)
                ->distinct()
                ->count('t1.Applicant_id'); // Count distinct Applicant_id

            $InProgressApplicants = DB::table('applicant_wise_statuses as t1')
                ->join('applicant_form_data as t2', 't2.id', '=', 't1.Applicant_id')
                ->join('vacancies as t3', 't3.id', '=', 't2.Parent_v_id') // Correct alias for vacancies
                ->where('t2.resort_id', $resort_id)
                ->where('t1.status', ['Sortlisted By Wisdom AI', 'Sortlisted'])
                ->where('t3.department', $Dept_id)
                ->distinct()
                ->count('t1.Applicant_id'); // Count distinct Applicant_id

            $UpcomingApplicants = Vacancies::join("applicant_form_data as t1", "t1.Parent_v_id", "=", "vacancies.id")
                ->join("countries as t2", "t2.id", "=", "t1.country")
                ->join('applicant_wise_statuses as t4', function ($join) {
                    $join->on('t4.Applicant_id', '=', 't1.id');
                })
                ->whereIn('t4.status', ['Round']) // Adjust 'Round' to actual status values in your database
                ->join('applicant_inter_view_details as t3', function ($join) {
                    $join->on('t3.Applicant_id', '=', 't1.id');
                })
                ->join("resort_positions as t5", "t5.id", "=", "vacancies.position")
                ->join("resort_departments as t6", "t6.id", "=", "t5.dept_id")
                ->whereBetween('t3.InterViewDate', [$currentMonthStart, $currentMonthEnd])
                ->where('vacancies.Resort_id', $resort_id)
                ->where('vacancies.department', $Dept_id)
                ->selectRaw('
                    t1.id as Applicant_id,
                    t1.Application_date,
                    t1.passport_photo,
                    t1.first_name,
                    t1.last_name,
                    t1.gender,
                    t1.mobile_number as Contact,
                    t1.email as Email,
                    t2.name AS Nationality,
                    t3.InterViewDate,
                    t3.ApplicantInterviewtime,
                    t3.ResortInterviewtime,
                    t3.Status AS InterviewStatus,
                    t3.MeetingLink,
                    t4.As_ApprovedBy AS ApprovedBy,
                    t4.status AS ApplicationStatus,
                    t5.position_title as Position,
                    t4.id as ApplicantStatus_id,
                    t3.id as Interview_id,
                    t6.name as Department,
                    t1.notes, -- Ensure this field exists
                    t3.ResortInterviewtime'
                )
                ->get()
                ->map(function ($applicant) use ($config) {
                    $applicant->name = ucfirst($applicant->first_name . ' ' . $applicant->last_name);
                    $applicant->InterViewDate = Carbon::parse($applicant->InterViewDate)->format('d M');
                    $applicant->rank_name = $config[$applicant->As_ApprovedBy] ?? 'Wisdom AI';
                    $applicant->profileImg = URL::asset($applicant->passport_photo);
                    $applicant->Notes = isset($applicant->notes) ? base64_encode($applicant->notes) : '';
                    $applicant->applicant_id = base64_encode($applicant->ApplicantStatus_id);
                    return $applicant;
                });



            $resort_positions = ResortPosition::where('status', 'active')->where('dept_id',$Dept_id)->where('resort_id', $resort_id)
                ->withCount(['employees' => function ($query) {
                    $query->where('status', 'Active');
                }])
                ->get();

            $local_employees_count = Employee::where('resort_id', $resort_id)
                ->where('Dept_id', $Dept_id)
                ->where('status', 'Active')
                ->where('nationality', 'Maldivian')
                ->count();

            $expatriate_employees_count = Employee::where('resort_id', $resort_id)
                ->where('Dept_id', $Dept_id)
                ->where('status', 'Active')
                ->where('nationality', '!=','Maldivian')
                ->count();

            $total_emp = ResortAdmin::where('resort_id', $resort_id)
                ->whereHas('GetEmployee', function ($query) use ($Dept_id) {
                    $query->where('Dept_id', $Dept_id);
                })
                ->count();

            $male_emp = ResortAdmin::where('resort_id', $resort_id)
                ->where('gender', 'male')
                ->whereHas('GetEmployee', function ($query) use ($Dept_id) {
                    $query->where('Dept_id', $Dept_id);
                })
                ->count();

            $female_emp = ResortAdmin::where('resort_id', $resort_id)
                ->where('gender', 'female')
                ->whereHas('GetEmployee', function ($query) use ($Dept_id) {
                    $query->where('Dept_id', $Dept_id);
                })
                ->count();

            $male_emp_percentage = $total_emp > 0 ? round(($male_emp / $total_emp) * 100 ,2): 0;
            $female_emp_percentage = $total_emp > 0 ? round(($female_emp / $total_emp) * 100,2): 0;

             $vacant_positions = DB::table('resort_positions as p')
                ->leftJoin('employees as e', 'e.Position_id', '=', 'p.id')
                ->where('p.resort_id', '=', $resort_id)
                ->where('p.dept_id', '=', $Dept_id);

            $vacant_positions = $vacant_positions->select(
                DB::raw('COUNT(DISTINCT p.id) as total_positions_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN e.id IS NOT NULL THEN p.id END) as positions_with_employees'),
                DB::raw('COUNT(DISTINCT p.id) - COUNT(DISTINCT CASE WHEN e.id IS NOT NULL THEN p.id END) as vacant_positions'),
            DB::raw('COUNT(e.id) as TotalBudgtedemp')
                )->first();

                $manning_response = (object) [
                    "total_budgeted_employees" => $vacant_positions->total_positions_count, // Total unique positions
                    "total_filled_positions_count" => $vacant_positions->positions_with_employees, // Positions where employees exist
                    "total_vacant_count" => $vacant_positions->vacant_positions, // Positions with no employees
                    "TotalBudgtedemp"=>$vacant_positions->TotalBudgtedemp,
            ];

            $todayleaveUsers = EmployeeLeave::where('resort_id', $resort_id)
                            ->whereIn('Emp_id', $resort_employee_ids)
                            ->whereDate('from_date', '<=', Carbon::today())
                            ->whereDate('to_date', '>=', Carbon::today())
                            ->where('status', 'Approved')
                            ->with('employee')
                            ->get();

            $upcomingLeaveUsers = EmployeeLeave::where('resort_id', $resort_id)
                            ->whereIn('Emp_id', $resort_employee_ids)
                            ->whereDate('from_date', '>', Carbon::today())
                            ->where('status', 'Approved')
                            ->with('employee')
                            ->get();

            $accommodationData = AssingAccommodation::
                                whereHas('employee', function ($query) use ($Dept_id) {
                                    $query->where('Dept_id', $Dept_id);
                                })
                                ->with('availableAccommodation', 'employee')
                                ->where('resort_id', $resort_id)
                                ->limit(5)->orderBy('created_at','desc')->get();

            $totalIncidentCounts = Incidents::where('resort_id', $resort_id)->count();
            $underInvestigationIncidentCounts = Incidents::where('resort_id', $resort_id)->where('status','Assigned To')->count();

            $incidentData = Incidents::where('resort_id', $resort_id)->where('status','Reported')->orderBy('created_at','DESC')->limit(5)->get();

            $SOSHistory = SOSHistoryModel::with(['getSos','employee','employee.resortAdmin'])
                ->where('resort_id', $resort_id)
                ->whereHas('employee', function ($query) use ($Dept_id) {
                    $query->where('Dept_id', $Dept_id);
                })
                ->limit(5)
                ->latest()
                ->get();

            $probationEmployees = Employee::where('resort_id', $resort_id)
                ->where('Dept_id', $Dept_id)
                ->where('status', 'Active')
                ->whereIn('probation_status', ['Active','Extended'])
                ->get();

            $AnnouncementData = Announcement::where('resort_id', $resort_id)
                ->whereHas('employee', function ($query) use ($Dept_id) {
                    $query->where('Dept_id', $Dept_id);
                })
                ->limit(10)
                ->orderBy('created_at', 'DESC')
                ->get();

             $grivanceSubmissionModel = GrivanceSubmissionModel::where('resort_id', $resort_id)
                                ->whereHas('GetEmployee', function ($query) use ($Dept_id) {
                                    $query->where('Dept_id', $Dept_id);
                                })
                                ->with('category')
                                ->selectRaw('Grivance_Cat_id, COUNT(*) as count')
                                ->groupBy('Grivance_Cat_id')
                                ->get()
                                ->map(function ($item) {
                                    $item->category_name = $item->category->Category_Name ?? 'Unknown';
                                    return $item;
                                });

            $disciplinarySubmissionModel = disciplinarySubmit::where('resort_id', $resort_id)
            ->whereHas('GetEmployee', function ($query) use ($Dept_id) {
                $query->where('Dept_id', $Dept_id);
            })
            ->with('category')
            ->selectRaw('Category_id, COUNT(*) as count')
            ->groupBy('Category_id')
            ->get()
            ->map(function ($item) {
                $item->category_name = $item->category->DisciplinaryCategoryName ?? 'Unknown';
                return $item;
            });


            $EmployeeResignation = EmployeeResignation::where('resort_id', $resort_id)
                ->whereIn('employee_id', $resort_employee_ids)
                ->where('status', '!=', 'Rejected')
                ->whereHas('assignedForm', function ($query) {
                    $query->where('assigned_to_type', 'employee');
                })
                ->whereHas('employee', function ($query) use ($Dept_id) {
                    $query->where('Dept_id', $Dept_id);
                })
                ->with('assignedForm')
                ->get();

            $pending_learning_request = LearningRequest::with('learning')->where('status','Pending')->where('resort_id',$resort_id)->where('created_by',$this->globalUser->GetEmployee->Admin_Parent_id)->get();

            $monthly = MonthlyCheckingModel::join("employees as t1", "t1.id", "=", "monthly_checking_models.emp_id")
                ->join("resort_admins as t2", "t2.id", "=", "t1.Admin_Parent_id")
                ->join("resort_positions as t3", "t3.id", "=", "t1.Position_id")
                ->leftjoin("learning_programs as t4", "t4.id", "=", "monthly_checking_models.tranining_id")
                ->where("t1.resort_id", $this->globalUser->resort_id)


                ->orderBy("monthly_checking_models.id", "desc")
                ->select([
                    't1.id as emp_orignal_id',
                    't4.name as traniningname',
                    't2.first_name',
                    't2.last_name',
                    't3.position_title as PositionName',
                    'monthly_checking_models.*'
                ]);

            // Get the collection after executing the query
            $monthlyCollection = $monthly->limit(5)->orderBy('monthly_checking_models.created_at','DESC')->get();

            // Process each record
            $monthlyCheckinPerformance = $monthlyCollection->map(function($ak) {
                if (isset($ak->tranining_id)) {
                    $l = LearningRequest::with('employees')
                        ->where("learning_id", $ak->tranining_id)
                        ->where("resort_id", $this->globalUser->resort_id)
                        ->whereHas('employees', function($q) use ($ak) {
                            $q->where('id', $ak->emp_orignal_id);
                        })
                        ->latest('id')
                        ->first();

                    $ak->duration = isset($l->start_date) && isset($l->end_date)
                        ? $l->start_date . '-' . $l->end_date
                        : '-';

                    $ak->status = isset($l->status) && $l->status == 'Approved'
                        ? 'In Progress'
                        : (isset($l->status) ? $l->status : 'Pending');
                } else {
                    $ak->duration = '-';
                    $ak->status = 'Pending';
                }
                $ak->new_date_of_dicussion = date("d M Y",strtotime($ak->date_discussion));
                return $ak;
            });

            $attendanceDataTodoList = app(TimeandAttendanceDashboardController::class)->Tododata();

            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();

        $config = config('settings.Position_Rank');

        $UplcomingApplicants1 = Vacancies::join("applicant_form_data as t1", "t1.Parent_v_id", "=", "vacancies.id")
            ->join("countries as t2", "t2.id", "=", "t1.country")
            ->join('applicant_wise_statuses as t4', function ($join) {
                $join->on('t4.Applicant_id', '=', 't1.id');
            })
            ->whereIn('t4.status', ['Round'])
            ->join('applicant_inter_view_details as t3', function ($join) {
                $join->on('t3.Applicant_id', '=', 't1.id');
            })
            ->join("resort_positions as t5", "t5.id", "=", "vacancies.position")
            ->join("resort_departments as t6", "t6.id", "=", "t5.dept_id")
            ->whereNotNull('t3.MeetingLink');


            $UplcomingApplicants1->whereBetween('t3.InterViewDate', [$currentMonthStart, $currentMonthEnd])
            ->orderBy("InterViewDate","asc");


        $UplcomingApplicants = $UplcomingApplicants1
            ->where('vacancies.Resort_id', $resort_id)
            ->selectRaw('
                t1.id as Applicant_id,
                t1.Application_date,
                t1.passport_photo,
                t1.first_name,
                t1.last_name,
                t1.gender,
                t1.mobile_number as Contact,
                t1.email as Email,
                t2.name AS Nationality,
                t3.InterViewDate,
                t3.ApplicantInterviewtime,
                t3.ResortInterviewtime,
                t3.Status AS InterviewStatus,
                t3.MeetingLink,
                t4.As_ApprovedBy AS ApprovedBy,
                t4.status AS ApplicationStatus,
                t5.position_title as Position,
                t4.id as ApplicantStatus_id,
                t3.id as Interview_id,
                t6.name as Department,
                t3.ResortInterviewtime
            ')
            ->get()

        ->map(function ($item) use($config) {
            $item->AppliedDate = Carbon::parse($item->Application_date)->format('d-m-Y');

            $item->InterViewDate = $item->InterViewDate ? Carbon::parse($item->InterViewDate)->format('d-m-Y') : '-';

            $item->MalidivanTime = $item->ResortInterviewtime ?? '-';

            $item->ApplicantTime = $item->ApplicantInterviewtime ?? '-';

            $item->InterviewStatus = $item->InterviewStatus ?? 'Slot Not Booked';
            $item->rank_name = $config[$item->ApprovedBy] ?? 'Unknown Rank';
            return $item;
            })->map(function ($applicant) use ($config) {
            // Map applicant fields
            $applicant->name = ucfirst($applicant->first_name . ' ' . $applicant->last_name);
            $applicant->RealInterViewDate =Carbon::parse($applicant->InterViewDate)->format('Y-m-d');
            $applicant->InterViewDate = Carbon::parse($applicant->InterViewDate)->format('d M');

            $applicant->rank_name = $config[$applicant->As_ApprovedBy] ?? 'Wisdom AI';
            $applicant->profileImg = URL::asset($applicant->passport_photo);
            $applicant->Notes = base64_encode($applicant->notes);
            $applicant->applicant_id = base64_encode($applicant->applicant_status_id);
            $applicant->passport_photo =URL::asset( $applicant->passport_photo);

            return $applicant;
        });

            $regularOt = 0;
            $holidayOtweekly = 0;
            $totalHours = 0;
            $reporting_to = $this->globalUser->GetEmployee->id;
             $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
            $underEmp_id = Common::getSubordinates($reporting_to);


            // Format dates to MM/DD/YYYY and display as a range
            $dateRange = $startOfMonth->format('m/d/Y') . ' - ' . $endOfMonth->format('m/d/Y');
            $rosterData = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
                ->join('duty_rosters as t3', 't3.Emp_id', '=', 'employees.id')
                ->select(
                    't3.id as duty_roster_id',
                    't3.DayOfDate',
                    't1.id as Parentid',
                    't1.first_name',
                    't1.last_name',
                    't1.profile_picture',
                    'employees.id as emp_id',
                    't2.position_title'
                )
                ->where('t3.ShiftDate', $dateRange)
                ->where('t1.resort_id', $this->globalUser->resort_id)
                // ->whereIn('employees.id', $underEmp_id)
                ->where('employees.reporting_to',$reporting_to )
                ->groupBy('employees.id', 't3.id', 't3.DayOfDate', 't1.id', 't1.first_name', 't1.last_name',
                        't1.profile_picture', 't2.position_title')
                ->get();

            $weekStartDate = Carbon::now()->startOfWeek();
            $weekendDate = Carbon::now()->endOfWeek();
            $year = now()->year;
            $month = now()->month;
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $headers = [];
            $currentDate = clone $weekStartDate;
            while ($currentDate <= $weekendDate) {
                $headers[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'day' => $currentDate->format('D'),
                    'display_date' => $currentDate->format('d M')
                ];
                $currentDate->addDay();
            }
            foreach ($rosterData as $r) {
                $rosterInternalData = Common::GetOverTime(
                    $resort_id,
                    $r->duty_roster_id,
                    $r->emp_id,
                    $weekStartDate,
                    $weekendDate,
                    $startOfMonth,
                    $endOfMonth,
                    "weekly"
                );

                $regularOt = 0;
                $holidayOtweekly = 0;
                $dailyOvertimes = [];

                if (!$rosterInternalData) {
                    $r->regularOvertime = 0;
                    $r->holidayOvertime = 0;
                    $r->totalOvertime = 0;
                    $r->dailyOvertimes = [];
                    continue;
                }

                foreach ($headers as $header) {
                    $formattedHeaderDate = $header['date'];

                    $shiftData = collect($rosterInternalData)
                        ->first(function($shift) use ($formattedHeaderDate) {
                            return isset($shift->date) && $shift->date === $formattedHeaderDate;
                        });

                    $dailyOT = 0;
                    $isHolidayOrFriday = false;

                    if ($shiftData && isset($shiftData->OverTime) && !empty($shiftData->OverTime)) {

                        $dayName = Carbon::parse($shiftData->date)->format('D');
                        $isHolidayOrFriday = ($dayName === "Fri" ||
                            (isset($shiftData->publicholiday) && $shiftData->publicholiday === "yes"));


                        $overtimeParts = explode(':', $shiftData->OverTime);
                        if (count($overtimeParts) >= 2) {
                            $hours = (int)$overtimeParts[0];
                            $minutes = (int)$overtimeParts[1];
                            $dailyOT = $hours + ($minutes / 60);

                            if ($isHolidayOrFriday) {
                                $holidayOtweekly += $dailyOT;
                            } else {
                                $regularOt += $dailyOT;
                            }
                        }
                    }


                    $dailyOvertimes[$formattedHeaderDate] = [
                        'hours' => $dailyOT,
                        'is_holiday' => $isHolidayOrFriday
                    ];
                }

                $totalHours = $holidayOtweekly + $regularOt;

                $r->regularOvertime = round($regularOt, 2);
                $r->holidayOvertime = round($holidayOtweekly, 2);
                $r->totalOvertime = round($totalHours, 2);
                $r->dailyOvertimes = $dailyOvertimes;
                $r->weekStartDate = $weekStartDate->format('Y-m-d');
                $r->weekEndDate = $weekendDate->format('Y-m-d');
            }

            $totalEmployees = $rosterData->count();
            $totalOverallWorkingHours = $rosterData->sum('totalOvertime');
            $totalNormalWorkingHours = $rosterData->sum('regularOvertime');
            $totalHolidayWorkingHours = $rosterData->sum('holidayOvertime');

            // Sort by totalOvertime for the table display
            $rosterData = $rosterData->sortByDesc('totalOvertime')->values();


            $ongoing_tranning = TrainingSchedule::where('resort_id', $resort_id)
                ->where('status', 'Ongoing')
                ->with(['learningProgram', 'trainingAttendances','participants.employee'])
                ->limit(10)->get();

            return view('resorts.master-dashboard.hoddashboard', compact(
                'resort_id','resort_divisions','resort_departments','resort_positions',
                'hiring_request','vacancies','TotalApplicants','TotalApplicantCounts','Interviews','Hired','UpcomingApplicants',
                'total_employees','present_employee_counts','absent_employee_counts','leave_employee_counts','resort_positions',
                'expatriate_employees_count','local_employees_count','male_emp_percentage','female_emp_percentage','manning_response','InProgressApplicants','todayleaveUsers','upcomingLeaveUsers','accommodationData','totalIncidentCounts','underInvestigationIncidentCounts','incidentData','SOSHistory','probationEmployees','AnnouncementData','grivanceSubmissionModel','disciplinarySubmissionModel','EmployeeResignation','pending_learning_request','monthlyCheckinPerformance','attendanceDataTodoList','rosterData','totalOverallWorkingHours','totalNormalWorkingHours','totalHolidayWorkingHours','totalEmployees','UplcomingApplicants','ongoing_tranning'
            ));

        // } catch (\Exception $e) {
        //     \Log::error("Error in HOD Dashboard: " . $e->getMessage(), [
        //         'file' => $e->getFile(),
        //         'line' => $e->getLine(),
        //     ]);

        //     // Default empty collections in case of failure
        //     $resort_divisions = $resort_departments = $resort_positions = collect();
        //     $hiring_request = $vacancies = collect();

        //     return view('resorts.talentacquisition.dashboard.hoddashboard', compact(
        //         'resort_id',
        //         'resort_divisions',
        //         'resort_departments',
        //         'resort_positions',
        //         'hiring_request',
        //         'vacancies'
        //     ));
        // }
    }


    public function gm_dashboard(Request $request){

         $page_title = 'Talent Acquisition Dashboard';
            $page_header = '<span class="arca-font">My</span> Dashboard';
            $currentYear = date('Y');
            $nextYear = $currentYear + 1;
            $resort_id = $this->globalUser->resort_id;
            $Dept_id = $this->globalUser->GetEmployee->Dept_id;
            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();
            $config = config('settings.Position_Rank');

            // Fetch active divisions, departments, and positions for the resort
            $resort_divisions = ResortDivision::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();

            $Employees = Employee::where('resort_id', $resort_id)->where('status','Active')->get();

            $total_employees = $Employees->count();
            $resort_employee_ids = $Employees->pluck('id')->toArray();

            $present_employee_counts = ParentAttendace::where('resort_id', $resort_id)
                ->whereIn('Emp_id', $resort_employee_ids)
                ->whereDate('date', Carbon::today())
                ->where('Status', 'Present')
                ->count();
            $absent_employee_counts = ParentAttendace::where('resort_id', $resort_id)
                ->whereIn('Emp_id', $resort_employee_ids)
                ->whereDate('date', Carbon::today())
                ->where('Status', 'Absent')
                ->count();

            $leave_employee_counts = EmployeeLeave::where('resort_id', $resort_id)
                ->whereIn('Emp_id', $resort_employee_ids)
                ->whereDate('from_date', '<=', Carbon::today())
                ->whereDate('to_date', '>=', Carbon::today())
                ->where('status', 'Approved')
                ->count();

            // Fetch hiring requests with related notifications
            $hiring_request = Vacancies::with(['Getdepartment','Getposition',
                'TAnotificationParent.TAnotificationChildren' => function ($query) {
                    $query->where('status', '!=', ''); // Filter by valid status
                }
            ])
            ->whereHas('TAnotificationParent.TAnotificationChildren', function ($query) {
                $query->where('status', '!=', ''); // Ensure valid notifications exist
            })
            ->where('department', $Dept_id)
            ->where('resort_id', $resort_id)
            ->orderBy('vacancies.id', 'desc') // Order by ID in descending order
            ->limit(6)
            ->get();

            $vacancies = Vacancies::with([
                'Getdepartment',
                'Getposition',
                'TAnotificationParent.TAnotificationChildren' => function ($query) {
                    $query->where('status', 'Approved')  // Only include notifications that are approved
                          ->where('Approved_By', 8);     // Ensure the approval is from GM (Approved_By = 8)
                }
            ])
            ->where('resort_id', $resort_id)
            ->where('department', $Dept_id)
            ->whereHas('TAnotificationParent.TAnotificationChildren', function ($query) {
                $query->where('status', 'ForwardedToNext')  // Ensure only those with "Approved" status are included
                      ->where('Approved_By', 8);     // Ensure the approval is by GM
            })
            ->orderByDesc('id')
            ->limit(6)
            ->get();


            $TotalApplicants = Applicant_form_data::join('applicant_wise_statuses as t1', 't1.Applicant_id', '=', 'applicant_form_data.id')
                ->join('vacancies as t2', 't2.id', '=', 'applicant_form_data.Parent_v_id')
                ->where('t1.status', '!=', 'Selected')
                ->where('applicant_form_data.resort_id', $resort_id)
                ->where('t2.department', $Dept_id)
                ->select(DB::raw('COUNT(DISTINCT t1.Applicant_id) as total_applicants'))
                ->first();

            $TotalApplicantCounts = $TotalApplicants->total_applicants ?? 0;

            $Interviews = DB::table('applicant_inter_view_details as t1')
                ->join('applicant_form_data as t2', 't2.id', '=', 't1.Applicant_id') // Linking to applicant_form_data
                ->join('vacancies as t3', 't3.id', '=', 't2.Parent_v_id') // Linking to vacancies for department
                ->where('t2.resort_id', $resort_id)
                ->where('t3.department', $Dept_id)
                ->count();

            $Hired = DB::table('applicant_wise_statuses as t1')
                ->join('applicant_form_data as t2', 't2.id', '=', 't1.Applicant_id')
                ->join('vacancies as t3', 't3.id', '=', 't2.Parent_v_id') // Correct alias for vacancies
                ->where('t2.resort_id', $resort_id)
                ->where('t1.As_ApprovedBy', 8)
                ->where('t1.status', 'Selected')
                ->where('t3.department', $Dept_id)
                ->distinct()
                ->count('t1.Applicant_id'); // Count distinct Applicant_id

            $InProgressApplicants = DB::table('applicant_wise_statuses as t1')
                ->join('applicant_form_data as t2', 't2.id', '=', 't1.Applicant_id')
                ->join('vacancies as t3', 't3.id', '=', 't2.Parent_v_id') // Correct alias for vacancies
                ->where('t2.resort_id', $resort_id)
                ->where('t1.status', ['Sortlisted By Wisdom AI', 'Sortlisted'])
                ->where('t3.department', $Dept_id)
                ->distinct()
                ->count('t1.Applicant_id'); // Count distinct Applicant_id

            $UpcomingApplicants = Vacancies::join("applicant_form_data as t1", "t1.Parent_v_id", "=", "vacancies.id")
                ->join("countries as t2", "t2.id", "=", "t1.country")
                ->join('applicant_wise_statuses as t4', function ($join) {
                    $join->on('t4.Applicant_id', '=', 't1.id');
                })
                ->whereIn('t4.status', ['Round']) // Adjust 'Round' to actual status values in your database
                ->join('applicant_inter_view_details as t3', function ($join) {
                    $join->on('t3.Applicant_id', '=', 't1.id');
                })
                ->join("resort_positions as t5", "t5.id", "=", "vacancies.position")
                ->join("resort_departments as t6", "t6.id", "=", "t5.dept_id")
                ->whereBetween('t3.InterViewDate', [$currentMonthStart, $currentMonthEnd])
                ->where('vacancies.Resort_id', $resort_id)
                ->where('vacancies.department', $Dept_id)
                ->selectRaw('
                    t1.id as Applicant_id,
                    t1.Application_date,
                    t1.passport_photo,
                    t1.first_name,
                    t1.last_name,
                    t1.gender,
                    t1.mobile_number as Contact,
                    t1.email as Email,
                    t2.name AS Nationality,
                    t3.InterViewDate,
                    t3.ApplicantInterviewtime,
                    t3.ResortInterviewtime,
                    t3.Status AS InterviewStatus,
                    t3.MeetingLink,
                    t4.As_ApprovedBy AS ApprovedBy,
                    t4.status AS ApplicationStatus,
                    t5.position_title as Position,
                    t4.id as ApplicantStatus_id,
                    t3.id as Interview_id,
                    t6.name as Department,
                    t1.notes, -- Ensure this field exists
                    t3.ResortInterviewtime'
                )
                ->get()
                ->map(function ($applicant) use ($config) {
                    $applicant->name = ucfirst($applicant->first_name . ' ' . $applicant->last_name);
                    $applicant->InterViewDate = Carbon::parse($applicant->InterViewDate)->format('d M');
                    $applicant->rank_name = $config[$applicant->As_ApprovedBy] ?? 'Wisdom AI';
                    $applicant->profileImg = URL::asset($applicant->passport_photo);
                    $applicant->Notes = isset($applicant->notes) ? base64_encode($applicant->notes) : '';
                    $applicant->applicant_id = base64_encode($applicant->ApplicantStatus_id);
                    return $applicant;
                });



            $resort_departments = ResortDepartment::where('status', 'active')->where('resort_id', $resort_id)->get();
            $resort_positions = ResortPosition::where('status', 'active')->where('resort_id', $resort_id)
                ->withCount(['employees' => function ($query) {
                    $query->where('status', 'Active');
                }])
                ->get();

            $local_employees_count = Employee::where('resort_id', $resort_id)
                ->where('status', 'Active')
                ->where('nationality', 'Maldivian')
                ->count();

            $expatriate_employees_count = Employee::where('resort_id', $resort_id)
                ->where('status', 'Active')
                ->where('nationality', '!=','Maldivian')
                ->count();

            $total_emp = ResortAdmin::where('resort_id', $resort_id)->count();
            $male_emp = ResortAdmin::where('resort_id', $resort_id)->where('gender', 'male')->count();
            $female_emp = ResortAdmin::where('resort_id', $resort_id)->where('gender', 'female')->count();

            $male_emp_percentage = $total_emp > 0 ? ($male_emp / $total_emp) * 100 : 0;
            $female_emp_percentage = $total_emp > 0 ? ($female_emp / $total_emp) * 100 : 0;

             $vacant_positions = DB::table('resort_positions as p')
                ->leftJoin('employees as e', 'e.Position_id', '=', 'p.id')
                ->where('p.resort_id', '=', $resort_id);

            $vacant_positions = $vacant_positions->select(
                DB::raw('COUNT(DISTINCT p.id) as total_positions_count'), // Total number of unique positions
                DB::raw('COUNT(DISTINCT CASE WHEN e.id IS NOT NULL THEN p.id END) as positions_with_employees'), // Positions with at least one employee
                DB::raw('COUNT(DISTINCT p.id) - COUNT(DISTINCT CASE WHEN e.id IS NOT NULL THEN p.id END) as vacant_positions'), // Positions with no employees
            DB::raw('COUNT(e.id) as TotalBudgtedemp')
                )->first();

                $manning_response = (object) [
                    "total_budgeted_employees" => $vacant_positions->total_positions_count, // Total unique positions
                    "total_filled_positions_count" => $vacant_positions->positions_with_employees, // Positions where employees exist
                    "total_vacant_count" => $vacant_positions->vacant_positions, // Positions with no employees
                    "TotalBudgtedemp"=>$vacant_positions->TotalBudgtedemp,
            ];

            $todayleaveUsers = EmployeeLeave::where('resort_id', $resort_id)
                            ->whereIn('Emp_id', $resort_employee_ids)
                            ->whereDate('from_date', '<=', Carbon::today())
                            ->whereDate('to_date', '>=', Carbon::today())
                            ->where('status', 'Approved')
                            ->with('employee')
                            ->get();

            $upcomingLeaveUsers = EmployeeLeave::where('resort_id', $resort_id)
                            ->whereIn('Emp_id', $resort_employee_ids)
                            ->whereDate('from_date', '>', Carbon::today())
                            ->where('status', 'Approved')
                            ->with('employee')
                            ->get();

                $accommodationData = AssingAccommodation::
                                    whereHas('employee')
                                    ->with('availableAccommodation', 'employee')
                                    ->where('resort_id', $resort_id)
                                    ->limit(5)->orderBy('created_at','desc')->get();

            $totalIncidentCounts = Incidents::where('resort_id', $resort_id)->count();
            $underInvestigationIncidentCounts = Incidents::where('resort_id', $resort_id)->where('status','Assigned To')->count();

            $incidentData = Incidents::where('resort_id', $resort_id)->where('status','Reported')->orderBy('created_at','DESC')->limit(5)->get();

            $SOSHistory = SOSHistoryModel::with(['getSos','employee','employee.resortAdmin'])->where('resort_id',$resort_id)->limit(5)->latest()->get();

            $probationEmployees = Employee::where('resort_id', $resort_id)
                ->where('status', 'Active')
                ->whereIn('probation_status', ['Active','Extended'])
                ->get();

            $AnnouncementData = Announcement::where('resort_id', $resort_id)->limit(10)->orderBy('created_at','DESC')->get();

             $grivanceSubmissionModel = GrivanceSubmissionModel::where('resort_id', $resort_id)
                                ->with('category')
                                ->selectRaw('Grivance_Cat_id, COUNT(*) as count')
                                ->groupBy('Grivance_Cat_id')
                                ->get()
                                ->map(function ($item) {
                                    $item->category_name = $item->category->Category_Name ?? 'Unknown';
                                    return $item;
                                });

            $disciplinarySubmissionModel = disciplinarySubmit::where('resort_id', $resort_id)
            ->with('category')
            ->selectRaw('Category_id, COUNT(*) as count')
            ->groupBy('Category_id')
            ->get()
            ->map(function ($item) {
                $item->category_name = $item->category->DisciplinaryCategoryName ?? 'Unknown';
                return $item;
            });


            $grivance_data = GrivanceSubmissionModel::where('resort_id',$resort_id)->get();
            $disiplinary_data = disciplinarySubmit::where('resort_id',$resort_id)->get();

            $EmployeeResignation = EmployeeResignation::where('resort_id', $resort_id)
                ->whereIn('employee_id', $resort_employee_ids)
                ->where('status', '!=', 'Rejected')
                ->whereHas('assignedForm', function ($query) {
                    $query->where('assigned_to_type', 'employee');
                })->with('assignedForm')
                ->get();



            //write a query to get the total number of employees in each department with their department name total no employee vecant position and today leave count
            $departmentEmployeeCounts = Employee::select('Dept_id', DB::raw('COUNT(*) as total_employees'))
                ->where('resort_id', $resort_id)
                ->where('status', 'Active')
                ->groupBy('Dept_id')
                ->get()
                ->map(function ($item) use ($resort_id) {
                    $department = ResortDepartment::find($item->Dept_id);
                    $todayLeaveCount = EmployeeLeave::where('resort_id', $resort_id)
                        ->whereIn('Emp_id', Employee::where('Dept_id', $item->Dept_id)->pluck('id'))
                        ->whereDate('from_date', '<=', Carbon::today())
                        ->whereDate('to_date', '>=', Carbon::today())
                        ->where('status', 'Approved')
                        ->count();

                    $vacantPositionsCount = ResortPosition::where('dept_id', $item->Dept_id)
                        ->where('resort_id', $resort_id)
                        ->whereDoesntHave('employees', function ($query) {
                            $query->where('status', 'Active');
                        })
                        ->count();

                    return [
                        'department_name' => $department->name ?? 'Unknown',
                        'total_employees' => $item->total_employees,
                        'today_leave_count' => $todayLeaveCount,
                        'vacant_positions_count' => $vacantPositionsCount,
                    ];
                });


            // Get service charge data for current year and last year
            $serviceChargeData = collect([]);
            $serviceChargesData = []; // Initialize the array before using it

            // Get service charges for current and last year
            $currentYearServiceCharges = ServiceCharges::where('resort_id', $resort_id)
                ->where('year', $currentYear)
                ->orderBy('month')
                ->get();

            $lastYearServiceCharges = ServiceCharges::where('resort_id', $resort_id)
                ->where('year', $currentYear - 1)
                ->orderBy('month')
                ->get();

            // Combine all months from both years to ensure we have all available data
            $allMonths = $currentYearServiceCharges->pluck('month')
                ->concat($lastYearServiceCharges->pluck('month'))
                ->unique()
                ->sort()
                ->values();

            // Create data array with existing month data only
        if (!empty($$allMonths))
        {
                $monthName = date('F', mktime(0, 0, 0, $month, 1));

                $currentYearValue = $currentYearServiceCharges
                    ->where('month', $month)
                    ->first()->service_charge ?? '0.00';

                $lastYearValue = $lastYearServiceCharges
                    ->where('month', $month)
                    ->first()->service_charge ?? '0.00';

                $serviceChargesData[] = [
                    'month' => $monthName,
                    'month_num' => $month,
                    'current_year_value' => $currentYearValue,
                    'last_year_value' => $lastYearValue,
                ];
            }
        else
        {
            $serviceChargesData[] = [
                    'month' => date('M'),
                    'month_num' => date('m'),
                    'current_year_value' => '',
                    'last_year_value' => '',
                ];
        }



            $ongoing_tranning = TrainingSchedule::where('resort_id', $resort_id)
                ->where('status', 'Ongoing')
                ->with(['learningProgram', 'trainingAttendances','participants.employee'])
                ->limit(10)->get();

            return view('resorts.master-dashboard.gmdashboard', compact(
                'resort_id','resort_divisions','resort_departments','resort_positions',
                'hiring_request','vacancies','TotalApplicants','TotalApplicantCounts','Interviews','Hired','UpcomingApplicants',
                'total_employees','present_employee_counts','absent_employee_counts','leave_employee_counts','resort_positions',
                'expatriate_employees_count','local_employees_count','male_emp_percentage','female_emp_percentage','manning_response','InProgressApplicants','todayleaveUsers','upcomingLeaveUsers','accommodationData','totalIncidentCounts','underInvestigationIncidentCounts','incidentData','SOSHistory','probationEmployees','AnnouncementData','grivanceSubmissionModel','disciplinarySubmissionModel','grivance_data','disiplinary_data','EmployeeResignation','departmentEmployeeCounts','serviceChargesData','ongoing_tranning'
            ));
    }


    public function sendBirthdayNotification(Request $request,$emp_id)
    {
        $resort_id = $this->globalUser->resort_id;
        $employee = Employee::where('id', $emp_id)->where('resort_id', $resort_id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }
        $notifyEmployees = Employee::where('resort_id', $resort_id)
            ->where('id', '!=', $emp_id)
            ->where('Dept_id', $employee->Dept_id)
            ->get();

        $title = "Birthday Celebration!";
        $msg = "Today is {$employee->resortAdmin->full_name}'s birthday. Join us in celebrating!";
        $moduleName = "Birthday Notification";

        foreach ($notifyEmployees as $notifyEmployee) {
            event(new ResortNotificationEvent(Common::nofitication(
                $resort_id,
                10,
                $title,
                $msg,
                $notifyEmployee->id,
                $moduleName
            )));
        }


        return response()->json(['success' => true, 'message' => 'Birthday notification sent successfully!']);

    }


    public function getMenuData(Request $request){

        $page_route = $request->page_route;
        $device = $request->input('deviceType');
        $resort = Auth::guard('resort-admin')->user();
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;
        $auth_id = isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->id : 26;

        $menu = Common::GetResortMenu($resort_id , Route::currentRouteName());


        foreach ($menu['menu'] as &$module) {
            $module['submenu'] = Common::GetResortMenuPage($module['ModuleId']);
            // Filter submenu items based on permissions
            $module['submenu'] = array_filter($module['submenu'], function($sub) use ($auth_id, $module) {
                return Common::resortHasPermissions($module['ModuleId'], $sub['Page_id'], config('settings.resort_permissions.view'));
            });
        }

        if ($device === 'mobile') {
            $html = view('resorts.layouts.mobile-menu', compact('menu', 'auth_id','device','page_route'))->render();
        }else{
            if($resort->menu_type == 'horizontal'){
                $html = view('resorts.layouts.desktop-menu', compact('menu', 'auth_id','device','page_route'))->render();
            }else{

                $html = view('resorts.layouts.vertical-menu', compact('menu', 'auth_id','device','page_route'))->render();
            }
        }

        return response()->json([
            'success' => true,
            'menuHtml' => $html,
            'device' => $device,
            'auth_id' => $auth_id,
            'menu_type' => $resort->menu_type,
        ]);
    }

}
