<?php

namespace App\Http\Controllers\Resorts\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;
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
use App\Models\PayrollDeduction;
use App\Models\PayrollEmployees;
use App\Models\PayrollReview;
use App\Models\PayrollServiceCharge;
use App\Models\PayrollTimeAndAttendance;
use App\Models\FinalSettlement;
use App\Models\FinalSettlementDeductions;
use App\Models\EmployeeResignation;
use App\Models\FinalSettlementEarnings;
use App\Models\EmployeePromotion;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Services\FinalSettlementService;

use App\Mail\SharePayslipMail; // Import your Mailable class
use App\Mail\FinalSettlementMail;
use Auth;
use Config;
use DB;
use Common;
use Carbon\Carbon;

class PayslipController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index(Request $request)
    {
        $page_title ='Payslip';
        $resort_id = $this->resort->resort_id;
        $employees = Employee::with('resortAdmin')->where('resort_id',$resort_id)->get();
        $positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
        return view('resorts.payroll.payslip.index',compact('page_title','positions','departments','employees'));
    }

    public function getEmployees(Request $request)
    {
        $resort_id = auth()->user()->resort_id;
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
                })->orWhereHas('resortAdmin', function ($q2) use ($request) {
                    $q2->where('email', 'LIKE', "%{$request->searchTerm}%");
                });
            });
        }

        if ($request->department) {
            $query->where('Dept_id', $request->department);
        }

        if ($request->position) {
            $query->orwhere('Position_id', $request->position);
        }

        $query = $query->get();
    

        $datatable = datatables()->of($query)
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
        ->addColumn('email', function ($employee) {
            return [
                'email' => $employee->resortAdmin->email ?? 'N/A',
            ];
        })
        
        ->rawColumns(['id', 'employee', 'position', 'department'])
        ->make(true);

        // ✅ Inject totalChecked into the JSON response
        $jsonData = $datatable->getData();

        return response()->json($jsonData);
    }

    public function viewPayslip(Request $request)
    {
        $employeeId = $request->employee_id;
        $month = $request->month;
        $year = $request->year;

        // Fetch payroll details — prioritize locked/completed payrolls over drafts
        $payroll = Payroll::join('payroll_employees as pe', 'pe.payroll_id', '=', 'payroll.id')
            ->where('pe.employee_id', $employeeId)
            ->where(function($q) use ($month, $year) {
                $q->where(function($q2) use ($month, $year) {
                    $q2->whereMonth('payroll.end_date', $month)->whereYear('payroll.end_date', $year);
                })->orWhere(function($q2) use ($month, $year) {
                    $q2->whereMonth('payroll.start_date', $month)->whereYear('payroll.start_date', $year);
                });
            })
            ->orderByRaw("FIELD(payroll.status, 'locked', 'completed', 'approved', 'pending_approval', 'draft')")
            ->first();

        if (!$payroll) {
            return response()->json(['success' => false, 'message' => 'Payslip not found']);
        }

        // Store data in session
        session([
            'payslip_employee_id' => $employeeId,
            'payslip_month' => $month,
            'payslip_year' => $year
        ]);

        return response()->json(['success' => true]);
    }

    public function showPayslip(Request $request)
    {
        $employeeId = session('payslip_employee_id');
        $month = session('payslip_month');
        $year = session('payslip_year');

        // dd($employeeId,$month, $year );

        $employee = Employee::with('resortAdmin')->find($employeeId);
        if (!$employee) {
            return redirect()->back()->with('error', 'Employee not found.');
        }

        // dd($employeeId,$year,$month);
        // Fetch payroll details for the selected month & year
        // $payroll = Payroll::join('payroll_employees as pe','pe.payroll_id','=','payroll.id')
        // ->join('employees as e','e.id','=','pe.employee_id')
        // ->join('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
        // ->join('resort_positions as rp','rp.id','=','e.Position_id')
        // ->join('resort_departments as rd','rd.id','=','e.Dept_id')
        // ->join('employee_bank_details as ebd','ebd.employee_id','=','e.id')
        // ->join('payroll_deductions as pd','pd.payroll_id','=','payroll.id')
        // ->join('payroll_reviews as pr','pr.payroll_id','=','payroll.id')
        // ->join('payroll_review_allowances as pra','pra.payroll_review_id','=','pr.id')
        // ->join('payroll_service_charges as psc','psc.payroll_id','=','payroll.id')
        // ->where('pe.employee_id',$employeeId)
        // ->where('psc.employee_id',$employeeId)
        // ->where('pr.employee_id',$employeeId)
        // ->where('pd.employee_id',$employeeId)
        // ->whereMonth('payroll.start_date', $month)
        // ->whereYear('payroll.end_date', $year)
        // ->select(
        //     'payroll.*','pr.*',
        //     'pra.*',
        //     'ra.first_name',
        //     'ra.last_name',
        //     'ra.profile_picture',
        //     'ra.id as admin_id',
        //     'rp.position_title as position',
        //     'rd.name as department',
        //     'e.joining_date',
        //     'e.Emp_id',
        //     'e.payment_mode',
        //     'ebd.bank_name',
        //     'ebd.bank_branch',
        //     'ebd.account_type','ebd.IFSC_BIC','ebd.account_holder_name','ebd.account_no','ebd.IBAN',
        //     'psc.total_working_days',
        //     'psc.service_charge_amount',
        //     'pd.ewt',
        //     'pd.staff_shop',
        //     'pd.pension',
        //     'pd.attendance_deduction',
        //     'pd.city_ledger',
        //     'pd.other',
        // )
        // ->first();

        $page_title = "View Payslip";
        $payroll = Payroll::with([
            'employees' => function($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            },
            'employees.employee.resortAdmin',
            'employees.employee.department',
            'employees.employee.position',
            'employees.employee.bankDetails',
            'reviews' => function($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            },
            'reviews.allowances',
            'deductions' => function($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            },
            'serviceCharges' => function($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            },
            'timeAndAttendances' => function($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            }
        ])
        ->where(function($q) use ($month, $year) {
            // Match by end_date month/year (payroll is identified by its end period)
            $q->where(function($q2) use ($month, $year) {
                $q2->whereMonth('end_date', $month)->whereYear('end_date', $year);
            })
            // Also try start_date in case user selected the start month
            ->orWhere(function($q2) use ($month, $year) {
                $q2->whereMonth('start_date', $month)->whereYear('start_date', $year);
            });
        })
        ->whereHas('employees', function($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })
        ->orderByRaw("FIELD(status, 'locked', 'completed', 'approved', 'pending_approval', 'draft')")
        ->first();
        // dd($payroll);
        if (!$payroll) {
            return redirect()->back()->with('error', 'Payslip not found for the selected month and year.');
        }

        // Generate the payslip view URL dynamically
        return view('resorts.payroll.payslip.payslip', compact('payroll','page_title'));
    }

    // public function sharePayslip(Request $request)
    // {
    //     $employeeId = $request->employee_id;
    //     $month = $request->month;
    //     $year = $request->year;

    //     // Fetch Employee Email
    //     $employee = Employee::with('resortAdmin')->find($employeeId);
    //     if (!$employee || !$employee->resortAdmin) {
    //         return response()->json(['success' => false, 'message' => 'Employee not found.']);
    //     }

    //     $email = $employee->resortAdmin->email;
        
    //     // Generate Payslip URL
    //     $payslipUrl = route('payslip.show', ['employee_id' => $employeeId, 'month' => $month, 'year' => $year]);

    //     // Send Email (Using Laravel Mail)
    //     try {
    //         Mail::to($email)->send(new SharePayslipMail($employee, $payslipUrl));
    //         return response()->json(['success' => true, 'message' => 'Payslip shared successfully.']);
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'message' => 'Failed to send email.']);
    //     }
    // }

    public function sharePayslip(Request $request)
    {
        set_time_limit(180);

        $employeeId = $request->employee_id;
        $month = $request->month;
        $year = $request->year;

        $employee = Employee::with(['resortAdmin', 'position', 'department', 'bankDetails'])->find($employeeId);

        if (!$employee || !$employee->resortAdmin) {
            return response()->json(['success' => false, 'message' => 'Employee not found or not assigned to any resort.']);
        }

        $payroll = Payroll::with([
            'employees' => fn($q) => $q->where('employee_id', $employeeId),
            'employees.employee.resortAdmin',
            'employees.employee.department',
            'employees.employee.position',
            'employees.employee.bankDetails',
            'reviews' => fn($q) => $q->where('employee_id', $employeeId),
            'reviews.allowances',
            'deductions' => fn($q) => $q->where('employee_id', $employeeId),
            'serviceCharges' => fn($q) => $q->where('employee_id', $employeeId),
            'timeAndAttendances' => fn($q) => $q->where('employee_id', $employeeId)
        ])
            ->whereMonth('start_date', $month)
            ->whereYear('end_date', $year)
            ->whereHas('employees', fn($q) => $q->where('employee_id', $employeeId))
            ->first();

        if (!$payroll) {
            return response()->json(['success' => false, 'message' => 'Payroll record not found.']);
        }

        try {
            $review = $payroll->reviews->first();
            $serviceCharge = $payroll->serviceCharges->first();
            $deductions = $payroll->deductions->first();

            $earnedSalary = optional($review)->earned_salary ?? 0;
            $deductionAmount = optional($deductions)->total_deductions ?? 0;
            $serviceAmount = optional($serviceCharge)->amount ?? 0;

            $net_salary = $earnedSalary - $deductionAmount + $serviceAmount;

            // Generate PDF
            $pdf = Pdf::loadView('resorts.payroll.payslip.payslip-view', compact(
                'employee', 'payroll', 'net_salary', 'earnedSalary', 'deductions', 'review', 'serviceCharge'
            ));

            // Save the PDF temporarily
            $fileName = 'Payslip-' . $employee->resortAdmin->first_name . '-' . $month . '-' . $year . '.pdf';
            $pdfPath = storage_path('app/' . $fileName);
            $pdf->save($pdfPath);

            // Send the email
            if (file_exists($pdfPath)) {
                Mail::to($employee->resortAdmin->email)->send(new SharePayslipMail($employee, $month, $year, $pdfPath, $fileName));
                return response()->json(['success' => true, 'message' => 'Payslip shared successfully.']);

            } else {
                // Log or return error
                \Log::error("Payslip PDF not found at $pdfPath");
                return response()->json(['success' => false, 'message' => 'Payslip PDF not found at'. $pdfPath]);

            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function finalsettlement(Request $request)
    {
        if(Common::checkRouteWisePermission('payslip.finalsettlement',config('settings.resort_permissions.create')) == false)
        {
            return redirect()->route('final.settlement.list');
        }
        $page_title ='Full and Final Settlement';
        $resort_id = $this->resort->resort_id;
        $employees = EmployeeResignation::with('employee.resortAdmin')->where('resort_id',$resort_id)->where('status','Approved')->where('full_and_final_settlement','no')->get();
        $positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
        $deductions = Deduction::where('resort_id',$resort_id)->get();
        $earnings = Earnings::where('resort_id',$resort_id)->get();

        // Optional pre-select when deep-linked from Exit Clearance "Full
        // And Final Settlement" button — that view passes `?empId=` as a
        // base64 of the employees.id. Validate the id is actually in the
        // approved-resignation list for this resort so we don't preselect
        // an unrelated employee. The blade reads $preselectedEmployeeId
        // to mark the matching <option selected> and to fire the existing
        // getEmpDetails() AJAX onload so all the salary/leave/pension
        // fields auto-populate.
        $preselectedEmployeeId = null;
        if ($request->filled('empId')) {
            $decoded = base64_decode((string) $request->empId, true);
            if ($decoded !== false && ctype_digit((string) $decoded)) {
                $candidate = (int) $decoded;
                if ($employees->contains(fn($r) => optional($r->employee)->id === $candidate)) {
                    $preselectedEmployeeId = $candidate;
                }
            }
        }
        return view('resorts.payroll.payslip.fullandfinalsettlement',compact('page_title','positions','departments','employees','deductions','earnings','preselectedEmployeeId'));
    }

    public function getEmployeeDetails(Request $request, FinalSettlementService $settlementService)
    {
        $resort_id = $this->resort->resort_id;
        $employee = Employee::where('id', $request->employee_id)
            ->with(['resortAdmin', 'position', 'department','section','resignation','allowance','advancedPaymentRecovey'])
            ->first();
    
        if (!$employee) {
            return response()->json(["success" => false, "message" => "Employee not found."]);
        }
        // dd($employee);

        $payrollData = $settlementService->calculateFinalMonthData($employee, $resort_id);
        // dd($payrollData);
       
        // dd( $totalAllocatedDays,$totalAvailableDays);
        $formattedHireddate = $employee->joining_date ? Carbon::createFromFormat('Y-m-d', $employee->joining_date)->format('d M Y') : null;
        // Last Promotion Date — only counts APPROVED promotions, ordered by
        // effective_date desc. Pending or Rejected rows shouldn't show up as
        // "last promotion" on the Initiate Promotion screen.
        $last_promotion = EmployeePromotion::where('employee_id', $request->employee_id)
            ->where('status', 'Approved')
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();
        $formattedLastPromotionDate = ($last_promotion && $last_promotion->effective_date)
            ? Carbon::parse($last_promotion->effective_date)->format('d M Y')
            : null;
        return response()->json([
            "success" => true,
            "data" => [
                "emp_id" => $employee->Emp_id,
                "full_name" => $employee->resortAdmin->full_name,
                "profile_picture" => Common::getResortUserPicture($employee->resortAdmin->id),
                "position" => $employee->position->position_title ?? "N/A",
                "department" => $employee->department->name ?? "N/A",
                "resignation_date" => $employee->resignation->resignation_date ?? "N/A",
                "last_working_day" => $employee->resignation->last_working_day ?? "N/A",
                "dept_id" => $employee->department->id ?? 'N/A',
                "pos_id" => $employee->position->id ?? 'N/A',
                "section" => $employee->section->name ?? null,
                "section_id" => $employee->section->id ?? null,
                "hired_date" => $formattedHireddate ?? "N/A",
                "basic_salary" => $employee->basic_salary ?? "N/A",
                'proratedBasic' => $payrollData['proratedBasic'] ?? 0,
                "benefit_grid_level" => $employee->benefit_grid_level ?? "N/A",
                "benefit_grid_url" => $employee->benefit_grid_level ? route("benefit.grid.view", ['level' => $employee->benefit_grid_level]) : null ,
                "job_desc_url" => $employee->position->id ? route("job.description.by.position", ['posId' => $employee->position->id]) : null ,         
                "last_promotion_date" => $formattedLastPromotionDate,
                'leave_balance' => $payrollData['leave_balance'],
                'leave_encashment' => $payrollData['leave_encashment'],
                'pension' => $payrollData['pension'],
                'ewt' => $payrollData['ewt'],
                'daily_salary' => $payrollData['daily_salary'],
                'final_month_days' => $payrollData['total_days'],
                'worked_days' => $payrollData['worked_days'],
                'loan_recovery' => $payrollData['loan_recovery'],
                'basic_salary_mvr' => $payrollData['basic_salary_mvr'],
                'allowances_mvr' => $payrollData['total_allowances_mvr'],
                'allowances' => $payrollData['allowances'],
                'regular_ot_amount' => $payrollData['regular_ot_amount'],
                'holiday_ot_amount' => $payrollData['holiday_ot_amount'],
                'total_ot_amount' => $payrollData['total_ot_amount'],
                'ramadan_bonus' => $payrollData['ramadan_bonus'] ?? 0,
                'earned_salary' => $payrollData['earned_salary'],
                'payment_mode' => $employee->payment_mode ?? 'Cash',
                'payroll_start' => $payrollData['payroll_start'] ?? Carbon::now()->format('d M Y'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        // Lock guard: a finalized F&F can't be re-edited. The form on
        // /resort/final-settlement is normally hidden behind a list-page
        // click, but a stale tab or a direct POST would otherwise sail
        // through updateOrCreate() and silently rewrite the row.
        $existing = FinalSettlement::where('employee_id', $request->input('select_emp'))->first();
        if ($existing && $existing->status === 'finalized') {
            return response()->json([
                'success' => false,
                'message' => 'This settlement is already finalized and cannot be re-submitted. '
                    . 'Open it from /resort/final-settlement/list to view the locked record.',
            ], 422);
        }

        $validated = $request->validate([
            'select_emp' => 'required|exists:employees,id',
            'pension' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'leave_balance' => 'required|numeric|min:0',
            'leave_encashment' => 'required|numeric|min:0',
            'loan_payment' => 'required|numeric|min:0',
            'service_charge' => 'required|numeric|min:0',
            'basic_salary' => 'required|numeric|min:0',
            'earned_salary' => 'required|numeric|min:0',
            'payment_mode' => 'nullable|string',
            'reference_no' => 'nullable|string',
            'doc_date' => 'nullable|date',
            'allowances' => 'nullable|string', // JSON string
            'deductions' => 'nullable|string', // JSON string
            'last_working_date' => 'nullable|date',
        ]);

        $deductions = json_decode($request->input('deductions'), true) ?? [];
        $earnings = json_decode($request->input('allowances'), true) ?? [];

        $employee = Employee::findOrFail($validated['select_emp']);
        $settings = ResortSiteSettings::where('resort_id', $this->resort->resort_id)->first();
        $usdToMvr = $settings->DollertoMVR ?? 15.42; // fallback rate

        $convertedDeductions = [];

        foreach ($deductions as $deduction) {
            $amount = floatval($deduction['amount']);
            $unit = strtolower($deduction['unit'] ?? 'mvr');

            $convertedAmount = $unit === 'usd' ? $amount * $usdToMvr : $amount;

            $convertedDeductions[] = [
                'deduction_id' => $deduction['id'],
                'amount' => round($convertedAmount, 2),
                'original_amount' => $amount,
                'amount_unit' => $unit,
            ];
        }

        DB::beginTransaction();
        try {
            $totalDeductionAmount = collect($convertedDeductions)->sum('amount');
            $totalDeductions = $totalDeductionAmount
                 + $validated['pension']
                 + $validated['tax']
                 + $validated['loan_payment'];
            $reference = 'FS-' . now()->format('Ym') . '-' . str_pad($employee->Emp_id, 4, '0', STR_PAD_LEFT);

            // Save or update main final settlement
            $finalSettlement = FinalSettlement::updateOrCreate(
                ['employee_id' => $validated['select_emp']],
                [
                    'pension' => $validated['pension'],
                    'tax' => $validated['tax'],
                    'leave_balance' => $validated['leave_balance'],
                    'leave_encashment' => $validated['leave_encashment'],
                    'loan_payment' => $validated['loan_payment'],
                    'basic_salary' => $validated['basic_salary'],
                    'total_earnings' => $validated['earned_salary'],
                    'service_charge' => $validated['service_charge'],
                    'payment_mode' => $validated['payment_mode'] ?? null,
                    'doc_date' => $validated['doc_date'] ?? now(),
                    'reference_no' => $reference,
                    'total_deductions' => $totalDeductions,
                    'net_pay' => $validated['earned_salary'] - $totalDeductions,
                    'last_working_date' => $validated['last_working_date'] ?? null,
                    'status' => 'review'
                ]
            );

            // Refresh deductions
            FinalSettlementDeductions::where('final_settlement_id', $finalSettlement->id)->delete();

            foreach ($convertedDeductions as $ded) {
                FinalSettlementDeductions::create([
                    'final_settlement_id' => $finalSettlement->id,
                    'deduction_id' => $ded['deduction_id'],
                    'amount' => $ded['amount'], // in MVR
                    'amount_unit' => 'MVR',
                ]);
            }

            // Refresh earnings
            FinalSettlementEarnings::where('final_settlement_id', $finalSettlement->id)->delete();

            foreach ($earnings as $earn) {
                // dd($earn);
                FinalSettlementEarnings::create([
                    'final_settlement_id' => $finalSettlement->id,
                    'earning_id' => $earn['id'], // assuming `id` refers to allowance ID
                    'amount' => $earn['converted_amount'],
                    'amount_unit' => $earn['unit'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Final settlement saved successfully.',
                'final_settlement_id' => $finalSettlement->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to save final settlement.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function review($id)
    {
        if(Common::checkRouteWisePermission('payslip.finalsettlement',config('settings.resort_permissions.view')) == false)
        {
            return abort(403, 'Unauthorized action.');
        }
        $page_title = 'Review Final Settlement';
        $finalSettlement = FinalSettlement::with([
            'employee.resortAdmin',
            'employee.division',
            'employee.department',
            'employee.position',
            // Bank record drives the Bank Name + Account Number block on the
            // review page (was hardcoded to a single placeholder before).
            'employee.bankDetails',
            // Resignation reason header on the review page —
            // previously a lazy fetch + null deref crashed when reason was
            // missing; eager-load + optional() in the view handles it.
            'employee.resignation.reason_title',
            'earnings',
            'deductions',
        ])->findOrFail($id);
        $today = Carbon::now(); 
        // dd($finalSettlement->employee->resignation->reason_title->reason);
        $service = new FinalSettlementService();
        $calculated = $service->calculateFinalMonthData($finalSettlement->employee, $this->resort->resort_id);
        $leaveBalances = $service->getLeaveBalance($finalSettlement->employee, $this->resort->resort_id);
        // dd($calculated,$leaveBalances);
        return view('resorts.payroll.payslip.final_settlement_review', compact(
            'finalSettlement',
            'calculated',
            'leaveBalances',
            'today', // ✅ Pass the $today variable
            'page_title',
        ));
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
    /**
     * Final submission of settlement data.
     */
    public function submit(Request $request)
    {
        $finalSettlement = FinalSettlement::find($request->final_settlement_id);

        if (!$finalSettlement) {
            return response()->json(['success' => false, 'message' => 'No settlement record found.'], 404);
        }

        // Idempotency: once a settlement is finalized it can't be
        // re-finalized. Without this, hitting Submit twice would re-
        // mark loan installments and re-fire the F&F email.
        if ($finalSettlement->status === 'finalized') {
            return response()->json([
                'success' => false,
                'message' => 'This settlement is already finalized and cannot be re-submitted.',
            ], 422);
        }

        \DB::beginTransaction();
        try {
            $finalSettlement->update([
                'status'       => 'finalized',
                'finalized_at' => now(),
                'finalized_by' => Auth::guard('resort-admin')->user()->id ?? null,
            ]);

            // ─── Mark outstanding loan / salary-advance installments as
            // Recovered so the next payroll run doesn't double-deduct
            // them. We only flip rows whose summed amount was actually
            // included in the F&F payout (loan_payment > 0). The DB
            // column update is wrapped in the same transaction as the
            // finalize, so a partial failure rolls both back.
            $loanInPayout = (float) ($finalSettlement->loan_payment ?? 0);
            if ($loanInPayout > 0 && $finalSettlement->employee_id) {
                $now = now();
                \DB::table('payroll_recovery_schedule as rs')
                    ->join('payroll_advance as pa', 'pa.id', '=', 'rs.payroll_advance_id')
                    ->where('pa.employee_id', $finalSettlement->employee_id)
                    ->where('rs.status', 'Pending')
                    ->update([
                        'rs.status'             => 'Recovered',
                        'rs.recovery_date'      => $now,
                        'rs.recovered_via'      => 'final_settlement',
                        'rs.final_settlement_id'=> $finalSettlement->id,
                        'rs.updated_at'         => $now,
                    ]);
            }

            // Dispatch the F&F PDF email to the employee and Finance.
            // Wrapped in try/catch so an SMTP outage doesn't roll back
            // the finalize (the settlement is still valid even if the
            // email fails — Finance can resend from the list page).
            try {
                $this->dispatchFinalSettlementEmail($finalSettlement);
            } catch (\Throwable $emailErr) {
                \Log::warning('F&F email dispatch failed: ' . $emailErr->getMessage(), [
                    'final_settlement_id' => $finalSettlement->id,
                ]);
            }

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error('F&F submit failed', [
                'final_settlement_id' => $finalSettlement->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to finalize settlement: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'Final settlement submitted successfully.']);
    }

    /**
     * Render the F&F PDF, email it to the employee + Finance, save the
     * path on the FinalSettlement row for re-download from the list
     * page. Called from submit() inside the finalize transaction.
     *
     * Failure modes are deliberately soft: an SMTP outage or missing
     * Finance email shouldn't roll back the finalize — Finance can
     * resend from the list page once the issue is fixed. Caller
     * already wraps this in try/catch + Log::warning.
     */
    protected function dispatchFinalSettlementEmail(FinalSettlement $finalSettlement): void
    {
        // Render the same review view to a PDF and persist on disk so
        // Finance can re-download later. Re-use the existing review
        // template — single source of truth for the layout.
        $finalSettlement->loadMissing([
            'employee.resortAdmin',
            'employee.division',
            'employee.department',
            'employee.position',
            'employee.bankDetails',
            'employee.resignation.reason_title',
            'earnings',
            'deductions',
        ]);

        $today      = \Carbon\Carbon::now();
        $service    = new \App\Services\FinalSettlementService();
        $calculated = $service->calculateFinalMonthData(
            $finalSettlement->employee,
            $finalSettlement->employee->resort_id
        );
        $leaveBalances = $service->getLeaveBalance(
            $finalSettlement->employee,
            $finalSettlement->employee->resort_id
        );

        $pdf = \PDF::loadView('resorts.payroll.payslip.final_settlement_review', [
            'finalSettlement' => $finalSettlement,
            'calculated'      => $calculated,
            'leaveBalances'   => $leaveBalances,
            'today'           => $today,
            'page_title'      => 'Final Pay Settlement',
        ])->setPaper('a4', 'portrait');
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);

        // Storage path: storage/app/final-settlements/<resort>/<emp_id>/<reference>.pdf
        $dir = storage_path('app/final-settlements/' . $finalSettlement->employee->resort_id
            . '/' . $finalSettlement->employee->Emp_id);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $finalSettlement->reference_no ?? ('FS-' . $finalSettlement->id)) . '.pdf';
        $pdfPath  = $dir . '/' . $filename;
        file_put_contents($pdfPath, $pdf->output());

        // Persist the path on the settlement row so the list page can
        // surface a "Download PDF" button without re-rendering.
        if (\Schema::hasColumn('final_settlement', 'pdf_path')) {
            $finalSettlement->update(['pdf_path' => $pdfPath]);
        }

        // Recipients: the employee's primary email + any Finance addresses
        // configured for this resort. Falls back to empty CC list when
        // Finance isn't set — the employee still gets their copy.
        $to = optional(optional($finalSettlement->employee)->resortAdmin)->email;
        if (empty($to)) {
            // Without an employee email there's no one to send to. Log
            // and bail; Finance can manually download from the list.
            \Log::info('F&F email skipped — employee has no email on file', [
                'final_settlement_id' => $finalSettlement->id,
            ]);
            return;
        }

        $financeCc = [];
        // Pull resort-configured Finance recipients. Two known fields
        // depending on resort schema age; coalesce gracefully.
        $resort = optional($finalSettlement->employee)->resort;
        if ($resort) {
            $candidate = $resort->finance_email ?? ($resort->finance_contact_email ?? null);
            if (!empty($candidate)) {
                foreach (preg_split('/[;,]+/', $candidate) as $addr) {
                    $addr = trim($addr);
                    if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                        $financeCc[] = $addr;
                    }
                }
            }
        }

        $mailable = new FinalSettlementMail($finalSettlement, $pdfPath);
        $pending  = \Mail::to($to);
        if (!empty($financeCc)) {
            $pending->cc($financeCc);
        }
        $pending->send($mailable);
    }

    public function settlementList()
    {
        if(Common::checkRouteWisePermission('payslip.finalsettlement',config('settings.resort_permissions.view')) == false)
        {
            return abort(403, 'Unauthorized action.');
        }
        $page_title = 'Final Settlements';
        $resort_id = $this->resort->resort_id;
         $positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
        return view('resorts.payroll.payslip.settlement_list', compact('page_title','positions','departments'));
    }

    public function getSettlements(Request $request)
    {
        $resort_id = $this->resort->resort_id;

        $query = FinalSettlement::with(['employee.resortAdmin', 'employee.department', 'employee.position'])
            ->whereHas('employee', function ($q) use ($resort_id) {
                $q->where('resort_id', $resort_id);
            });

        // Search logic
        if ($request->searchTerm) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('employee.resortAdmin', function ($q2) use ($request) {
                    $q2->where('first_name', 'LIKE', "%{$request->searchTerm}%")
                        ->orWhere('last_name', 'LIKE', "%{$request->searchTerm}%")
                        ->orWhere('email', 'LIKE', "%{$request->searchTerm}%");
                })->orWhereHas('employee.department', function ($q2) use ($request) {
                    $q2->where('name', 'LIKE', "%{$request->searchTerm}%");
                })->orWhereHas('employee.position', function ($q2) use ($request) {
                    $q2->where('position_title', 'LIKE', "%{$request->searchTerm}%");
                });
            });
        }

        // Department filter
        if ($request->department) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('Dept_id', $request->department);
            });
        }

        // Position filter
        if ($request->position) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('Position_id', $request->position);
            });
        }

        // Fetch data
        $settlements = $query->get();

        return datatables()->of($settlements)
            ->addColumn('employee', function ($settlement) {
                return [
                    'name' => $settlement->employee->resortAdmin->full_name ?? 'N/A',
                    'emp_id' => $settlement->employee->Emp_id ?? 'N/A',
                    'profile_picture' => Common::getResortUserPicture($settlement->employee->Admin_Parent_id),
                ];
            })
            ->addColumn('position', function ($settlement) {
                return $settlement->employee->position->position_title ?? 'N/A';
            })
            ->addColumn('department', function ($settlement) {
                return $settlement->employee->department->name ?? 'N/A';
            })
            ->addColumn('last_working_date', function ($settlement) {
                return Carbon::parse($settlement->last_working_date)->format('d M Y');
            })
            ->addColumn('net_pay', function ($settlement) {
                return number_format($settlement->net_pay, 2) . ' MVR';
            })
            ->addColumn('status', function ($settlement) {
                $statusClass = [
                    'draft' => 'badge-secondary',
                    'review' => 'badge-warning',
                    'finalized' => 'badge-success',
                ][$settlement->status] ?? 'badge-secondary';

                return '<span class="badge ' . $statusClass . '">' . ucfirst($settlement->status) . '</span>';
            })
            ->addColumn('action', function ($settlement) {
                return '<a href="' . route('final.settlement.review', $settlement->id) . '" class="btn btn-sm btn-primary">Review</a>';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

}