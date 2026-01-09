<?php

namespace App\Http\Controllers\Resorts\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Earnings;
use App\Models\Deduction;
use App\Models\PayrollConfig;
use App\Models\PublicHoliday;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use App\Models\ResortDepartment;
use App\Models\ParentAttendace;
use App\Models\ResortPosition;
use App\Models\ResortSection;
use App\Models\Payment;
use App\Models\ResortSiteSettings;
use App\Models\Payroll;
use App\Models\PayrollAttendanceActivityLog;
use App\Models\PayrollDeduction;
use App\Models\PayrollEmployees;
use App\Models\PayrollReview;
use App\Models\PayrollReviewAllowances;
use App\Models\PayrollServiceCharge;
use App\Models\PayrollTimeAndAttendance;
use App\Models\PayrollRecoverySchedule;
use App\Models\ResortBudgetCost;
use App\Models\ResortBenifitGrid;
use App\Models\ResortBenifitGridChild;
use App\Models\EmployeeAllowance;
use App\Models\ServiceCharges;
use App\Models\Compliance;
use App\Events\ResortNotificationEvent;

use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use Auth;
use Config;
use DB;
use Common;

class PayrollController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function index(Request $request)
    {
        $page_title ='Payroll Run';
        $resort_id = $this->resort->resort_id;
       
        $settings = ResortSiteSettings::where('resort_id', $resort_id)->first();
        $currency = $settings['currency'];
        // dd($settings);
        $employees = Employee::with('resortAdmin')->where('resort_id',$resort_id)->whereIn('status', ['Active', 'Probationary','Resigned'])->get();
        $deductions = Deduction::where('resort_id',$resort_id)->get();
        // $earnings = Earnings::where('resort_id',$resort_id)->get();
        // $allowances = ResortBudgetCost::where('resort_id',$resort_id)->get();
        $positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
        $sections = ResortSection::where('status','active')->where('resort_id',$resort_id)->get();
        $cut_off_date = PayrollConfig::where('resort_id',$resort_id)->first();// Default to 15 if not set
        $cutoffDay = $cut_off_date ? $cut_off_date->cutoff_day : 15;
        return view('resorts.payroll.run.index',compact('page_title','positions','departments','sections','deductions','employees','currency','cutoffDay'));
    }

    public function getEmployees(Request $request)
    {
        $resort_id = auth()->user()->resort_id;
        $isChecked = $request->isChecked;
        $query = Employee::with(['resortAdmin', 'position', 'department'])
            ->where('resort_id', $resort_id)
            ->whereIn('status', ['Active', 'Probationary','Resigned']);

        if ($request->searchTerm) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('resortAdmin', function ($q2) use ($request) {
                    $q2->where('first_name', 'LIKE', "%{$request->searchTerm}%")
                    ->orWhere('last_name', 'LIKE', "%{$request->searchTerm}%");
                })->orWhereHas('department', function ($q2) use ($request) {
                    $q2->where('name', 'LIKE', "%{$request->searchTerm}%");
                })->orWhereHas('position', function ($q2) use ($request) {
                    $q2->where('position_title', 'LIKE', "%{$request->searchTerm}%");
                });
            });
        }

        if ($request->department) {
            $query->where('Dept_id', $request->department);
        }

        if ($request->position) {
            $query->orwhere('Position_id', $request->position);
        }

        $query = $query->get()
        ->map(function($i) use($isChecked){
    
            
                $i->isChecked = $isChecked;
            return $i;
        });
        // dd($query);
        $totalChecked = 0; // Initialize counter before processing rows

        $datatable = datatables()->of($query)
        ->addColumn('id', function ($employee) use (&$totalChecked) {
            $isChecked = ($employee->isChecked == "true") ? "checked" : "";

            // ✅ Increment total checked count
            if ($employee->isChecked == "true") {
                $totalChecked++;
            }

            return '<div class="form-check no-label">
                        <input class="form-check-input" type="checkbox" value="'.$employee->id.'" '.$isChecked.'>
                    </div>';
        })
        ->addColumn('employee', function ($employee) {
            return [
                'first_name' => $employee->resortAdmin->first_name ?? 'N/A',
                'last_name' => $employee->resortAdmin->last_name ?? 'N/A',
                'profile_picture' => Common::getResortUserPicture($employee->Admin_Parent_id),
            ];
        })
        ->addColumn('position', function ($employee) {
            return [
                'postion_title' => $employee->position->position_title ?? 'N/A',
                'position_code' => $employee->position->code ?? 'N/A',
            ];
        })
        ->addColumn('department', function ($employee) {
            return [
                'department_name' => $employee->department->name ?? 'N/A',
                'department_code' => $employee->department->code ?? 'N/A',
            ];
        })
        ->addColumn('payment_method', function ($employee) {
            return $employee->payment_mode; // Adjust based on actual logic
        })
        ->rawColumns(['id', 'employee', 'position', 'department', 'payment_method'])
        ->make(true);

        // ✅ Inject totalChecked into the JSON response
        $jsonData = $datatable->getData();
        $jsonData->totalChecked = $totalChecked; // ✅ Add totalChecked count

        return response()->json($jsonData);
    }

    // public function saveDraftPayroll(Request $request)
    // {
    //     $request->validate([
    //         'start_date' => 'required|date',
    //         'end_date' => 'required|date',
    //         'status' => 'required|string'
    //     ]);

    //     try {
            
    //         // Save payroll as draft
    //         $payroll = Payroll::updateOrCreate([
    //             'resort_id'=>$this->resort->resort_id,
    //             'start_date' => $request->start_date,
    //             'end_date' => $request->end_date,
    //             'status' => 'draft', 
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Payroll draft saved successfully.',
    //             'payroll_id' => $payroll->id
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error saving payroll draft: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function saveDraftPayroll(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'status' => 'required|string'
        ]);

        try {
            $resortId = $this->resort->resort_id;

            // ❌ Check if a locked payroll already exists for the same date range
            $lockedPayrollExists = Payroll::where('resort_id', $resortId)
                ->where('start_date', $request->start_date)
                ->where('end_date', $request->end_date)
                ->where('status', 'locked')
                ->exists();

            if ($lockedPayrollExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payroll is locked for the selected date range and cannot be edited.'
                ], 403);
            }

            // ✅ Proceed to save or update draft
            $payroll = Payroll::updateOrCreate(
                [
                    'resort_id' => $resortId,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date
                ],
                [
                    'status' => 'draft'
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Payroll draft saved successfully.',
                'payroll_id' => $payroll->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving payroll draft: ' . $e->getMessage()
            ], 500);
        }
    }

    public function saveEmployeesToPayroll(Request $request)
    {
        try {
            foreach ($request->employees as $employee) {
                // Fetch Employee details along with position and department
                $emp_detail = Employee::with(['position', 'department'])->where('Emp_id', $employee['id'])->first();

                if (!$emp_detail) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Employee not found for ID: ' . $employee['id']
                    ], 404);
                }

                // Check if section exists, otherwise set it to NULL
                $sectionId = isset($emp_detail['section']) ? $emp_detail['section'] : null;

                // Insert or Update payroll employee record
                PayrollEmployees::updateOrCreate(
                    [
                        'payroll_id' => $request->payroll_id,
                        'employee_id' => $emp_detail->id,
                    ],
                    [
                        'Emp_id' => $employee['id'],
                        'position' => $emp_detail->position->id,
                        'department' => $emp_detail->department->id,
                        'section' => $sectionId, // NULL if section doesn't exist
                        'paymentMethod' => $employee['paymentMethod'],
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Employees successfully added to payroll.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving employees: ' . $e->getMessage()
            ], 500);
        }
    }

    public function saveAttendanceToPayroll(Request $request)
    {
        DB::beginTransaction(); // ✅ Start transaction for data consistency

        try {
            $activityLog = []; // ✅ Array to track changes

            foreach ($request->attendance as $attendance) {
                // ✅ Fetch Employee details
                $emp_detail = Employee::with(['position', 'department'])
                    ->where('Emp_id', $attendance['id'])
                    ->first();

                if (!$emp_detail) {
                    DB::rollBack(); // Rollback transaction if employee not found
                    return response()->json([
                        'success' => false,
                        'message' => 'Employee not found for ID: ' . $attendance['id']
                    ], 404);
                }

                // ✅ Compute total OT dynamically
                $regularOT = $attendance['regularOT'] ?? 0;
                $holidayOT = $attendance['holidayOT'] ?? 0;
                $totalOT = $regularOT + $holidayOT; // ✅ Auto-update total OT

                // ✅ Prepare Updated Data
                $updatedData = [
                    'Emp_id' => $attendance['id'],
                    'present_days' => $attendance['present'] ?? 0,
                    'absent_days' => $attendance['absent'] ?? 0,
                    'leave_types' => $attendance['leaveTypes'] ?? '',
                    'regular_ot_hours' => $regularOT,
                    'holiday_ot_hours' => $holidayOT,
                    'total_ot' => $totalOT, // ✅ Auto-updated
                    'notes' => $attendance['notes'] ?? null,
                ];

                // ✅ Fetch Existing Payroll Data for Comparison
                $existingRecord = PayrollTimeAndAttendance::where([
                    'payroll_id' => $request->payroll_id,
                    'employee_id' => $emp_detail->id
                ])->first();

                if ($existingRecord) {
                    foreach ($updatedData as $key => $value) {
                        if ($existingRecord->$key != $value) { // Only log changes
                            $activityLog[] = [
                                'resort_id'=>$this->resort->resort_id,
                                'payroll_id'=>$request->payroll_id,
                                'user_id' => $this->resort->GetEmployee->id, // Track who made changes
                                'employee_id' => $emp_detail->id,
                                'field' => $key,
                                'old_value' => $existingRecord->$key,
                                'new_value' => $value,
                                'updated_at' => now(),
                            ];
                        }
                    }
                }  // ✅ Insert or Update Payroll Attendance Data
                PayrollTimeAndAttendance::updateOrCreate(
                    [
                        'payroll_id' => $request->payroll_id,
                        'employee_id' => $emp_detail->id,
                    ],
                    $updatedData
                );
            }
            
             // ✅ Save Activity Log if Changes Were Made
        if (!empty($activityLog)) {
            DB::table('payroll_attendance_activity_log')->insert($activityLog);
        }
            DB::commit(); // ✅ Commit transaction

            return response()->json([
                'success' => true,
                'message' => 'Employees Attendance successfully added to payroll.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // ✅ Rollback in case of error
            Log::error('Error saving Attendance: ' . $e->getMessage()); // ✅ Log error for debugging

            return response()->json([
                'success' => false,
                'message' => 'Error saving Attendance. Please try again.'
            ], 500);
        }
    }

    public function saveAttendanceNote(Request $request)
    {
        try {
            // ✅ Fetch attendance record (DO NOT reset existing data)
            $attendance = PayrollTimeAndAttendance::where([
                'payroll_id' => $request->payroll_id,
                'employee_id' => $request->empid
            ])->first();

            // dd($attendance);

            if ($attendance) {
                // ✅ Update ONLY the notes field, keeping other data unchanged
                $attendance->notes = $request->note;
                $attendance->save();
            } else {
                // ✅ Create new record WITH frontend-passed values
                $attendance = PayrollTimeAndAttendance::create([
                    'payroll_id' => $request->payroll_id,
                    'employee_id' => $request->employee_id,
                    'present_days' => $request->present ?? 0,
                    'absent_days' => $request->absent ?? 0,
                    'leave_types' => $request->leave_type ?? '',
                    'regular_ot_hours' => $request->regular_ot ?? 0,
                    'holiday_ot_hours' => $request->holiday_ot ?? 0,
                    'total_ot' => $request->total_ot ?? 0,
                    'notes' => $request->note
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Note saved successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save note. Please try again.'
            ], 500);
        }
    }

    public function getEligibleEmployees(Request $request)
    {
        $employeeIds = $request->input('employee_ids', []);
        // dd($employeeIds);
        $resortId = $this->resort->resort_id;

        $ids = collect($employeeIds)->map(function ($item) {
            $decoded = json_decode($item, true);
            return $decoded['id'] ?? null;
        })->filter()->values()->toArray();


        $eligibleEmployeeIds = [];
        // dd($ids);
        $employees = Employee::whereIn('Emp_id', $ids)->get();

        foreach ($employees as $employee) {
            // Check employee's emp_grade and find matching benefit grid
            $grid = ResortBenifitGrid::where('resort_id', $resortId)
                ->where('emp_grade', $employee->benefit_grid_level)
                ->where('service_charge', 1)
                ->where('status', 'Active') // Ensure the grid is active
                ->first();

            if ($grid) {
                $eligibleEmployeeIds[] = (string)$employee->Emp_id; // Cast to string for front-end consistency
            }
        }

        return response()->json($eligibleEmployeeIds);
    }

    public function saveServiceChargesToPayroll(Request $request)
    {
        DB::beginTransaction(); // ✅ Start transaction for data consistency
        $payroll = Payroll::findOrFail($request->payroll_id);
        // Extract month and year from payroll dates for service charge calculations
        $payrollDate = Carbon::parse($payroll->start_date);
        $month = $payrollDate->month;
        $year = $payrollDate->year;

        // dd($month, $year);

        // Import the ServiceCharges model if not already available
        // dd($request->all());
        try {
            foreach ($request->ServiceChargesData as $serviceCharge) {
                // dd($serviceCharge);
                ServiceCharges::updateOrCreate(
                    [
                        'resort_id' => $this->resort->resort_id,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'service_charge' => $serviceCharge['totalServiceCharge'],
                    ]
                );
                // ✅ Fetch Employee details
                $emp_detail = Employee::with(['position', 'department'])
                    ->where('Emp_id', $serviceCharge['id'])
                    ->first();

                if (!$emp_detail) {
                    DB::rollBack(); // Rollback transaction if employee not found
                    return response()->json([
                        'success' => false,
                        'message' => 'Employee not found for ID: ' . $serviceCharge['id']
                    ], 404);
                }

                PayrollServiceCharge::updateOrCreate(
                    [
                        'payroll_id' => $request->payroll_id,
                        'employee_id' => $emp_detail->id,
                    ],
                    [
                        'Emp_id' => $serviceCharge['id'],
                        'total_working_days' => $serviceCharge['totalWorkingDays'],
                        'service_charge_amount' => $serviceCharge['serviceCharge'],
                    ]
                );

               
            }
        
            DB::commit(); // ✅ Commit transaction
            $notify_person = Employee::where('resort_id', $this->resort->resort_id)->where('rank','3')->first();
            if(!$notify_person) 
            {
                $notify_person = Employee::where('resort_id', $this->resort->resort_id)->where('rank','2')->first();
            }
                $payroll_service_charges = PayrollServiceCharge::where("Emp_id",$emp_detail->id)->where('payroll_id',$request->payroll_id)->get();
                foreach ($payroll_service_charges as $payroll) 
                {
                    $employee           =  Employee::where('id', $payroll->employee_id)->where('resort_id', $this->resort->resort_id)->first();
                    $grade              =  Common::getEmpGrade($employee->rank);
                    $resortBenifitsGrid =  ResortBenifitGrid::where('resort_id', $this->resort->resort_id)->where('emp_grade', $grade)->where('service_charge', '1')->where('status','Active')->first();
                    
                    if($resortBenifitsGrid && $payroll->service_charge_amount > 0) 
                    {
                            Compliance::create([
                                'resort_id' => $this->resort->resort_id,
                                'employee_id' => $employee->id,
                                'module_name' => 'Payroll',
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
                                'Payroll Minimum Wage Complianc'
                            )));
                        
                    }
                }
               
            return response()->json([
                'success' => true,
                'message' => 'Employees Service charges successfully added to payroll.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // ✅ Rollback in case of error
            Log::error('Error saving Service charges: ' . $e->getMessage()); // ✅ Log error for debugging

            return response()->json([
                'success' => false,
                'message' => 'Error saving Service charges. Please try again.'
            ], 500);
        }
    }

    public function saveDeductionsToPayroll(Request $request)
    {
        DB::beginTransaction(); // ✅ Start transaction for data consistency
        // dd($request->all());
        try {
            foreach ($request->DeductionData as $deduction) {
                // ✅ Fetch Employee details
                $emp_detail = Employee::with(['position', 'department'])
                    ->where('Emp_id', $deduction['id'])
                    ->first();

                if (!$emp_detail) {
                    DB::rollBack(); // Rollback transaction if employee not found
                    return response()->json([
                        'success' => false,
                        'message' => 'Employee not found for ID: ' . $deduction['id']
                    ], 404);
                }

           
                PayrollDeduction::updateOrCreate(
                    [
                        'payroll_id' => $request->payroll_id,
                        'employee_id' => $emp_detail->id,
                    ],
                    [
                        'Emp_id' => $deduction['id'],
                        'attendance_deduction' => $deduction['attendanceDeduction'],
                        'city_ledger' => $deduction['cityLedger'],
                        'staff_shop' => $deduction['staffShop'],
                        'advance_loan' => $deduction['advanceLoan'],
                        'pension' => $deduction['pension'],
                        'ewt' => $deduction['ewt'],
                        'other' => $deduction['other'],
                        'total_deductions' => $deduction['total'],
                    ]
                );
                if($emp_detail->nationality == 'Maldivian' &&  in_array($deduction['pension'] , ["MVR 0.00" , "0.00"]))
                {
                    $notify_person = Employee::where('resort_id', $this->resort->resort_id)->where('rank','3')->first();
                    if (!$notify_person) 
                    {
                         $notify_person = Employee::where('resort_id',$this->resort->resort_id)->where('rank','2')->first();
                    }
                    
                    $newemployee = Employee::with(['resortAdmin','position','department'])->where('resort_id', $this->resort->resort_id)->where('id',$emp_detail->id)->first();
                      event(new ResortNotificationEvent(Common::nofitication(
                            $this->resort->resort_id,
                            10,
                            'Payroll Pension Compliance',
                            "Employee {$newemployee->resortAdmin->full_name}  ({$newemployee->position->position_title}) is Maldivian but has no pension deduction applied. Maldivian regulations require a mandatory 7% pension contribution for all Maldivian employees.",
                            $notify_person->id,
                            'Payroll'
                        
                    )));

                    $compliance = Compliance::Create([
                            'resort_id' => $this->resort->resort_id,
                            'employee_id' =>$newemployee->id,
                            'module_name' => 'Payroll',
                            'compliance_breached_name' => 'Payroll Pension Compliance',
                            "Employee {$newemployee->resortAdmin->full_name}  ({$newemployee->position->position_title}) is Maldivian but has no pension deduction applied. Maldivian regulations require a mandatory 7% pension contribution for all Maldivian employees.",
                            'reported_on' => Carbon::now(),
                            'status' => 'Breached'
                    ]);
                }



              
            }
        
            DB::commit(); // ✅ Commit transaction



            return response()->json([
                'success' => true,
                'message' => 'Employees Deductions successfully added to payroll.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // ✅ Rollback in case of error
            Log::error('Error saving Deductions: ' . $e->getMessage()); // ✅ Log error for debugging

            return response()->json([
                'success' => false,
                'message' => 'Error saving Deductions. Please try again.'
            ], 500);
        }
    }

    public function saveReviewsToPayroll(Request $request)
    {
        DB::beginTransaction(); // ✅ Start transaction for data consistency
        // dd($request->reviewData);
        try {
            foreach ($request->reviewData as $review) {
                $total_earnings = $review['earnedSalary'] + $review['overtimeTotal'] + $review['serviceCharge'] + $review['earningsAllowance'];
                $total_deductions = $review['totalDeductions'] ?? 0;
                // dd($review);
                // ✅ Fetch Employee details
                $emp_detail = Employee::with(['position', 'department'])
                    ->where('Emp_id', $review['id'])
                    ->first();

                if (!$emp_detail) {
                    DB::rollBack(); // Rollback transaction if newemployee not found
                    return response()->json([
                        'success' => false,
                        'message' => 'Employee not found for ID: ' . $review['id']
                    ], 404);
                }

              $reviewRecord = PayrollReview::updateOrCreate(
                    [
                        'payroll_id' => $request->payroll_id,
                        'employee_id' => $emp_detail->id,
                    ],
                    [
                        'Emp_id' => $review['id'],
                        'service_charge' => $review['serviceCharge'],
                        'regularOTPay' => $review['overtimeNormal'],
                        'holidayOTPay' => $review['overtimeHoliday'],
                        'earnings_basic' => $review['earningsBasic'],
                        'earned_salary' => $review['earnedSalary'],
                        'earnings_allowance' => $review['earningsAllowance'],
                        'earnings_overtime' => $review['overtimeTotal'],
                        'total_earnings' => $total_earnings,
                        'total_deductions' => $total_deductions,
                        'net_salary' => $total_earnings - $total_deductions,
                    ]
                );

            //    here minimum wage check
            $minWageMVR = 8021; // Minimum wage in MVR
               $minWageUSD = 520; // Minimum wage in MVR

               $employeesBelowMinWageInMVR = Employee::where('resort_id', $this->resort->resort_id)
                    ->where('basic_salary', '<', $minWageMVR)
                    ->where('basic_salary_currency', 'MVR')
                    ->get();

               $employeesBelowMinWageInUSD = Employee::where('resort_id', $this->resort->resort_id)
                    ->where('basic_salary', '<', $minWageUSD) // Assuming you have a conversion rate set in your config
                    ->where('basic_salary_currency', 'USD')
                    ->get();
               $employeesBelowMinWage = $employeesBelowMinWageInMVR->merge($employeesBelowMinWageInUSD);
               if ($employeesBelowMinWage->isNotEmpty()) {
                    foreach ($employeesBelowMinWage as $employee) 
                    {
                         $compliance = Compliance::firstOrCreate([
                              'resort_id' => $this->resort->resort_id,
                              'employee_id' => $employee->id,
                              'module_name' => 'Workforce Planning',
                              'compliance_breached_name' => 'Minimum Wage',
                              'description' => "Employee {$employee->resortAdmin->full_name} has a basic salary below the minimum wage.",
                              'reported_on' => Carbon::now(),
                              'status' => 'Breached'
                         ]);
                    }
               }

                // Delete previous allowances to avoid duplicates
                PayrollReviewAllowances::where('payroll_review_id', $reviewRecord->id)->delete();

                // Save new dynamic allowances
                if (!empty($review['allowances'])) {
                    foreach ($review['allowances'] as $allowance) {
                        // dd($allowance);
                        PayrollReviewAllowances::create([
                            'payroll_review_id' => $reviewRecord->id,
                            'allowance_type' => $allowance['type'],
                            'amount' => $allowance['amount'],
                            'amount_unit' => $allowance['amount_unit'],
                        ]);
                    }
                }
            }
        
            DB::commit(); // ✅ Commit transaction

            return response()->json([
                'success' => true,
                'message' => 'Employees Earning Review successfully added to payroll.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // ✅ Rollback in case of error
            Log::error('Error saving Review: ' . $e->getMessage()); // ✅ Log error for debugging

            return response()->json([
                'success' => false,
                'message' => 'Error saving Review. Please try again.'
            ], 500);
        }
    }

    public function fetchTotalPayrollAmount(Request $request)
    {
        $request->validate([
            'payrollId' => 'required|exists:payroll,id',
        ]);

        $payrollId = $request->payrollId;

        // Sum net salary from payroll_review table
        $totalPayroll = PayrollReview::where('payroll_id', $payrollId)
            ->sum('net_salary');

        // Optional: Count employees
        $totalEmployees = PayrollReview::where('payroll_id', $payrollId)->count();

        return response()->json([
            'success' => true,
            'total_payroll' => round($totalPayroll, 2),
            'total_employees' => $totalEmployees,
        ]);
    }

    public function saveSummaryToPayroll(Request $request)
    {
        DB::beginTransaction(); // ✅ Start transaction for data consistency
        // dd($request->all());
        try {
            
            Payroll::updateOrCreate(
                [
                    'id' => $request->payroll_id,
                ],
                [
                    'total_payroll' => $request['summaryData']['totalPayrollAmount'],
                    'total_employees' => $request['summaryData']['totalEmployees'],
                    'draft_date' => $request['summaryData']['payrollDraftDate'],
                    // 'payment_date' => $request['summaryData']['payrollPaymentDate'],
                    'status' => 'locked'
                ]
            );
            $payrollId = $request->payroll_id;
            $payroll = Payroll::findOrFail($payrollId);
            $deductions = PayrollDeduction::where('payroll_id', $payrollId)->get();
            foreach ($deductions as $deduction) {
                $employeeId = $deduction->employee_id;
                $advanceLoanAmount = $deduction->advance_loan;
                $staffshoptAmount = $deduction->staff_shop;

                if ($advanceLoanAmount > 0) {
                    PayrollRecoverySchedule::where('employee_id', $employeeId)
                        ->where('status', 'pending')
                        ->whereBetween('repayment_date', [$payroll->start_date, $payroll->end_date])
                        ->limit(1) // in case of partial deduction logic
                        ->update([
                            'status' => 'Paid',
                        ]);
                }

                if ($staffshoptAmount > 0) {
                    Payment::where('emp_id', $employeeId)
                        ->where('status', 'pending')
                        ->whereBetween('purchased_date', [$payroll->start_date, $payroll->end_date])
                        ->limit(1) // in case of partial deduction logic
                        ->update([
                            'status' => 'Paid',
                        ]);
                }
            }
        
            DB::commit(); // ✅ Commit transaction

            return response()->json([
                'success' => true,
                'message' => 'Payroll Locked Successfully.',
                'redirect_url' => route('payroll.view', ['payroll_id' => base64_encode($request->payroll_id)])
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // ✅ Rollback in case of error
            Log::error('Error for payroll locking: ' . $e->getMessage()); // ✅ Log error for debugging

            return response()->json([
                'success' => false,
                'message' => 'Error locking Payroll. Please try again.'
            ], 500);
        }
    }

    public function getDraftStepData(Request $request)
    {
        $step = $request->step; // Get the step identifier
        $sessionData = Session::get('payroll_form', []);

        if (isset($sessionData[$step])) {
            return response()->json(['success' => true, 'data' => $sessionData[$step]]);
        }

        return response()->json(['success' => false, 'message' => 'No data found for this step.']);
    }

    // public function fetchTimeAttendance(Request $request)
    // {
    //     $request->validate([
    //         'employees' => 'required|array',
    //         'startDate' => 'required|date',
    //         'endDate' => 'required|date|after_or_equal:startDate',
    //     ]);

    //     $resortId = $this->resort->resort_id;
    //     $currency = $request->input('currency', 'Dollar');
    //     $conversionRate = floatval($request->input('conversionRate', 1));
    //     $settings = ResortSiteSettings::where('resort_id', $resortId)->first();

    //     // ✅ Fetch latest attendance updates from the log table
    //     $latestAttendanceUpdates = DB::table('payroll_attendance_activity_log')
    //         ->whereIn('employee_id', $request->employees)
    //         ->where('resort_id', $resortId)
    //         ->where('payroll_id', $request->payrollId)
    //         ->orderBy('updated_at', 'desc')
    //         ->get()
    //         ->groupBy('employee_id');

    //     // Fetch original attendance records
    //     $attendanceRecords = ParentAttendace::whereIn('Emp_id', $request->employees)
    //         ->whereBetween('date', [$request->startDate, $request->endDate])
    //         ->get();

    //     if ($attendanceRecords->isEmpty()) {
    //         return response()->json(['success' => false, 'message' => 'No attendance records found.'], 404);
    //     }

    //     $employeeQuery = Employee::with(['resortAdmin', 'department', 'position'])
    //         ->whereIn('id', $request->employees);

    //     // Apply Search Across Multiple Fields
    //     if ($request->has('searchTerm') && !empty($request->searchTerm)) {
    //         $searchTerm = strtolower($request->searchTerm);
        
    //         $employeeQuery->where(function ($query) use ($searchTerm, $attendanceRecords) {
    //             $query->whereHas('resortAdmin', function ($q) use ($searchTerm) {
    //                 $q->whereRaw("LOWER(first_name) LIKE ?", ["%{$searchTerm}%"])
    //                   ->orWhereRaw("LOWER(last_name) LIKE ?", ["%{$searchTerm}%"]);
    //             })
    //             ->orWhereHas('department', function ($q) use ($searchTerm) {
    //                 $q->whereRaw("LOWER(name) LIKE ?", ["%{$searchTerm}%"])
    //                   ->orWhereRaw("LOWER(code) LIKE ?", ["%{$searchTerm}%"]);
    //             })
    //             ->orWhereHas('position', function ($q) use ($searchTerm) {
    //                 $q->whereRaw("LOWER(position_title) LIKE ?", ["%{$searchTerm}%"])
    //                   ->orWhereRaw("LOWER(code) LIKE ?", ["%{$searchTerm}%"]);
    //             })
    //             ->orWhereRaw("LOWER(Emp_id) LIKE ?", ["%{$searchTerm}%"])
    //             ->orWhereRaw("LOWER(basic_salary) LIKE ?", ["%{$searchTerm}%"]);
        
    //             // Search in Attendance Records for Present, Absent, OT
    //             $query->orWhereExists(function ($subQuery) use ($searchTerm, $attendanceRecords) {
    //                 $subQuery->select(DB::raw(1))
    //                     ->from('parent_attendaces')
    //                     ->whereRaw("parent_attendaces.Emp_id = employees.id")
    //                     ->where(function ($q) use ($searchTerm) {
    //                         $q->whereRaw("LOWER(Status) LIKE ?", ["%{$searchTerm}%"])  // Search "Present" or "Absent"
    //                           ->orWhereRaw("CAST(OverTime AS CHAR) LIKE ?", ["%{$searchTerm}%"]); // Search OT
    //                     });
    //             });

    //             // Search in Attendance Records for Present, Absent, OT calculations
    //         $query->orWhereIn('id', function ($subQuery) use ($searchTerm) {
    //             $subQuery->select('Emp_id')
    //                 ->from('parent_attendaces')
    //                 ->groupBy('Emp_id')
    //                 ->havingRaw("SUM(CASE WHEN Status = 'Present' THEN 1 ELSE 0 END) LIKE ?", ["%{$searchTerm}%"]) // Present Days
    //                 ->orHavingRaw("SUM(CASE WHEN Status = 'Absent' THEN 1 ELSE 0 END) LIKE ?", ["%{$searchTerm}%"]) // Absent Days
    //                 ->orHavingRaw("SUM(STR_TO_DATE(OverTime, '%H:%i')) LIKE ?", ["%{$searchTerm}%"]) // Total OT
    //                 ->orHavingRaw("SUM(CASE WHEN EXISTS (SELECT 1 FROM public_holidays WHERE public_holidays.holiday_date = parent_attendaces.date) THEN STR_TO_DATE(OverTime, '%H:%i') ELSE 0 END) LIKE ?", ["%{$searchTerm}%"]) // Holiday OT
    //                 ->orHavingRaw("SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM public_holidays WHERE public_holidays.holiday_date = parent_attendaces.date) THEN STR_TO_DATE(OverTime, '%H:%i') ELSE 0 END) LIKE ?", ["%{$searchTerm}%"]); // Regular OT
    //         });
        
    //             // Search in Leave Types
    //             $query->orWhereExists(function ($subQuery) use ($searchTerm) {
    //                 $subQuery->select(DB::raw(1))
    //                     ->from('employees_leaves')
    //                     ->whereRaw("employees_leaves.emp_id = employees.id")
    //                     ->whereExists(function ($q) use ($searchTerm) {
    //                         $q->select(DB::raw(1))
    //                             ->from('leave_categories')
    //                             ->whereRaw("leave_categories.id = employees_leaves.leave_category_id")
    //                             ->whereRaw("LOWER(leave_categories.leave_type) LIKE ?", ["%{$searchTerm}%"]);
    //                     });
    //             });
    //         });
    //     }
        
    //     // Fetch Filtered Employees
    //     $employees = $employeeQuery->get()->keyBy('id');

    //     // Fetch Employee Details
    //     // $employees = Employee::with(['resortAdmin', 'department', 'position'])
    //     //     ->whereIn('id', $request->employees)
    //     //     ->get()
    //     //     ->keyBy('id');

    //     // Calculate total working days and days off for each employee
    //     $groupedAttendance = $attendanceRecords->groupBy('Emp_id')->map(function ($records, $empId) use (
    //         $employees, $currency, $settings, $resortId, $latestAttendanceUpdates, $request) {
    //             // dd($records);
    //         if (!isset($employees[$empId])) {
    //             return null; // Skip employees not in search results
    //         }

    //         // Get the days off for the employee in the current month
    //         $currentMonth = Carbon::now()->month;
    //         $currentYear = Carbon::now()->year;
    //         $TotalDayoff = $this->getDaysOffForEmployee($empId, $currentMonth, $currentYear);
    //         $totalDaysInMonth = Carbon::now()->daysInMonth;
    //         $totalWorkdaysInMonth = $totalDaysInMonth - $TotalDayoff ; // Calculate workdays

    //         $totalHours = 0;
    //         $regularOT = 0;
    //         $holidayOT = 0;
    //         $attendance_id = null;
    //         $totalWorkdays = 0;
    //         $absentDays = 0;

    //         foreach ($records as $record) {
    //             $attendance_id = $record->id;
    //             if (!empty($record->OverTime)) {
    //                 list($hours, $minutes) = explode(':', $record->OverTime ?? '00:00');
    //                 $totalHours += (int)$hours + ((int)$minutes / 60);
    //             }

    //             // Check if it's a public holiday
    //             $PublicHoliday = PublicHoliday::where('holiday_date', date('d-m-Y', strtotime($record->date)))->first();
    //             if (isset($PublicHoliday)) {
    //                 list($hours, $minutes) = explode(':', $record->OverTime ?? '00:00');
    //                 $holidayOT += (int)$hours + ((int)$minutes / 60);
    //             } else {
    //                 list($hours, $minutes) = explode(':', $record->OverTime ?? '00:00');
    //                 $regularOT += (int)$hours + ((int)$minutes / 60);
    //             }
    //         }

    //         // ✅ Apply Log Updates if available
    //         if (isset($latestAttendanceUpdates[$empId])) {
    //             foreach ($latestAttendanceUpdates[$empId] as $log) {
    //                 if ($log->field == 'present_days') {
    //                     $totalWorkdays = $log->new_value; // Use updated present days
    //                 } elseif ($log->field == 'absent_days') {
    //                     $absentDays = $log->new_value; // Use updated absent days
    //                 } elseif ($log->field == 'total_ot') {
    //                     $totalHours = $log->new_value; // Use updated OT
    //                 } elseif ($log->field == 'regular_ot_hours') {
    //                     $regularOT = $log->new_value; // Use updated Regular OT
    //                 } elseif ($log->field == 'holiday_ot_hours') {
    //                     $holidayOT = $log->new_value; // Use updated Holiday OT
    //                 }
    //             }
    //         } else {
    //             // If no log updates, use default values
    //             $totalWorkdays = $records->where('Status', 'Present')->count();
    //             $absentDays = $records->where('Status', 'Absent')->count();
    //         }

    //         //  Fetch leave records
    //         $leaveRecords = EmployeeLeave::where('emp_id', $empId)
    //             ->where(function ($query) use ($request) {
    //                 $query->whereBetween('from_date', [$request->startDate, $request->endDate])
    //                     ->orWhereBetween('to_date', [$request->startDate, $request->endDate])
    //                     ->orWhere(function ($q) use ($request) {
    //                         $q->where('from_date', '<=', $request->startDate)
    //                             ->where('to_date', '>=', $request->endDate);
    //                     });
    //             })
    //             ->selectRaw('leave_category_id, SUM(total_days) as days')
    //             ->groupBy('leave_category_id')
    //             ->get();

    //         $leaveDetails = $leaveRecords->map(function ($leave) {
    //             $leaveCategory = LeaveCategory::find($leave->leave_category_id);
    //             return [
    //                 'type'  => $leaveCategory->leave_type ?? 'Unknown',
    //                 'days'  => $leave->days,
    //                 'color' => $leaveCategory->color ?? '#000000'
    //             ];
    //         });

    //         // Calculate deductions based on the absent days
    //         // $perDaySalary = $employees[$empId]->basic_salary / $totalWorkdaysInMonth;
    //         // $deductionAmount = $perDaySalary * $absentDays;
    //         $totalDaysInMonth = Carbon::parse($request->startDate)->daysInMonth;
    //         $basic_salary = floatval( $employees[$empId]->basic_salary);

    //         // 1. Get Day-offs for employee
    //         $dayOffCount = $this->getDaysOffForEmployee($empId, $currentMonth, $currentYear);
    //         $workingDaysInMonth = $totalDaysInMonth - $dayOffCount;

    //         // 2. Per-day salary (Only for payable working days)
    //         $perDaySalary = $basic_salary / $workingDaysInMonth;

    //         // 3. Salary breakdown
    //         $earnedSalary = $perDaySalary * $totalWorkdays;
    //         $absentDeduction = $perDaySalary * $absentDays;

    //         // 4. Currency adjustment
    //         if ($currency === 'MVR') {
    //             $absentDeduction = $absentDeduction * $settings->DollertoMVR;
    //         }
    //         // ✅ Define total working hours per month (Assumed 208 hours = 8 hours/day * 26 days)
    //         $totalWorkingHoursPerMonth = ($totalWorkdaysInMonth * 8); 
            
    //         // ✅ Compute Overtime Pay
    //         $normalOtRate = ( $employees[$empId]->basic_salary / $totalWorkingHoursPerMonth) * 1.25;
    //         $holidayOtRate = ( $employees[$empId]->basic_salary / $totalWorkingHoursPerMonth) * 1.50;
            
    //         $normalOtPay = $normalOtRate * $regularOT;
    //         $holidayOtPay = $holidayOtRate * $holidayOT;
    //         $totalOtPay = $normalOtPay + $holidayOtPay; // ✅ Total OT Pay

    //         if ($currency === 'MVR') {
    //             $totaldeductionAmount = $absentDeduction * $settings->DollertoMVR;
    //         } else {
    //             $totaldeductionAmount = $absentDeduction;
    //         }

    //         // Employee info for the response
    //         $employee = $employees[$empId];
    //         $EmpID = $employee->Emp_id;
    //         $empName = $employee->resortAdmin->first_name . " " . $employee->resortAdmin->last_name ?? 'Unknown';
    //         $department = $employee->department->name ?? 'Unknown';
    //         $department_code = $employee->department->code ?? 'Unknown';
    //         $position = $employee->position->position_title ?? 'Unknown';
    //         $position_code = $employee->position->code ?? 'Unknown';
    //         $profile_picture = Common::getResortUserPicture($employee->Admin_Parent_id);

    //         $basic_salary = floatval($employee->basic_salary);

    //         $monthlyAllowances = Common::getMonthlyAllowances($employee->nationality, $resortId,$basic_salary, $frequency="monthly");
    //         $serviceCharge = Common::getServiceCharge($employee->id, $resortId,$request->payrollId);

    //         $normal_pay = $totalOtPay + $serviceCharge + $monthlyAllowances + $earnedSalary;
    //         return [
    //             'empid' => $employee->id,
    //             'attendance_id' => $attendance_id,
    //             'employee_id' => $EmpID,
    //             'name' => $empName,
    //             'department' => $department,
    //             'code' => $department_code,
    //             'position' => $position,
    //             'position_code' => $position_code,
    //             'section' => 'N/A',
    //             'present' => $totalWorkdays,
    //             'absent' => $absentDays,
    //             'leave_types' => $this->formatLeaveTypes($leaveDetails),
    //             'total_ot' => $totalHours,
    //             'regular_ot' => $regularOT,
    //             'holiday_ot' => $holidayOT,
    //             'workdays' => $totalWorkdays,
    //             'per_day_salary' => round($perDaySalary, 2),
    //             'absent_deduction' => round($absentDeduction, 2),
    //             'day_offs' => $dayOffCount,
    //             'earned_salary' => round($earnedSalary, 2),
    //             'image' => $profile_picture,
    //             'basic_salary' => $basic_salary,
    //             'allowance' => $monthlyAllowances,
    //             'normal_pay' => $normal_pay
    //         ];
    //     })->filter()->values();

    //     return response()->json(['success' => true, 'data' => $groupedAttendance]);
    // }

    // public function fetchTimeAttendance(Request $request)
    // {
    //     $request->validate([
    //         'employees' => 'required|array',
    //         'startDate' => 'required|date',
    //         'endDate' => 'required|date|after_or_equal:startDate',
    //     ]);

    //     $resortId = $this->resort->resort_id;
    //     $currency = $request->input('currency', 'Dollar');
    //     $conversionRate = floatval($request->input('conversionRate', 1));
    //     $settings = ResortSiteSettings::where('resort_id', $resortId)->first();

    //     $latestAttendanceUpdates = DB::table('payroll_attendance_activity_log')
    //         ->whereIn('employee_id', $request->employees)
    //         ->where('resort_id', $resortId)
    //         ->where('payroll_id', $request->payrollId)
    //         ->orderBy('updated_at', 'desc')
    //         ->get()
    //         ->groupBy('employee_id');

    //     $attendanceRecords = ParentAttendace::whereIn('Emp_id', $request->employees)
    //         ->whereBetween('date', [$request->startDate, $request->endDate])
    //         ->get();

    //     if ($attendanceRecords->isEmpty()) {
    //         return response()->json(['success' => false, 'message' => 'No attendance records found.'], 404);
    //     }

    //     // $pendingLeaves = EmployeeLeave::whereIn('emp_id', $request->employees)
    //     //     ->where('status', 'Pending')
    //     //     ->where(function ($query) use ($request) {
    //     //         $query->whereBetween('from_date', [$request->startDate, $request->endDate])
    //     //             ->orWhereBetween('to_date', [$request->startDate, $request->endDate])
    //     //             ->orWhere(function ($q) use ($request) {
    //     //                 $q->where('from_date', '<=', $request->startDate)
    //     //                 ->where('to_date', '>=', $request->endDate);
    //     //             });
    //     //     })->get();

    //     // if ($pendingLeaves->isNotEmpty()) {
    //     //     $employeeIds = $pendingLeaves->pluck('emp_id')->unique();
    //     //     $employeeNames = Employee::with('resortAdmin')->whereIn('id', $employeeIds)->get()
    //     //         ->map(fn($e) => $e->resortAdmin->first_name . ' ' . $e->resortAdmin->last_name)->implode(', ');

    //     //     return response()->json([
    //     //         'success' => false,
    //     //         'message' => 'Cannot proceed. The following employees have pending leave applications: ' . $employeeNames
    //     //     ], 422);
    //     // }

    //     // $invalidOTs = ParentAttendace::whereIn('Emp_id', $request->employees)
    //     //     ->whereBetween('date', [$request->startDate, $request->endDate])
    //     //     ->whereNotNull('OverTime')
    //     //     ->where(function ($q) {
    //     //         $q->whereNull('OTStatus')->orWhere('OTStatus', '');
    //     //     })->get();

    //     // if ($invalidOTs->isNotEmpty()) {
    //     //     $employeeIds = $invalidOTs->pluck('Emp_id')->unique();
    //     //     $employeeNames = Employee::with('resortAdmin')->whereIn('Emp_id', $employeeIds)->get()
    //     //         ->map(fn($e) => $e->resortAdmin->first_name . ' ' . $e->resortAdmin->last_name)->implode(', ');

    //     //     return response()->json([
    //     //         'success' => false,
    //     //         'message' => 'Cannot proceed. The following employees have OT entries with missing OT status: ' . $employeeNames
    //     //     ], 422);
    //     // }

    //     // $invalidAttendance = ParentAttendace::whereIn('Emp_id', $request->employees)
    //     //     ->whereBetween('date', [$request->startDate, $request->endDate])
    //     //     ->where(function ($query) {
    //     //         $query->whereNull('Status')
    //     //             ->orWhere('Status', '')
    //     //             ->orWhereNull('CheckingTime')
    //     //             ->orWhereNull('CheckingOutTime');
    //     //     })->get();

    //     // if ($invalidAttendance->isNotEmpty()) {
    //     //     $employeeIds = $invalidAttendance->pluck('Emp_id')->unique();
    //     //     $employeeNames = Employee::with('resortAdmin')->whereIn('Emp_id', $employeeIds)->get()
    //     //         ->map(fn($e) => $e->resortAdmin->first_name . ' ' . $e->resortAdmin->last_name)->implode(', ');

    //     //     return response()->json([
    //     //         'success' => false,
    //     //         'message' => 'Cannot proceed. The following employees have attendance entries with missing status or check-in/out times: ' . $employeeNames
    //     //     ], 422);
    //     // }

    //     $employeeQuery = Employee::with(['resortAdmin', 'department', 'position'])
    //         ->whereIn('id', $request->employees);

    //     $employees = $employeeQuery->get()->keyBy('id');

    //     $groupedAttendance = $attendanceRecords->groupBy('Emp_id')->map(function ($records, $empId) use (
    //         $employees, $currency, $settings, $resortId, $latestAttendanceUpdates, $request) {

    //         if (!isset($employees[$empId])) return null;

    //         $currentMonth = Carbon::now()->month;
    //         $currentYear = Carbon::now()->year;

    //         $dayOffCount = $this->getDaysOffForEmployee($empId, $currentMonth, $currentYear);
    //         $totalDaysInMonth = Carbon::parse($request->startDate)->daysInMonth;
    //         $workingDaysInMonth = max(1, $totalDaysInMonth - $dayOffCount); // prevent /0

    //         $totalHours = 0;
    //         $regularOT = 0;
    //         $holidayOT = 0;
    //         $attendance_id = null;
    //         $totalWorkdays = $records->where('Status', 'Present')->count();
    //         $absentDays = $records->where('Status', 'Absent')->count();

    //         foreach ($records as $record) {
    //             $attendance_id = $record->id;
    //             if (!empty($record->OverTime)) {
    //                 list($hours, $minutes) = explode(':', $record->OverTime ?? '00:00');
    //                 $otInHours = (int)$hours + ((int)$minutes / 60);
    //                 $totalHours += $otInHours;

    //                 $isHoliday = PublicHoliday::where('holiday_date', date('d-m-Y', strtotime($record->date)))->exists();
    //                 if ($isHoliday) {
    //                     $holidayOT += $otInHours;
    //                 } else {
    //                     $regularOT += $otInHours;
    //                 }
    //             }
    //         }

    //         // ✅ Apply log override safely
    //         if (isset($latestAttendanceUpdates[$empId])) {
    //             foreach ($latestAttendanceUpdates[$empId] as $log) {
    //                 switch ($log->field) {
    //                     case 'present_days':
    //                         if ((int)$log->new_value > 0) $totalWorkdays = $log->new_value;
    //                         break;
    //                     case 'absent_days':
    //                         if ((int)$log->new_value >= 0) $absentDays = $log->new_value;
    //                         break;
    //                     case 'total_ot':
    //                         if ((float)$log->new_value >= 0) $totalHours = $log->new_value;
    //                         break;
    //                     case 'regular_ot_hours':
    //                         if ((float)$log->new_value >= 0) $regularOT = $log->new_value;
    //                         break;
    //                     case 'holiday_ot_hours':
    //                         if ((float)$log->new_value >= 0) $holidayOT = $log->new_value;
    //                         break;
    //                 }
    //             }
    //         }

    //         $employee = $employees[$empId];
    //         $basic_salary = floatval($employee->basic_salary);
    //         $perDaySalary = $basic_salary / $workingDaysInMonth;
    //         $earnedSalary = round($perDaySalary * $totalWorkdays, 2);
    //         $absentDeduction = round($perDaySalary * $absentDays, 2);

    //         // Currency conversion
    //         if ($currency === 'MVR') {
    //             $absentDeduction *= $settings->DollertoMVR;
    //             $earnedSalary *= $settings->DollertoMVR;
    //         }

    //         $totalWorkingHoursPerMonth = $workingDaysInMonth * 8;
    //         $normalOtRate = $basic_salary / $totalWorkingHoursPerMonth * 1.25;
    //         $holidayOtRate = $basic_salary / $totalWorkingHoursPerMonth * 1.50;

    //         $normalOtPay = round($normalOtRate * $regularOT, 2);
    //         $holidayOtPay = round($holidayOtRate * $holidayOT, 2);
    //         $totalOtPay = $normalOtPay + $holidayOtPay;

    //         $monthlyAllowances = Common::getMonthlyAllowances($employee->nationality, $resortId, $basic_salary, "monthly");
    //         $serviceCharge = Common::getServiceCharge($employee->id, $resortId, $request->payrollId);

    //         $normal_pay = round($totalOtPay + $monthlyAllowances + $serviceCharge + $earnedSalary, 2);

    //         $leaveRecords = EmployeeLeave::where('emp_id', $empId)
    //             ->where(function ($query) use ($request) {
    //                 $query->whereBetween('from_date', [$request->startDate, $request->endDate])
    //                     ->orWhereBetween('to_date', [$request->startDate, $request->endDate])
    //                     ->orWhere(function ($q) use ($request) {
    //                         $q->where('from_date', '<=', $request->startDate)
    //                         ->where('to_date', '>=', $request->endDate);
    //                     });
    //             })
    //             ->selectRaw('leave_category_id, SUM(total_days) as days')
    //             ->groupBy('leave_category_id')
    //             ->get();

    //         $leaveDetails = $leaveRecords->map(function ($leave) {
    //             $leaveCategory = LeaveCategory::find($leave->leave_category_id);
    //             return [
    //                 'type'  => $leaveCategory->leave_type ?? 'Unknown',
    //                 'days'  => $leave->days,
    //                 'color' => $leaveCategory->color ?? '#000000'
    //             ];
    //         });

    //         $profile_picture = Common::getResortUserPicture($employee->Admin_Parent_id);
    //         $EmpID = $employee->Emp_id;
    //         $empName = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name ?? 'Unknown';

    //         return [
    //             'empid' => $employee->id,
    //             'attendance_id' => $attendance_id,
    //             'employee_id' => $EmpID,
    //             'name' => $empName,
    //             'department' => $employee->department->name ?? 'Unknown',
    //             'code' => $employee->department->code ?? 'Unknown',
    //             'position' => $employee->position->position_title ?? 'Unknown',
    //             'position_code' => $employee->position->code ?? 'Unknown',
    //             'section' => 'N/A',
    //             'present' => $totalWorkdays,
    //             'absent' => $absentDays,
    //             'leave_types' => $this->formatLeaveTypes($leaveDetails),
    //             'total_ot' => $totalHours,
    //             'regular_ot' => $regularOT,
    //             'holiday_ot' => $holidayOT,
    //             'workdays' => $totalWorkdays,
    //             'per_day_salary' => round($perDaySalary, 2),
    //             'absent_deduction' => round($absentDeduction, 2),
    //             'day_offs' => $dayOffCount,
    //             'earned_salary' => $earnedSalary,
    //             'image' => $profile_picture,
    //             'basic_salary' => $basic_salary,
    //             'allowance' => $monthlyAllowances,
    //             'normal_pay' => $normal_pay
    //         ];
    //     })->filter()->values();

    //     return response()->json(['success' => true, 'data' => $groupedAttendance]);
    // }

    public function fetchTimeAttendance(Request $request)
    {
        $request->validate([
            'employees' => 'required|array',
            'startDate' => 'required|date',
            'endDate'   => 'required|date|after_or_equal:startDate',
        ]);

        $resortId = $this->resort->resort_id;
        $currency = $request->input('currency', 'Dollar');
        $settings = ResortSiteSettings::where('resort_id', $resortId)->first();

        $latestAttendanceUpdates = DB::table('payroll_attendance_activity_log')
            ->whereIn('employee_id', $request->employees)
            ->where('resort_id', $resortId)
            ->where('payroll_id', $request->payrollId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('employee_id');

        $attendanceRecords = ParentAttendace::whereIn('Emp_id', $request->employees)
            ->whereBetween('date', [$request->startDate, $request->endDate])
            ->get();

        if ($attendanceRecords->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No attendance records found.'], 404);
        }

        $pendingLeaves = EmployeeLeave::whereIn('emp_id', $request->employees)
            ->where('status', 'Pending')
            ->where(function ($query) use ($request) {
                $query->whereBetween('from_date', [$request->startDate, $request->endDate])
                    ->orWhereBetween('to_date', [$request->startDate, $request->endDate])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('from_date', '<=', $request->startDate)
                        ->where('to_date', '>=', $request->endDate);
                    });
            })->get();

        if ($pendingLeaves->isNotEmpty()) {
            $employeeIds = $pendingLeaves->pluck('emp_id')->unique();
            $employeeNames = Employee::with('resortAdmin')->whereIn('id', $employeeIds)->get()
                ->map(fn($e) => $e->resortAdmin->first_name . ' ' . $e->resortAdmin->last_name)->implode(', ');

            return response()->json([
                'success' => false,
                'message' => 'Cannot proceed. The following employees have pending leave applications: ' . $employeeNames
            ], 422);
        }

        $invalidOTs = ParentAttendace::whereIn('Emp_id', $request->employees)
            ->whereBetween('date', [$request->startDate, $request->endDate])
            ->whereNotNull('OverTime')
            ->where(function ($q) {
                $q->whereNull('OTStatus')->orWhere('OTStatus', '');
            })->get();

        if ($invalidOTs->isNotEmpty()) {
            $employeeIds = $invalidOTs->pluck('Emp_id')->unique();
            $employeeNames = Employee::with('resortAdmin')->whereIn('Emp_id', $employeeIds)->get()
                ->map(fn($e) => $e->resortAdmin->first_name . ' ' . $e->resortAdmin->last_name)->implode(', ');

            return response()->json([
                'success' => false,
                'message' => 'Cannot proceed. The following employees have OT entries with missing OT status: ' . $employeeNames
            ], 422);
        }

        $invalidAttendance = ParentAttendace::whereIn('Emp_id', $request->employees)
            ->whereBetween('date', [$request->startDate, $request->endDate])
            ->where(function ($query) {
                $query->whereNull('Status')
                    ->orWhere('Status', '')
                    ->orWhereNull('CheckingTime')
                    ->orWhereNull('CheckingOutTime');
            })->get();

        if ($invalidAttendance->isNotEmpty()) {
            $employeeIds = $invalidAttendance->pluck('Emp_id')->unique();
            $employeeNames = Employee::with('resortAdmin')->whereIn('Emp_id', $employeeIds)->get()
                ->map(fn($e) => $e->resortAdmin->first_name . ' ' . $e->resortAdmin->last_name)->implode(', ');

            return response()->json([
                'success' => false,
                'message' => 'Cannot proceed. The following employees have attendance entries with missing status or check-in/out times: ' . $employeeNames
            ], 422);
        }
        // Check if next month is Ramadan (based on start date)
        $nextMonthDate = Carbon::parse($request->startDate)->addMonthNoOverflow()->startOfMonth();
        $response = Http::get("https://api.aladhan.com/v1/gToH", [
            'date' => $nextMonthDate->format('d-m-Y')
        ]);
        $isNextMonthRamadan = $response->successful()
            && ($response['data']['hijri']['month']['number'] == 9);

        // Load employee data
        $employees = Employee::with(['resortAdmin', 'department', 'position', 'allowance'])
            ->whereIn('id', $request->employees)
            ->get()
            ->keyBy('id');

        // Group attendance by employee
        $grouped = $attendanceRecords->groupBy('Emp_id')->map(function ($records, $empId) use (
            $employees,
            $currency,
            $settings,
            $latestAttendanceUpdates,
            $request,
            $isNextMonthRamadan
        ) {
            if (!isset($employees[$empId])) return null;

            $employee = $employees[$empId];
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;

            $dayOffCount = $this->getDaysOffForEmployee($empId, $currentMonth, $currentYear);
            $totalDaysInMonth = Carbon::parse($request->startDate)->daysInMonth;
            $workingDays = max(1, $totalDaysInMonth - $dayOffCount);

            $totalHours = $regularOT = $holidayOT = 0;
            $attendance_id = null;
            $presentCount = $records->where('Status', 'Present')->count();

            $absentRecords = $records->where('Status', 'Absent');
            $absentCount = $absentRecords->count(); // Total absents from attendance
            $unpaidAbsentCount = 0; // Count of unpaid absents (for deduction)
            $absentDeduct = 0;
            $absentDates = $absentRecords->pluck('date');
            // dd($absentDates);
            $paidLeaveDays = collect();
            $leaveDetails = collect();

            foreach ($absentDates as $date) {
                $paidLeave = EmployeeLeave::where('emp_id', $empId)
                    ->where('status', 'Approved')
                    ->where(function ($q) use ($date) {
                        $q->where('from_date', '<=', $date)
                            ->where('to_date', '>=', $date);
                    })
                    ->first();

                $resort_benefitGrid = ResortBenifitGrid::where('emp_grade', $employee->benefit_grid_level)->where('status','Active')->first();
                    // dd($paidLeave, $resort_benefitGrid);
                if ($paidLeave && $resort_benefitGrid) {
                    $benefitGrid = ResortBenifitGridChild::where('benefit_grid_id', optional($resort_benefitGrid)->id)
                        ->where('leave_cat_id', $paidLeave->leave_category_id)
                        ->first();

                    if ($benefitGrid && $benefitGrid->allocated_days > 0) {
                        $used = EmployeeLeave::where('emp_id', $empId)
                            ->where('leave_category_id', $paidLeave->leave_category_id)
                            ->where('status', 'Approved')
                            ->whereYear('from_date', Carbon::parse($request->startDate)->year)
                            ->sum('total_days');

                        if ($used < $benefitGrid->allocated_days) {
                            $paidLeaveDays->push($date);
                            $presentCount++; // Count paid leave as present for salary
                            $leaveCategory = LeaveCategory::find($paidLeave->leave_category_id);
                            $leaveDetails->push([
                                'type'  => $leaveCategory->leave_type ?? 'Unknown',
                                'color' => $leaveCategory->color ?? '#000000',
                            ]);
                            continue; // Skip deduction
                        }
                    }
                }

                // Count as unpaid absent
                $unpaidAbsentCount++;
            }
            // dd($leaveDetails) ;
            foreach ($records as $rec) {
                $attendance_id = $rec->id;
                if (!empty($rec->OverTime)) {
                    [$h, $m] = explode(':', $rec->OverTime);
                    $hours = (int)$h + ((int)$m / 60);
                    $totalHours += $hours;

                    $isHoliday = PublicHoliday::where('holiday_date', date('d-m-Y', strtotime($rec->date)))->exists();
                    $isHoliday ? $holidayOT += $hours : $regularOT += $hours;
                }
            }

            // Apply overrides from logs
            if (isset($latestAttendanceUpdates[$empId])) {
                foreach ($latestAttendanceUpdates[$empId] as $log) {
                    if ($log->field === 'present_days') $presentCount = $log->new_value;
                    if ($log->field === 'absent_days') $absentCount = $log->new_value;
                    if ($log->field === 'total_ot') $totalHours = $log->new_value;
                    if ($log->field === 'regular_ot_hours') $regularOT = $log->new_value;
                    if ($log->field === 'holiday_ot_hours') $holidayOT = $log->new_value;
                }
            }

            $basic = floatval($employee->basic_salary);
            // dd($employee->basic_salary_currency);
            // dd($basic, $employee->basic_salary_currency, $currency, $settings->DollertoMVR);
            if ($currency === 'MVR' && $employee->basic_salary_currency === 'USD') {
                $basic *= $settings->DollertoMVR;
            } elseif ($currency === 'Dollar' && $employee->basic_salary_currency === 'MVR') {
                $basic /= $settings->DollertoMVR;
            }
            $perDay = $basic / $workingDays;
            $earnedSalary = round($perDay * $presentCount, 2); // presentCount already includes paid leave
            $absentDeduct = round($perDay * $unpaidAbsentCount, 2);

            $regularOTPay = round(($basic / ($workingDays * 8) * 1.25) * $regularOT, 2);
            $holidayOTPay = round(($basic / ($workingDays * 8) * 1.50) * $holidayOT, 2);
            $totalOTPay = round(
                ($basic / ($workingDays * 8) * 1.25) * $regularOT +
                ($basic / ($workingDays * 8) * 1.50) * $holidayOT,
                2
            );

            $allowanceDetails = EmployeeAllowance::with('allowanceName')
                ->where('employee_id', $employee->id)
                ->get();

            $allowanceBreakdown = [];
            $totalAllowance = 0;
            foreach ($allowanceDetails as $allowance) {
                $amount = $allowance->amount;
                if ($currency === 'MVR' && $allowance->amount_unit === 'USD') {
                    $amount *= $settings->DollertoMVR;
                } elseif ($currency === 'Dollar' && $allowance->amount_unit === 'MVR') {
                    $amount /= $settings->DollertoMVR;
                }
                $label = $allowance->allowanceName->particulars ?? 'Unnamed';
                $allowanceBreakdown[$label] = round($amount, 2);
                $totalAllowance += $amount;
            }

            $serviceCharge = Common::getServiceCharge($employee->id, $this->resort->resort_id, $request->payrollId);
             if ($currency === 'MVR')
             {
                $serviceCharge *= $settings->DollertoMVR;
             }
             else{
                $serviceCharge = $serviceCharge;
             }
            // dd($serviceCharge);
            // Ramadan Bonus check
            $ramadanBonus = 0;
            if ($isNextMonthRamadan && $employee->resort?->benefitGrid) {
                $grid = $employee->resort->benefitGrid;
                $eligibility = $grid->ramadan_bonus_eligibility;
                $isLocal = strtolower($employee->nationality) === 'maldivian';
                $eligible = match ($eligibility) {
                    'all' => true,
                    'all_muslim' => $employee->religion === 'Muslim',
                    'local_muslim' => $employee->religion === 'Muslim' && $isLocal,
                    'all_local' => $isLocal,
                    default => false
                };
                if ($eligible && $grid->ramadan_bonus) {
                    $ramadanBonus = floatval($grid->ramadan_bonus);
                }
            }

            $deduction = DB::table('payroll_deductions')
            ->where('employee_id', $employee->id)
            ->where('payroll_id', $request->payrollId)
            ->value('total_deductions');

            // dd($presentCount,$totalOTPay ,$totalAllowance ,$serviceCharge ,$earnedSalary , $ramadanBonus); 

            $normalPay = round($totalOTPay + $totalAllowance + $serviceCharge + $earnedSalary + $ramadanBonus, 2);

            return [
                'empid' => $employee->id,
                'attendance_id' => $attendance_id,
                'employee_id' => $employee->Emp_id,
                'name' => $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name,
                'department' => $employee->department->name ?? 'Unknown',
                'code' => $employee->department->code ?? 'Unknown',
                'position' => $employee->position->position_title ?? 'Unknown',
                'position_code' => $employee->position->code ?? 'Unknown',
                'present' => $presentCount,
                'absent' => $absentCount,
                'unpaid_absent' => $unpaidAbsentCount,
                'absent_deduction' => round($absentDeduct, 2),
                'section' => 'N/A',
                'total_ot' => $totalHours,
                'regular_ot' => $regularOT,
                'holiday_ot' => $holidayOT,
                'workdays' => $presentCount,
                'per_day_salary' => round($perDay, 2),
                'absent_deduction' => round($absentDeduct, 2),
                'earned_salary' => $earnedSalary,
                'basic_salary' => $basic,
                'total_deduction' => round($deduction ?? 0, 2),
                'allowance_breakdown' => $allowanceBreakdown,
                'allowances' => $employee->allowance->map(function ($a) use ($currency, $settings) {
                    $amount = $a->amount;
                    if ($currency === 'MVR' && $a->amount_unit === 'USD') {
                        $amount *= $settings->DollertoMVR;
                    } elseif ($currency === 'Dollar' && $a->amount_unit === 'MVR') {
                        $amount /= $settings->DollertoMVR;
                    }
                    return [
                        'name' => $a->allowanceName->particulars ?? 'Unknown',
                        'amount' => round($amount, 2),
                        'unit' => $a->amount_unit ?? 'USD'
                    ];
                }),
                'service_charge' => $serviceCharge,
                'ramadan_bonus' => $ramadanBonus,
                'normal_pay' => $normalPay,
                'image' => Common::getResortUserPicture($employee->Admin_Parent_id),
                'leave_types' => $this->formatLeaveTypes($leaveDetails),
                'totalOTPay' => round($totalOTPay, 2),
                'regularOTPay' => round($regularOTPay, 2),
                'holidayOTPay' => round($holidayOTPay, 2),
            ];
        })
        ->filter()
        ->values();

        return response()->json(['success' => true, 'data' => $grouped]);
    }

    public function getDaysOffForEmployee($employeeId, $month, $year)
    {
        // Get all attendance records for the employee in the given month and year
        $attendanceRecords = DB::table('parent_attendaces')
            ->where('Emp_id', $employeeId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('status', 'DayOff')  // Only count dayoffs
            ->count();

        return $attendanceRecords;
    }

    public function fetchServiceCharge(Request $request)
    {
        
        $selectedEmployeeIds = $request->employees;

       
        $employees = Employee::with(['resortAdmin','department','position'])->whereIn('id', $request->employees)->get()->keyBy('id');

        
        $totalWorkdays = $employees->sum('workdays');

        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }

    private function formatLeaveTypes($leaveTypes)
    {
        // dd($leaveTypes);
        if ($leaveTypes->isEmpty()) {
            return '-';
        }

        return $leaveTypes
            ->groupBy('type')
            ->map(function ($group, $type) {
                $color = $group->first()['color'] ?? '#000000';
                return "<span class='badge border-0' style='color:{$color}; background:{$color}1F;'>{$type} ({$group->count()})</span>";
            })
            ->implode('<br>');
    }

    public function fetchStaffShop(Request $request) {
        $resortId = $this->resort->resort_id;

        $settings = ResortSiteSettings::where('resort_id', $resortId)->first();

        $employeeIds = $request->input('employees');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $currency = $request->input('currency', 'Dollar'); // Default currency is USD
        $conversionRate = floatval($request->input('conversionRate', 1)); // Default 1 (no conversion)
        $salary_currency = "Dollar"; // Assume salary is stored in Dollar
    
        $staffShopData = Payment::whereIn('payments.emp_id', $employeeIds)
            ->join('employees as e', 'e.id', '=', 'payments.emp_id')
            ->whereIn('payments.status', ['Approved', 'Partial Paid'])
            ->whereBetween('payments.purchased_date', [$startDate, $endDate])
            ->select(
                'e.Emp_id',
                DB::raw("COALESCE(SUM(CASE 
                            WHEN payments.status = 'Partial Paid' 
                            THEN GREATEST(0, payments.price - payments.cash_paid) 
                            ELSE payments.price 
                        END), 0) as total_usd") // ✅ Stored as USD
            )
            ->groupBy('e.Emp_id')
            ->get()
            ->mapWithKeys(function ($item) use ($currency, $conversionRate,$settings) {
                // ✅ Convert currency correctly
                // dd($currency,$settings->DollertoMVR,$item->total_usd);
                if ($currency === 'MVR') {
                    $total = $item->total_usd * $settings->DollertoMVR; // Convert USD to MVR
                } else {
                    $total = $item->total_usd; // Keep in USD
                }
    
                return [$item->Emp_id => [
                    'Emp_id' => $item->Emp_id,
                    'total' => round($total, 2), // Keep numeric format
                ]];
            });
    
        return response()->json([
            'success' => true,
            'data' => $staffShopData->values()
        ]);
    }

    // public function calculatePensionAndEWT(Request $request)
    // {
    //     $resortId = $this->resort->resort_id;
    //     $settings = ResortSiteSettings::where('resort_id', $resortId)->first();
    //     // dd($settings['DollertoMVR']);

    //     $employeeIds = $request->input('employees');
    //     $currency = $request->input('currency', 'Dollar');
    //     $conversionRate = floatval($request->input('conversionRate', 1));
    //     $payrollId = $request->input('payrollId');

    //     $employees = Employee::whereIn('id', $employeeIds)
    //         ->select('id', 'emp_id', 'basic_salary', 'nationality')
    //         ->get();

    //     $responseData = [];

    //     foreach ($employees as $employee) {
    //         // ✅ Get total earnings (basic salary + monthly allowances + service charges)

    //         $salary = floatval($employee->basic_salary);
    //         $monthlyAllowances = Common::getMonthlyAllowances($employee->nationality, $resortId, $salary, $frequency="monthly");
    //         $serviceCharge = Common::getServiceCharge($employee->id, $resortId,$payrollId);

    //         // dd($monthlyAllowances,$serviceCharge,$salary);

    //         // dd( $salary , $settings['DollertoMVR'] ,$currency);

    //         // ✅ Convert salary to MVR if stored in USD
    //         $salaryInMVR = ($currency === 'Dollar') ? $salary * $settings['DollertoMVR'] : $salary;
    //         // dd($salaryInMVR);
    //         $allowancesInMVR = ($currency === 'Dollar') ? $monthlyAllowances * $settings['DollertoMVR'] :  $monthlyAllowances;
    //         $serviceChargeInMVR = ($currency === 'Dollar') ? $serviceCharge * $settings['DollertoMVR'] : $serviceCharge ;

    //         // ✅ Calculate taxable income (basic salary + allowances + service charge)
    //         $taxableIncomeMVR = $salaryInMVR + $allowancesInMVR + $serviceChargeInMVR;

    //         // dd($allowancesInMVR,$salaryInMVR,$serviceChargeInMVR);

    //         // ✅ Deduct pension (7% of basic salary)
    //         $pensionMVR = ($salaryInMVR * 0.07);
    //         $taxableIncomeMVR -= $pensionMVR;

    //         // ✅ Calculate EWT in MVR (progressive tax)
    //         $ewtMVR = Common::calculateEWT($taxableIncomeMVR);

    //         // dd($currency,$ewtMVR ,$pensionMVR,$settings['MVRtoDoller'] );

    //         // ✅ Convert to selected currency
    //         $ewtFinal = ($currency === 'Dollar') ? $ewtMVR * $settings['MVRtoDoller'] : $ewtMVR;
    //         $pensionFinal = ($currency === 'Dollar') ? $pensionMVR * $settings['MVRtoDoller']  : $pensionMVR;

    //         // dd($ewtFinal, $pensionFinal);

    //         $responseData[] = [
    //             'Emp_id' => $employee->emp_id,
    //             'pension' => $pensionFinal,
    //             'ewt' => $ewtFinal,
    //             'currency' => $currency
    //         ];
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data' => $responseData,
    //     ]);
    // }

    // public function calculatePensionAndEWT(Request $request)
    // {
    //     $resortId = $this->resort->resort_id;
    //     $settings = ResortSiteSettings::where('resort_id', $resortId)->first();

    //     if (!$settings || !isset($settings['DollertoMVR'], $settings['MVRtoDoller'])) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Currency settings not found.'
    //         ], 400);
    //     }

    //     // $employeeIds = $request->input('employees');
    //     $currency = $request->input('currency', 'Dollar');
    //     $payrollId = $request->input('payrollId');

    //     $employeeData = $request->input('employees'); // array of ['id' => ..., 'earned_salary' => ...]

    //     $employees = collect($employeeData)->map(function ($emp) {
    //         $employee = Employee::select('id', 'emp_id', 'basic_salary', 'nationality')->find($emp['id']);
    //         if (!$employee) return null;

    //         $employee->earned_salary = floatval($emp['earned_salary']); // attach earned salary
    //         return $employee;
    //     })->filter();

    //     $responseData = [];

    //     foreach ($employees as $employee) {
    //         $earnedSalary = floatval($employee->earned_salary);
    //         $basicSalary = floatval($employee->basic_salary);

    //         // Allowances and Service Charge in local currency
    //         $monthlyAllowances = Common::getMonthlyAllowances($employee->nationality, $resortId, $basicSalary, $frequency = "monthly");
    //         $serviceCharge = Common::getServiceCharge($employee->id, $resortId, $payrollId);

    //         $salaryInMVR = ($currency === 'Dollar') ? $basicSalary * $settings['DollertoMVR'] : $basicSalary;
           
    //         $allowancesInMVR = ($currency === 'Dollar') ? $monthlyAllowances * $settings['DollertoMVR'] : $monthlyAllowances;
    //         $serviceChargeInMVR = ($currency === 'Dollar') ? $serviceCharge * $settings['DollertoMVR'] : $serviceCharge;
    //         $grossIncomeMVR = $salaryInMVR + $allowancesInMVR + $serviceChargeInMVR;

    //         // Calculate Pension (7% of salary only)
    //         $pensionMVR = Common::calculatePension($salaryInMVR);

    //         // Taxable income = gross income - pension
    //         $taxableIncomeMVR = max($grossIncomeMVR - $pensionMVR, 0); // ensure not negative

    //         // Calculate EWT in MVR
    //         $ewtMVR = Common::calculateEWT($taxableIncomeMVR);
    //         // dd($ewtMVR);

    //         // Convert to selected currency
    //         $pensionFinal = ($currency === 'Dollar') ? $pensionMVR * $settings['MVRtoDoller'] : $pensionMVR;
    //         $ewtFinal = ($currency === 'Dollar') ? $ewtMVR * $settings['MVRtoDoller'] : $ewtMVR;

    //         // Round results
    //         $responseData[] = [
    //             'Emp_id' => $employee->emp_id,
    //             'pension' => round($pensionFinal, 2),
    //             'ewt' => round($ewtFinal, 2),
    //             'currency' => $currency
    //         ];
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data' => $responseData,
    //     ]);
    // }

    public function calculatePensionAndEWT(Request $request)
    {
        // dd($request->all());
        $resortId = $this->resort->resort_id;
        $settings = ResortSiteSettings::where('resort_id', $resortId)->first();

        if (!$settings || !isset($settings['DollertoMVR'], $settings['MVRtoDoller'])) {
            return response()->json([
                'success' => false,
                'message' => 'Currency settings not found.'
            ], 400);
        }

        $currency = $request->input('currency', 'Dollar');
        $payrollId = $request->input('payrollId');
        $employeeData = $request->input('employees'); // array of ['id' => ..., 'earned_salary' => ...]
        // dd($employeeData);
        $employees = collect($employeeData)->map(function ($emp) {
        $employee = Employee::with('allowance.allowanceName','resortAdmin','department', 'position')
            ->select('id', 'emp_id', 'basic_salary', 'basic_salary_currency', 'nationality','ewt_status')
            ->find($emp['id']);

            if ($employee) {
                // Attach custom fields to the model
                $employee->earned_salary = $emp['earned_salary'];
                $employee->total_ot_pay = $emp['totalOTPay']; // attach OT pay
            }

            return $employee;
        })->filter();
        $responseData = [];

        foreach ($employees as $employee) 
        {
            $basicSalary = floatval($employee->basic_salary);
            $basicCurrency = $employee->basic_salary_currency;

            // Convert basic salary to MVR if in USD
            $salaryInMVR = ($basicCurrency === 'USD') ? $basicSalary * $settings['DollertoMVR'] : $basicSalary;

            // Allowance in MVR
            $totalAllowanceMVR = 0;
            foreach ($employee->allowance as $allowance) 
            {
                $amount = floatval($allowance->amount);
                if ($allowance->amount_unit === 'USD') {
                    $amount *= $settings['DollertoMVR'];
                }
                $totalAllowanceMVR += $amount;
            }

            // Service Charge
            $serviceCharge = Common::getServiceCharge($employee->id, $resortId, $payrollId);
            $serviceChargeInMVR = ($currency === 'Dollar') ? $serviceCharge * $settings['DollertoMVR'] : $serviceCharge;

            // ✅ Convert total OT pay to MVR if needed
            $totalOTPay = isset($employee->total_ot_pay) ? floatval($employee->total_ot_pay) : 0;
            $totalOTPayInMVR = ($currency === 'Dollar') ? $totalOTPay * $settings['DollertoMVR'] : $totalOTPay;

            // ✅ Gross income includes OT
            $grossIncomeMVR = $salaryInMVR + $totalAllowanceMVR + $serviceChargeInMVR + $totalOTPayInMVR;

            // Pension for Maldivian
            $isMaldivian = $employee->nationality === 'Maldivian';
            $pensionMVR = $isMaldivian ? Common::calculatePension($salaryInMVR) : 0;

            $taxableIncomeMVR = max($grossIncomeMVR - $pensionMVR, 0);

            $ewtMVR = Common::calculateEWT($taxableIncomeMVR);

            if($employee->ewt_status =="Yes" && $taxableIncomeMVR > 60000)
            {
                $notify_person = Employee::where('resort_id', $resort->resort_id)->where('rank','3')->first();
                if (!$notify_person) {
                    $notify_person = Employee::where('resort_id', $resort->resort_id)->where('rank','2')->first();
                }
                if($ewtMVR === 0)
                {
                        //tax Kapavo joy to hto  compliance breached
                    event(new ResortNotificationEvent(Common::nofitication(
                            $this->resort->resort_id,
                            10,
                            'Payroll EWT Compliance',
                            "Employee {$employee->resortAdmin->first_name} {$employee->resortAdmin->last_name} ({$employee->position->position_title}) has taxable income exceeding MVR 60,000 but no EWT deductions applied. Tax compliance requires applying standard EWT brackets.",
                            $notify_person->id,
                            'Payroll'
                    )));

                    $compliance = Compliance::firstOrCreate([
                            'resort_id' => $this->resort->resort_id,
                            'employee_id' => null,
                            'module_name' => 'Payroll',
                            'compliance_breached_name' => 'Payroll EWT Compliance',
                            'description' => "Employee {$employee->resortAdmin->first_name} {$employee->resortAdmin->last_name} ({$employee->position->position_title}) has taxable income exceeding MVR 60,000 but no EWT deductions applied. Tax compliance requires applying standard EWT brackets.",
                            'reported_on' => Carbon::now(),
                            'status' => 'Breached'
                    ]);
                }
                else
                {
                        $expectedEWT = 0;
                        $brackets = DB::table('ewt_tax_brackets')->orderBy('min_salary')->get();

                        foreach ($brackets as $bracket) {
                            $min = $bracket->min_salary;
                            $max = is_null($bracket->max_salary) ? PHP_INT_MAX : $bracket->max_salary;
                            $rate = $bracket->tax_rate;

                            if ($taxableIncomeMVR > $min) {
                                $taxableAmount = min($taxableIncomeMVR, $max) - $min;
                                if ($taxableAmount > 0) {
                                    $expectedEWT += $taxableAmount * ($rate / 100);
                                }
                            }
                        }

                        if (round($expectedEWT, 2) !== round($calculatedEWT, 2)) {
                                event(new ResortNotificationEvent(Common::nofitication(
                                    $this->resort->resort_id,
                                    10,
                                    'Payroll EWT Compliance',
                                    " EWT mismatch detected for Employee {$employee->resortAdmin->first_name} {$employee->resortAdmin->last_name} ({$employee->position->position_title}). 
                                            Expected EWT: MVR " . round($expectedEWT, 2) . ", 
                                            Calculated EWT: MVR " . round($calculatedEWT, 2) . ". 
                                            Please review the applied tax brackets or salary components.",
                                    'Payroll'
                                )));

                                $compliance = Compliance::firstOrCreate([
                                        'resort_id' => $this->resort->resort_id,
                                        'employee_id' => null,
                                        'module_name' => 'Payroll',
                                        'compliance_breached_name' => 'Payroll EWT Compliance',
                                        'description' => " EWT mismatch detected for Employee {$employee->resortAdmin->first_name} {$employee->resortAdmin->last_name} ({$employee->position->position_title}). 
                                            Expected EWT: MVR " . round($expectedEWT, 2) . ", 
                                            Calculated EWT: MVR " . round($calculatedEWT, 2) . ". 
                                            Please review the applied tax brackets or salary components.",                                        
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached'
                                ]);                        
                        }

                }

            }

            $pensionFinal = ($currency === 'Dollar') ? $pensionMVR * $settings['MVRtoDoller'] : $pensionMVR;
            $ewtFinal = ($currency === 'Dollar') ? $ewtMVR * $settings['MVRtoDoller'] : $ewtMVR;

            $responseData[] = [
                'Emp_id' => $employee->emp_id,
                'pension' => round($pensionFinal, 2),
                'ewt' => round($ewtFinal, 2),
                'currency' => $currency,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $responseData,
        ]);
    }

    public function viewPayroll($encoded_payroll_id)
    {
        $page_title ='Payroll Run';
        $resort_id = $this->resort->resort_id;
        $payroll_id = base64_decode($encoded_payroll_id);
     
        $positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
       
        $payroll = Payroll::with([
            'employees',
            'timeAndAttendances',
            'serviceCharges',
            'deductions',
            'reviews'
        ])->findOrFail($payroll_id);

        $start_date = $payroll->start_date;
        $end_date = $payroll->end_date;
        
        return view('resorts.payroll.run.payroll',compact('page_title','positions','departments','payroll','resort_id','payroll_id','start_date','end_date'));
    }

    public function getPayrollData(Request $request, $payroll_id)
    {
        $resortId = $this->resort->resort_id;
        $payroll = Payroll::with([
            'employees.employee.resortAdmin',
            'employees.employee.department',
            'employees.employee.position',
            'timeAndAttendances',
            'serviceCharges',
            'deductions',
            'reviews',
            'reviews.allowances' // <-- correct relationship name

        ])->findOrFail($payroll_id);

        // dd($payroll);
        $resortId = is_array($resortId) ? $resortId : [$resortId];

        $allowanceColumns = collect($payroll->reviews)
        ->flatMap(function ($review) {
            return $review->allowances->pluck('allowance_type');
        })
        ->unique()
        ->values()
        ->toArray();

        if (!$payroll || $payroll->employees->isEmpty()) {
            return response()->json([
                "draw" => request()->input('draw'),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            ]);
        }

        // Start DataTables query with filtering
        $query = datatables()->of($payroll->employees)
            ->filter(function ($query) use ($request, $payroll) {
                $collection = $query->collection;
                
                // Search filter
                if ($request->has('searchTerm') && !empty($request->searchTerm)) {
                    $searchTerm = strtolower($request->searchTerm);
                
                    $collection = $collection->filter(function ($item) use ($searchTerm) {
                        $emp_detail = Employee::with(['resortAdmin', 'department', 'position'])->find($item['employee_id']);
                
                        if (!$emp_detail || !$emp_detail->resortAdmin) {
                            return false;
                        }
                
                        $fullName = strtolower($emp_detail->resortAdmin->first_name . ' ' . $emp_detail->resortAdmin->last_name);
                        $departmentName = strtolower(optional($emp_detail->department)->name);
                        $positionTitle = strtolower(optional($emp_detail->position)->position_title);
                        
                        // Ensure salary is treated as a string or set it to an empty string if it's null
                        $salary = (string) optional($emp_detail)->basic_salary;
                
                        // Check if the search term matches any of the attributes
                        return str_contains($fullName, $searchTerm) ||
                            str_contains($departmentName, $searchTerm) ||
                            str_contains($positionTitle, $searchTerm) ||
                            str_contains($salary, $searchTerm);
                    });
                }

                // Department filter
                if ($request->has('department') && !empty($request->department)) {
                    $collection = $collection->filter(function ($item) use ($request) {
                        $emp_detail = Employee::find($item['employee_id']);
                                                
                        return $emp_detail && $emp_detail->department && $emp_detail->department->id == $request->department;
                    });
                }

                // Position filter
                if ($request->has('position') && !empty($request->position)) {
                    $collection = $collection->filter(function ($item) use ($request) {
                        // Get the employee details
                        $emp_detail = Employee::find($item['employee_id']);
                        
                        // Filter employees by position
                        return $emp_detail && $emp_detail->position && $emp_detail->position->id == $request->position;
                    });
                }

                // Date range filter
                if ($request->has('start_date') && $request->has('end_date')) {
                    $startDate = Carbon::parse($request->start_date)->startOfDay();
                    $endDate = Carbon::parse($request->end_date)->endOfDay();
                    
                    // Filter based on payroll period
                    if ($payroll->start_date && $payroll->end_date) {
                        $payrollStartDate = Carbon::parse($payroll->start_date);
                        $payrollEndDate = Carbon::parse($payroll->end_date);
                        
                        // Check if requested date range overlaps with payroll period
                        if ($startDate->lte($payrollEndDate) && $endDate->gte($payrollStartDate)) {
                            // Date range overlaps with payroll period, keep the collection
                        } else {
                            // Date range doesn't overlap, empty the collection
                            $collection = $collection->where('id', 0); // This will effectively empty the collection
                        }
                    }
                }

                $query->collection = $collection;
            })
            ->addColumn('employee_name', function ($employee) {
                $emp_detail = Employee::with('resortAdmin')->where('id', $employee->employee_id)->first();
                return optional($emp_detail->resortAdmin)->first_name . " " . optional($emp_detail->resortAdmin)->last_name ?? 'N/A';
            })
            ->addColumn('department', function ($employee) {
                $emp_detail = Employee::with('department')->where('id',$employee->employee_id)->first();

                return optional($emp_detail->department)->name ?? 'N/A';
            })
            ->addColumn('position', function ($employee) {
                $emp_detail = Employee::with('position')->where('id',$employee->employee_id)->first();

                return optional($emp_detail->position)->position_title ?? 'N/A';
            })
            ->addColumn('hire_date', function ($employee) {
                $emp_detail = Employee::with('position')->where('id',$employee->employee_id)->first();

                return $emp_detail->joining_date ? \Carbon\Carbon::parse($emp_detail->joining_date)->format('d M Y') : 'N/A';
            })
            ->addColumn('present_days', function ($employee) use ($payroll) {
                $emp_detail = Employee::with('position')->where('id',$employee->employee_id)->first();

                return optional($payroll->timeAndAttendances->where('employee_id', $emp_detail->id)->first())->present_days ?? 0;
            })
            ->addColumn('total_OTPay', function ($employee) use ($payroll) {
                $emp_detail = Employee::with('position')->where('id',$employee->employee_id)->first();

                $earnings_overtime = ($payroll->reviews->where('employee_id', $emp_detail->id)->first())->earnings_overtime ?? 0;
               return number_format($earnings_overtime , 2);            
            })
            ->addColumn('service_charge', function ($employee) use ($payroll) {
                $emp_detail = Employee::with('position')->where('id',$employee->employee_id)->first();

                $service_charge = ($payroll->reviews->where('employee_id', $emp_detail->id)->first())->service_charge ?? 0;
               return number_format($service_charge , 2);            
            })
            ->addColumn('basic_pay', function ($employee) use ($payroll) {
                $emp_detail = Employee::with('position')->where('id',$employee->employee_id)->first();

               $earnings_basic = ($payroll->reviews->where('employee_id', $emp_detail->id)->first())->earnings_basic ?? 0;
               return number_format($earnings_basic , 2);
            })
            ->addColumn('earned_salary', function ($employee) use ($payroll) {
                $emp_detail = Employee::with('position')->where('id',$employee->employee_id)->first();

               $earnings_salary = ($payroll->reviews->where('employee_id', $emp_detail->id)->first())->earned_salary ?? 0;
               return number_format($earnings_salary , 2);
            })           
            ->addColumn('total_allowance', function ($employee) use ($payroll) {
               $emp_detail = Employee::with('position')->where('id',$employee->employee_id)->first();

               $earnings_allowance = ($payroll->reviews->where('employee_id', $emp_detail->id)->first())->earnings_allowance ?? 0;
               return number_format($earnings_allowance , 2);
            })
            ->addColumn('total_pay', function ($employee) use ($payroll) {
                $emp_detail = Employee::with('position')->where('id',$employee->employee_id)->first();

                $total_earnings = optional($payroll->reviews->where('employee_id', $emp_detail->id)->first())->total_earnings ?? 0;
                return number_format($total_earnings , 2);
            })
            ->addColumn('deductions', function ($employee) use ($payroll) {
                $emp_detail = Employee::with('position')->where('id',$employee->employee_id)->first();

                $total_deductions = optional($payroll->reviews->where('employee_id', $emp_detail->id)->first())->total_deductions ?? 0;
                return number_format($total_deductions , 2);
            })
            ->addColumn('net_pay', function ($employee) use ($payroll) {
                $emp_detail = Employee::with('position')->where('id',$employee->employee_id)->first();

                $net_salary = optional($payroll->reviews->where('employee_id', $emp_detail->id)->first())->net_salary ?? 0;
                return number_format($net_salary , 2);
            });
            
            foreach ($allowanceColumns as $allowance) {
                $query->addColumn($allowance, function ($employee) use ($allowance, $payroll) {
                    $review = $payroll->reviews->where('employee_id', $employee->employee_id)->first();
                    $allowanceAmount = 0;
                    if ($review && $review->allowances) {
                        $allowanceAmount = optional($review->allowances->firstWhere('allowance_type', $allowance))->amount ?? 0;
                    }                   
                    return number_format($allowanceAmount, 2);
                });        
            }   
            return $query->make(true);
    }

    // public function getPayrollColumns($payroll_id)
    // {
    //     $resortId = $this->resort->resort_id;

    //     $payroll = Payroll::with(['employees', 'reviews', 'deductions'])->findOrFail($payroll_id);

    //     if (!$payroll) {
    //         return response()->json([
    //             "draw" => request()->input('draw'),
    //             "recordsTotal" => 0,
    //             "recordsFiltered" => 0,
    //             "data" => []
    //         ]);
    //     }

    //     $resortId = is_array($resortId) ? $resortId : [$resortId]; // Ensure it's an array

    //     $allowanceColumns = DB::table('resort_budget_costs')
    //         ->whereIn('resort_id', $resortId)
    //         ->where('status', 'active')
    //         ->where("particulars", "!=", "Basic Salary")
    //         ->where("particulars", "!=", "Pension ( employer Contibution)")
    //         ->where("particulars", "!=", "Pension International")
    //         ->where('cost_title', 'Operational Cost')
    //         ->where('frequency', 'Monthly')
    //         ->distinct()
    //         ->pluck('particulars')
    //         ->toArray();

    //     // Merge all dynamic columns
    //     $dynamicColumns = array_merge($allowanceColumns);
    //     // dd($dynamicColumns);
       
    //     return response()->json([
    //         'success' => true,
    //         'columns' => $dynamicColumns
    //     ]);
       
        
       
    // }

    public function getPayrollColumns($payroll_id)
    {
        $payroll = Payroll::with('reviews.allowances')->findOrFail($payroll_id);

        if (!$payroll) {
            return response()->json([
                "draw" => request()->input('draw'),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            ]);
        }

        // Extract all unique allowance types from payroll reviews
        $allowanceColumns = collect($payroll->reviews)
            ->flatMap(function ($review) {
                return $review->allowances->pluck('allowance_type');
            })
            ->unique()
            ->values()
            ->toArray();

        return response()->json([
            'success' => true,
            'columns' => $allowanceColumns
        ]);
    }

    public function getActivityLog(Request $request,$payroll_id)
    {
        $logs = DB::table('payroll_attendance_activity_log as paal')
            ->join('payroll as p', 'p.id', '=', 'paal.payroll_id')
            ->join('employees as user', 'user.id', '=', 'paal.user_id') // User who made the change
            ->join('employees as employee', 'employee.id', '=', 'paal.employee_id') // Employee whose data changed
            // Join resort_admins for the employee
            ->join('resort_admins as employee_admin', 'employee_admin.id', '=', 'employee.Admin_Parent_id') 
            // Join resort_admins for the user who made the change
            ->join('resort_admins as user_admin', 'user_admin.id', '=', 'user.Admin_Parent_id')
            ->where('paal.payroll_id', $payroll_id)
            ->orderBy('paal.updated_at', 'desc')
            ->select(
                'paal.*',
                'employee_admin.first_name as employee_first_name',
                'employee_admin.last_name as employee_last_name',
                'user_admin.first_name as updated_by_first_name', // User who made the change
                'user_admin.last_name as updated_by_last_name'  // User who made the change
            );

            // Apply the search filter if a search term is provided
            if ($request->searchTerm) {
                $searchTerm = $request->searchTerm;
                $logs->where(function ($q) use ($searchTerm) {
                    $q->where('employee_admin.first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('employee_admin.last_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('user_admin.first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('user_admin.last_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('paal.field', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('paal.old_value', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('paal.new_value', 'LIKE', "%{$searchTerm}%");
                });
            }


            $logs = $logs->get();
    
        $datatable = datatables()->of($logs)
            ->addColumn('employee', function ($log) {
                return $log->employee_first_name . ' ' . $log->employee_last_name;
            })
            ->addColumn('updated_by', function ($log) {
                return $log->updated_by_first_name . ' ' . $log->updated_by_last_name;
            })
            ->addColumn('updated_at', function ($log) {
                return \Carbon\Carbon::parse($log->updated_at)->format('d M Y, H:i'); // Format updated_at
            })
            ->rawColumns(['employee', 'updated_by'])
            ->make(true);
    
        return $datatable;
    }
    
    // Add this method in your PayrollController
    public function showActivityLog($encoded_payroll_id)
    {
        $page_title ='Payroll Attendance Activity Log';
        $payroll_id = base64_decode($encoded_payroll_id);

        // Pass the logs to the view
        return view('resorts.payroll.run.activity-log',compact('page_title','payroll_id'));

    }

    public function getNotes($payroll_id)
    {
        $notes = DB::table('payroll_time_and_attandance')
            ->where('payroll_id', $payroll_id)
            ->whereNotNull('notes')
            ->get(['employee_id', 'notes']);
    
        // Attach Employee Names
        $notes->transform(function ($note) {
            $employee = Employee::with('resortAdmin')->find($note->employee_id);
            $note->employee_name = $employee ? $employee->resortAdmin->first_name . " " . $employee->resortAdmin->last_name : "Unknown";
            return $note;
        });
    
        return response()->json([
            'success' => true,
            'data' => $notes
        ]);
    }

    public function downloadPayroll(Request $request, $payroll_id)
    {
        try {
            $payroll = Payroll::with([
                'employees.employee.resortAdmin',
                'employees.employee.department',
                'employees.employee.position',
                'timeAndAttendances',
                'serviceCharges',
                'deductions',
                'reviews',
                'reviews.allowances' // Ensure this relationship is defined in your Payroll model
            ])->findOrFail($payroll_id);

            // Initialize query for employees
            $employees = $payroll->employees;

            // Apply filters if they exist
            if ($request->filled('searchTerm') || $request->filled('department') || 
                $request->filled('position') || ($request->filled('start_date') && $request->filled('end_date'))) {
                
                // Get filtered data using existing getPayrollData method
                $filteredData = $this->getPayrollData($request, $payroll_id)->getData();
                
                // Extract employee IDs from filtered data
                $filteredEmployeeIds = collect($filteredData->data)->pluck('employee_id')->toArray();
                
                // Filter the employees collection
                $employees = $employees->filter(function($employee) use ($filteredEmployeeIds) {
                    return in_array($employee->employee_id, $filteredEmployeeIds);
                });
            }

            // Create new Spreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $headers = [
                'Employee ID',
                'Emp ID',
                'Name',
                'Department',
                'Position',
                'Hire Date',
                'No. of Days',
                'Total OT Amount',
                'Service Charge',
                'Basic Pay',
                'Earned Salary',
            ];

            // Add allowance headers
            $allowanceColumns = collect($payroll->reviews)
            ->flatMap(function ($review) {
                return $review->allowances->pluck('allowance_type');
            })
            ->unique()
            ->values()
            ->toArray();

            $headers = array_merge($headers, $allowanceColumns, ['Total Allowances','Total Earnings','Deduction','Net Pay']);

            // Write headers and style them
            foreach ($headers as $index => $header) {
                $column = chr(65 + $index);
                $sheet->setCellValue($column . '1', $header);
                $sheet->getStyle($column . '1')->getFont()->setBold(true);
                $sheet->getStyle($column . '1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E0E0E0');
            }

            // Write data
            $row = 2;
            foreach ($employees as $employee) {
                $emp_detail = Employee::with(['resortAdmin', 'department', 'position'])
                    ->where('id', $employee->employee_id)
                    ->first();

                if (!$emp_detail || !$emp_detail->resortAdmin) {
                    continue;
                }

                $col = 'A';
                // Employee ID
                $sheet->setCellValue($col++ . $row, $employee->employee_id);
                // Emp_ID
                $sheet->setCellValue($col++ . $row, $emp_detail->Emp_id);
                // Name
                $sheet->setCellValue($col++ . $row, $emp_detail->resortAdmin->first_name . ' ' . $emp_detail->resortAdmin->last_name);
                // Department
                $sheet->setCellValue($col++ . $row, optional($emp_detail->department)->name ?? 'N/A');
                // Position
                $sheet->setCellValue($col++ . $row, optional($emp_detail->position)->position_title ?? 'N/A');
                // Hire Date
                $sheet->setCellValue($col++ . $row, $emp_detail->joining_date ? \Carbon\Carbon::parse($emp_detail->joining_date)->format('d M Y') : 'N/A');
                // Present Days
                $sheet->setCellValue($col++ . $row, optional($payroll->timeAndAttendances->where('employee_id', $emp_detail->id)->first())->present_days ?? 0);
                // Total OT Pay
                $earnings_overtime = optional($payroll->reviews->where('employee_id', $emp_detail->id)->first())->earnings_overtime ?? 0;
                $sheet->setCellValue($col++ . $row, $earnings_overtime);
                
                // Service Days
                $service_charge = optional($payroll->reviews->where('employee_id', $emp_detail->id)->first())->service_charge ?? 0;
                $sheet->setCellValue($col++ . $row, $service_charge);                
                // Basic Pay
                $earnings_basic = optional($payroll->reviews->where('employee_id', $emp_detail->id)->first())->earnings_basic ?? 0;
                $sheet->setCellValue($col++ . $row, $earnings_basic);

                 // Earned Salary
                $earnings_salary = optional($payroll->reviews->where('employee_id', $emp_detail->id)->first())->earned_salary ?? 0;
                $sheet->setCellValue($col++ . $row, $earnings_salary);
                                
               foreach ($allowanceColumns as $allowance) {
                    $review = $payroll->reviews->where('employee_id', $emp_detail->id)->first();
                    $allowanceAmount = 0;
                    if ($review && $review->allowances) {
                        $allowanceAmount = optional($review->allowances->firstWhere('allowance_type', $allowance))->amount ?? 0;
                    }
                    $sheet->setCellValue($col++ . $row, $allowanceAmount);
                }
                // Total Allowance
                $earnings_allowance = optional($payroll->reviews->where('employee_id', $emp_detail->id)->first())->earnings_allowance ?? 0;
                $sheet->setCellValue($col++ . $row, $earnings_allowance);

                // Total Earnings
                $total_earnings = optional($payroll->reviews->where('employee_id', $emp_detail->id)->first())->total_earnings ?? 0;
                $sheet->setCellValue($col++ . $row, $total_earnings);

                 // Total Deductions
                $total_deductions = optional($payroll->reviews->where('employee_id', $emp_detail->id)->first())->total_deductions ?? 0;
                $sheet->setCellValue($col++ . $row, $total_deductions);
                
                // net_salary
                $net_salary = optional($payroll->reviews->where('employee_id', $emp_detail->id)->first())->net_salary ?? 0;
                $sheet->setCellValue($col++ . $row, $net_salary);          
                
                $row++;
            }

            // Format numbers in currency columns
            $lastRow = $row - 1;
            foreach (range('H', $col) as $column) { // Starting from Basic Pay column
                $sheet->getStyle($column . '2:' . $column . $lastRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            }

            // Auto-size columns
            foreach (range('A', $col) as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            // Create Excel file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $fileName = 'Payroll_Report_' . date('Y-m-d') . '.xlsx';
            
            // Save to temp file and return as download
            $temp_file = tempnam(sys_get_temp_dir(), $fileName);
            $writer->save($temp_file);
            
            return response()->download($temp_file, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function fetchAdvanceRecovery(Request $request)
    {
        $employeeIds = $request->employee_ids;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $currency = $request->input('currency', 'Dollar'); // Default currency is USD
        $conversionRate = floatval($request->input('conversionRate', 1)); // Default 1 (no conversion)
        $salary_currency = "Dollar"; 

        $recoveryData = PayrollRecoverySchedule::whereIn('employee_id', $employeeIds)
            ->where('status', 'Pending')
            ->whereBetween('repayment_date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id')
            ->map(function ($group) {
                return $group->sum(function ($item) {
                    return ($item->amount ?? 0) + ($item->interest_amount ?? 0);
                });
            });

        return response()->json(['success' => true,'data' => $recoveryData]);
    }

    public function isRamadanMonth($carbonDate)
    {
        $day = $carbonDate->format('d');
        $month = $carbonDate->format('m');
        $year = $carbonDate->format('Y');

        $response = Http::get("https://api.aladhan.com/v1/gToH", [
            'date' => "$day-$month-$year"
        ]);

        if ($response->successful()) {
            $hijriMonth = $response['data']['hijri']['month']['number'];
            return $hijriMonth == 9; // 9 = Ramadan
        }

        return false;
    }

    public function downloadBankAndCashSheets(Request $request, $payroll_id)
    {
        try {
            $payroll = Payroll::with([
                'employees.employee.resortAdmin',
                'employees.employee.department',
                'employees.employee.position',
                'employees.employee.bankDetails', // required
                'reviews.allowances'
            ])->findOrFail($payroll_id);

            $spreadsheet = new Spreadsheet();

            // Split employees by payment mode
            $employees = $payroll->employees;
            $cashEmployees = $employees->filter(fn($emp) => $emp->employee->payment_mode === 'Cash');
            $bankEmployees = $employees->filter(fn($emp) => $emp->employee->payment_mode === 'Bank');

            // Add cash sheet
            $this->addCashSheet($spreadsheet, $cashEmployees, $payroll);

            // ✅ Add unified bank employees to both sheets
            $this->addBankSheet($spreadsheet, $bankEmployees, $payroll, 'Bank Sheet - MVR', 'MVR');
            $this->addBankSheet($spreadsheet, $bankEmployees, $payroll, 'Bank Sheet - USD', 'USD');

            $writer = new Xlsx($spreadsheet);
            $fileName = 'Payroll_Sheets_' . now()->format('Y_m_d') . '.xlsx';
            $temp_file = tempnam(sys_get_temp_dir(), $fileName);
            $writer->save($temp_file);

            return response()->download($temp_file, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function addCashSheet($spreadsheet, $employees, $payroll)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cash Sheet');

        // Title Row
        $sheet->mergeCells('A1:F1')->setCellValue('A1', 'Cash Sheet - ' . Carbon::parse($payroll->start_date)->format('F Y'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header Row
        $headers = ['No', 'Emp ID', 'Name', 'Position', 'Net Pay', 'Signature'];
        foreach ($headers as $i => $header) {
            $col = chr(65 + $i); // A, B, C...
            $sheet->setCellValue($col . '2', $header);
            $sheet->getStyle($col . '2')->getFont()->setBold(true);
            $sheet->getStyle($col . '2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        }

        $row = 3;
        foreach ($employees as $index => $employee) {
            $emp = $employee->employee;
            $review = $payroll->reviews->where('employee_id', $emp->id)->first();

            // dd($review);
            $sheet->setCellValue("A$row", $index + 1);
            $sheet->setCellValue("B$row", $emp->Emp_id);
            $sheet->setCellValue("C$row", $emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name);
            $sheet->setCellValue("D$row", optional($emp->position)->position_title ?? '');
            $sheet->setCellValue("E$row", $review->net_salary ?? 0);
            $sheet->setCellValue("F$row", '');
            $row++;
        }

        // Currency format
        $sheet->getStyle("E3:E" . ($row - 1))
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

        // Autosize
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function addBankSheet($spreadsheet, $employees, $payroll, $sheetName, $currency = 'USD')
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($sheetName);

        // Header row
        $headers = ['SL.NO', 'ACCOUNT NUMBER', 'ACCOUNT NAME', 'AMOUNT (' . $currency . ')'];
        foreach ($headers as $i => $header) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        }

        $row = 2;
        $total = 0;

        foreach ($employees as $index => $employee) {
            $emp = $employee->employee;
            $review = $payroll->reviews->where('employee_id', $emp->id)->first();
            if (!$review) continue;

            $bankAccount = optional($emp->bankDetails)->firstWhere('currency', $currency);
            $accountNumber = $bankAccount?->account_no ?? 'N/A';

            $amount = 0;

            // dd($currency);

            if ($currency === 'USD') {
                // Basic salary (only if in USD)
                if ($emp->basic_salary_currency === 'USD') {
                    $amount += $emp->basic_salary ?? 0;
                }
                // dd($amount);
                // Allowances in USD
                $amount += $review->allowances
                    ->where('amount_unit', 'USD')
                    ->sum('amount');

                // OT and SC always in USD
                $amount += $review->earnings_overtime ?? 0;
                $amount += $review->service_charge ?? 0;

                // Subtract total deductions (in USD)
                $amount -= $review->total_deductions ?? 0;

                $commission = 3.25; 

                // dd($amount);
            }

            if ($currency === 'MVR') {
                // Only include MVR allowances
                $amount += $review->allowances
                    ->where('amount_unit', 'MVR')
                    ->sum('amount');
                    // dd($amount);
                $commission = 50.00; 
            }

            // Skip if amount is zero or less
            if ($amount <= 0) continue;

            $sheet->setCellValue("A$row", $row - 1);
            $sheet->setCellValue("B$row", $accountNumber);
            $sheet->setCellValue("C$row", $emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name);
            $sheet->setCellValue("D$row", $amount);

            $total += $amount;
            $row++;
        }

        // Totals
        $sheet->setCellValue("C$row", 'Total');
        $sheet->setCellValue("D$row", $total);
        $row++;

        $sheet->setCellValue("C$row", 'Commission');
        $sheet->setCellValue("D$row", $commission);
        $row++;

        $grandTotal = $total + $commission;
        $sheet->setCellValue("C$row", 'Grand Total');
        $sheet->setCellValue("D$row", $grandTotal);

        // Format amount cells
        $sheet->getStyle("D2:D$row")
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

        // Autosize columns
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}