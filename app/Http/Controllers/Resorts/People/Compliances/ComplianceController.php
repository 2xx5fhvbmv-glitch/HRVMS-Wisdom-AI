<?php

namespace App\Http\Controllers\Resorts\People\Compliances;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Auth;
use Config;
use DB;
use PDF;
use App\Helpers\Common;
use App\Models\Compliance;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
use App\Models\EmployeeInfoUpdateRequest;
use App\Models\ManningandbudgetingConfigfiles;
use App\Models\ParentAttendace;
use App\Models\BreakAttendaces;
use App\Models\ChildAttendace;
use App\Models\Vacancies;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use App\Models\PayrollServiceCharge;
use App\Models\Payroll;
use App\Models\Incidents;
use App\Models\ResortBenifitGrid;
use App\Models\PayrollDeduction;
use App\Events\ResortNotificationEvent;
use App\Models\ResortNotification;
use Carbon\Carbon;
use App\Models\EmployeeOnboardingAcknowledgements;
use App\Models\EmployeePromotion;
use App\Models\ResortSiteSettings;
use App\Models\ResortHoliday;

class ComplianceController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

     public function index(Request $request){
          $page_title = 'Compliance';
        $resort = $this->resort;
            $positions = ResortPosition::where('resort_id', $resort->resort_id)->where('status', 'active')->get();
               $departments = ResortDepartment::where('resort_id', $resort->resort_id)->where('status', 'active')->get();
            return view('resorts.people.compliance.list', compact('positions', 'departments','page_title'));

     }

     // List Page
     public function list(Request $request){
          $resort = $this->resort;
          $positions = ResortPosition::where('resort_id', $resort->resort_id)->where('status', 'active')->get();
          $departments = ResortDepartment::where('resort_id', $resort->resort_id)->where('status', 'active')->get();
          $query = Compliance::where('resort_id', $resort->resort_id)->with([
               'employee.resortAdmin',
          ]);
          if ($request->searchTerm != null) {
               $searchTerm = $request->searchTerm;

               $query->whereHas('employee', function ($q) use ($request, $searchTerm) {   
                    $q->whereHas('resortAdmin', function ($Qname) use ($searchTerm) {
                         $Qname->where('id', 'LIKE', "%{$searchTerm}%")
                              ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")     
                              ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                              ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$searchTerm}%");
                    });
               });
          }
          if ($request->status != null) {
               $query->where('status', $request->status);
          }

          if ($request->module_name != null) {
               $query->where('module_name', $request->module_name);
          }

          if ($request->compliance_breached_name != null) {
               $query->where('compliance_breached_name', $request->compliance_breached_name);
          }
          $compliances = $query->whereHas('employee.resortAdmin')->where("Dismissal_status","Pending")->get();

          if ($request->ajax()) 
          {
               $edit_class = '';
               if (Common::checkRouteWisePermission('people.compliances.edit', config('settings.resort_permissions.edit')) == false) 
               {
                    $edit_class = 'd-none';
               }
               return datatables()->of($compliances)
                    ->addIndexColumn()  // This adds the DT_RowIndex column
                    ->addColumn('employee_id', function ($compliance) {
                         return $compliance->employee ? $compliance->employee->Emp_id : '-';
                    })
                    ->addColumn('employee_name', function ($compliance) {
                         $name = $compliance->employee ? $compliance->employee->resortAdmin->full_name : 'N/A';
                         if (!$compliance->employee) {
                              return '<span class="text-danger">-</span>';
                         }
                         $img = '';
                              $profileImage = Common::getResortUserPicture($compliance->employee->Admin_Parent_id);
                              $img = '<img src="' . $profileImage . '" class="rounded-circle mr-2" width="30" height="30" alt="Profile">';

                         return $img . ' ' . $name;
                    })
                    ->addColumn('reported_on', function ($compliance) {
                         return $compliance->reported_on ? Carbon::parse($compliance->reported_on)->format('Y-m-d H:i:s') : 'N/A';
                    })
                    ->addColumn('status', function ($compliance) {
                         if($compliance->status =="")
                         {
                              $status = 'Breached';
                              $color ="warning";

                         }
                         elseif($compliance->status =="Breached")
                         {
                              $status = 'Breached';
                              $color ="warning";

                         }
                         else 
                         {
                              $status = 'Resolved';
                              $color ="success";

                         }
                        
                         return '<span class="badge badge-' . ($color) . '">' . $status . '</span>';
                    
                    })
                    ->addColumn('action', function ($compliance) use ($edit_class) {  // Changed from 'actions' to 'action'
                         $actions = '<div>';
                         if($edit_class != '') {
                              $actions .= '<a href="javascript:void(0)" class="a-link">-</a>';
                         }else{
                              $actions .= '<a href="javascript::void(0)" data-id="'.base64_encode($compliance->id).'" class="a-link dismmisal '.$edit_class.'">Dismiss</a>';
                         }
                         $actions .= '</div>';
                         return $actions;
                    })
                    ->rawColumns(['employee_name','status', 'action'])  // Updated to match columns that contain HTML
                    ->make(true);
          }    
          
     }
     

     public function checkCompliance(Request $request){
          $resort = $this->resort;
            
          $today = Carbon::today()->format('Y-m-d');

          $minWageMVR = 8021; // Minimum wage in MVR
          $minWageUSD = 520; // Minimum wage in MVR
          $notify_person = Employee::where('resort_id', $resort->resort_id)->where('rank','3')->first();
          if (!$notify_person) 
          {
               $notify_person = Employee::where('resort_id', $resort->resort_id)->where('rank','2')->first();
          }

          
               // Payroll Service Charges Start
               // Get last month's date range
               $lastMonth = Carbon::now()->subMonth();
               $startOfLastMonth = $lastMonth->copy()->startOfMonth()->format('Y-m-d');
               $endOfLastMonth = $lastMonth->copy()->endOfMonth()->format('Y-m-d');
                  
               // Fetch payroll data for last month
               $payrollData = Payroll::where('resort_id', $resort->resort_id)
                    ->whereBetween('start_date', [$startOfLastMonth, $endOfLastMonth])
                    ->orWhereBetween('end_date', [$startOfLastMonth, $endOfLastMonth])
                    ->first();

               if($payrollData) 
               {
                    $payroll_service_charges = PayrollServiceCharge::where('payroll_id', $payrollData->id)->get();
                    foreach ($payroll_service_charges as $payroll) 
                    {
                         $employee           =  Employee::where('id', $payroll->employee_id)->where('resort_id', $resort->resort_id)->first();
                         $grade              =  Common::getEmpGrade($employee->rank);
                         $resortBenifitsGrid =  ResortBenifitGrid::where('resort_id', $resort->resort_id)->where('emp_grade', $grade)->where('service_charge', '1')->where('status','Active')->first();
                         
                         if($resortBenifitsGrid && $payroll->service_charge_amount > 0) 
                         {
                             
                                   Compliance::create([
                                        'resort_id' => $resort->resort_id,
                                        'employee_id' => $employee->id,
                                        'module_name' => 'Payroll Compliance',
                                        'compliance_breached_name' => 'Service Charge Compliance',
                                        'description' => "Employee " . $employee->resortAdmin->full_name . " is eligible for service charge but receiving less than the required amount. Expected: " . $resortBenifitsGrid->service_charge_amount . ", Received: " . $payroll->service_charge_amount,
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached'
                                   ]);
                                   event(new ResortNotificationEvent(Common::nofitication(
                                        $this->resort->resort_id,
                                        10,
                                        'Service Charge Compliance',
                                        "Employee " . $employee->resortAdmin->full_name . " is eligible for service charge but receiving less than the required amount. Expected: " . $resortBenifitsGrid->service_charge_amount . ", Received: " . $payroll->service_charge_amount,
                                        0,
                                        $notify_person->id,
                                        'Service Charge Compliance'
                                   )));
                         }
                    }
               }
               // Payroll Service Charges Start





               // Incident Compliance Start
               $incidentData = Incidents::where('resort_id', $resort->resort_id)->where('status','Reported')->get();
               foreach ($incidentData as $incident) 
               {
                    
               }
               

               // Probation Compliance start
               $probationData = Employee::where('resort_id', $resort->resort_id)->where('status', 'Active')->whereIn('probation_status', ['Active','Extended'])->get();
               if($probationData->isEmpty()) 
               {
                    foreach ($probationData as $probation) 
                    {
                         $probationEndDate = Carbon::parse($probation->probation_end_date);
                         $probationStartDate = Carbon::parse($probation->joining_date);

                         // Calculate the difference in months
                         $probationMonths = $probationStartDate->diffInMonths($probationEndDate);

                         // Check if probation period is more than 3 months
                         if ($probationMonths > 3 && $probation->probation_status == 'Active') {
                              Compliance::create([
                                   'resort_id' => $resort->resort_id,
                                   'employee_id' => $probation->id,
                                   'module_name' => 'Probation',
                                   'compliance_breached_name' => 'Extended Probation Period',
                                   'description' => "Probation period for " . $probation->resortAdmin->full_name . "(" . $probation->position->position_title . ') is set to ' . $probationMonths . ' months. Reduce to comply with the 3-month maximum',
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
                    }
               }
               // Probation Compliance End

                    // OverTime Agreement Compliance Start
              
               $overtimeData = Employee::where('resort_id', $resort->resort_id)
                         ->where('status', 'Active')
                         ->get();
               if($overtimeData->isNotEmpty())
               {
                    foreach ($overtimeData as $overtime) 
                    {
                         $grade = Common::getEmpGrade($overtime->rank);
                         $resortBenifitsGrid = ResortBenifitGrid::where('resort_id', $resort->resort_id)->where('emp_grade', $grade)->where('status','Active')
                              ->where('overtime', '!=','yes')
                              ->first();

                         $attendance = ParentAttendace::where('resort_id',$resort->resort_id)->where('Emp_id', $overtime->id)
                              ->whereDate('date', $today)
                              ->where('CheckingTime', '!=', null)
                              ->where('status', 'Present')
                              ->where('OverTime', '!=', null)
                              ->first();

                         if ($resortBenifitsGrid && $attendance) {
                              Compliance::create([
                                   'resort_id' => $resort->resort_id,
                                   'employee_id' => $overtime->id,
                                   'module_name' => ' Overtime  Agreement',
                                   'compliance_breached_name' => 'Overtime Agreement Lacked',
                                   'description' => "Overtime logged for " . $overtime->resortAdmin->full_name . "(" . $overtime->position->position_title . ') but employment agreement lacks overtime terms. Update contract or adjust hours',
                                   'reported_on' => Carbon::now(),
                                   'status' => 'Breached'
                              ]);

                              event(new ResortNotificationEvent(Common::nofitication(
                                   $this->resort->resort_id,
                                   10,
                                   'Overtime Agreement Lacked',
                                   "Overtime logged for " . $overtime->resortAdmin->full_name . "(" . $overtime->position->position_title . ") but employment agreement lacks overtime terms. Update contract or adjust hours",
                                   0,
                                   $notify_person->id,
                                   'Overtime Agreement Lacked'
                              )));
                         }

                    }
               }
                    // OverTime Compliance End



                    // Senior HR and Management start
                    $total_employees = Employee::where('resort_id', $resort->resort_id)->where('status', 'Active')->get();
                    $total_employees_count = $total_employees->count();
                    if($total_employees_count > 50) 
                    {
                         $seniorHR = Employee::where('resort_id', $resort->resort_id)->where('rank', '3')->first();
                        
                         if ($seniorHR && $seniorHR->nationality != 'Maldivian') {
                              Compliance::create([
                                   'resort_id' => $resort->resort_id,
                                   'employee_id' => $seniorHR->id,
                                   'module_name' => 'Senior HR and Management ',
                                   'compliance_breached_name' => 'Senior HR Non-Maldivian',
                                   'description' => "Senior HR and Management position at " . $resort->resort_name . " should be held by a Maldivian. Current  holder is " . $seniorHR->resortAdmin->full_name . "(" . $seniorHR->position->position_title . ')',
                                   'reported_on' => Carbon::now(),
                                   'status' => 'Breached'
                              ]);

                              event(new ResortNotificationEvent(Common::nofitication(
                                   $this->resort->resort_id,
                                   10,
                                   'Senior HR Non-Maldivian',
                                  "Senior HR and Management position at " . $resort->resort_name . " should be held by a Maldivian. Current  holder is " . $seniorHR->resortAdmin->full_name . "(" . $seniorHR->position->position_title . ")",
                                   0,
                                   $notify_person->id,
                                   'Senior HR and Management'
                              )));
                         }

                         // Add condition here for Management Position where its should be Maldivian of 60% of the total employees

                         $managementEmployees = Employee::where('resort_id', $resort->resort_id)
                              ->where('rank',1)
                              ->where('status', 'Active')
                              ->get();
                         
                         $managementCount = $managementEmployees->count();
                         $maldivianCount = $managementEmployees->where('nationality', 'Maldivian')->count();
                         $NonMaldivian = $managementEmployees->where('nationality', '!=', 'Maldivian')->whereIn('rank', ['3','1'])->get();

                         if ($maldivianCount < ($managementCount * 0.6)) {
                              Compliance::create([
                                   'resort_id' => $resort->resort_id,
                                   'module_name' => 'Senior HR and Management',
                                   'compliance_breached_name' => 'Management Non-Maldivian',
                                   'description' => "Management positions at " . $resort->resort_name . " should have at least 60% Maldivian representation. Current ratio is " . $maldivianCount . "/" . $managementCount,
                                   'reported_on' => Carbon::now(),
                                   'status' => 'Breached'
                              ]);

                              event(new ResortNotificationEvent(Common::nofitication(
                                   $this->resort->resort_id,
                                   10,
                                   'Management Non-Maldivian',
                                   "Management positions at " . $resort->resort_name . " should have at least 60% Maldivian representation. Current ratio is " . $maldivianCount . "/" . $managementCount,
                                   0,
                                   $notify_person->id,
                                   'Senior HR and Management'
                              )));    
                         }

                         foreach ($NonMaldivian as $nonMaldivian) {
                              Compliance::create([
                                   'resort_id' => $resort->resort_id,
                                   'employee_id' => $nonMaldivian->id,
                                   'module_name' => 'Senior HR and Management',
                                   'compliance_breached_name' => 'Management Non-Maldivian',
                                   'description' => "Management position at " . $resort->resort_name .
                                   " should be held by a Maldivian. Current holder is " . $nonMaldivian->resortAdmin->full_name . "(" . $nonMaldivian->position->position_title . ')',
                                   'reported_on' => Carbon::now(),
                                   'status' => 'Breached'
                              ]);

                              event(new ResortNotificationEvent(Common::nofitication(
                                   $this->resort->resort_id,
                                   10,
                                   'Management Non-Maldivian',
                                   "Management position at " . $resort->resort_name .
                                   " should be held by a Maldivian. Current holder is " . $nonMaldivian->resortAdmin->full_name . "(" . $nonMaldivian->position->position_title . ")",
                                   0,
                                   $notify_person->id,
                                   'Senior HR and Management'
                              )));
                         }
                    }
                    // Senior HR and Management end
                    

                    // Pension Compliance start
               $payrollData = Payroll::where('resort_id', $resort->resort_id)->whereBetween('start_date', [$startOfLastMonth, $endOfLastMonth])->orWhereBetween('end_date', [$startOfLastMonth, $endOfLastMonth])->first();
               if($payrollData)
               {
                    $payrollDeductions = PayrollDeduction::where('payroll_id', $payrollData->id)->get();
                    foreach(  $payrollDeductions as $deduction) 
                    {
                         $employee = Employee::where('id', $deduction->employee_id)->where('resort_id', $resort->resort_id)->first();
                         if ($employee) {
                              $grade = Common::getEmpGrade($employee->rank);
                              // Calculate age from date of birth
                              if ($employee->dob) {
                                         try {
                                            $age = Carbon::createFromFormat('d/m/Y', $employee->dob)->age;
                                        } catch (\Exception $e) {
                                            // If parsing fails, try another common format
                                            try {
                                                $age = Carbon::parse($employee->dob)->age;
                                            } catch (\Exception $e) {
                                                $age = 0; // Default value if parsing fails
                                            }
                                        }
                                        
                                        // Check if employee is eligible for pension (between 16 and 65 years)
                                        if ($age >= 16 && $age <= 65) {
                                             
                                             $basicSalary = $employee->basic_salary;
                                             $sevenPercentOfSalary = $basicSalary * 0.07;
                                             
                                             if ($deduction->pension < $sevenPercentOfSalary) {
                                                  Compliance::create([
                                                       'resort_id' => $resort->resort_id,
                                                       'employee_id' => $employee->id,
                                                       'module_name' => 'Pension Compliance',
                                                       'compliance_breached_name' => 'Pension Deduction Below 7%',
                                                       'description' => "Employee " . $employee->resortAdmin->full_name . " is eligible for pension (age " . $age . " years). Pension deduction is below the required 7% of basic salary (" . $basicSalary . ").",
                                                       'reported_on' => Carbon::now(),
                                                       'status' => 'Breached'
                                                  ]);
                                                  
                                                  event(new ResortNotificationEvent(Common::nofitication(
                                                       $this->resort->resort_id,
                                                       10,
                                                       'Pension Compliance',
                                                       "Employee " . $employee->resortAdmin->full_name . " is eligible for pension (age " . $age . " years). Pension deduction is below the required 7% of basic salary (" . $basicSalary . ").",
                                                       0,
                                                       $notify_person->id,
                                                       'Pension Deduction Below 7%'
                                                  )));
                                             }
                                        }
                              }
                         }
                    }
               }
                    // Pension Compliance end


               $ManningandbudgetingConfigfiles = ManningandbudgetingConfigfiles::where('resort_id', $resort->resort_id)->first();
               if (!$ManningandbudgetingConfigfiles) {
                    return response()->json([
                         'status' => 'error',
                         'message' => 'Workforce Planning is not configured for this resort.'
                    ]);
               }

               $xpat = $ManningandbudgetingConfigfiles->xpat;
               $local = $ManningandbudgetingConfigfiles->local;

               // Get counts
               $totalEmployees = Employee::where('resort_id', $resort->resort_id)->count();
               $expatCount = Employee::where('resort_id', $resort->resort_id)
                    ->where('nationality', '!=', 'Maldivian')
                    ->count();

               $localCount = Employee::where('resort_id', $resort->resort_id)
                    ->where('nationality', 'Maldivian')
                    ->count();

               $compliance = null;
                  // Expat-Local Ratio compliance check
                  if ($totalEmployees > 0 && $xpat > 0 && $local > 0) 
                  {
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
                                     'Workforce Planning'
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
               // Minumum wage compliance check
               $minWageMVR = 8021; // Minimum wage in MVR
               $minWageUSD = 520; // Minimum wage in MVR

               $employeesBelowMinWageInMVR = Employee::where('resort_id', $resort->resort_id)
                    ->where('basic_salary', '<', $minWageMVR)
                    ->where('basic_salary_currency', 'MVR')
                    ->get();

               $employeesBelowMinWageInUSD = Employee::where('resort_id', $resort->resort_id)
                    ->where('basic_salary', '<', $minWageUSD) // Assuming you have a conversion rate set in your config
                    ->where('basic_salary_currency', 'USD')
                    ->get();
               $employeesBelowMinWage = $employeesBelowMinWageInMVR->merge($employeesBelowMinWageInUSD);
               if ($employeesBelowMinWage->isNotEmpty()) {
                    foreach ($employeesBelowMinWage as $employee) 
                    {
                         $compliance = Compliance::firstOrCreate([
                              'resort_id' => $resort->resort_id,
                              'employee_id' => $employee->id,
                              'module_name' => 'Workforce Planning',
                              'compliance_breached_name' => 'Minimum Wage',
                              'description' => "Employee {$employee->resortAdmin->full_name} has a basic salary below the minimum wage.",
                              'reported_on' => Carbon::now(),
                              'status' => 'Breached'
                         ]);
                    }
               }
               // Minumum Wage Compliance End


               // . Time & Attendance
               // Time & Attendance Compliance Start
                
               $employeeIds = Employee::where('resort_id', $resort->resort_id)->where('status', 'active')->pluck('id')->toArray();


               // Check for employees working without breaks
               $attendacedata = ParentAttendace::where('resort_id',$resort->resort_id)->whereIn('Emp_id', $employeeIds)
                    ->whereDate('date', $today)
                    ->where('CheckingTime', '!=', null)
                    ->where('status', 'Present')
                    ->get();

               // List of employees who didn't take a break in 5+ hours
               $employeesWithoutBreak = [];

               foreach ($attendacedata as $attendance) 
               {
                    // Get employee check in time
                    $checkInTime = Carbon::parse($attendance->CheckingTime);
                    
                    $breaks = BreakAttendaces::where('Parent_attd_id', $attendance->id)
                              ->get();
                    
                    if ($breaks->isEmpty()) {
                              $hoursWorked = $checkInTime->diffInHours(Carbon::now());
                              if ($hoursWorked >= 5) {
                                   $employee = Employee::find($attendance->Emp_id);
                                   $employeesWithoutBreak[] = [
                                        'resort_id' => $resort->resort_id,
                                        'employee_id' => $employee->id,
                                        'module_name' => 'Time & Attendance',
                                        'compliance_breached_name' => 'Without Mandatory Break',
                                        'description' => "Employee {$employee->resortAdmin->full_name} ({$employee->position->position_title}) worked {$hoursWorked} hours without a break. Review schedule to ensure adherence to labor laws.",
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached'
                                   ];
                              }
                    } else {
                              $lastBreakTime = $checkInTime;
                              $hadLongPeriod = false;
                              
                              foreach ($breaks as $break) {
                                   $breakStartTime = Carbon::parse($break->start_time);
                                   $timeDifference = $lastBreakTime->diffInHours($breakStartTime);
                                   
                                   if ($timeDifference >= 5) {
                                        $hadLongPeriod = true;
                                        $employee = Employee::find($attendance->Emp_id);
                                        $employeesWithoutBreak[] = [
                                             'resort_id' => $resort->resort_id,
                                             'employee_id' => $employee->id,
                                             'module_name' => 'Time & Attendance',
                                             'compliance_breached_name' => 'Without Mandatory Break',
                                             'description' => "Employee {$employee->resortAdmin->full_name} ({$employee->position->position_title}) worked {$timeDifference} hours without a break. Review schedule to ensure adherence to labor laws.",
                                             'reported_on' => Carbon::now(),
                                             'status' => 'Breached'
                                        ];
                                             
                                   }
                                   
                                   $lastBreakTime = Carbon::parse($break->end_time);
                              }

                              // Check if there's been 5+ hours since last break
                              $timeSinceLastBreak = $lastBreakTime->diffInHours(Carbon::now());
                              if ($timeSinceLastBreak >= 5) {
                                   $employee = Employee::find($attendance->Emp_id);
                                   
                                   $employeesWithoutBreak[] = [
                                        'resort_id' => $resort->resort_id,
                                        'employee_id' => $employee->id,
                                        'module_name' => 'Time & Attendance',
                                        'compliance_breached_name' => 'Without Mandatory Break',
                                        'description' => "Employee {$employee->resortAdmin->full_name} ({$employee->position->position_title}) worked {$timeSinceLastBreak} hours without a break. Review schedule to ensure adherence to labor laws.",
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached'
                                   ];
                                   
                              }
                    }
               }

               // Insert all compliance breaches in one go
               if (!empty($employeesWithoutBreak)) 
               {
                    Compliance::insert($employeesWithoutBreak);
               }

               // Time & Attendance Compliance End

               // Weekly Working Hours Compliance Start

               // Check for employees scheduled to work more than 48 hours in a week (Monday to Saturday)
               $startOfWeek = Carbon::now()->subWeek()->startOfWeek();
               $endOfWeek =  Carbon::now()->copy()->endOfWeek();
               
               $overworkedEmployees = [];
               // Get only the employee at index 3 (4th element) if it exists
               foreach($employeeIds as $empId)
               {
                    $weeklyHours = ParentAttendace::where('resort_id', $resort->resort_id)->where('Emp_id', $empId)
                         ->where('CheckingTime', '!=', null)
                              ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
                              ->where('Status', 'Present')
                              ->get();
                              
                    $totalHoursWorked = 0;
                    foreach ($weeklyHours as $day) {
                         if (!empty($day->CheckingTime) && !empty($day->CheckingOutTime)) {
                              $checkIn = Carbon::parse($day->CheckingTime);
                              $checkOut = Carbon::parse($day->CheckingOutTime);
                              $hoursWorked = $checkIn->diffInHours($checkOut);
                              $totalHoursWorked += $hoursWorked;
                         }
                    }
                    
                    if ($totalHoursWorked > 48) {
                         $employee = Employee::find($empId);
                         $overworkedEmployees[] = [
                              'resort_id' => $resort->resort_id,
                              'employee_id' => $employee->id,
                              'module_name' => 'Time & Attendance',
                              'compliance_breached_name' => 'Weekly Working Hours Limit',
                              'description' => "Employee {$employee->resortAdmin->full_name} ({$employee->position->position_title}) is scheduled for {$totalHoursWorked} hours this week. Adjust schedule to comply with the 48-hour weekly limit.",
                              'reported_on' => Carbon::now(),
                              'status' => 'Breached'
                         ];
                    }
               }
               
               if (!empty($overworkedEmployees)) {
                    Compliance::insert($overworkedEmployees);
               }

               // Weekly Working Hours Compliance End


               // vacancy compliance start
               $vacancies = Vacancies::where('resort_id', $resort->resort_id)->where('status', 'Active')->get();
               foreach ($vacancies as $vacancy) 
               {
                    $base_salary = $vacancy->budgeted_salary;    
                    $propsed_salary = $vacancy->propsed_salary;
                    // Usd
                    if($propsed_salary > $base_salary){
                         $compliance = Compliance::firstOrCreate([
                              'resort_id' => $resort->resort_id,
                              'employee_id' => null,
                              'module_name' => 'Talent Acquisition',
                              'compliance_breached_name' => 'Salary Compliance',
                              'description' => "Vacancy {$vacancy->Getposition->position_title} has a proposed salary ({$propsed_salary}) exceeding the budgeted salary ({$base_salary}).",
                              'reported_on' => Carbon::now(),
                              'status' => 'Breached'
                         ]);
                    }
                    if($base_salary > $minWageUSD){
                         $compliance = Compliance::firstOrCreate([
                              'resort_id' => $resort->resort_id,
                              'employee_id' => null,
                              'module_name' => 'Talent Acquisition',
                              'compliance_breached_name' => 'Minimum Wage Compliance',
                              'description' => "Vacancy {$vacancy->Getposition->position_title} has a budgeted salary ({$base_salary}) below the minimum wage.",
                              'reported_on' => Carbon::now(),
                              'status' => 'Breached'
                         ]);
                    }
               }
               // Vacancy compliance end




          // Visa Module   
               $flag = 'all';
               $filterStart = Carbon::now()->startOfMonth();
               $filterEnd = Carbon::now()->endOfMonth();
               $overworkedEmployeesVisa = [];
               $Employee = Employee::with(['resortAdmin','position', 'department','VisaRenewal.VisaChild','WorkPermitMedicalRenewal.WorkPermitMedicalRenewalChild','WorkPermit','EmployeeInsurance.InsuranceChild','QuotaSlotRenewal'])->where("nationality", '!=', "Maldivian")->where('resort_id', $this->resort->resort_id)->get()
               ->map(function ($employee) use (&$overworkedEmployeesVisa) 
               {

                    $employee->Emp_name        = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                    $employee->Emp_id          = $employee->Emp_id;
                    $employee->Department_name = $employee->department->name;
                    $employee->Position_name   = $employee->position->position_title;
                    $employee->ProfilePic      = Common::getResortUserPicture($employee->resortAdmin->id);

                    $newVisaMArray = [];
                    $ExpiryDocument = [];
                    // Visa
                    $visa = $employee->VisaRenewal;
                    if ($visa && $this->isExpired($visa->end_date)) {
                         $employee->VisaExpiryDate = $this->getFormattedExpiryStatus($visa->end_date);
                         $employee->VisaExpiryExpiryAmt = $visa->Amt;
                         $newVisaMArray[] = [
                              'type' => 'Visa',
                              'ExpiryDate' => $this->getFormattedExpiryStatus($visa->end_date),
                              'amount' => $visa->Amt
                         ];
                    }

                    // Insurance
                    $insurance = $employee->EmployeeInsurance()
                         ->where('employee_id', $employee->id)
                         ->where('resort_id', $this->resort->resort_id)
                         ->orderBy('id', 'desc')
                         ->first();

                    if ($insurance && $this->isExpired($insurance->insurance_end_date)) {
                         $employee->InsuranceExpiryDate = $this->getFormattedExpiryStatus($insurance->insurance_end_date);
                         $employee->Premium = $insurance->Premium;
                         $newVisaMArray[] = [
                              'type' => 'Medical International Insurance',
                              'ExpiryDate' => $this->getFormattedExpiryStatus($insurance->insurance_end_date),
                              'amount' => $insurance->Premium
                         ];
                    }

                    // Work Permit
                    $currentWP = $employee->WorkPermit->where('Status', 'Unpaid')->filter(fn($item) => $this->isExpired($item->Due_Date))->sortByDesc('id')->first();
                    if($currentWP) 
                    {
                         $employee->WorkPermitExpiryDate = $this->getFormattedExpiryStatus($currentWP->Due_Date);
                         $employee->WorkPermitAmt = number_format($currentWP->Amt, 2);
                         $newVisaMArray[] = [ 'type' => 'Work Permit','ExpiryDate' => $this->getFormattedExpiryStatus($currentWP->Due_Date),'amount' => number_format($currentWP->Amt, 2)];
                    }
                    // Medical Test
                    $med = $employee->WorkPermitMedicalRenewal;
                    if ($med && $this->isExpired($med->end_date)) 
                    {
                         $employee->WorkPermitMedicalPermitExpiryDate = $this->getFormattedExpiryStatus($med->end_date);
                         $employee->WorkPermitMedicalPermitAmt = number_format($med->Amt, 2);
                         $newVisaMArray[] =  [
                                                  'type' => 'Work Permit Medical Test Fee',
                                                  'ExpiryDate' => $this->getFormattedExpiryStatus($med->end_date),
                                                  'amount' => number_format($med->Amt,2)
                                             ];
                    }
                    // Quota Slot
                    $currentQuota = $employee->QuotaSlotRenewal->filter(fn($item) => $this->isExpired($item->Due_Date))->where('Status', 'Unpaid')->first();
                         if ($currentQuota) 
                         {
                              $employee->QuotaSlotAmtForThisMonth = $this->getFormattedExpiryStatus($currentQuota->Due_Date);
                              $employee->QuotaSlotAmtForThisMonthAmt = $currentQuota->Amt;
                              $newVisaMArray[] = [
                                   'type' => 'Quota Slot',
                                   'ExpiryDate' => $this->getFormattedExpiryStatus($currentQuota->Due_Date),
                                   'amount' => number_format($currentQuota->Amt, 2)
                              ];
                         }
                         if (!empty($newVisaMArray)) 
                         {
                              $ExpiryDocument[$employee->Emp_name] = $newVisaMArray;
                              if (!empty($ExpiryDocument))
                              {    
                                   $overworkedEmployeesVisa[] = [
                                        'resort_id' => $this->resort->resort_id,
                                        'employee_id' => $employee->id,
                                        'module_name' => 'Visa',
                                        'compliance_breached_name' => 'Expired Visa Documents',
                                        'description' => $this->stringifyExpiryDocument($ExpiryDocument),
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached'
                                   ];
                              }
                         }
                    return !empty($newVisaMArray) ? $employee : null;
               })->filter();
               if(!empty($overworkedEmployeesVisa))
               {
                    Compliance::insert($overworkedEmployeesVisa);
               }
          // VisaEnd

          // Incident Compliance Start
               $fourtieenEightHoursAgo = Carbon::now()->subHours(48);
               $Incidentemployees= array();
               $incidents = incidents::with(['Investigation','victim.resortAdmin','victim.position','victim.department'])->where('priority', 'high')
                                   ->where('severity', 'Severe')
                                   ->where('created_at', '<=', $fourtieenEightHoursAgo)
                                   ->whereHas( 'Investigation', function ($query) use ($fourtieenEightHoursAgo) {
                                        $query->where('Ministry_notified','No');
                                   })
                                   ->get()->map(function ($incident) use(&$Incidentemployees) 
                                   {
                                        $incident->Fullname = $incident->victim->resortAdmin->first_name . ' ' . $incident->victim->resortAdmin->last_name;
                                        $incident->poistion = $incident->victim->position->position_title;
                                        $incident->department = $incident->victim->department->name;
                                             $Incidentemployees[] = [
                                                            'resort_id' =>$incident->resort_id,
                                                            'employee_id' => $incident->victims,
                                                            'module_name' => 'Incident Compliance',
                                                            'compliance_breached_name' => 'Ministry is not Notified yet',
                                                            'description' => "High severity incident for {$incident->Fullname} was created more than 48 hours ago and ministry has not been notified yet.",
                                                            'reported_on' => Carbon::now(),
                                                            'status' => 'Breached'
                                                       ];
                                        return $incident;
                                   });

                    if(!empty($Incidentemployees))
                    {
                         $compliance = Compliance::insert($Incidentemployees);
                    }

          // End Incident Compliance Start
                    
          // On Bording
                    

          // Probation Compliance
               $EmployeeOnBording = array();
               $gracePeriods = [30, 90]; // Different grace periods to check

               foreach ($gracePeriods as $gracePeriod) 
               {
                    // Calculate cutoff date for current grace period
                    $cutoffDate = Carbon::now()->subDays($gracePeriod)->format('Y-m-d');
                    
                    // Build base query
                    $query = Employee::with(['resortAdmin', 'OnboardingAcknowledgements', 'position', 'department'])
                         ->where('resort_id', $this->resort->resort_id)
                         ->where('status', 'Active')
                         ->whereHas('OnboardingAcknowledgements', function ($query) {
                              $query->where('acknowledgement_type', 'Job Description Received?')
                                   ->where('status', 'no');
                         })
                         ->whereDate('joining_date', '<=', $cutoffDate);
                    
                    // Apply probation status condition based on grace period
                    if ($gracePeriod == 30) {
                         // For 30 days: check employees with active probation
                         $query->where('probation_status', 'Active');
                    } elseif ($gracePeriod == 90) {
                         // For 90 days: check employees with inactive probation
                         $query->where('probation_status', '!=', 'Active');
                    }
                    
                    // Execute query and process results
                    $employees = $query->get()->map(function ($employee) use (&$EmployeeOnBording, $gracePeriod) {
                         $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                         $employee->Emp_id = $employee->Emp_id;
                         $employee->Department_name = $employee->department->name;
                         $employee->Position_name = $employee->position->position_title;
                         $joiningDate = Carbon::parse($employee->joining_date)->format('d M Y');
                         $daysSinceJoining = Carbon::parse($employee->joining_date)->diffInDays(Carbon::now());
                         
                         // Create compliance record with grace period context
                         $probationStatus = $employee->probation_status == 'Active' ? 'Active Probation' : 'Inactive Probation';
                         $complianceDescription = "Employee {$employee->Emp_name} ({$employee->Position_name}) joined on {$joiningDate} ({$daysSinceJoining} days ago) and has not received the job description yet. Grace period: {$gracePeriod} days ({$probationStatus}).";
                         
                         $EmployeeOnBording[] = [
                              'resort_id' => $this->resort->resort_id,
                              'employee_id' => $employee->id,
                              'module_name' => 'On Boarding',
                              'compliance_breached_name' => 'Job Description Not Received',
                              'description' => $complianceDescription,
                              'reported_on' => Carbon::now(),
                              'status' => 'Breached',
                         ];
                         
                         return $employee;
                    });
               }
           


               // Insert all compliance records at once
               if (!empty($EmployeeOnBording)) 
               {                                                                           
                    Compliance::insert($EmployeeOnBording);
               }

          //End of On Bording
               
          // Start EmployeePromotion
               $EmployeePromotion = [];
               $query = Employee::with(['resortAdmin', 'promotions.newPosition', 'position', 'department'])
                         ->where('resort_id', $this->resort->resort_id)
                         ->where('status', 'Active')
                         ->whereHas('promotions', function ($query) {
                              $query->where('Jd_id',"=", null); // Check if Job Description is not received
                         })
                         ->get()
                         ->map(function ($employee) use (&$EmployeePromotion) {
                              $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                              $employee->Emp_id = $employee->Emp_id;
                              $employee->Department_name = $employee->department->name;
                              $employee->Position_name = $employee->position->position_title;

                              // Check if the employee has a promotion with a Job Description
                              if ($employee->promotions && $employee->promotions->count() > 0) 
                              {
                                   $employee->promotions->each(function ($promotion) use (&$EmployeePromotion, $employee) {
                                        if (is_null($promotion->Jd_id)) 
                                        {
                                             $employee->PromotionDate = Carbon::parse($promotion->created_at)->format('d M Y');
                                             $employee->PromotionPosition = $promotion->newPosition->position_title;
                                               $EmployeePromotion[] = 
                                               [
                                                  'resort_id' => $this->resort->resort_id,
                                                  'employee_id' => $employee->id,
                                                  'module_name' => 'Employee Promotion',
                                                  'compliance_breached_name' => 'Job Description Not Received',
                                                  'description' => "Employee {$employee->Emp_name} ({$employee->Position_name}) was promoted to {$employee->PromotionPosition} on {$employee->PromotionDate} but has not received the job description.",
                                                  'reported_on' => Carbon::now(),
                                                  'status' => 'Breached',
                                             ];
                                           
                                        }
                                   });
                                 
                                 
                                   
                              }
                              return $employee;
                         });
    
                          if (!empty($EmployeePromotion)) 
                         {                                                                           
                              Compliance::insert($EmployeePromotion);
                         }



          
          
          // People Module Start
          
          $ResortSiteSettings = ResortSiteSettings::where('resort_id', $this->resort->resort_id)->first();
          $employee = Employee::with(['resortAdmin','position','department','division','section','education','experiance','allowance','language','sosTeams','document','bankDetails'])
               ->get()
               ->map(function ($employee) use ($ResortSiteSettings) 
               {
                    $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                    $employee->Emp_id = $employee->Emp_id;
                    $employee->Department_name = $employee->department->name;
                    $employee->Position_name = $employee->position->position_title;
                    

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

                    $notify_person = Employee::where('resort_id', $this->resort->resort_id)->where('rank','3')->first();
                    if (!$notify_person) 
                    {
                         $notify_person = Employee::where('resort_id',$this->resort->resort_id)->where('rank','2')->first();
                    }
                    if($totalMonthlyEarningMvr >= 30000 && !$tin)
                    {
                         event(new ResortNotificationEvent(Common::nofitication(
                              $this->resort->resort_id,
                              10,
                              'TIN Required for Employee',
                              "{$employee->Emp_name} ({$employee->Emp_id} - {$employee->Position_name}) (RSWT: MVR {$totalMonthlyEarningMvr}/month) not registered. Submit MIRA 118 form.",
                              0,
                              $notify_person->id,
                              'People Management (TIN Requirement)'
                         )));
                         Compliance::firstOrCreate([
                              'resort_id' => $this->resort->resort_id,
                              'employee_id' => $employee->id,
                              'module_name' => 'People Management',
                              'compliance_breached_name' => 'TIN Requirement',
                              'description' => "{$employee->Emp_name} ({$employee->Emp_id} - {$employee->Position_name}) (RSWT: MVR {$totalMonthlyEarningMvr}/month) not registered. Submit MIRA 118 form.",
                              'reported_on' => Carbon::now(),
                              'status' => 'Breached'
                         ]);
                    }
                    return $employee;
               });

            
          // End Of People Module


          // Over Time check Employee eligible to do over time or not
          
               $employee = Employee::with(['resortAdmin','position','department','EmployeeAttandance'])->where('resort_id', $this->resort->resort_id)
               ->where('status', 'active')    
               ->whereHas('resortAdmin', function ($query) {
                    $query->where('status', 'Active');
               }) 
               ->get()
               ->map(function ($employee) 
               {
                    $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                    $employee->Emp_id = $employee->Emp_id;
                    $employee->Department_name = $employee->department->name;
                    $employee->Position_name = $employee->position->position_title;

                    $employee->EmployeeAttandance->each(function ($attendance) use ($employee){
                           if ($attendance->OTStatus =="Approved" &&  $employee->entitled_overtime  =="no" ) 
                              {
                                   Compliance::firstOrCreate([
                                        'resort_id' => $this->resort->resort_id,
                                        'employee_id' => $employee->id,
                                        'module_name' => 'Time and Attendance',
                                        'compliance_breached_name' => 'Over Time Not Eligibile',
                                        'description' => "{$employee->Emp_name} ({$employee->Emp_id} - {$employee->Position_name}) is not eligible for overtime.",
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Compliant'
                                   ]);
                              }
                    });
                    // Check if the employee is eligible for overtime
                  
                    return $employee;
               });

               // Extra leave day if a public holiday falls during annual leave
               $employees     =    Employee::with(['EmployeeLeave'])
                                        ->where('resort_id', $this->resort->resort_id)
                                        ->where('status', 'Active')
                                        ->get()->map(function ($employee) {
                                             $complianceRecords       =    [];
                                             
                                             // Process each leave for this employee
                                             foreach ($employee->EmployeeLeave as $leave) {
                                                  $startDate          =    Carbon::parse($leave->from_date);
                                                  $endDate            =    Carbon::parse($leave->to_date);
                                                  
                                                  // Get holidays within this leave period
                                                  $holidays           =    ResortHoliday::select([
                                                                                'resortholidays.id',
                                                                                'resortholidays.PublicHolidaydate as date',
                                                                                'resortholidays.PublicHolidayName as title',
                                                                           ])
                                                                           ->where('resort_id', $this->resort->resort_id)
                                                                           ->whereRaw("DATE(PublicHolidaydate) BETWEEN ? AND ?", [
                                                                                $startDate->format('Y-m-d'),
                                                                                $endDate->format('Y-m-d')
                                                                           ])
                                                                           ->get();
                                                  
                                                  // Create compliance records for overlapping holidays
                                                  foreach ($holidays as $holiday)    {
                                                       $complianceRecords[]               =    [
                                                            'resort_id'                   =>   $this->resort->resort_id,
                                                            'employee_id'                 =>   $employee->id,
                                                            'module_name'                 =>   'Leave',
                                                            'compliance_breached_name'    =>   'Leave taken on holiday',
                                                            'description'                 =>   'In leave request ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d') . ', the ' . $holiday->title . ' on ' . $holiday->date . ' has been included in the leave.',
                                                            'reported_on'                 =>   Carbon::now(),
                                                            'status'                      =>   'Breached',
                                                       ];
                                                  }
                                             }
                                             
                                             // Bulk insert compliance records if any
                                             if (!empty($complianceRecords)) {
                                                  Compliance::insert($complianceRecords);
                                             }
                                             
                                             return $employee;
                                        });

               // Check for overlap with leave or notice periods.
                $employees     =    Employee::with(['EmployeeResignation','EmployeeLeave'])
                                        ->where('resort_id', $this->resort->resort_id)
                                        ->where('status', 'Active')
                                       ->whereHas('EmployeeResignation', function($query) {
                                             $query->where('status', 'Approved');
                                        })->get()->map(function ($employee) {
                                             $complianceRecords                      =    [];
                                             
                                             // Process each resignation for this employee
                                             foreach ($employee->EmployeeResignation as $resignation) {

                                                  $resignationDate    =    Carbon::parse($resignation->resignation_date);
                                                  $lastWorkingDay     =    Carbon::parse($resignation->last_working_day);

                                                  foreach ($employee->EmployeeLeave as $leave) {
                                                       $leaveStart    =    Carbon::parse($leave->from_date);
                                                       $leaveEnd      =    Carbon::parse($leave->to_date);
                                                       if (($leaveStart->between($resignationDate, $lastWorkingDay) || $leaveEnd->between($resignationDate, $lastWorkingDay) || ($leaveStart->lte($resignationDate) && $leaveEnd->gte($lastWorkingDay)))) {
                                                            $complianceRecords[]               =    [
                                                                 'resort_id'                   =>   $this->resort->resort_id,
                                                                 'employee_id'                 =>   $employee->id,
                                                                 'module_name'                 =>   'Resignation',
                                                                 'compliance_breached_name'    =>   'Leave taken on notice period',
                                                                 'description'                 =>   'In leave request ' . $leaveStart->format('Y-m-d') . ' to ' . $leaveEnd->format('Y-m-d') . ', the employee has taken leave during their notice period (resignation date: ' . $resignationDate->format('Y-m-d') . ', last working day: ' . $lastWorkingDay->format('Y-m-d') . ').',
                                                                 'reported_on'                 =>   Carbon::now(),
                                                                 'status'                      =>   'Breached',
                                                            ];
                                                       }
                                                  }
                                             }
                                           
                                           // Bulk insert compliance records if any
                                            if (!empty($complianceRecords)) {
                                                  Compliance::insert($complianceRecords);
                                             }
                                             
                                             return $employee;
                                        });

          return redirect()->route('people.compliance.index')->with('success', 'Compliance checks completed successfully.');  
     }

     public function isExpired($date)
     {
          return  Carbon::parse($date)->lt(Carbon::today());
     }
     function stringifyExpiryDocument(array $expiryDocument, bool $asArray = true)
     {
         $out = [];
          foreach ($expiryDocument as $employeeName => $docs) 
          {

               // Build the part after "EmployeeName: "
               $docParts = array_map(function ($doc) {
                    return sprintf(
                         '%s — %s, MVR %s',
                         $doc['type'],
                         $doc['ExpiryDate'],
                         $doc['amount']
                    );
               }, $docs);

               $out = $employeeName . ': ' . implode('; ', $docParts);
          }

          // Return as‑is (separate strings) or one single string
          return $asArray ? $out : implode("\n", $out);
     }
     function getFormattedExpiryStatus($endDate)
     {
          $start = Carbon::today();
          $end = Carbon::parse($endDate);
          $daysDiff = $start->diffInDays($end, false);
          if ($daysDiff < 0) 
          {
               return $end->format('d M Y')."  (Expired " . abs($daysDiff) . " days ago)";
          }
          else
          {
               return $end->format('d M Y')."  (Expires in " . ($daysDiff ) . " days)";
          }


     }
     public function download(Request $request)
     {
          $resort = $this->resort;
          
          $query = Compliance::where('resort_id', $resort->resort_id)->with([
               'employee.resortAdmin',
          ]);
          
          if ($request->searchTerm != null) {
               $searchTerm = $request->searchTerm;
               $query->whereHas('employee', function ($q) use ($searchTerm) {   
                    $q->whereHas('resortAdmin', function ($Qname) use ($searchTerm) {
                         $Qname->where('id', 'LIKE', "%{$searchTerm}%")
                              ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")     
                              ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                              ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$searchTerm}%");
                    });
               });
          }
          
          if ($request->status != null) {
               $query->where('status', $request->status);
          }
          
          if ($request->module_name != null) {
               $query->where('module_name', $request->module_name);
          }
          
          if ($request->compliance_breached_name != null) {
               $query->where('compliance_breached_name', $request->compliance_breached_name);
          }
          
          $compliances = $query->whereHas('employee.resortAdmin')->get();
          
          // Transform data similar to how the DataTable displays it
          $formattedData = [];
          foreach ($compliances as $index => $compliance) {
               $formattedData[] = [
                    'no' => $index + 1,
                    'module_name' => $compliance->module_name,
                    'compliance_breached_name' => $compliance->compliance_breached_name,
                    'employee_id' => $compliance->employee ? $compliance->employee->Emp_id : 'N/A',
                    'employee_name' => $compliance->employee ? $compliance->employee->resortAdmin->full_name : 'N/A',
                    'description' => $compliance->description,
                    'reported_on' => $compliance->reported_on ? Carbon::parse($compliance->reported_on)->format('Y-m-d H:i:s') : 'N/A',
                    'status' => $compliance->status
               ];
          }
          
          // Generate PDF
          $data = [
               'compliances' => $formattedData,
               'title' => 'Compliance Report',
               'date' => Carbon::now()->format('Y-m-d')
          ];
          
          $pdf = PDF::loadView('resorts.people.compliance.pdf', $data);
          return $pdf->download('compliance-report-' . Carbon::now()->format('Y-m-d') . '.pdf');
     }


     public function test(){
          
          $resort = $this->resort;

          $employees = Employee::where('resort_id',$resort->resort_id)->where('status','Active')->get();

          foreach($employees as $employee) {
               $emp_grade = Common::getEmpGrade($employee->rank);
               $leaveCategory = LeaveCategory::where('resort_id', $resort->resort_id)
                    ->where(function($query) use ($emp_grade) {
                         $query->where('eligibility', $emp_grade)
                              ->orWhereRaw("FIND_IN_SET(?, eligibility)", [$emp_grade]);
                    })->get();

               if($leaveCategory) {

                    $sickLeaveCategory = $leaveCategory->where('leave_type', 'Sick Leave')->first();
                    $sickLeaveEmployees = EmployeeLeave::where('resort_id', $resort->resort_id)->where('leave_category_id',$sickLeaveCategory->id)->where('emp_id',$employee->id)->get();
                    if($sickLeaveEmployees->count() > 0) {
                         foreach ($sickLeaveEmployees as $employeeLeave) {

                              if($employeeLeave->total_days <= 2){
                                   $leaveDate = Carbon::parse($employeeLeave->from_date);
                                   $leaveMonth = $leaveDate->month;
                                   $leaveYear = $leaveDate->year;

                                   $checkCurrentMonthLeaves = EmployeeLeave::where('resort_id', $resort->resort_id)
                                        ->where('emp_id', $employeeLeave->emp_id)
                                        ->where('leave_category_id', $employeeLeave->leave_category_id)
                                        ->whereMonth('from_date', $leaveMonth)
                                        ->whereYear('from_date', $leaveYear)
                                        ->where('total_days','<=',2)
                                        ->get();

                                   $sickLeaveDaysInCurrentMonth = $checkCurrentMonthLeaves->sum('total_days');

                                   if ($sickLeaveDaysInCurrentMonth >= 15) {
                                        $compliance = Compliance::firstOrCreate([
                                        'resort_id' => $resort->resort_id,
                                        'employee_id' => $employeeLeave->emp_id,
                                        'module_name' => 'Sick Leave',
                                        'compliance_breached_name' => 'Medical certificates Required',
                                        'description' => "Employee {$employee->resortAdmin->full_name} has taken more than {$sickLeaveDaysInCurrentMonth} days of Sick Leave without a medical certificate.",
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached'
                                        ]);
                                   }
                              }

                              if($employeeLeave->total_days > 2){
                              
                                   $compliance = Compliance::firstOrCreate([
                                        'resort_id' => $resort->resort_id,
                                        'employee_id' => $employeeLeave->emp_id,
                                        'module_name' => 'Sick Leave',
                                        'compliance_breached_name' => 'Medical certificates Required',
                                        'description' => "Employee {$employee->resortAdmin->full_name} has taken more than {$sickLeaveDaysInCurrentMonth} days of Sick Leave without a medical certificate.",
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached'
                                   ]);
                              }


                         }
                    }

                    // Check if employee has completed at least one year in the company
                    if($employee->joining_date && Carbon::parse($employee->joining_date)->diffInYears(Carbon::now()) >= 1) {
                         $annualLeaveCategory = $leaveCategory->where('leave_type', 'Annual Leave')->first();
                         $annualLeaveEmployees = EmployeeLeave::where('resort_id', $resort->resort_id)
                              ->where('leave_category_id', $annualLeaveCategory->id)
                              ->where('emp_id', $employee->id)
                              ->whereYear('from_date', Carbon::now()->year)
                              ->get();

                         if($annualLeaveEmployees->count() > 0) {     
                              $totalLeavs = $annualLeaveEmployees->sum('total_days');
                              if($totalLeavs == $annualLeaveCategory->number_of_days){

                              }elseif($totalLeavs < $annualLeaveCategory->number_of_days){

                              }
                         }
                    }  


               }

          }

          $LeaveCategory = LeaveCategory::where('resort_id', $resort->resort_id)->where('leave_type','Annual Leave')->first();
          $sickLeaveEmployees = EmployeeLeave::where('resort_id', $resort->resort_id)->where('leave_category_id',$LeaveCategory->id)->get();


          return 'sada';
          // Sick Leave Compliance Start
               $sickLeaveCategory = LeaveCategory::where('resort_id', $resort->resort_id)->where('leave_type','Sick Leave')->first();
               $sickLeaveEmployees = EmployeeLeave::where('resort_id', $resort->resort_id)->where('leave_category_id',$sickLeaveCategory->id)
               ->get();
               

               foreach ($sickLeaveEmployees as $employeeLeave) {

                    if($employeeLeave->total_days <= 2){
                          $leaveDate = Carbon::parse($employeeLeave->from_date);
                          $leaveMonth = $leaveDate->month;
                          $leaveYear = $leaveDate->year;

                         $checkCurrentMonthLeaves = EmployeeLeave::where('resort_id', $resort->resort_id)
                              ->where('emp_id', $employeeLeave->emp_id)
                              ->where('leave_category_id', $employeeLeave->leave_category_id)
                              ->whereMonth('from_date', $leaveMonth)
                              ->whereYear('from_date', $leaveYear)
                              ->where('total_days','<=',2)
                              ->get();

                         $sickLeaveDaysInCurrentMonth = $checkCurrentMonthLeaves->sum('total_days');

                         // Check if employee has taken more than 2 sick leaves in current month
                         if ($sickLeaveDaysInCurrentMonth >= 15) {
                              $compliance = Compliance::firstOrCreate([
                              'resort_id' => $resort->resort_id,
                              'employee_id' => $employeeLeave->employee_id,
                              'module_name' => 'Sick Leave',
                              'compliance_breached_name' => 'Medical Certificate',
                              'description' => "Employee {$employeeLeave->employee->resortAdmin->full_name} has taken more than 2 Sick Leaves without a medical certificate.",
                              'reported_on' => Carbon::now(),
                              'status' => 'Breached'
                         ]);
                         }
                    }
                    // $check_medical_cetificate = ;

                    if($check_medical_cetificate == false) {
                         $compliance = Compliance::firstOrCreate([
                              'resort_id' => $resort->resort_id,
                              'employee_id' => $employeeLeave->employee_id,
                              'module_name' => 'Sick Leave',
                              'compliance_breached_name' => 'Medical Certificate',
                              'description' => "Employee {$employeeLeave->employee->resortAdmin->full_name} has taken more than 2 Sick Leaves without a medical certificate.",
                              'reported_on' => Carbon::now(),
                              'status' => 'Breached'
                         ]);
                    }
               }
          // Sick Leave Compliance End
     }


     public function Calendar()
     {
          $resort = $this->resort;
         
          $compliances = Compliance::where('resort_id', $resort->resort_id)
               ->with(['employee.resortAdmin'])
               ->get()
               ->map(function ($compliance) {
                    return [
                         'title' => $compliance->compliance_breached_name,
                         'start' => Carbon::parse($compliance->reported_on)->format('Y-m-d'),
                         'end' => Carbon::parse($compliance->reported_on)->format('Y-m-d'),
                         'description' => $compliance->description,
                         'employee_name' => $compliance->employee ? $compliance->employee->resortAdmin->full_name : 'N/A',
                         'status' => $compliance->status
                    ];
               });

          return view('resorts.people.compliance.calendar', compact('compliances'));
     }

     public function DismissCompliance($id)
     {
          $compliance = Compliance::find(base64_decode($id));
          if ($compliance) {
              $compliance->Dismissal_status = 'Rejected';
              $compliance->save();
 
               return response()->json([
                   'success' => true,
                   'message' => 'Compliance dismissed successfully.'
               ],200);
          }
          else
          {
               return response()->json([
                   'success' => false,
                   'errors' => $validator->errors()
               ], 422);
          }

         
     }
}