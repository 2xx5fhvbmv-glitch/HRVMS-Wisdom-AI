<?php

namespace App\Http\Controllers\Resorts\People;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\EmployeeReminder;
use App\Models\Employee;
use App\Models\IncrementType;
use App\Models\ResortPosition;
use App\Models\TrainingSchedule;
use App\Models\PeopleSalaryIncrement;
use App\Models\PeopleSalaryIncrementStatus;
use App\Helpers\Common;
use Carbon\Carbon;
use App\Exports\SalaryIncrementExport;
use Auth;
use Config;
use DB;

class SalaryIncrementController extends Controller
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    // Employee List view and show if any update request is pending
    public function index()
    {
        $page_title = 'Salary Increment Management';
        $incrementTypes = IncrementType::where('resort_id', $this->resort->resort_id)->where('status','Active')->get();
         $payIncreaseTypes = PeopleSalaryIncrement::PAY_INCREASE_TYPES;
        return view('resorts.people.salary-increment.includes.list', compact('page_title','incrementTypes','payIncreaseTypes'));
    }
   

    public function list(Request $request){
        if($request->ajax())
        {
            $query = PeopleSalaryIncrement::where('resort_id',$this->resort->resort_id)
            ->whereIn('status',['Pending','Change-Request'])
            ->select('id', 'employee_id', 'increment_type', 'effective_date', 'value','pay_increase_type', 'previous_salary', 'new_salary', 'increment_amount', 'remarks', 'status','created_at')
                ->with([
                    'employee.resortAdmin:id,first_name,last_name', 
                    'employee.department:id,name',      
                    'employee.position:id,position_title' 
                ])
                ->whereHas('employee', function ($q) {
                    $q->where('resort_id', $this->resort->resort_id);
                })->get();

                
            $currentBasicSalary = (clone $query)->sum('previous_salary');
            $newBasicSalary = (clone $query)->sum('new_salary');
            $monthlyPayrollIncrease = $newBasicSalary - $currentBasicSalary;
            $annualPayrollIncrease = $monthlyPayrollIncrease * 12;
            
            $edit_class = '';
            if(Common::checkRouteWisePermission('people.salary-increment.index',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
            return datatables()->of($query)
                ->addColumn('Emp_id', function($row){
                    return optional($row->employee)->Emp_id ?? '-';
                })
                ->addColumn('employee_name', function($row){
                    return optional(optional($row->employee)->resortAdmin)->full_name ?? '-';
                })
                ->addColumn('position_title', function($row){
                    return optional(optional($row->employee)->position)->position_title ?? '-';
                })
                ->addColumn('department_name', function($row){
                    return optional(optional($row->employee)->department)->name ?? '-';
                })
                ->editColumn('effective_date', function($query){
                    return $query->effective_date ? Carbon::parse($query->effective_date)->format('d M Y') : '-';
                })
                ->addColumn('last_activity', function($query){
                   $activity = '';
                    if ($query->peopleSalaryIncrementStatusFinance) {
                        $activity .= 'Finance: ' . $query->peopleSalaryIncrementStatusFinance->status;
                        if (!empty($query->peopleSalaryIncrementStatusFinance->remarks)) {
                            $activity .= ' with ' . $query->peopleSalaryIncrementStatusFinance->remarks;
                        }
                         $activity .='<br>';
                    }
                    if ($query->peopleSalaryIncrementStatusGM) {
                        $activity .= 'GM: ' . $query->peopleSalaryIncrementStatusGM->status;
                        if (!empty($query->peopleSalaryIncrementStatusGM->remarks)) {
                            $activity .= ' with ' . $query->peopleSalaryIncrementStatusGM->remarks;
                        }
                         $activity .='<br>';

                    }
                   return $activity;
                })
                ->addColumn('action', function($query) use ($edit_class) {
                    $id = base64_encode($query->id);
                    return ' 
                        <div class="d-flex align-items-center">
                           <a href="' . route('people.salary-increment.edit', $query->id) . '" data-bs-toggle="modal" data-bs-target="#editData-modal" class="a-linkTheme open-ajax-modal ' . $edit_class . '"> <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid"></a>
                        </div>';
                })
                ->with([
                    'currentBasicSalary' => $currentBasicSalary,
                    'newBasicSalary' => $newBasicSalary,
                    'monthlyPayrollIncrease' => $monthlyPayrollIncrease,
                    'annualPayrollIncrease' => $annualPayrollIncrease
                ])
                ->rawColumns(['Emp_id','department_name','employee_name','position_title','last_activity','action'])
                ->make(true);
        }
    }

    public function edit($id)
    {
        if(Common::checkRouteWisePermission('people.salary-increment.index',config('settings.resort_permissions.edit')) == false){
            return abort(403, 'Unauthorized access');
        }
        $incrementTypes = IncrementType::where('resort_id', $this->resort->resort_id)->where('status','Active')->get();
        $peopleSalaryIncrement = PeopleSalaryIncrement::with(['employee.resortAdmin:id,first_name,last_name', 'employee.department:id,name', 'employee.position:id,position_title'])->find($id);
        $payIncreaseTypes = PeopleSalaryIncrement::PAY_INCREASE_TYPES;
         $html = view('resorts.people.salary-increment.includes.edit-modal', ['peopleSalaryIncrement'=>$peopleSalaryIncrement,'incrementTypes'=>$incrementTypes,'payIncreaseTypes'=>$payIncreaseTypes])->render();

        return response()->json(['status' => 'success', 'message' => 'get.','html'=> $html]);
    }


    public function update(Request $request,$id){

        $peopleSalaryIncrement = PeopleSalaryIncrement::find($id);

        $effectiveDate = Carbon::createFromFormat('d/m/Y', $request->effective_date)->format('Y-m-d');

        if ($peopleSalaryIncrement) {
            if($request->pay_increase_type == PeopleSalaryIncrement::PAY_INCREASE_TYPE_PERCENTAGE){
               $value = $peopleSalaryIncrement->previous_salary * $request->value / 100;
            }elseif($request->pay_increase_type == PeopleSalaryIncrement::PAY_INCREASE_TYPE_FIXED){
                $value = $request->value;

            }
            
            $peopleSalaryIncrement->update([
                'increment_type' => $request->increment_type,
                'pay_increase_type' => $request->pay_increase_type,
                'value' => $request->value,
                'new_salary' => $peopleSalaryIncrement->previous_salary + $value, 
                'increment_amount' => $value,
                'effective_date' => $effectiveDate,
                'remark'=> $request->remark,
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'People Salary updated successfully.'
        ]);
    }

    // grid View where update salary bulk action 
     public function gridIndex()
    {
        if(Common::checkRouteWisePermission('people.salary-increment.index',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
        }
        
        $page_title = 'Salary Increment Management';
        $incrementTypes = IncrementType::where('resort_id', $this->resort->resort_id)->where('status','Active')->get();
        $payIncreaseTypes = PeopleSalaryIncrement::PAY_INCREASE_TYPES; 

        return view('resorts.people.salary-increment.index', compact('page_title','incrementTypes','payIncreaseTypes'));
    }

    public function employeeGridView(Request $request)
    {
        $query = Employee::where('resort_id', $this->resort->resort_id)
            ->where('status', 'active')->where('basic_salary', '>', 0);
        if ($request->search) {
           $query->whereHas('resortAdmin', function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                    ->orWhere('last_name', 'like', '%' . $request->search . '%');
            });
        }

        if($request->exclude_probation == 1) {
            $query->where('employment_type','Probationary');
            // ->where('probation_status', '=', ['active','extended']);
        }

        if($request->exclude_disciplinary == 1) {
            $query->whereDoesntHave('disciplinarySubmits', function ($q) {
                $q->where('status', ['pending','in-review']);
            });
        }

        if($request->exclude_recent_promotion == 1) {
            $query->whereDoesntHave('promotions', function ($q) {
                $q->where('status', 'Approved')
                  ->where('effective_date', '>=', now()->subMonth(6));
            });
        }

        if($request->exclude_no_training == 1) {
           
            $trainingScheduleId = TrainingSchedule::where('resort_id', $this->resort->resort_id)
                ->where('status', ['ongoing'])
                ->where('end_date', '>=', now())
                ->pluck('id');
            
            $query->whereDoesntHave('trainingParticipants', function ($q) use ($trainingScheduleId) {
                $q->whereIn('training_schedule_id', $trainingScheduleId);
            });
            
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate(15);
        $employee_count = $employees->count();
        $payIncreaseTypes = PeopleSalaryIncrement::PAY_INCREASE_TYPES; 
        $incrementTypes = IncrementType::where('resort_id', $this->resort->resort_id)->where('status','Active')->get();

        $html = view('resorts.people.salary-increment.includes.grid-view', compact('employees','incrementTypes','payIncreaseTypes'))->render();
        return response()->json([
            'success' => true,
            'status' => 'success',
            'employee_count' => $employee_count,
            'html' => $html,
        ]);
    }

    // This  two summary function are used to show dat to hr
    public function summaryStore(Request $request)
    {

        $request->validate([
            'increments.*.emp_id' => 'required',
            'increments.*.increment_type' => 'required',
            'increments.*.pay_increase_type' => 'required',
            'increments.*.value' => 'required|numeric|min:0',
            'increments.*.effective_date' => 'required',
            'increments.*.remark' => 'nullable|string',
        ]);

        $arr_increments = []; // Initialize an array to store increment data

        foreach ($request->increments as $inc) {
            $employee = Employee::find($inc['emp_id']);
            if (!$employee) {
                continue;
            }

            $effectiveDate = Carbon::createFromFormat('d/m/Y', $inc['effective_date'])->format('Y-m-d');
            if($inc['pay_increase_type'] == PeopleSalaryIncrement::PAY_INCREASE_TYPE_PERCENTAGE){
                $incrementAmount = $employee->basic_salary * $inc['value'] / 100;
            }elseif($inc['pay_increase_type'] == PeopleSalaryIncrement::PAY_INCREASE_TYPE_FIXED){
                $incrementAmount = $inc['value'] ;
            }

            $employee_image = Common::GetAdminResortProfile($employee->Admin_Parent_id);
            $arr_increments[] = [
                'emp_id' => $inc['emp_id'],
                'employee_code' => $employee->Emp_id,
                'employee_image' => $employee_image,
                'employee_name' => $employee->resortAdmin->full_name,
                'employee_position' => $employee->position->position_title,
                'employee_department' => $employee->department->name,
                'increment_type' => $inc['increment_type'],
                'effective_date' => $effectiveDate,
                'pay_increase_type' => $inc['pay_increase_type'],
                'value' => $inc['value'],
                'previous_salary' => $employee->basic_salary,
                'new_salary' => $employee->basic_salary + $incrementAmount,
                'increment_amount' => $incrementAmount,
                'remark' => $inc['remark'],
            ];
        }

        // Store the increments data in the session to pass to the next page
        session(['increments_summary' => $arr_increments]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'redirect_url' => route('people.salary-increment.summary-view'),
        ]);
    }

    public function summaryView()
    {
        $page_title = 'Salary Increment Summary';
        $employees_data = session('increments_summary', []); 
        $currentBasicSalary = 0;
        $newSalary = 0;
        $totalEmployees = count($employees_data);
        foreach ($employees_data as $key => $employee) {
            $currentBasicSalary +=$employee['previous_salary'];
            $newSalary +=$employee['new_salary'];
        }
        $monthlyDifference = $newSalary - $currentBasicSalary;
        $yearlyDifference = $monthlyDifference * 12;

        return view('resorts.people.salary-increment.summary-view', compact('page_title', 'employees_data','totalEmployees','currentBasicSalary','newSalary','monthlyDifference','yearlyDifference'));
    }

    // store data in PeopleSalaryIncrement and PeopleSalaryIncrementStatus table
    public function bulkStore(Request $request)
    {    
         $approvalRank = [
                'Finance',
                'GM'
            ];
        foreach ($request->employee_data as $inc) {
       
            $employee = Employee::find($inc['emp_id']);
            if (!$employee) {
                continue; 
            }
            $chk_people_salary_increment = PeopleSalaryIncrement::where('employee_id', $inc['emp_id'])
                ->where('status','Pending')->first();
            if(!$chk_people_salary_increment){
                $increment = PeopleSalaryIncrement::Create(
                    [
                        'resort_id' => $this->resort->resort_id,
                        'employee_id' => $inc['emp_id'],
                        'increment_type' => $inc['increment_type'],
                        'effective_date' => $inc['effective_date'],
                        'pay_increase_type' => $inc['pay_increase_type'],
                        'value' => $inc['value'],
                        'previous_salary' => $inc['previous_salary'],
                        'new_salary' => $inc['new_salary'],
                        'increment_amount' => $inc['increment_amount'],
                        'remarks' => $inc['remark'],
                        'status' => 'Pending',
                    ]
                );
               
                foreach($approvalRank as $approval){
                    $peopleSalaryIncrementStatus = PeopleSalaryIncrementStatus::create([
                        'people_salary_increment_id' => $increment->id,
                        'approval_rank' => $approval,
                        'status' => 'Pending',
                    ]);
                }
            }
        }

        return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Increments saved successfully.',
                'redirect_url' => route('people.salary-increment.index'),
            ]);
    }

    public function bulkUpdate(Request $request){
        $ids = $request->selected_ids;
        $effectiveDate = Carbon::createFromFormat('d/m/Y', $request->effective_date)->format('Y-m-d');

        foreach ($ids as $id) {
            $peopleSalaryIncrement = PeopleSalaryIncrement::find($id);
            if($request->pay_increase_type == PeopleSalaryIncrement::PAY_INCREASE_TYPE_PERCENTAGE){
               $value = $peopleSalaryIncrement->previous_salary * $request->value / 100;
            }elseif($request->pay_increase_type == PeopleSalaryIncrement::PAY_INCREASE_TYPE_FIXED){
                $value = $request->value;

            }
            
            $peopleSalaryIncrement->update([
                'increment_type' => $request->increment_type,
                'pay_increase_type' => $request->pay_increase_type,
                'value' => $request->value,
                'new_salary' => $peopleSalaryIncrement->previous_salary + $value, 
                'increment_amount' => $value,
                'effective_date' => $effectiveDate,
                'remark'=> $request->remark,
            ]);
        }
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'People Salary updated successfully.'
        ]);
    }

    public function bulkUpdateStatus(Request $request){
        $ids = $request->ids;
        
        foreach ($ids as $id) {
            $peopleSalaryIncrement = PeopleSalaryIncrement::find($id);
           
            $peopleSalaryIncrement->update([
                'status'=> 'Pending'
            ]);
            $peopleSalaryIncrementStatus = PeopleSalaryIncrementStatus::where('people_salary_increment_id', $id)->where('status','Change-Request')->first();
            if($peopleSalaryIncrementStatus){
                $peopleSalaryIncrementStatus->update([
                    'status'=> 'Pending'
                ]);
            }
        }
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'People Salary updated successfully.'
        ]);
    }

    // below function is used to  Finance and GM view only 
    public function summaryIndex(Request $request)
    {
        $page_title = 'Salary Increment Summary';
            $hasFinanceApproval = false;
            $hasGMApproval = false;
            $downloadBtn = false;

            $ids = PeopleSalaryIncrement::whereIn('status',['Pending','Hold','Change-Request'])->where('resort_id', $this->resort->resort_id)
                ->pluck('id');
              
            $query = PeopleSalaryIncrement::whereIn('id', $ids)->whereIn('status',['Pending','Hold','Change-Request'])
                ->select('id', 'employee_id', 'increment_type', 'effective_date', 'value', 'pay_increase_type', 'previous_salary', 'new_salary', 'increment_amount', 'remarks', 'status','created_at')
                ->with([
                    'employee.resortAdmin:id,first_name,last_name',
                    'employee.department:id,name',
                    'employee.position:id,position_title'
                ])->latest()
                ->get();

            if($query->count() > 0) {
                $downloadBtn = true;
            }
        $financeManagerTitles = ['Director of Finance', 'Finance Manager'];

        $positionIds = ResortPosition::where('resort_id', $this->resort->resort_id)
            ->whereIn('position_title', $financeManagerTitles)
            ->pluck('id');

        $financeApprover = Employee::with(['resortAdmin', 'position'])
            ->whereIn('position_id', $positionIds)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();

        $gmApprover = Employee::with('position')->where('rank', 8)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();

            if($financeApprover){
                $peopleSalaryIncrementStatusFinance = PeopleSalaryIncrementStatus::whereIn('people_salary_increment_id', $ids)->where('approval_rank', 'Finance')->whereIn('status', ['Pending','Hold'])->get();                
                if($peopleSalaryIncrementStatusFinance->count() > 0){
                    $hasFinanceApproval = true;
                }
            }elseif($gmApprover){
                $peopleSalaryIncrementStatusFinanceIds = PeopleSalaryIncrementStatus::whereIn('people_salary_increment_id', $ids)->where('approval_rank', 'Finance')->whereIn('status', ['Approved','Hold'])->get();
                if($peopleSalaryIncrementStatusFinanceIds ->count() > 0){
                    
                    $peopleSalaryIncrementStatusGM = PeopleSalaryIncrementStatus::whereIn('people_salary_increment_id', $ids)->where('approval_rank', 'GM')->whereIn('status', ['Pending','Hold'])->get();
                    if($peopleSalaryIncrementStatusGM->count() > 0){
                        $hasGMApproval = true;
                    }      
                }       
            }

            $currentBasicSalary = (clone $query)->sum('previous_salary');
            $newBasicSalary = (clone $query)->sum('new_salary');
            $monthlyPayrollIncrease = $newBasicSalary - $currentBasicSalary;
            $annualPayrollIncrease = $monthlyPayrollIncrease * 12;
            
             if($request->ajax())
                {
                    return datatables()->of($query)
                        ->addColumn('Emp_id', function($row){
                            return optional($row->employee)->Emp_id ?? '-';
                        })
                        ->addColumn('employee_name', function($row){
                            return optional(optional($row->employee)->resortAdmin)->full_name ?? '-';
                        })
                        ->addColumn('position_title', function($row){
                            return optional(optional($row->employee)->position)->position_title ?? '-';
                        })
                        ->addColumn('department_name', function($row){
                            return optional(optional($row->employee)->department)->name ?? '-';
                        })
                        ->editColumn('effective_date', function($query){
                            return $query->effective_date ? Carbon::parse($query->effective_date)->format('d M Y') : '-';
                        })
                        ->with([
                            'currentBasicSalary' => $currentBasicSalary,
                            'newBasicSalary' => $newBasicSalary,
                            'monthlyPayrollIncrease' => $monthlyPayrollIncrease,
                            'annualPayrollIncrease' => $annualPayrollIncrease
                        ])
                        ->rawColumns(['Emp_id','department_name','employee_name','position_title'])
                        ->make(true);
                }
        
        return view('resorts.people.salary-increment-summary.list', compact('page_title','hasGMApproval','hasFinanceApproval','downloadBtn'));
    }
    
    // update status of salary increment
    public function updateStatus(Request $request){
       
        $status = $request->status;
        $paylaod = is_string($request->payload) ? json_decode($request->payload, true) : $request->payload;
        
        $financeManagerTitles = ['Director of Finance', 'Finance Manager'];

        
        $positionIds = ResortPosition::where('resort_id', $this->resort->resort_id)
            ->whereIn('position_title', $financeManagerTitles)
            ->pluck('id');
            
        $financeApprover = Employee::with(['resortAdmin', 'position'])
            ->whereIn('position_id', $positionIds)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();

       
        $gmApprover = Employee::with('position')
            ->where('rank', 8)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();
        
        // Add approvers to each incrementData array
        if (is_array($paylaod)) {
            foreach ($paylaod as &$incrementData) {
            if ($financeApprover) {
                $incrementData['approver'] = $financeApprover;
                $incrementData['approval_rank'] = 'Finance';
            }
            if ($gmApprover) {
                $incrementData['approver'] = $gmApprover;
                $incrementData['approval_rank'] = 'GM';
            }
            }
            unset($incrementData);
        }
        
        if (is_array($paylaod)) {
            foreach ($paylaod as $incrementData) {
                $increment = PeopleSalaryIncrement::find($incrementData['id']);
                
                if ($increment) {
                    $peopleSalaryIncrementStatus = PeopleSalaryIncrementStatus::where('people_salary_increment_id', $increment->id);
                    if($incrementData['approval_rank']){
                                $update_key = false;

                        if($incrementData['approval_rank'] == 'Finance') {
                                $update_key = true;

                            $peopleSalaryIncrementStatus->where('approval_rank', $incrementData['approval_rank'])
                            ->where('status', 'Pending')->first();
    
                        }elseif($incrementData['approval_rank'] == 'GM') {
                            $peopleSalaryIncrementStatusFinance =  PeopleSalaryIncrementStatus::where('people_salary_increment_id', $increment->id)->where('approval_rank', 'Finance')->where('status', 'Approved')->first();
    
                            if($peopleSalaryIncrementStatusFinance){
                                $update_key = true;
                                $peopleSalaryIncrementStatus->where('approval_rank', $incrementData['approval_rank'])
                                ->where('status', 'Pending')->first();
                            }else{
                                return response()->json([
                                        'success' => false,
                                        'status' => 'Error',
                                        'message' => 'Action can be taken after finance aprroval.'
                                    ]);
                            }
                        }
                        
                        if ($update_key == true) {
                            $peopleSalaryIncrementStatus->update([
                                'status' => $status,
                                'approved_by' => $incrementData['approver']->id,
                                'action_date' => now(),
                                'remarks' => $request->remarks,
                                'reject_reason' => $request->rejected_reason,
                            ]);


                            if($status == 'Rejected' || $status == 'Change-Request'){
                                $increment->update([
                                    'status' => $status,
                                ]);
                            }
                        }
    
                        $peopleSalaryIncrementStatusGm =  PeopleSalaryIncrementStatus::where('people_salary_increment_id', $increment->id)->where('approval_rank', 'GM')->where('status', '!=','Pending')->first();
    
                        if($peopleSalaryIncrementStatusGm){
                            $increment->update([
                                'status' => $peopleSalaryIncrementStatusGm->status,
                            ]);

                            //update employee Basic Salary

                            $employee = Employee::find($increment->employee_id);
                            if($employee) {
                                $employee->update([
                                    'basic_salary' => $increment->new_salary,
                                    'incremented_date' => $increment->effective_date,
                                    'last_increment_salary_amount' => $increment->increment_amount,
                                    'last_salary_increment_type'=> $increment->increment_type,
                                    'notes'  => $increment->remarks,
                                ]);
                            }
                        }
                    }else{
                        return response()->json([
                                'success' => false,
                                'status' => 'Error',
                                'message' => 'Unauthorized action.'
                            ]);
                    }
                }
            }
        }
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Data updated successfully.',
            'redirect_url' => route('people.salary-increment.summary-list'),
        ]);
    }
    

    public function requestChange(Request $request){
        $paylaod = is_string($request->payload) ? json_decode($request->payload, true) : $request->payload;
         $financeManagerTitles = ['Director of Finance', 'Finance Manager'];

        $positionIds = ResortPosition::where('resort_id', $this->resort->resort_id)
            ->whereIn('position_title', $financeManagerTitles)
            ->pluck('id');
            
        $financeApprover = Employee::with(['resortAdmin', 'position'])
            ->whereIn('position_id', $positionIds)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();

       
        $gmApprover = Employee::with('position')
            ->where('rank', 8)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();
        
        // Add approvers to each incrementData array
        if (is_array($paylaod)) {
            foreach ($paylaod as &$incrementData) {
            if ($financeApprover) {
                $incrementData['approver'] = $financeApprover;
                $incrementData['approval_rank'] = 'Finance';
            }
            if ($gmApprover) {
                $incrementData['approver'] = $gmApprover;
                $incrementData['approval_rank'] = 'GM';
            }
            }
            unset($incrementData);
        }

        if (is_array($paylaod)) {
            foreach ($paylaod as $incrementData) {
                
                $increment = PeopleSalaryIncrement::find($incrementData['id']);
                
                if ($increment) {
                   $peopleSalaryIncrementStatus = PeopleSalaryIncrementStatus::where('people_salary_increment_id', $increment->id);
                    if($incrementData['approval_rank']){
                                $update_key = false;

                        if($incrementData['approval_rank'] == 'Finance') {
                                $update_key = true;

                            $peopleSalaryIncrementStatus->where('approval_rank', $incrementData['approval_rank'])
                            ->where('status', 'Pending')->first();
    
                        }elseif($incrementData['approval_rank'] == 'GM') {
                            $peopleSalaryIncrementStatusFinance =  PeopleSalaryIncrementStatus::where('people_salary_increment_id', $increment->id)->where('approval_rank', 'Finance')->where('status', 'Approved')->first();
    
                            if($peopleSalaryIncrementStatusFinance){
                                $update_key = true;
                                $peopleSalaryIncrementStatus->where('approval_rank', $incrementData['approval_rank'])
                                ->where('status', 'Pending')->first();
                            }
                        }
                        
                        if ($update_key == true) {
                            $peopleSalaryIncrementStatus->update([
                                'status' => 'Change-Request',
                                'action_date' => now(),
                                'remarks' => $request->remarks,
                            ]);
                            
                            $increment->update([
                                'status' => 'Change-Request',
                            ]);
                        }
                    }
                }
            }
        }
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Data updated successfully.',
            'redirect_url' => route('people.salary-increment.summary-list'),
        ]);
    }

    public function holdRequest(Request $request){
        
      $paylaod = is_string($request->payload) ? json_decode($request->payload, true) : $request->payload;
         $financeManagerTitles = ['Director of Finance', 'Finance Manager'];

        
        $positionIds = ResortPosition::where('resort_id', $this->resort->resort_id)
            ->whereIn('position_title', $financeManagerTitles)
            ->pluck('id');
            
        $financeApprover = Employee::with(['resortAdmin', 'position'])
            ->whereIn('position_id', $positionIds)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();

       
        $gmApprover = Employee::with('position')
            ->where('rank', 8)
            ->where('resort_id', $this->resort->resort_id)
            ->where('Admin_Parent_id',$this->resort->id)
            ->select('id')
            ->first();

            if ($financeApprover) {
                $approver = $financeApprover;
                $approval_rank = 'Finance';
            }else{

                $approver = $gmApprover;
                $approval_rank = 'GM';
            }
        
            $dueDate = Carbon::createFromFormat('d/m/Y', $request->due_date)->format('Y-m-d');

        if (is_array($paylaod)) {
            
            foreach ($paylaod as $incrementData) {
       
                $increment = PeopleSalaryIncrement::find($incrementData['id']);
                
                if ($increment) {
                   $peopleSalaryIncrementStatus = PeopleSalaryIncrementStatus::where('people_salary_increment_id', $increment->id);
                    if($approval_rank){
                        $update_key = false;

                        if($approval_rank == 'Finance') {
                            $update_key = true;
                            $peopleSalaryIncrementStatus->where('approval_rank', $approval_rank)
                            ->where('status', 'Pending')->first();
                            
                        }elseif($approval_rank == 'GM') {
                            $peopleSalaryIncrementStatusFinance =  PeopleSalaryIncrementStatus::where('people_salary_increment_id', $increment->id)->where('approval_rank', 'Finance')->where('status', 'Approved')->first();
    
                            if($peopleSalaryIncrementStatusFinance){
                                $update_key = true;
                                $peopleSalaryIncrementStatus->where('approval_rank', $approval_rank)
                                ->where('status', 'Pending')->first();
                            }
                        }
                        
                        if ($update_key == true) {
                            $peopleSalaryIncrementStatus->update([
                                'status' => 'Hold',
                                'action_date' => now(),
                                'remarks' => $request->remarks,
                            ]);
                            
                            $increment->update([
                                'status' => 'Hold',
                                'due_date' => $dueDate,
                            ]);
                        }
                    }
                }
            }
        }
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Hold Request successfully.',
            'redirect_url' => route('people.salary-increment.summary-list'),
        ]);
    }

    public function downloadByFormate(Request $request)
    {
        if($request->file == 'excel'){
            return Excel::download(new SalaryIncrementExport, 'salary_increment_summary.xlsx'); 
        
        }else{
            $data = PeopleSalaryIncrement::where('resort_id',$this->resort->resort_id)->select('id', 'employee_id', 'increment_type', 'effective_date', 'value', 'previous_salary', 'new_salary', 'increment_amount', 'remarks', 'status')
                    ->with([
                        'employee.resortAdmin:id,first_name,last_name', 
                        'employee.department:id,name',      
                        'employee.position:id,position_title',
                        'peopleSalaryIncrementStatusFinance',
                        'peopleSalaryIncrementStatusGM'
                    ])
                    ->whereHas('employee', function ($q) {
                        $q->where('resort_id', $this->resort->resort_id);
                    })->get();

                $currentBasicSalary = (clone $data)->sum('previous_salary');
                $newBasicSalary = (clone $data)->sum('new_salary');
                $monthlyPayrollIncrease = $newBasicSalary - $currentBasicSalary;
                $annualPayrollIncrease = $monthlyPayrollIncrease * 12;

            $pdf = \PDF::loadView('resorts.people.salary-increment-summary.pdf', compact('data','currentBasicSalary','newBasicSalary','monthlyPayrollIncrease','annualPayrollIncrease'));
            $pdf->setPaper('A4', 'landscape');

            return $pdf->download('salary_increment_summary.pdf');
        }
        return response()->json([
            'success' => false,
            'status' => 'error',
            'message' => 'try agin successfully.'
        ]);
    }

    public function incrementHistory (Request $request){

        if(Common::checkRouteWisePermission('people.salary-increment.summary-list',config('settings.resort_permissions.view')) == false && Common::checkRouteWisePermission('people.salary-increment.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        $page_title = 'Salary Increment History';
        $employeeId = base64_decode($request->id);

        $ids = PeopleSalaryIncrement::where('resort_id', $this->resort->resort_id)
            ->pluck('id');

        $query = PeopleSalaryIncrement::whereIn('id', $ids)
            ->select('id', 'employee_id', 'increment_type', 'effective_date', 'value', 'pay_increase_type', 'previous_salary', 'new_salary', 'increment_amount', 'remarks', 'status','created_at')
            ->with([
                'employee.resortAdmin:id,first_name,last_name',
                'employee.department:id,name',
                'employee.position:id,position_title'
            ])
            ->get();

       
            if($request->ajax())
            {
                return datatables()->of($query)
                    ->addColumn('Emp_id', function($row){
                        return optional($row->employee)->Emp_id ?? '-';
                    })
                    ->addColumn('employee_name', function($row){
                        return optional(optional($row->employee)->resortAdmin)->full_name ?? '-';
                    })
                    ->addColumn('position_title', function($row){
                        return optional(optional($row->employee)->position)->position_title ?? '-';
                    })
                    ->addColumn('department_name', function($row){
                        return optional(optional($row->employee)->department)->name ?? '-';
                    })
                    ->editColumn('effective_date', function($query){
                        return $query->effective_date ? Carbon::parse($query->effective_date)->format('d M Y') : '-';
                    })
        
                    ->rawColumns(['Emp_id','department_name','employee_name','position_title'])
                    ->make(true);
            }
        return view('resorts.people.salary-increment-summary.increment-history', compact('page_title'));

    }
}

