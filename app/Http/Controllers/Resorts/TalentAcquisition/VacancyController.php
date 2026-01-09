<?php
namespace App\Http\Controllers\Resorts\TalentAcquisition;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortSection;
use App\Models\ResortPosition;
use App\Models\Resort;
use App\Models\ResortAdmin;
use App\Models\Employee;
use App\Models\Vacancies;
use App\Models\ApplicationLink;
use App\Models\JobAdvertisement;
use App\Models\ApplicantInterViewDetails;
use App\Models\Compliance;
use Validator;
use DB;
use App\Models\TAnotificationChild;
use App\Models\TAnotificationParent;
use App\Models\ServiceProvider;
use App\Models\PositionMonthlyData;
use App\Models\ManningResponse;
use App\Models\StoreManningResponseParent;
use App\Models\StoreManningResponseChild;
use App\Models\ResortBudgetCost;
use App\Models\TaEmailTemplate;
use App\Events\ResortNotificationEvent;
use App\Helpers\Common;
use Carbon\Carbon;
use URL;

class VacancyController extends Controller
{
    public $resort;
    protected $type;

    public function __construct()
    {
        $this->type = config('settings.Notifications');

        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function index()
    {
        try {
            $page_title ='View Vacancies';
            
            return view('resorts.talentacquisition.vacancies.index', compact('page_title'));
        } catch( \Exception $e ) {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return view('resorts.talentacquisition.vacancies.index');
        }
    }

    public function create()
    {

        try {
            $page_title = 'Add New Vacancy';
            $resort= Auth::guard('resort-admin')->user();
            $resort_id =$resort->resort_id;
            $id = Auth::guard('resort-admin')->user()->id;
            $position_id = $resort->GetEmployee->Position_id;
            $Dept_id = $resort->GetEmployee->Dept_id;
            $emp_details = Employee::where('Admin_Parent_id',$id)->get();
            $department_details = ResortDepartment::where('id',$emp_details[0]->Dept_id)->get();
            $resort_divisions = ResortDivision::where('id',$department_details[0]->division_id)->get();
            $resort_sections = ResortSection::where('dept_id',$Dept_id)->get();
            $sectionName = isset($resort_sections[0]) ? $resort_sections[0]->name : '';
            $sectionId = isset($resort_sections[0]) ? $resort_sections[0]->id : '';
            $resort_positions = ResortPosition::where('dept_id',$Dept_id)->get();
            $serviceProviders = ServiceProvider::orderBy('name')->get();
            // Define the rank values for HOD, MGR, GM from settings config
            $targetRanks = [
                array_search('HOD', config('settings.Position_Rank')),
                array_search('MGR', config('settings.Position_Rank')),
                array_search('GM', config('settings.Position_Rank'))
            ];
            // Query for employees in the same resort, with rank HOD/MGR/GM, and HODs from the same department
            $reportingEmployees = DB::table('employees')
                ->join('resort_admins', 'employees.Admin_Parent_id', '=', 'resort_admins.id')
                ->where('employees.resort_id', $resort_id)
                ->whereIn('employees.rank', $targetRanks)
                ->where(function ($query) use ($Dept_id) {
                    $query->where('employees.rank', array_search('HOD', config('settings.Position_Rank')))
                          ->where('employees.Dept_id', $Dept_id)
                          ->orWhere('employees.rank', '<>', array_search('HOD', config('settings.Position_Rank')));
                })
                ->select(
                    'employees.*',
                    'resort_admins.first_name as first_name',
                    'resort_admins.last_name as last_name',
                    'resort_admins.email as admin_email'
                )
                ->get();

            // dd($reportingEmployees);
            return view('resorts.talentacquisition.vacancies.create', compact('page_title','position_id', 'Dept_id', 'emp_details', 'department_details','resort_positions','resort_divisions','resort_sections','sectionName','sectionId','reportingEmployees','serviceProviders'));
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return back()->with('error', 'Error loading vacancy creation page.');
        }
    }

    public function store(Request $request)
    {

        $validatedData = $request->validate([
            'status' => 'required|string',
            'budgeted' => 'required|string',
            'dept_id' => 'required|integer',
            'position' => 'required|string|max:255',
            'reporting_to' => 'nullable|integer',
            'division_id' => 'nullable|integer',
            'section_id' => 'nullable|integer',
            'employee_type' => 'nullable|string|max:255',
            'Total_position_required' => 'required|integer',
            'new_service_provider' => 'nullable|string|max:255',
            'service_provider' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric',
            'food' => 'nullable|string|max:255',
            'accommodation' => 'nullable|string|max:255',
            'transportation' => 'nullable|string|max:255',
            'budget_salary' => 'nullable|numeric',
            'proposed_salary' => 'nullable|numeric',
            'budgeted_accommodation' => 'nullable|numeric',
            'allowance' => 'nullable|numeric',
            'service_charge' => 'required|string|in:YES,NO',
            'uniform' => 'nullable|required|string|in:YES,NO',
            'medical' => 'nullable|numeric',
            'insurance' => 'nullable|numeric',
            'pension' => 'nullable|numeric',
            'recruitement' => 'nullable|array',
            'employee_name' => 'nullable|string|max:255',
            'amount_unit'=> 'required|string|in:MVR,USD',
            'is_required_local' => 'required|string|in:Yes,No'
        ]);
        $resort = Auth::guard('resort-admin')->user();
        $resort_id = $resort->resort_id;
        $recruitment = !empty($validatedData['recruitement']) ? implode(",", $validatedData['recruitement']) : null;
            if (!empty($validatedData['new_service_provider'])) 
            {
                $serviceProvider = ServiceProvider::firstOrCreate(['name' => $validatedData['new_service_provider'],'resort_id' => $resort_id]);
                $validatedData['service_provider'] = $serviceProvider->name;
            }
            elseif (!empty($validatedData['service_provider'])) 
            {
                $serviceProvider = ServiceProvider::where('name', $validatedData['service_provider'])->where('resort_id', $resort_id)->first();
            } 
            else
            {
                $serviceProvider = null;
            }

            // Check for duplicate
            $duplicate = Vacancies::where('Resort_id', $resort_id)
                ->where('department', $validatedData['dept_id'])
                ->where('position', $validatedData['position'])
                ->where('employee_type', $validatedData['employee_type'])
                ->where('service_provider_name', $serviceProvider ? $serviceProvider->name : null)
                ->whereDate('required_starting_date', Carbon::createFromFormat('d/m/Y', $request['required_starting_date'])->format('Y-m-d'))
                ->exists();

            if ($duplicate) 
            {
                return response()->json([
                                            'success' => false,
                                            'msg' => 'Duplicate vacancy found with the same position, department, and service provider.',
                                        ]);
            }
            // Save vacancy
            $vacancy = new Vacancies();
            $vacancy->Resort_id = $resort_id;
            $vacancy->budgeted = $validatedData['budgeted'];
            $vacancy->department = $validatedData['dept_id'];
            $vacancy->required_starting_date = Carbon::createFromFormat('d/m/Y', $request['required_starting_date'])->format('Y-m-d');
            $vacancy->position = $validatedData['position'];
            $vacancy->reporting_to = $validatedData['reporting_to'];
            $vacancy->rank = $request['rank_id'];
            $vacancy->division = $validatedData['division_id'];
            $vacancy->section = $validatedData['section_id'] ?? null;
            $vacancy->employee_type = $validatedData['employee_type'];
            $vacancy->Total_position_required = $validatedData['Total_position_required'];
            $vacancy->service_provider_name = $serviceProvider ? $serviceProvider->name : null;
            $vacancy->salary = $validatedData['salary'];
            $vacancy->food = $validatedData['food'];
            $vacancy->accomodation = $validatedData['accommodation'];
            $vacancy->transportation = $validatedData['transportation'];
            $vacancy->employee = $validatedData['employee_name'] ?? null;
            $vacancy->budgeted_salary = $validatedData['budget_salary'] ?? 0.00;
            $vacancy->budgeted_accomodation = $validatedData['budgeted_accommodation'] ?? 0.00;
            $vacancy->service_charge = $validatedData['service_charge'] ?? 0.00;
            $vacancy->propsed_salary = $validatedData['proposed_salary'] ?? 0.00;
            $vacancy->allowance = $validatedData['allowance'] ?? 0.00;
            $vacancy->uniform = $validatedData['uniform'] ?? 0.00;
            $vacancy->medical = $validatedData['medical'];
            $vacancy->insurance = $validatedData['insurance'];
            $vacancy->pension = $validatedData['pension'];
            $vacancy->recruitment = $recruitment;
            $vacancy->status = $validatedData['status'];
            $vacancy->amount_unit = $validatedData['amount_unit'];
            $vacancy->is_required_local = $request->is_required_local;
            $vacancy->save();

            $minWageMVR = 8021; // Minimum wage in MVR
            $minWageUSD = 520;
            $notify_person = Employee::where('resort_id', $this->resort->resort_id)->where('rank','3')->first();
           
            if($vacancy->propsed_salary < $minWageMVR && $vacancy->amount_unit == 'MVR' || $vacancy->propsed_salary < $minWageUSD && $vacancy->amount_unit == 'USD') {
                    event(new ResortNotificationEvent(Common::nofitication(
                        $this->resort->resort_id,
                        10,
                        'Talent Acquisition (Minimum Wage)',
                        "Vacancy {$vacancy->Getposition->position_title} has a proposed salary {$vacancy->propsed_salary} below the minimum wage.",
                        0,
                        $notify_person->id,
                        'Telent Acquisition (Minimum Wage)'
                    )));

                    Compliance::firstOrCreate([
                        'resort_id' => $this->resort->resort_id,
                        'employee_id' => $this->resort->GetEmployee->id,
                        'module_name' => 'Talent Acquisition',
                        'compliance_breached_name' => 'Minimum Wage',
                        'description' =>"Vacancy {$vacancy->Getposition->position_title} has a proposed salary {$vacancy->propsed_salary} below the minimum wage.",
                        'reported_on' => Carbon::now(),
                        'status' => 'Breached'
                    ]);

            }

            $position = ResortPosition::find($vacancy->position);
           
            if($position->is_reserved == 'Yes' && $vacancy->is_required_local == 'No') {
                    event(new ResortNotificationEvent(Common::nofitication(
                        $this->resort->resort_id,
                        10,
                        'Talent Acquisition (Reserved Position For Local Candidates)',
                        "Vacancy {$vacancy->Getposition->position_title} is a reserved position for local candidates.",
                        0,
                        $notify_person->id,
                        'Telent Acquisition (Reserved Position For Local Candidates)'
                    )));

                    event(new ResortNotificationEvent(Common::nofitication(
                        $this->resort->resort_id,
                        10,
                        'Talent Acquisition (Reserved Position For Local Candidates)',
                        "Vacancy {$vacancy->Getposition->position_title} is a reserved position for local candidates.",
                        0,
                        $this->resort->GetEmployee->id,
                        'Telent Acquisition (Reserved Position For Local Candidates)'
                    )));


                  $compli =  Compliance::Create([
                        'resort_id' => $this->resort->resort_id,
                        'employee_id' => $this->resort->GetEmployee->id,
                        'module_name' => 'Talent Acquisition',
                        'compliance_breached_name' => 'Reserved Position ',
                        'description' =>"Vacancy {$vacancy->Getposition->position_title} is a reserved position for local candidates.",
                        'reported_on' => Carbon::now(),
                        'status' => 'Breached'
                    ]);
            }

            // Save notifications
                $t1 = TAnotificationParent::create([
                    'Resort_id' => $resort_id,
                    'V_id' => $vacancy->id,
                ]);


                 $FainalKey = Common::TaFinalApproval($resort_id) ;
                $position_rank = config('settings.final_rank');


                $CycleOfRequest = array_filter($position_rank, function ($value, $key) use ($FainalKey) {
                    return $key < $FainalKey;
                }, ARRAY_FILTER_USE_BOTH);


                $newData = ["3" => "HR","8" => "GM"];
                $CycleOfRequest += $newData;

                foreach ($CycleOfRequest as $key => $value) {
                    TAnotificationChild::create([
                        'Parent_ta_id' => $t1->id,
                        'status' => 'Active',
                        'Approved_By' =>  $key,
                    ]);
                }


            DB::commit();

            $msg = 'HOD Created new hiring request';
            $title = ' Hiring Request';
            $ModuleName = "Talent Acquisition  ";
            $hr_id = Common::FindResortHR($this->resort)->id;
            // event(new ResortNotificationEvent(Common::nofitication($this->resort->resort_id, $this->type[6],$title,$msg,0,$hr_id,$ModuleName)));
         
            event(new ResortNotificationEvent(Common::nofitication(
                            $this->resort->resort_id, // Make sure resort_id exists on the meetings table
                            $this->type[6],
                            'Upcoming Investigation Meeting Reminder',
                            $msg,
                            0,
                            $hr_id,
                            $ModuleName
                        )));
            return response()->json([
                'success' => true,
                'msg' => 'Vacancy added successfully.',
            ]);
        DB::beginTransaction();
        try
        {
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error adding vacancy', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['success' => false, 'msg' => $e->getMessage()]);
        }
    }
    public function getRank(Request $request)
    {
        $positionId = $request->query('positionId');
        $position = ResortPosition::find($positionId);

        if ($position) {
            $rankId = $position->Rank; // Assume 'rank' field stores the rank ID
            $rankName = config("settings.Position_Rank.$rankId") ?? null;

            return response()->json(['rank' => $rankName,'rank_id'=> $rankId ]);
        } else {
            return response()->json(['rank' => null]);
        }
    }

    public function GetViewVacancies()
    {

        if(Common::checkRouteWisePermission('resort.vacancies.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        $page_title = 'View All Vacancies';
        return view("resorts.talentacquisition.vacancies.hrAllBacancies",compact('page_title'));
    }
    public function getAllVacancies()
    {

        $config = config('settings.Position_Rank');
        $rank = $this->resort->GetEmployee->rank;
        // dd($this->resort->GetEmployee);
        // if($rank == 2)
        // {
        //     $Vacancies = Vacancies::with(['Getdepartment','Getposition',
        //         'TAnotificationParent.TAnotificationChildren' => function ($query) {
        //             $query->where('status', '!=', ''); // Filter by valid status
        //         }
        //     ])
        //     ->whereHas('TAnotificationParent.TAnotificationChildren', function ($query) {
        //         $query->where('status', '!=', ''); // Ensure valid notifications exist
        //     })
        //     ->where('department', $this->resort->GetEmployee->Dept_id)
        //     ->where('resort_id', $this->resort->GetEmployee->resort_id)
        //     ->get();
        // }
        // else{
            $Vacancies = Common::GetTheFreshVacancies($this->resort->resort_id,"Active",$rank);
        // }

        return datatables()->of($Vacancies)
          ->addColumn('action', function ($row) {
              $editUrl = asset('resorts_assets/images/edit.svg');
              $deleteUrl = asset('resorts_assets/images/trash-red.svg');

              return '
                  <div class="d-flex align-items-center">
                      <a href="" class="btn-lg-icon icon-bg-green me-1 edit-row-btn"
                      data-dept-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                          <img src="' . $editUrl . '" alt="Edit" class="img-fluid" />
                      </a>
                      <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn"
                     data-Parent-id="'. htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '" data-dept-id="' . htmlspecialchars($row->child_id, ENT_QUOTES, 'UTF-8') . '">
                          <img src="' . $deleteUrl . '" alt="Delete" class="img-fluid" />
                      </a>
                  </div>';
          })
           ->addColumn('Budget', function ($row) {

                    if($row->Budget == "Budgeted") {
                        return '<span class="badge bg-info">' . htmlspecialchars($row->Budget, ENT_QUOTES, 'UTF-8') . '</span>';
                    } else{
                        return '<span class="badge bg-danger">' . htmlspecialchars($row->Budget, ENT_QUOTES, 'UTF-8') . '</span>';
                    }

                })

                ->addColumn('EmployeeType', function ($row) {

                        return '<span class="badge bg-info">' . htmlspecialchars($row->EmployeeType, ENT_QUOTES, 'UTF-8') . '</span>';

                })

                ->addColumn('rank_name', function ($row) {

                        return '<span class="badge bg-primary">' . htmlspecialchars($row->rank_name, ENT_QUOTES, 'UTF-8') . '</span>';
                })
          ->rawColumns(['Department', 'Position', 'EmployeeType','Required','Budget','ReportingTo','rank_name'])
          ->make(true);



    }

    public function ViewAllToDo(Request $request)
    {
        $config = config('settings.Position_Rank');
        $rank = $this->resort->GetEmployee->rank;

        $resort_id = $this->resort->resort_id;
        $Vacancies  = Vacancies::join('employees as t1','t1.id','=','vacancies.reporting_to')
        ->join('t_anotification_parents as t2','t2.V_id','=','vacancies.id')
        ->join('t_anotification_children as t3','t3.Parent_ta_id','=','t2.id')
        ->join('resort_departments as t4','t4.id','=','vacancies.department')
        ->join('resort_positions as t5','t5.id','=','vacancies.position')
        ->join('resort_admins as t6','t6.id','=','t1.Admin_Parent_id')
        ->join('job_advertisements as t7', 't7.Resort_id', '=', 'vacancies.Resort_id')
        ->leftjoin('application_links as t8', 't8.ta_child_id', '=', 't3.id')
        ->leftjoin('applicant_form_data as t9', 't9.Parent_v_id', '=', 'vacancies.id')
        ->where('vacancies.Resort_id',$resort_id)
        ->whereIn('t3.status',["Approved","ForwardedToNext"])
        ->where('t3.Approved_By', '=', $rank)
        ->whereNull('t9.id')
        ->where('vacancies.status', '=', "Active")
        ->latest('t3.created_at')->get([
                        't3.reason',
                        't1.rank',
                        'vacancies.id as V_id',
                        't3.id as ta_childid',
                        't5.position_title as Position',
                        't4.name as Department',
                        'vacancies.Resort_id',
                        't6.id as user_id',
                        't6.first_name',
                        't6.last_name',
                        'vacancies.required_starting_date',
                        'vacancies.budgeted as Budget',
                        'vacancies.employee_type as EmployeeType',
                        't7.Jobadvimg',
                        't8.link as adv_link',
                        't8.link_Expiry_date as ExpiryDate',
                        // DB::raw('SUM(t9.id) as t9_id_sum')
        ])
        ->map(function ($vacancy) use ($config,$resort_id)
        {
            $vacancy->rank_name = $config[$vacancy->rank] ?? 'Unknown Rank';


                $resort_id_decode =base64_encode($resort_id.'/'.$vacancy->ta_childid.'/'.$vacancy->V_id);
                $applicant_link = route('resort.applicantForm',$resort_id_decode);
                if(isset($vacancy->adv_link))
                {
                    $vacancy->applicant_link =$vacancy->adv_link;
                }
                else
                {
                    $vacancy->applicant_link = route('resort.applicantForm',$resort_id_decode);
                }

                $resort_id =  Auth::guard('resort-admin')->user()->resort->resort_id;
                $vacancy->Required = Carbon::createFromFormat('Y-m-d', $vacancy->required_starting_date)->format('Y-m-d');
                $vacancy->ReportingTo =  ucfirst($vacancy->first_name.'  ' .$vacancy->last_name);
                $vacancy->applicationUrlshow = substr($applicant_link, 0, 30).'...';
                $vacancy->JobAdvertisement= URL::asset(config('settings.Resort_JobAdvertisement').'/'.$resort_id."/".$vacancy->Jobadvimg);
                return $vacancy;
        });
        $page_title = 'View All To Do';
        if($request->ajax())
        {

            return datatables()->of($Vacancies)
            ->addColumn('action', function ($row) {
                $editUrl = asset('resorts_assets/images/edit.svg');
                $deleteUrl = asset('resorts_assets/images/trash-red.svg');

                return '
                    <div class="d-flex align-items-center">
                        <a href="" class="btn-lg-icon icon-bg-green me-1 edit-row-btn"
                        data-dept-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                            <img src="' . $editUrl . '" alt="Edit" class="img-fluid" />
                        </a>
                        <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn"
                       data-Parent-id="'. htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '" data-dept-id="' . htmlspecialchars($row->child_id, ENT_QUOTES, 'UTF-8') . '">
                            <img src="' . $deleteUrl . '" alt="Delete" class="img-fluid" />
                        </a>
                    </div>';
            })
             ->addColumn('Budget', function ($row) {

                      if($row->Budget == "Budgeted") {
                          return '<span class="badge bg-info">' . htmlspecialchars($row->Budget, ENT_QUOTES, 'UTF-8') . '</span>';
                      } else{
                          return '<span class="badge bg-danger">' . htmlspecialchars($row->Budget, ENT_QUOTES, 'UTF-8') . '</span>';
                      }
                      //
                  })

                  ->addColumn('EmployeeType', function ($row) {

                          return '<span class="badge bg-info">' . htmlspecialchars($row->EmployeeType, ENT_QUOTES, 'UTF-8') . '</span>';

                  })

                  ->addColumn('rank_name', function ($row) {

                          return '<span class="badge bg-primary">' . htmlspecialchars($row->rank_name, ENT_QUOTES, 'UTF-8') . '</span>';
                  })
                //   ->addColumn('Required', function ($row) {
                //     try {
                //         $formattedDate =
                //         return '<span>' . htmlspecialchars($formattedDate, ENT_QUOTES, 'UTF-8') . '</span>';
                //     } catch (\Exception $e) {
                //         return '<span class="text-danger">Invalid Date</span>';
                //     }
                // })
                ->rawColumns(['action', 'Budget', 'EmployeeType', 'rank_name', 'Required'])
            ->make(true);
        }

        return view("resorts.talentacquisition.vacancies.alltodoList",compact('page_title'));
    }

    public function GetAllApplicatioWiseVacancies(Request $request)
    {
        $page_title  = "Vacancies";
        $searchTerm = $request->get('searchTerm');
        $Department = $request->get('Department');
        $Postision = $request->get('Positions');
        $resort_id = $this->resort->resort_id;
        $view = $request->frontview;



            $NewVacancies = Vacancies::join("resort_departments as t1", "t1.id", "=", "vacancies.department")
            ->join("resort_positions as t2", "t2.id", "=", "vacancies.position")
            ->join("t_anotification_parents as t3", "t3.V_id", "=", "vacancies.id")
            ->join("t_anotification_children as t4", "t4.Parent_ta_id", "=", "t3.id")
            ->join("application_links as t5", "t5.ta_child_id", "=", "t4.id")
            ->leftjoin("applicant_form_data as t6", "t6.Parent_v_id", "=", "vacancies.id")
            ->where("vacancies.resort_id", $resort_id)
            ->where("t4.Approved_By", Common::TaFinalApproval($resort_id))
            ->where('t4.status','ForwardedToNext');
            if (!empty($searchTerm))
            {
                $Department ='';
                $Postision ='';
                $NewVacancies->where(function ($query) use ($searchTerm) {
                    $query->where("t1.name", "like", "%$searchTerm%")
                        ->orWhere("t1.code", "like", "%$searchTerm%")
                        ->orWhere("t2.position_title", "like", "%$searchTerm%")
                        ->orWhere("t2.code", "like", "%$searchTerm%")
                        ->orWhere("vacancies.id", $searchTerm);
                    // Check if the searchTerm is a date
                    try 
                    {
                        $date = Carbon::createFromFormat('d-m-Y', $searchTerm)->format('Y-m-d');
                        $query->orWhere("t6.Application_date", "like", "%$date%")->orWhere("t5.link_Expiry_date", "like", "%$date%");
                    } catch (\Exception $e) {

                    }
                });
            }
            if (!empty($Department)) {
                $NewVacancies->where("vacancies.Department", $Department);
            }

            if (!empty($Postision)) {
                $NewVacancies->where("vacancies.position", $Postision);
            }



            // Add selected fields and fetch results
            $NewVacancies = $NewVacancies->selectRaw("
            vacancies.id AS vacancy_id,
            vacancies.position,
            t2.position_title AS positionTitle,
            t2.id AS PositionID,
            t2.code AS PositionCode,
            t1.name AS Department,
            t1.code AS DepartmentCode,
            COUNT(t6.id) AS NoOfApplication,
            t5.link_Expiry_date,
            t6.Application_date,
            t5.id as application_id,
            vacancies.Total_position_required
            ")
            ->groupBy("vacancies.id", "t2.position_title", "t2.code", "t1.name", "t1.code", "t5.link_Expiry_date", "t6.Application_date", "t6.id", "vacancies.Total_position_required")
            ->get();
        $ResortDepartment = ResortDepartment::where('resort_id',$resort_id)->get();
        foreach($NewVacancies  as $v)
        {
            // $applicationdata =$v->TAnotificationParent[0]->TaNotificationChildren->where("Approved_By",Common::TaFinalApproval($resort_id))->first();
            // $ApplicationLink = ApplicationLink::where('ta_child_id',$applicationdata->id)->first();
            $v->positionTitle;
            $v->PositonCode;
            $v->Department;
            $v->DepartmentCode;
            $v->NoOfVacnacy = $v->Total_position_required; // No of positions
            $v->NoOfApplication;
            $v->ApplicationDate =  Carbon::parse($v->Application_date)->format('d-m-Y');
            $v->ExpiryDate = Carbon::parse($v->link_Expiry_date)->format('d-m-Y');
            $v->ApplicationId= $v->application_id;
        }

        $config = config('settings.Position_Rank');

        if($request->ajax())
        {
    

              return datatables()->of($NewVacancies)

              ->addColumn('action', function ($row) {
                  $editUrl = asset('resorts_assets/images/edit.svg');
                  $deleteUrl = asset('resorts_assets/images/trash-red.svg');

                $route = route("resort.ta.Applicants",    base64_encode($row->vacancy_id));
                    return'<div class="dropdown table-dropdown">
                                <button class="btn btn-secondary dropdown-toggle dots-link" type="button"
                                    id="dropdownMenuButton'.$row->vacancy_id.'" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis"></i>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton'.$row->vacancy_id.'">
                                    <li><a class="dropdown-item"  href="'.$route.'" data-id="'.$row->vacancy_id.'" >View Applicants</a></li>
                                    <li><a class="dropdown-item ExtendJobLink" data-ExpiryDate="'.$row->ExpiryDate.'" data-ApplicationId="'.$row->ApplicationId.'"   href="javascript:void(0)">Extend The Job Ad Link</a></li>
                                </ul>
                            </div>';

                        })
                    ->addColumn('Position', function ($row) {
                        return $row->positionTitle.' '.'<span class="badge badge-themeLight">' . htmlspecialchars($row->PositonCode  , ENT_QUOTES, 'UTF-8') . '</span>';
                    })

                    ->addColumn('Department', function ($row)
                    {
                        return $row->Department.' '.'<span class="badge badge-themeLight">' . htmlspecialchars($row->DepartmentCode  , ENT_QUOTES, 'UTF-8') . '</span>';
                    })


                    ->rawColumns(['Position', 'Department','NoOfVacnacy','NoOfApplication','ApplicationDate','ExpiryDate','action'])
                    ->make(true);

                    
        }
        return view("resorts.talentacquisition.vacancies.allApplicationWiseVacancies",compact('page_title','ResortDepartment'));
    }

    public function GridViewData(Request $request)
    {

        $searchTerm = $request->get('searchTerm');
        $Department = $request->get('Department');
        $Postision = $request->get('Positions');
        $resort_id = $this->resort->resort_id;
        $view = $request->frontview;
        //      $NewVacancies = Vacancies::join("resort_departments as t1", "t1.id", "=", "vacancies.department")
        //     ->join("resort_positions as t2", "t2.id", "=", "vacancies.position")
        //     ->join("t_anotification_parents as t3", "t3.V_id", "=", "vacancies.id")
        //     ->join("t_anotification_children as t4", "t4.Parent_ta_id", "=", "t3.id")
        //     ->join("application_links as t5", "t5.ta_child_id", "=", "t4.id")
        //     ->join("applicant_form_data as t6", "t6.Parent_v_id", "=", "vacancies.id")
        //     ->join('job_advertisements as t7', 't7.Resort_id', '=', 'vacancies.Resort_id')
        //     ->where("vacancies.resort_id", $resort_id)
        //     ->where("t4.Approved_By", Common::TaFinalApproval($resort_id));

        // // Apply search filters
        // if (!empty($searchTerm)) {
        //     $NewVacancies->where(function ($query) use ($searchTerm) {
        //         $query->where("t1.name", "like", "%$searchTerm%")
        //             ->orWhere("t1.code", "like", "%$searchTerm%")
        //             ->orWhere("t2.position_title", "like", "%$searchTerm%")
        //             ->orWhere("t2.code", "like", "%$searchTerm%")
        //             ->orWhere("vacancies.id", $searchTerm);

        //         // Check if the searchTerm is a date
        //         try {
        //             $date = Carbon::createFromFormat('d-m-Y', $searchTerm)->format('Y-m-d');
        //             $query->orWhere("t6.Application_date", "like", "%$date%")
        //                 ->orWhere("t5.link_Expiry_date", "like", "%$date%");
        //         } catch (\Exception $e) {
        //             // Ignore invalid date format
        //         }
        //     });
        // }

        // // Filter by department and position
        // if (!empty($Department)) {
        //     $NewVacancies->where("vacancies.Department", $Department);
        // }

        // if (!empty($Postision)) {
        //     $NewVacancies->where("vacancies.position", $Postision);
        // }
        //     $NewVacancies = $NewVacancies->get();

        $NewVacancies = Vacancies::join("resort_departments as t1", "t1.id", "=", "vacancies.department")
            ->join("resort_positions as t2", "t2.id", "=", "vacancies.position")
            ->join("t_anotification_parents as t3", "t3.V_id", "=", "vacancies.id")
            ->join("t_anotification_children as t4", "t4.Parent_ta_id", "=", "t3.id")
            ->join("application_links as t5", "t5.ta_child_id", "=", "t4.id")
            ->leftjoin("applicant_form_data as t6", "t6.Parent_v_id", "=", "vacancies.id")
            ->join('job_advertisements as t7', 't7.Resort_id', '=', 'vacancies.Resort_id')
            ->where("vacancies.resort_id", $resort_id)
            ->where("t4.Approved_By", Common::TaFinalApproval($resort_id))
            ->where('t4.status','ForwardedToNext');
            if (!empty($searchTerm))
            {
                $Department ='';
                $Postision ='';
                $NewVacancies->where(function ($query) use ($searchTerm) {
                    $query->where("t1.name", "like", "%$searchTerm%")
                        ->orWhere("t1.code", "like", "%$searchTerm%")
                        ->orWhere("t2.position_title", "like", "%$searchTerm%")
                        ->orWhere("t2.code", "like", "%$searchTerm%")
                        ->orWhere("vacancies.id", $searchTerm);
                    // Check if the searchTerm is a date
                    try 
                    {
                        $date = Carbon::createFromFormat('d-m-Y', $searchTerm)->format('Y-m-d');
                        $query->orWhere("t6.Application_date", "like", "%$date%")->orWhere("t5.link_Expiry_date", "like", "%$date%");
                    } catch (\Exception $e) {

                    }
                });
            }
            if (!empty($Department)) {
                $NewVacancies->where("vacancies.Department", $Department);
            }

            if (!empty($Postision)) {
                $NewVacancies->where("vacancies.position", $Postision);
            }



            // Add selected fields and fetch results
          
        $NewVacancies = $NewVacancies->selectRaw("
                            vacancies.id AS vacancy_id,
                            vacancies.position,
                            t2.position_title AS positionTitle,
                            t2.id AS PositionID,
                            t2.code AS PositionCode,
                            t1.name AS Department,
                            t1.code AS DepartmentCode,
                            COUNT(t6.id) AS NoOfApplication,
                            t5.link_Expiry_date,
                            t6.Application_date,
                            t5.id as application_id,
                            vacancies.Total_position_required as NoOfVacnacy,
                            t7.Jobadvimg
                        ")
                        ->groupBy(
                                    "vacancies.id",
                                    "t2.position_title",
                                    "t2.code",
                                    "t1.name",
                                    "t1.code",
                                    "t5.link_Expiry_date",
                                    "t6.Application_date",
                                    "t6.id",
                                    "vacancies.Total_position_required",
                                    "t7.Jobadvimg"
                                )->paginate(10);


                    $NewVacancies->getCollection()->transform(function ($vacancy) {
                        $vacancy->image = URL::asset(
                            config('settings.Resort_JobAdvertisement') . '/' .
                            Auth::guard('resort-admin')->user()->resort->resort_id . '/' .
                            $vacancy->Jobadvimg
                        );
                        return $vacancy;
                    });
            $view = view('resorts.renderfiles.VacanciesGridView',compact('NewVacancies'))->render();
                $pagination =  $NewVacancies->links()->render();

            return response()->json(['view' => $view,  'pagination'=>$pagination,   'success' => true]);


    }

    public function getVacancyStatus(Request $request)
    {
        $positionId = $request->position_id;
        $requestedVacancy = $request->requested_vacancy;
        $currentMonth = Carbon::now()->month;
        // $currentYear = Carbon::now()->year;
        $currentYear = 2025;
        // dd($currentYear);
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;;

        $manningresponse = ManningResponse::where('resort_id',$resort_id)->where('year',$currentYear)->get();
        // dd( $manningresponse);
        // Fetch budgeted vacant count for the position
        $vacantCount = PositionMonthlyData::where('position_id', $positionId)
            ->where('month', $currentMonth)->where('manning_response_id',$manningresponse[0]->id)
            ->value('vacantcount');

        $result = StoreManningResponseParent::where('Budget_id',$manningresponse[0]->id)->get();
        $manning_data = StoreManningResponseChild::where('Parent_SMRP_id',$result[0]->id)->get();
        // dd($manning_data);
        // Determine if the requested vacancy is within budget
        $status = $requestedVacancy <= $vacantCount ? 'Budgeted' : 'Out of Budget';
        $budgeted_salary = $manning_data[0]->Current_Basic_salary;
        $proposed_salary = $manning_data[0]->Proposed_Basic_salary;

        $searchPatterns = [
            'pension' => '%pension%',
            'allowance' => '%allowance%',
            'medical' => 'medical%',
            'accommodation' => ['%accomodation%', '%accommodation%'], // Handle both spellings
            'insurance' => '%insurance%'
        ];

        $costs = [];

        foreach ($searchPatterns as $key => $pattern) {
            $query = ResortBudgetCost::where('resort_id', $resort_id);

            if (is_array($pattern)) {
                // Handle multiple patterns
                foreach ($pattern as $subPattern) {
                    $query->orWhere('particulars', 'LIKE', $subPattern);
                }
            } else {
                $query->where('particulars', 'LIKE', $pattern);
            }

            $costData = $query->first();
            if ($costData) {
                $amount = $costData->amount;
                $unit = $costData->amount_unit;

                // Calculate final cost based on unit
                $costs[$key] = ($unit === '%') ? ($amount / 100) * $budgeted_salary : $amount;
            } else {
                $costs[$key] = 0; // Default to 0 if no data found
            }
        }

        return response()->json([
            'status' => $status,
            'vacantCount' => $vacantCount,
            'budgeted_salary' =>$budgeted_salary,
            'proposed_salary'=>$proposed_salary,
            'accommodation' => $costs['accommodation'],
            'allowance' => $costs['allowance'],
            'medical' => $costs['medical'],
            'insurance' => $costs['insurance'],
            'pension' => $costs['pension'],
        ]);
    }
    public function shortlisted(Request $request,$id)
    {
     
        if(Common::checkRouteWisePermission('resort.ta.shortlistedapplicants',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }

        $resort_id = $this->resort->resort_id;
            if($request->ajax())
            {

                $searchTerm =  $request->searchTerm;
                $Department = $request->Department;
                $config = config('settings.Position_Rank');
                $rank = $this->resort->GetEmployee->rank;


                $SorlistedApplicants = Vacancies::join("applicant_form_data as t1", "t1.Parent_v_id", "=", "vacancies.id")
                ->join("countries as t2", "t2.id", "=", "t1.country")
                ->join('applicant_wise_statuses as t4', function ($join) {
                    $join->on('t4.Applicant_id', '=', 't1.id')
                        ->whereRaw('t4.id = (
                            SELECT MAX(id)
                            FROM applicant_wise_statuses
                            WHERE Applicant_id = t1.id
                        )')
                        ->where('t4.status', '=', 'Sortlisted')
                        ->where('t4.As_ApprovedBy', '=', 3);
                })
                ->leftjoin('applicant_inter_view_details as t3', function ($join) {
                    $join->on('t3.Applicant_id', '=', 't1.id')
                        ->whereRaw('t3.id = (
                            SELECT MAX(id)
                            FROM applicant_inter_view_details
                            WHERE Applicant_id = t1.id
                        )');
                        // ->where('t3.MeetingLink','=',"");

                })
                ->leftjoin("resort_positions as t5", "t5.id", "=", "vacancies.position")
                ->leftjoin("resort_departments as t6", "t6.id", "=", "t5.dept_id")
                ->where('vacancies.Resort_id', $resort_id)
                ->where('vacancies.id', base64_decode($id));
                if ($searchTerm) {
                    $SorlistedApplicants->where(function ($query) use ($searchTerm) {
                        $query->where('t1.first_name', 'LIKE', "%$searchTerm%")
                            ->orWhere('t1.last_name', 'LIKE', "%$searchTerm%")
                            ->orWhere('t1.email', 'LIKE', "%$searchTerm%")
                            ->orWhere('t2.name', 'LIKE', "%$searchTerm%")
                            ->orWhere('t5.position_title', 'LIKE', "%$searchTerm%");
                    });
                }
                if ($Department) {
                    $SorlistedApplicants->where('t6.id', $Department);

                }
                $SorlistedApplicants = $SorlistedApplicants->selectRaw('
                            t1.id as Applicant_id,
                            t1.Application_date,
                            t1.passport_photo,
                            t1.first_name,
                            t1.last_name,
                            t1.gender,
                            t1.mobile_number as Contact,
                            t1.email as Email,
                            t1.created_at,
                            t2.name AS Nation,
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
                            t6.name as Department
                    ')

            ->get();
                    $SorlistedApplicants->map(function ($item) use($config) {
                        $item->AppliedDate = Carbon::parse($item->Application_date)->format('d-m-Y');
                        $item->Rank =$item->As_ApprovedBy;
                        $item->AppliedDate = Carbon::parse($item->Application_date)->format('d-m-Y');
                        $item->Score = 90 ;
                        $item->InterViewDate = $item->InterViewDate ? Carbon::parse($item->InterViewDate)->format('d-m-Y') : '-';
                        $item->MalidivanTime = $item->ResortInterviewtime ?? '-';
                        $item->ApplicantTime = $item->ApplicantInterviewtime ?? '-';
                        $item->InterviewStatus = $item->InterviewStatus ?? 'Slot Not Booked';
                        return $item;
                    });

                    return datatables()->of($SorlistedApplicants)
                    ->addColumn('Applicants', function ($row){
                        $userName = htmlspecialchars(ucfirst($row->first_name . ' ' . $row->last_name), ENT_QUOTES, 'UTF-8');
                        $photo = URL::asset($row->passport_photo);
                        $string = '<div class="tableUser-block">
                            <div class="img-circle"><img src="'.$photo.'" alt="user"></div>
                            <span class="userApplicants-btn" data-id="' . base64_encode($row->Applicant_id) . '">' . $userName . '</span>
                        </div>';
                    return $string;
                    })
                    ->addColumn('Stage', function ($row){
                        $userName = htmlspecialchars(ucfirst($row->first_name . ' ' . $row->last_name), ENT_QUOTES, 'UTF-8');

                        $string = ' <span class="badge badge-themeBlue">HR Sortlisted</span>';
                    return $string;
                    })



                    ->addColumn('Action', function ($row) {
                        $editUrl = asset('resorts_assets/images/edit.svg');
                        $deleteUrl = asset('resorts_assets/images/trash-red.svg');
                        $dropdownId = htmlspecialchars($row->Applicant_id, ENT_QUOTES, 'UTF-8');
                        $notes = htmlspecialchars($row->Notes ?? '', ENT_QUOTES, 'UTF-8'); // Handle null `Notes`
                        $applicantid = htmlspecialchars(base64_encode($row->Applicant_id), ENT_QUOTES, 'UTF-8');
                        $sendInterviewRequest = '';

                        if ($row->InterviewStatus != "Slot Booked") {
                            $sendInterviewRequest = '<li><a class="dropdown-item userApplicants-btn SortlistedEmployee"
                            data-resort_id="' . $this->resort->resort_id . '"
                            data-applicantstatus_id="'. $row->ApplicantStatus_id.'"
                            data-applicantid="' . base64_encode($row->Applicant_id) . '" href="#">Send Interview Request</a></li>';
                        }

                        return '
                            <div class="dropdown table-dropdown">
                                <button class="btn btn-secondary dropdown-toggle dots-link" type="button" id="dropdownMenuButton' . $dropdownId . '" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis"></i>
                                </button>
                                <ul class="dropdown-menu " aria-labelledby="dropdownMenuButton' . $dropdownId . '">
                                    ' . $sendInterviewRequest . '
                                    <li>
                                        <a class="dropdown-item ApplicantShareLink"  data-Interview_id="' . base64_encode($row->Interview_id) . '" href="javascript:void(0)">Add Interview Link</a>
                                    </li>
                                </ul>
                            </div>';

                        return $string;
                    })

                        ->rawColumns(['Applicants','Action', 'Stage', 'rank_name', 'Required'])
                    ->make(true);
            }

        $page_title = "Shortlisted Applicants";
        $department_details = ResortDepartment::where('resort_id',$resort_id)->get();

    //   return view("resorts.talentacquisition.vacancies.shortlisted",compact('page_title','id','department_details'));
      return view("resorts.talentacquisition.vacancies.shortlisted",compact('page_title','id','department_details'));

    }

    public function shortlistedapplicantsShareLink(Request $request)
    {

        
        $config = config('settings.Position_Rank');
        $rank = $this->resort->GetEmployee->rank ?? '';

        $resort_id = $this->resort->resort_id;
        $SorlistedApplicants = Vacancies::join("applicant_form_data as t1", "t1.Parent_v_id", "=", "vacancies.id")
        ->join("countries as t2", "t2.id", "=", "t1.country")
        ->join('applicant_wise_statuses as t4', function ($join)use($rank) {
            $join->on('t4.Applicant_id', '=', 't1.id')
                ->whereRaw('t4.id = (
                    SELECT MAX(id)
                    FROM applicant_wise_statuses
                    WHERE Applicant_id = t1.id
                )');
                if($rank == 3)
                {
                    $join->whereIn('t4.status', ['Sortlisted']);
                }
                elseif($rank == 2)
                {
                    $join->where('t4.status', '=', 'Round');
                }
                $join->where('t4.As_ApprovedBy', '=', $rank);
        })
        ->leftjoin('applicant_inter_view_details as t3', function ($join) {
            $join->on('t3.Applicant_id', '=', 't1.id')
                ->whereRaw('t3.id = (
                    SELECT MAX(id)
                    FROM applicant_inter_view_details
                    WHERE Applicant_id = t1.id
                )');
                // ->where('t3.MeetingLink','=',"");

        })
        ->leftjoin("resort_positions as t5", "t5.id", "=", "vacancies.position")
        ->where('vacancies.Resort_id', $resort_id)
        ->selectRaw('
            t1.id as Applicant_id,
            t1.Application_date,
            t1.passport_photo,
            t1.first_name,
            t1.last_name,
            t1.gender,
            t1.created_at,
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
            t3.id as Interview_id
        ')
        ->get()
        ->map(function ($item) {
            $item->AppliedDate = Carbon::parse($item->Application_date)->format('d-m-Y');

            $item->InterViewDate = $item->InterViewDate ? Carbon::parse($item->InterViewDate)->format('d-m-Y') : '-';

            $item->MalidivanTime = $item->ResortInterviewtime ?? '-';

            $item->ApplicantTime = $item->ApplicantInterviewtime ?? '-';

            $item->InterviewStatus = $item->InterviewStatus ?? 'Slot Not Booked';

            return $item;
            });
            if($request->ajax())
            {


                    return datatables()->of($SorlistedApplicants)
                    ->addColumn('Applicants', function ($row){
                        $userName = htmlspecialchars(ucfirst($row->first_name . ' ' . $row->last_name), ENT_QUOTES, 'UTF-8');
                            if($row->passport_photo)
                            {
                            $getFileapplicant = Common::GetApplicantAWSFile($row->passport_photo);

                            $getFileapplicant =  $getFileapplicant['NewURLshow'];
                            }
                            else
                            {
                                $getFileapplicant = null;
                            }
                        $string = '<div class="tableUser-block">
                            <div class="img-circle"><img src="'.$getFileapplicant.'" alt="user"></div>
                            <span class="userApplicants-btn" data-id="' . base64_encode($row->Applicant_id) . '">' . $userName . '</span>
                        </div>';
                    return $string;
                    })
                    ->addColumn('Stage', function ($row){
                        $userName = htmlspecialchars(ucfirst($row->first_name . ' ' . $row->last_name), ENT_QUOTES, 'UTF-8');

                        $string = ' <span class="badge badge-themeBlue">HR Sortlisted</span>';
                    return $string;
                    })



                    ->addColumn('Action', function ($row) {
                        $editUrl = asset('resorts_assets/images/edit.svg');
                        $deleteUrl = asset('resorts_assets/images/trash-red.svg');
                        $dropdownId = htmlspecialchars($row->Applicant_id, ENT_QUOTES, 'UTF-8');
                        $notes = htmlspecialchars($row->Notes ?? '', ENT_QUOTES, 'UTF-8'); // Handle null `Notes`
                        $applicantid = htmlspecialchars(base64_encode($row->Applicant_id), ENT_QUOTES, 'UTF-8');
                        $ApplicantStatus_id = htmlspecialchars(base64_encode($row->ApplicantStatus_id), ENT_QUOTES, 'UTF-8');
                        $sendInterviewRequest = '';

                        if ($row->InterviewStatus == "Slot Booked") 
                        {
                            $sendInterviewRequest = '<li><a class="dropdown-item userApplicants-btn " target="_blank" href="'.$row->MeetingLink.'">Start Interview</a></li>';
                        }
                        else
                        {
                            $sendInterviewRequest = '<li><a class="dropdown-item userApplicants-btn" href="javascript:void(0)">No Slot Found</a></li>';
                        }

                        return '
                            <div class="dropdown table-dropdown">
                                <button class="btn btn-secondary dropdown-toggle dots-link" type="button" id="dropdownMenuButton' . $dropdownId . '" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis"></i>
                                </button>
                                <ul class="dropdown-menu " aria-labelledby="dropdownMenuButton' . $ApplicantStatus_id . '">
                                    ' . $sendInterviewRequest . '
                                    <li><a class="dropdown-item userApplicants-btn"  href="javascript:void(0)" data-id="'.$ApplicantStatus_id.'">View</a></li> 
                                </ul>
                            </div>';

                        return $string;
                    })

                        ->rawColumns(['Applicants','Action', 'Stage', 'rank_name', 'Required'])
                    ->make(true);
            }
       $EmailTamplete = TaEmailTemplate::where('Resort_id',$this->resort->resort_id)->orderByDesc("id")->get();
        $page_title = "Shortlisted Applicants To Share Link";
      return view("resorts.talentacquisition.vacancies.SortlistedapplicantLinkShare",compact('page_title','EmailTamplete'));
    }

    public function UpcomingApplicants(Request $request)
    {
        if($request->ajax())
        {

                $config = config('settings.Position_Rank');
                $rank = $this->resort->GetEmployee->rank;

                $resort_id = $this->resort->resort_id;
                        $UplcomingApplicants = Vacancies::join("applicant_form_data as t1", "t1.Parent_v_id", "=", "vacancies.id")
                            ->join("countries as t2", "t2.id", "=", "t1.country")
                            ->join('applicant_wise_statuses as t4', function ($join) {
                                $join->on('t4.Applicant_id', '=', 't1.id')
                                    ->whereRaw('t4.id = (
                                        SELECT MAX(id)
                                        FROM applicant_wise_statuses
                                        WHERE Applicant_id = t1.id
                                    )')
                                    ->whereIn('t4.status', ['Sortlisted','Complete','Round']);
                                    // ->where('t4.As_ApprovedBy', '=', 3);
                            })
                            ->join('applicant_inter_view_details as t3', function ($join) {
                                $join->on('t3.Applicant_id', '=', 't1.id')
                                    ->whereRaw('t3.id = (
                                        SELECT MAX(id)
                                        FROM applicant_inter_view_details
                                        WHERE Applicant_id = t1.id
                                    )');
                            })

                            ->join("resort_positions as t5", "t5.id", "=", "vacancies.position")
                            ->where('t3.MeetingLink','!=',null)
                            ->whereBetween('t3.InterViewDate', [Carbon::today(), Carbon::today()->addDays(7)]) // Upcoming 7 days
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
                                t3.id as Interview_id
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
                                });

                return datatables()->of($UplcomingApplicants)
                ->addColumn('Applicants', function ($row){
                    $userName = htmlspecialchars(ucfirst($row->first_name . ' ' . $row->last_name), ENT_QUOTES, 'UTF-8');
                    $photo = URL::asset($row->passport_photo);
                    $string = '<div class="tableUser-block">
                        <div class="img-circle"><img src="'.$photo.'" alt="user"></div>
                        <span class="userApplicants-btn" data-id="' . base64_encode($row->Applicant_id) . '">' . $userName . '</span>
                    </div>';
                return $string;
                })
                ->addColumn('Stage', function ($row) {
                    // Define badge themes for each applicant status
                    $badgeThemes = [
                        'Sortlisted' => 'badge-themeBlue',
                        'Round' => [
                            2 => 'badge-themePurple', // GM Round
                            3 => 'badge-themeBlue',   // HR Round
                            8 => 'badge-themePink',   // Final Round
                        ],
                        'Selected' => 'badge-themeSuccess',
                        'Rejected' => 'badge-themeDanger',
                        'Complete' => 'badge-themeGreen',
                        'Pending' => 'badge-themeOrange',
                    ];

                    // Determine the badge theme based on status and approved level
                    if (isset($badgeThemes[$row->ApplicationStatus])) {
                        if (is_array($badgeThemes[$row->ApplicationStatus])) {
                            // Fetch theme based on approved level for 'Round'
                            $theme = $badgeThemes[$row->ApplicationStatus][$row->ApprovedBy] ?? 'badge-themeDefault';
                        } else {
                            // Fetch static theme for other statuses
                            $theme = $badgeThemes[$row->ApplicationStatus];
                        }
                    } else {
                        // Default badge theme
                        $theme = 'badge-themeDefault';
                    }

                    // Generate the badge HTML
                    $badgeText = $row->rank_name . ' ' . ucfirst($row->ApplicationStatus);
                    $string = "<span class='badge $theme'>$badgeText</span>";

                    return $string;
                })



                ->addColumn('Link', function ($row) {

                        $string = '<a target="_blank" href="' . $row->MeetingLink . '"> <span class="badge badge-success">Link</span></a>';

                    return $string;
                })

                    ->rawColumns(['Applicants','Link', 'Stage', 'rank_name', 'Required'])
                ->make(true);
        }
        $page_title = "Upcoming Interview";
        return view("resorts.talentacquisition.vacancies.UpcomingInterview",compact('page_title'));
    }


    public function RejactedApplicants(Request $request)
    {

        if($request->ajax())
        {
                $config = config('settings.Position_Rank');
                $rank = $this->resort->GetEmployee->rank;

                $resort_id = $this->resort->resort_id;
                        $UplcomingApplicants = Vacancies::join("applicant_form_data as t1", "t1.Parent_v_id", "=", "vacancies.id")
                            ->join("countries as t2", "t2.id", "=", "t1.country")
                            ->join('applicant_wise_statuses as t4', function ($join) {
                                $join->on('t4.Applicant_id', '=', 't1.id')
                                    ->whereRaw('t4.id = (
                                        SELECT MAX(id)
                                        FROM applicant_wise_statuses
                                        WHERE Applicant_id = t1.id
                                    )')
                                    ->where('t4.status', '=', 'Sortlisted')
                                    ->where('t4.As_ApprovedBy', '=', 3);
                            })
                            ->join('applicant_inter_view_details as t3', function ($join) {
                                $join->on('t3.Applicant_id', '=', 't1.id')
                                    ->whereRaw('t3.id = (
                                        SELECT MAX(id)
                                        FROM applicant_inter_view_details
                                        WHERE Applicant_id = t1.id
                                    )');
                            })

                            ->join("resort_positions as t5", "t5.id", "=", "vacancies.position")
                            ->where('t3.MeetingLink','!=',null)
                            ->whereBetween('t3.InterViewDate', [Carbon::today(), Carbon::today()->addDays(7)]) // Upcoming 7 days
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
                                t3.id as Interview_id
                            ')
                            ->get()
                            ->map(function ($item) {
                                $item->AppliedDate = Carbon::parse($item->Application_date)->format('d-m-Y');

                                $item->InterViewDate = $item->InterViewDate ? Carbon::parse($item->InterViewDate)->format('d-m-Y') : '-';

                                $item->MalidivanTime = $item->ResortInterviewtime ?? '-';

                                $item->ApplicantTime = $item->ApplicantInterviewtime ?? '-';

                                $item->InterviewStatus = $item->InterviewStatus ?? 'Slot Not Booked';

                                return $item;
                                });

                return datatables()->of($UplcomingApplicants)
                ->addColumn('Applicants', function ($row){
                    $userName = htmlspecialchars(ucfirst($row->first_name . ' ' . $row->last_name), ENT_QUOTES, 'UTF-8');
                    $photo = URL::asset($row->passport_photo);
                    $string = '<div class="tableUser-block">
                        <div class="img-circle"><img src="'.  $photo.'" alt="user"></div>
                        <span class="userApplicants-btn" data-id="' . base64_encode($row->Applicant_id) . '">' . $userName . '</span>
                    </div>';
                return $string;
                })
                ->addColumn('Stage', function ($row){
                    $userName = htmlspecialchars(ucfirst($row->first_name . ' ' . $row->last_name), ENT_QUOTES, 'UTF-8');

                    $string = ' <span class="badge badge-themeBlue">HR Sortlisted</span>';
                return $string;
                })



                ->addColumn('Link', function ($row) 
                {

                    $string = '<a target="_blank" href="' . $row->MeetingLink . '"> <span class="badge badge-success">Link</span></a>';
                    return $string;
                })
                ->rawColumns(['Applicants','Link', 'Stage', 'rank_name', 'Required'])
                ->make(true);
        }
        $page_title = "Rejected Applicants";
        return view("resorts.talentacquisition.vacancies.UpcomingInterview",compact('page_title'));
    }

    public function AddInterViewLink(Request $request)
    {
       
            DB::beginTransaction();
            $resort_id =$this->resort->resort_id;
            $Interview_id = base64_decode($request->Interview_id);
            $round = $request->Round;
            $Interview_type = $request->InterviewType;

            $ApplicantInterViewDetails =ApplicantInterViewDetails::updateOrCreate(['id' => $Interview_id],['MeetingLink' => $request->MeetingLink]);
            DB::commit();

            $resort_details = Resort::find($resort_id); // Use find() instead of where()->get() for a single result.
            // dd($resort_details);
            if (!$resort_details) {
                return response()->json(['error' => 'Resort not found'], 404);
            }
            // End mail code

            $Final_response_data = Vacancies::join("applicant_form_data as t1", "t1.Parent_v_id", "=", "vacancies.id")
            ->join("countries as t2", "t2.id", "=", "t1.country")
            ->join('applicant_wise_statuses as t4', function ($join) 
            {
                $join->on('t4.Applicant_id', '=', 't1.id');
            })

            ->Join('applicant_inter_view_details as t3', function ($join) {
                $join->on('t3.Applicant_id', '=', 't1.id')
                    ->whereRaw('t3.id = (
                        SELECT MAX(id)
                        FROM applicant_inter_view_details
                        WHERE Applicant_id = t1.id
                    )');
            })
            ->join("resort_positions as t5", "t5.id", "=", "vacancies.position")
            ->join("resort_departments as t6", "t6.id", "=", "t5.dept_id")
            ->where('t1.id',$ApplicantInterViewDetails->Applicant_id)
            ->where('t4.id',$ApplicantInterViewDetails->ApplicantStatus_id)
            ->where('vacancies.status', '=', "Active")
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
                t6.name as Department
            ')
            ->first();
            $InterViewDate = Carbon::parse($Final_response_data->InterViewDate)->format('Y-m-d');
            $FianlResponse ='';
            if($Final_response_data)
            {
                $FianlResponse ='<tr>
                <th>Name:</th>
                <td>'.ucfirst($Final_response_data->first_name.' '.$Final_response_data->last_name).'</td>
            </tr>
            <tr>
                <th>Position:</th>
                <td>'.$Final_response_data->Position.'</td>
            </tr>
            <tr>
                <th>Department:</th>
                <td>'.$Final_response_data->Department.'</td>
            </tr>
            <tr>
                <th>Interview Date:</th>
                <td>'.$InterViewDate.'</td>
            </tr>
            <tr>
                <th>Malidivan Time:</th>
                <td>'.$Final_response_data->ResortInterviewtime.'</td>
            </tr>
             <tr>
                <th>Applicant Time:</th>
                <td>'.$Final_response_data->ApplicantInterviewtime.'</td>
            </tr><tr>
                <th>Interview Link:</th>
                <td>'.$Final_response_data->MeetingLink.'</td>
            </tr>';
            }
           
            $dynamic_data = [
                'candidate_name' => $Final_response_data->first_name . ' ' . $Final_response_data->last_name,
                'position_title' => $Final_response_data->Position,
                'department' => $Final_response_data->Department,
                'resort_name' => $resort_details->resort_name,
                'interview_date' => Carbon::parse($InterViewDate)->format('d-m-Y'),
                'interview_time' => $Final_response_data->ApplicantInterviewtime,
                'interview_link' => $Final_response_data->MeetingLink,  // Can be added based on your logic
                'interview_type' =>  $Interview_type,
                'interview_round'=> $round,
            ];
            // dd($Final_response_data);
            $recipientEmail = $Final_response_data->Email;
            $templateId = $request->EmailTemplate;

            $result = Common::sendTemplateEmail("TalentAcquisition",$templateId, $recipientEmail, $dynamic_data);
            if ($result === true) {
                return response()->json(['success' =>true,'Final_response_data'=>$FianlResponse, 'message' => 'Meeting Link  Added succefully'], 200);

            } else {
                return response()->json(['error' => $result], 500);
            }
            
         try
        {}
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Upload  data'], 500);
        }
    }



}
