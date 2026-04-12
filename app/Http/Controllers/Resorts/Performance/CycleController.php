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
    function index()
    {

        if(Common::checkRouteWisePermission('Performance.cycle',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }

        $page_title = " Cycle";

        $PerformanceCycle   = PerformanceCycle::where('resort_id',$this->resort->resort_id)->get()
        ->map(function($p){
            $ChildCycle = PerformaChildCycle ::where('Parent_cycle_id',$p->id)->get();

            $p->child_count = $ChildCycle->count();
            $p->ManagerReview = $ChildCycle->whereNotNull('Manager_review_date',null)->count();
            $p->SelfReview = $ChildCycle->whereNotNull('Self_review_date',null)->count();
            return $p;
        });
        return view('resorts.Performance.Cycle.index',compact('page_title','PerformanceCycle'));
    }
    function create()
    {
        if(Common::checkRouteWisePermission('Performance.cycle',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
        }
        $main_resort_id  = $this->resort->resort->resort_id;

        $page_title = "Create Cycle";
        $ResortDepartment = ResortDepartment::where('resort_id',$this->resort->resort_id)->get();
        $Location =  Employee::where('resort_id',$this->resort->resort_id)->get()->pluck('nationality')->unique();
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
        $joining_date       =    $request->joining_date;
        $tenure_duration    =   (int)$request->tenure_duration;
        $CheckedAll         = $request->CheckedAll;
        
        $employees = Employee::join('resort_admins as t3', 't3.id', '=', 'employees.Admin_Parent_id')
                                ->join('resort_departments as t1', 't1.id', '=', 'employees.Dept_id')
                                ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
                                ->where('employees.resort_id', $this->resort->resort_id)
                                ->where('employees.status', 'Active');
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
                                    $employees->where('employees.employment_type', $emp_status);
                                }
                                if(!empty($gender))
                                {
                                    $employees->where('t3.gender', strtolower($gender));
                                }
                                if(!empty($joining_date))
                                {
                                    $date = Carbon::parse($joining_date)->format('Y-m-d');
                                    $employees->whereDate('employees.joining_date', $date);
                                }
                                if(!empty($Location))
                                {
                                    $employees->where('employees.nationality', $Location);
                                }

                                if (isset($tenure_duration) && $tenure_duration !=0)
                                {

                                    $employees->whereRaw('TIMESTAMPDIFF(YEAR, employees.joining_date, CURDATE()) >= ?', [$tenure_duration]);
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
            $templates = $templateQuery->get(['id', 'FormName'])->map(function($t) {
                $t->FormName = $t->FormName . ' (Template)';
                return $t;
            });
            $allForms = $allForms->merge($templates);

            // 2. 90 Day Appraisal Forms
            $nintyDayForms = NintyDayPeformanceForm::where("resort_id", $resort_id)
                ->get(['id', 'FormName'])
                ->map(function($f) {
                    $f->id = 'ninty_' . $f->id;
                    $f->FormName = $f->FormName . ' (90 Day)';
                    return $f;
                });
            $allForms = $allForms->merge($nintyDayForms);

            // 3. Professional Development Forms
            if (class_exists(\App\Models\Professionalform::class)) {
                $professionalForms = \App\Models\Professionalform::where("resort_id", $resort_id)
                    ->get(['id', 'FormName'])
                    ->map(function($f) {
                        $f->id = 'prof_' . $f->id;
                        $f->FormName = $f->FormName . ' (Professional)';
                        return $f;
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
        
     
        $cycle_name =  $request->cycle_name;
        $CycleStartDate = $request->Step_One_start_date;
        $Step_One_end_date = $request->Step_One_end_date;
        $CycleSummary = $request->CycleSummary;
        $Emp_main_id = $request->Emp_main_id;
        $review_type = $request->review;
        $form_review_type = $request->FormTemplete;
        $Activity_self_Start_date = $request->step_four_start_date_self_hidden;
        $Activity_self_End_date = $request->step_four_end_date_self_hidden;
        $Activity_manager_Start_date = $request->step_four_start_date_manager_hidden;
        $Activivty_manager_End_date = $request->step_four_end_date_manager_hidden;
        $CycleReminders = $request->CycleReminders;

        if (empty($CycleStartDate) || empty($Step_One_end_date)) {
            return response()->json(['success' => false, 'errors' => ['date' => ['Start date and end date are required']]], 422);
        }
        try {
            $CycleStartDate = Carbon::createFromFormat('d/m/Y', $CycleStartDate)->format('Y-m-d');
            $CycleEndDate = Carbon::createFromFormat('d/m/Y', $Step_One_end_date)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'errors' => ['date' => ['Invalid date format. Expected dd/mm/yyyy']]], 422);
        }

        $Self_Activity_Start_Date = 0;
        $Self_Activity_End_Date = 0;
        $self_review = null;
        $manager_review = null;
        try 
        {
            DB::beginTransaction();
                if(array_key_exists('Self_Review', $form_review_type) && isset($form_review_type['Self_Review'][0]) ) 
                {
                    $self_review = $form_review_type['Self_Review'][0];  
                    if(isset($Activity_self_Start_date))
                    {
                        $Self_Activity_Start_Date = Carbon::createFromFormat('d/m/Y', $Activity_self_Start_date)->format('Y/m/d');
                        $Self_Activity_End_Date = Carbon::createFromFormat('d/m/Y', $Activity_self_End_date)->format('Y/m/d');
                    }          
                }
                if(array_key_exists('Manager_Review', $form_review_type) && isset($form_review_type['Manager_Review'][0]))
                {
                    $manager_review = $form_review_type['Manager_Review'][0];
                    if(isset($Activity_manager_Start_date))
                    {
                        $Activity_manager_Start_date = Carbon::createFromFormat('d/m/Y', $Activity_manager_Start_date)->format('Y/m/d');
                        $Activivty_manager_End_date = Carbon::createFromFormat('d/m/Y', $Activivty_manager_End_date)->format('Y/m/d');
                    }   
                }
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
                    foreach ( $Emp_main_id as $key => $emp_id) 
                    {
                        PerformaChildCycle::create([
                            'Parent_cycle_id'=>$p_id->id,
                            'Emp_main_id'=>$emp_id,
                            'Self_review_date'=>null,
                            'Manager_review_date'=>null,
                        ]);
                    }
                }                 
                DB::commit();
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
    public function Destroy($id)
    {
        $id= base64_decode($id);
        $p = PerformaChildCycle::where("id",$id)->delete();

        PerformaChildCycle::where("Parent_cycle_id",$id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Cycle Delete Successfully..',
        ], 200);
    }
}
