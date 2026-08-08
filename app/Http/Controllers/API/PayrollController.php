<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\Employee;
use App\Models\Payroll;
use App\Helpers\Common;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Mail;
use App\Mail\SharePayslipMail; 
use Carbon\Carbon;
use Validator;
use Auth;
use DB;
use File;

class PayrollController extends Controller
{
    protected $user;
    protected $resort_id;
  
    public function __construct()
    {
        if (Auth::guard('api')->check()) {
            $this->user = Auth::guard('api')->user();
            $this->resort_id = $this->user->resort_id;
        }
    }

    public function payrollDashboard(Request $request)
    {

        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $employee_id                                    =   $this->user->GetEmployee->id;
        try {
            
            $year                                       =   $request->input('year', date('Y'));

        // Get payroll data for the selected year
            $payrollData                                =   DB::table('payroll')
                                                            ->select(
                                                                DB::raw("MONTHNAME(start_date) as month"),
                                                                DB::raw("SUM(total_payroll) as payroll_cost")
                                                            )
                                                            ->whereYear('start_date', $year)
                                                            ->groupBy('month')
                                                            ->get();

            $otData                                     =   DB::table('payroll as p')
                                                            ->join('payroll_time_and_attandance as pta', 'p.id', '=', 'pta.payroll_id')
                                                            ->join('employees as e', 'e.id', '=', 'pta.employee_id')
                                                            ->whereYear('start_date', $year)
                                                            ->where('pta.employee_id',$employee_id)
                                                            ->where(function ($query) {
                                                                $query->where('pta.regular_ot_hours', '>', 0)
                                                                    ->orWhere('pta.holiday_ot_hours', '>', 0);
                                                            }) // Only fetch employees with OT
                                                            ->selectRaw("
                                                                MONTHNAME(p.start_date) as month,
                                                                pta.employee_id,
                                                                e.basic_salary,
                                                                pta.regular_ot_hours,
                                                                pta.holiday_ot_hours,
                                                                (pta.regular_ot_hours * e.basic_salary * 1.25) as regular_ot_cost,
                                                                (pta.holiday_ot_hours * e.basic_salary * 1.50) as holiday_ot_cost,
                                                                ((pta.regular_ot_hours * e.basic_salary * 1.25) + 
                                                                (pta.holiday_ot_hours * e.basic_salary * 1.50)) as ot_cost
                                                            ")->get();
        
            // Get service charge data
            $serviceChargeData                          =   DB::table('payroll_service_charges')
                                                            ->join('payroll','payroll.id','=','payroll_service_charges.payroll_id')
                                                            ->where('payroll_service_charges.employee_id',$employee_id)
                                                            ->select(
                                                                DB::raw("MONTHNAME(payroll.start_date) as month"),
                                                                DB::raw("SUM(payroll_service_charges.service_charge_amount) as service_charge")
                                                            )
                                                            ->whereYear('payroll.start_date', $year)
                                                            ->groupBy('month')
                                                            ->get();
                                                            
            // Generate labels (Months)
            $months                                     =   ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

            // Initialize arrays
            $payrollCost                                =   array_fill(0, 12, 0);
            $otCost                                     =   array_fill(0, 12, 0);
            $serviceCharge                              =   array_fill(0, 12, 0);

            // Map database results to correct month index
            foreach ($payrollData as $row) {
                $index                                  = array_search($row->month, $months);
                if ($index !== false) {
                    $payrollCost[$index]                = $row->payroll_cost;
                }
            }

            foreach ($otData as $row) {
                $index                                  = array_search(strtolower($row->month), array_map('strtolower', $months));
            
                if ($index !== false) {
                    if (!isset($otCost[$index])) {
                        $otCost[$index]                 = 0;
                    }
                    $otCost[$index]                     += $row->ot_cost; // Accumulate OT costs
                }
            }

            foreach ($serviceChargeData as $row) {
                $index                                  = array_search($row->month, $months);
                if ($index !== false) {
                    $serviceCharge[$index]              = $row->service_charge;
                }
            }
       
            // payroll_deductions/payroll_reviews/payroll_service_charges don't
            // necessarily have a row for every employee in every payroll run
            // (e.g. no service charge for non-tipped departments) — these
            // used to be INNER JOINs, so any employee missing even one of
            // those three rows for the period got $payroll = null and every
            // field silently defaulted to 0 below, even though their payroll
            // genuinely ran. Left-joined instead; every field already has a
            // '?? 0' fallback for a genuinely missing value.
            $payroll                                    = Payroll::join('payroll_employees as pe','pe.payroll_id','=','payroll.id')
                                                            ->join('employees as e','e.id','=','pe.employee_id')
                                                            ->join('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
                                                            ->join('resort_positions as rp','rp.id','=','e.Position_id')
                                                            ->join('resort_departments as rd','rd.id','=','e.Dept_id')
                                                            ->leftJoin('payroll_deductions as pd', function($j) use ($employee_id) {
                                                                $j->on('pd.payroll_id','=','payroll.id')->where('pd.employee_id',$employee_id);
                                                            })
                                                            // Was a left join — several of an employee's payroll_employees
                                                            // rows are just roster entries with earnings never actually
                                                            // calculated (no payroll_reviews row at all yet), so picking
                                                            // "the latest period" alone could still land on an
                                                            // unprocessed one and show 0. Requiring the review row to
                                                            // exist means this always lands on the latest period that
                                                            // was genuinely processed. Deductions/service-charge stay
                                                            // left-joined — those can legitimately be absent even for a
                                                            // fully processed period (e.g. no service charge for a
                                                            // non-tipped role).
                                                            ->join('payroll_reviews as pr', function($j) use ($employee_id) {
                                                                $j->on('pr.payroll_id','=','payroll.id')->where('pr.employee_id',$employee_id);
                                                            })
                                                            ->leftJoin('payroll_service_charges as psc', function($j) use ($employee_id) {
                                                                $j->on('psc.payroll_id','=','payroll.id')->where('psc.employee_id',$employee_id);
                                                            })
                                                            ->where('pe.employee_id',$employee_id)
                                                            // Was hardcoded to exactly last calendar month — if payroll
                                                            // for that specific month hasn't been run yet (a common lag;
                                                            // e.g. today is in August but the latest processed payroll
                                                            // run only covers 26 Mar-25 Apr), this matched nothing and
                                                            // every field on the dashboard silently showed 0 even though
                                                            // the employee's payslip history (a separate endpoint) has
                                                            // real data. Show whatever the most recently processed
                                                            // payroll period actually is instead of demanding an exact
                                                            // month match.
                                                            ->orderBy('payroll.start_date', 'desc')
                                                            ->select(
                                                                'payroll.*',
                                                                'ra.first_name',
                                                                'ra.last_name',
                                                                'ra.profile_picture',
                                                                'ra.id as admin_id',
                                                                'rp.position_title as position',
                                                                'rd.name as department',
                                                                'e.joining_date',
                                                                'e.Emp_id',
                                                                'e.basic_salary',
                                                                'psc.total_working_days',
                                                                'psc.service_charge_amount',
                                                                'pr.earnings_allowance',
                                                                'pr.earnings_basic',
                                                                'pd.ewt',
                                                                'pd.staff_shop',
                                                                'pd.pension',
                                                                'pd.attendance_deduction',
                                                                'pd.city_ledger',
                                                                'pd.other',
                                                                'pd.total_deductions'
                                                            )
                                                            ->first();
                // if (!$payroll) {
                //     return response()->json(['error' => 'Payroll data not found'], 404);
                // }

                $earningsTotal                          =   ($payroll->earnings_basic ?? 0) + ($payroll->service_charge_amount ?? 0) + ($payroll->earnings_allowance ?? 0);

                $totalAmount                            =   ($payroll->earnings_basic ?? 0)+ ($payroll->service_charge_amount ?? 0) + ($payroll->earnings_allowance ?? 0 ) - ($payroll->total_deductions ?? 0);

                $payrollNetSalary                           =   ($payroll->earnings_allowance ?? 0) + ($payroll->earnings_basic ?? 0) - ($payroll->total_deductions ?? 0);
                $data = [
                    'payrollCost'                       => $payrollCost,
                    'otCost'                            => $otCost,
                    'serviceCharge'                     => $serviceCharge,
                    'salary'                            => round($payroll->earnings_basic ?? 0,2),
                    'earnings'                          => round($earningsTotal, 2),  // Ensuring two decimal places
                    'deductions'                        => round($payroll->total_deductions ?? 0, 2),
                    'pension_total'                     => round(($payroll->pension ?? 0) * 2, 2),
                    'pension'                           => [
                    'employee_pension'                  => round($payroll->pension ?? 0,2),
                    'employer_pension'                  => round($payroll->pension ?? 0,2),
                    // No per-employee/per-resort pension rate is stored
                    // anywhere in the schema (checked — same dead
                    // '$employee->contribution' reference, always falling
                    // back to this same hardcoded value, exists in the web
                    // Pension module too) — a real configurable rate needs a
                    // new settings field, not a query fix.
                    'pension_percentage'                => '7%',
                    ],
                    'city_ledger'                       => round($payroll->city_ledger ?? 0, 2),
                    'payslip_details'                   => [
                    'payslip_total'                     => round($totalAmount, 2),
                    'payslip_start_date'                => $payroll->start_date ?? 0 ,
                    'payslip_end_date'                  => $payroll->end_date ?? 0,
                    ],
                    'net_salary'                        =>  number_format($payrollNetSalary,2),
                ];
            
        return response()->json(['success' => true, 'message' => 'Payroll Employee Dashboard', 'payroll_data' => $data], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function paySlipList(Request $request)
    {

        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        $employee_id                                    =   $this->user->GetEmployee->id;
        $year                                           =   $request->year ?? Carbon::now()->format('Y');

        try {
             // Fetch Employee Details
            $employee                                   =   Employee::join('resort_admins as ra','ra.id','=','employees.Admin_Parent_id')
                                                                ->join('resort_positions as rp','rp.id','=','employees.Position_id')
                                                                ->join('resort_departments as rd','rd.id','=','employees.Dept_id')
                                                                ->where('employees.id',$employee_id)
                                                                ->select( 
                                                                    'employees.id', 'employees.Emp_id', 'ra.id as parentId',
                                                                    'ra.first_name', 'ra.last_name', 'ra.profile_picture',
                                                                    'ra.id as admin_id', 'rp.position_title as position','rd.name as department'
                                                                )->first();
            if (!$employee) {
                return response()->json(['error' => 'Employee not found'], 200);
            }
                                                               
            $employee->profile_picture                  =   Common::getResortUserPicture($employee->parentId);
             
            // Fetch Last Month's Payroll Data. payroll_time_and_attandance /
            // payroll_reviews / payroll_deductions don't necessarily have a
            // row for every employee in every run — was INNER JOIN, so a
            // missing row on any one of the three silently dropped this to
            // "no payroll data" even though the employee's payroll ran fine.
            $payrollNetSalAndOT                         =   Payroll::join('payroll_employees as pe','pe.payroll_id','=','payroll.id')
                                                            ->leftJoin('payroll_time_and_attandance as ptaa', function($j) use ($employee_id) {
                                                                $j->on('ptaa.payroll_id','=','payroll.id')->where('ptaa.employee_id',$employee_id);
                                                            })
                                                            // Inner join, not left — several payroll_employees rows are
                                                            // roster-only with earnings never calculated (no
                                                            // payroll_reviews row yet); requiring it here means "latest
                                                            // period" (below) always lands on one that was genuinely
                                                            // processed, same reasoning as payrollDashboard().
                                                            ->join('payroll_reviews as pr', function($j) use ($employee_id) {
                                                                $j->on('pr.payroll_id','=','payroll.id')->where('pr.employee_id',$employee_id);
                                                            })
                                                            ->leftJoin('payroll_deductions as pd', function($j) use ($employee_id) {
                                                                $j->on('pd.payroll_id','=','payroll.id')->where('pd.employee_id',$employee_id);
                                                            })
                                                            ->where('pe.employee_id',$employee_id)
                                                            // Same "exactly last calendar month" bug as payrollDashboard()
                                                            // — shows the most recently processed period instead of
                                                            // demanding an exact match, so this header snapshot doesn't
                                                            // go blank while the list right below it (queried by year
                                                            // only) has real rows for the same employee.
                                                            ->orderBy('payroll.start_date', 'desc')
                                                            ->select(
                                                                'payroll.id', 'ptaa.total_ot', 'pr.earnings_allowance',
                                                                'pr.earnings_basic', 'pd.total_deductions'
                                                            )->first();
             // Ensure payroll data is always present
            $payrollNetSalAndOT                         =   (object) [
                'id'                                    =>   $payrollNetSalAndOT->id ?? null,
                'total_ot'                              =>   $payrollNetSalAndOT->total_ot ?? 0,
                'earnings_allowance'                    =>   $payrollNetSalAndOT->earnings_allowance ?? 0,
                'earnings_basic'                        =>   $payrollNetSalAndOT->earnings_basic ?? 0,
                'total_deductions'                      =>   $payrollNetSalAndOT->total_deductions ?? 0
            ];
        
            $payrollNetSalAndOT->net_salary = round(($payrollNetSalAndOT->earnings_allowance ?? 0) + ($payrollNetSalAndOT->earnings_basic ?? 0) - ($payrollNetSalAndOT->total_deductions ?? 0), 2);
                                                            
            // This is the actual "View All Payslips" list — was INNER JOIN
            // on deductions/reviews/service_charges, so any payroll period
            // missing even one of those three rows for this employee (e.g.
            // no service charge for a non-tipped role) was silently dropped
            // from the whole list, not just shown with a zero — reported as
            // "no payslips are available" and "March payroll data is not
            // displayed despite being included in payroll".
            $payroll                                    =   Payroll::join('payroll_employees as pe','pe.payroll_id','=','payroll.id')
                                                            ->join('employees as e','e.id','=','pe.employee_id')
                                                            ->leftJoin('payroll_deductions as pd', function($j) use ($employee_id) {
                                                                $j->on('pd.payroll_id','=','payroll.id')->where('pd.employee_id',$employee_id);
                                                            })
                                                            ->leftJoin('payroll_reviews as pr', function($j) use ($employee_id) {
                                                                $j->on('pr.payroll_id','=','payroll.id')->where('pr.employee_id',$employee_id);
                                                            })
                                                            ->leftJoin('payroll_service_charges as psc', function($j) use ($employee_id) {
                                                                $j->on('psc.payroll_id','=','payroll.id')->where('psc.employee_id',$employee_id);
                                                            })
                                                            ->where('pe.employee_id',$employee_id)
                                                            ->whereYear('payroll.start_date', $year)
                                                            ->select(
                                                                'payroll.id',  'payroll.resort_id', 'payroll.start_date',
                                                                'payroll.end_date', 'payroll.payment_date', 'psc.service_charge_amount',
                                                                'pr.earnings_allowance', 'pr.earnings_basic', 'pd.total_deductions'
                                                            )->get()->map(function($row){
                                                                $row->net_salary = round(($row->earnings_allowance ?? 0) + ($row->earnings_basic ?? 0) - ($row->total_deductions ?? 0), 2);
                                                                return $row;
                                                            });
                                                            
                if (!$payroll) {
                    return response()->json(['success' => false, 'error' => 'Payroll data not found'], 200);
                }
                $data = [
                    'employee_data'                     =>  $employee,
                    'payroll_net_sal_ot'                =>  $payrollNetSalAndOT,
                    'payslip_list_data'                 =>  $payroll,
                ];

        return response()->json(['success' => true, 'message' => 'Payroll Employee Dashboard', 'payslip_data' => $data], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function paySlipDetails(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        $validator = Validator::make($request->all(), [
            'month'                                 => 'required',
            'year'                                  => 'required',
           
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        
        $employee_id                                    =   $this->user->GetEmployee->id;
        $year                                           =   $request->year ?? Carbon::now()->format('Y');
        $month                                          =   $request->month;
       
        try {
             
            // Fetch Last Month's Payroll Data. Deductions/service-charge stay
            // left-joined (a missing row there just means $0 deductions/no
            // service charge, still a real payslip) — but payroll_reviews
            // must be an inner join: no payroll_reviews row means this
            // payroll_employees row is roster-only, earnings never
            // calculated (same reasoning already applied in
            // payrollDashboard()/paySlipList()'s header-snapshot query).
            // Left-joining it here meant a real processed payslip could lose
            // to a roster-only placeholder for the same month via
            // ->first(), returning an all-null/all-zero "payslip" for a
            // period that actually has real data (this is exactly what the
            // Payslip List screen — queried without this bug — showed
            // correctly while this detail endpoint showed zeros).
            $payroll                                    =   Payroll::join('payroll_employees as pe','pe.payroll_id','=','payroll.id')
                                                                ->join('employees as e','e.id','=','pe.employee_id')
                                                                ->join('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
                                                                ->join('resort_positions as rp','rp.id','=','e.Position_id')
                                                                ->join('resort_departments as rd','rd.id','=','e.Dept_id')
                                                                ->leftJoin('payroll_deductions as pd', function($j) use ($employee_id) {
                                                                    $j->on('pd.payroll_id','=','payroll.id')->where('pd.employee_id',$employee_id);
                                                                })
                                                                ->join('payroll_reviews as pr', function($j) use ($employee_id) {
                                                                    $j->on('pr.payroll_id','=','payroll.id')->where('pr.employee_id',$employee_id);
                                                                })
                                                                ->leftJoin('payroll_service_charges as psc', function($j) use ($employee_id) {
                                                                    $j->on('psc.payroll_id','=','payroll.id')->where('psc.employee_id',$employee_id);
                                                                })
                                                                ->where('pe.employee_id',$employee_id);
                                                                if($month) {
                                                                    // A pay period is labelled by the month it ENDS in
                                                                    // (e.g. 26 Feb - 25 Mar is "March's" payslip — matches
                                                                    // how the Payslip List screen already displays it), not
                                                                    // the month it starts in. Filtering on start_date meant
                                                                    // asking for "March" never matched this period at all
                                                                    // (its start_date is in February), even though it's
                                                                    // exactly the real, fully-processed period the list
                                                                    // screen shows for that card.
                                                                    $payroll->whereMonth('payroll.end_date', $month);
                                                                }

            $payroll                                    =   $payroll->whereYear('payroll.start_date', $year)
                                                                // Two genuinely-processed periods can both end in the
                                                                // same month (e.g. a correction/re-run) — most recently
                                                                // created wins, matching real data where a later run
                                                                // (higher id) is the one with the actually-current figures.
                                                                ->orderBy('payroll.id', 'desc')
                                                                ->select(
                                                                    'payroll.*', 'ra.first_name', 'ra.last_name', 'ra.profile_picture',
                                                                    'ra.id as admin_id', 'rp.position_title as position', 'rd.name as department', 'e.joining_date',
                                                                    'e.Emp_id', 'psc.total_working_days', 'psc.service_charge_amount', 'pr.earnings_basic',
                                                                    'pr.earnings_allowance', 'pd.ewt', 'pd.staff_shop', 'pd.pension',
                                                                    'pd.attendance_deduction','pd.city_ledger', 'pd.other', 'pd.total_deductions'
                                                                )->first();
            if (!$payroll) {
                return response()->json(['success' => false, 'error' => 'Payroll data not found'], 200);
            }
                                                        
            $totalAmount                                =   ($payroll->earnings_basic ?? 0) + ($payroll->service_charge_amount ?? 0) + ($payroll->earnings_allowance ?? 0) - ($payroll->total_deductions ?? 0);
            $earningtotalAmount                         =   ($payroll->earnings_basic ?? 0) + ($payroll->earnings_allowance?? 0);
            $payrollNetSalary                           =   ($payroll->earnings_allowance ?? 0) + ($payroll->earnings_basic ?? 0) - ($payroll->total_deductions ?? 0);
           
            $payrollEmpData                             =   [
                'Emp_id'                                =>  $payroll->Emp_id,
                'first_name'                            =>  $payroll->first_name,
                'last_name'                             =>  $payroll->last_name,
                'position'                              =>  $payroll->position,
                'department'                            =>  $payroll->department,
                'daywork'                               =>  $payroll->total_working_days,
                'joining_date'                          =>  $payroll->joining_date,
                'start_date'                            =>  $payroll->start_date,
                'end_date'                              =>  $payroll->end_date,
                'profile_picture'                       =>  Common::getResortUserPicture($payroll->id),
            ];

            $bankDetails                                =   [
                'total_amount'                          =>  number_format($totalAmount, 2),
            ];

            $earningDetails                             =   [
                'basic_pay'                             =>  $payroll->earnings_basic,
                'allowance'                             =>  $payroll->earnings_allowance,
                'bonus'                                 =>  '',
                'earning_total_amount'                  =>  number_format($earningtotalAmount,2),
            ];

            $deductionsDetails                          =   [
                'monthly_tax_deduction:'                =>  $payroll->ewt,
                'insurance:'                            =>  '',
                'loans'                                 =>  '',
                'city_ledger'                           =>  $payroll->city_ledger,
                'total_deductions'                      =>  $payroll->total_deductions ?? 0,
            ];

            $payrollArray                               =   [
                'employee'                              =>  $payrollEmpData,
                'bank_details'                          =>  $bankDetails,
                'earning_details'                       =>  $earningDetails,
                'deductions_details'                    =>  $deductionsDetails,
                'net_salary'                            =>  number_format($payrollNetSalary,2),
            ];

        return response()->json(['success' => true, 'message' => 'Payslip Employee Details', 'payslip_data' => $payrollArray], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function downloadPayslip(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        $validator = Validator::make($request->all(), [
            'month'                                 => 'required',
            'year'                                  => 'required',
           
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $employee_id                                    =   $this->user->GetEmployee->id;
        $year                                           =   $request->year ?? Carbon::now()->format('Y');
        $month                                          =   $request->month;
       
        try {
             
            // Fetch Last Month's Payroll Data. Deductions/service-charge stay
            // left-joined (a missing row there just means $0 deductions/no
            // service charge, still a real payslip) — but payroll_reviews
            // must be an inner join: no payroll_reviews row means this
            // payroll_employees row is roster-only, earnings never
            // calculated (same reasoning already applied in
            // payrollDashboard()/paySlipList()'s header-snapshot query).
            // Left-joining it here meant a real processed payslip could lose
            // to a roster-only placeholder for the same month via
            // ->first(), returning an all-null/all-zero "payslip" for a
            // period that actually has real data (this is exactly what the
            // Payslip List screen — queried without this bug — showed
            // correctly while this detail endpoint showed zeros).
            $payroll                                    =   Payroll::join('payroll_employees as pe','pe.payroll_id','=','payroll.id')
                                                                ->join('employees as e','e.id','=','pe.employee_id')
                                                                ->join('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
                                                                ->join('resort_positions as rp','rp.id','=','e.Position_id')
                                                                ->join('resort_departments as rd','rd.id','=','e.Dept_id')
                                                                ->leftJoin('payroll_deductions as pd', function($j) use ($employee_id) {
                                                                    $j->on('pd.payroll_id','=','payroll.id')->where('pd.employee_id',$employee_id);
                                                                })
                                                                ->join('payroll_reviews as pr', function($j) use ($employee_id) {
                                                                    $j->on('pr.payroll_id','=','payroll.id')->where('pr.employee_id',$employee_id);
                                                                })
                                                                ->leftJoin('payroll_service_charges as psc', function($j) use ($employee_id) {
                                                                    $j->on('psc.payroll_id','=','payroll.id')->where('psc.employee_id',$employee_id);
                                                                })
                                                                ->where('pe.employee_id',$employee_id);
                                                                if($month) {
                                                                    // A pay period is labelled by the month it ENDS in
                                                                    // (e.g. 26 Feb - 25 Mar is "March's" payslip — matches
                                                                    // how the Payslip List screen already displays it), not
                                                                    // the month it starts in. Filtering on start_date meant
                                                                    // asking for "March" never matched this period at all
                                                                    // (its start_date is in February), even though it's
                                                                    // exactly the real, fully-processed period the list
                                                                    // screen shows for that card.
                                                                    $payroll->whereMonth('payroll.end_date', $month);
                                                                }

            $payroll                                    =   $payroll->whereYear('payroll.start_date', $year)
                                                                // Two genuinely-processed periods can both end in the
                                                                // same month (e.g. a correction/re-run) — most recently
                                                                // created wins, matching real data where a later run
                                                                // (higher id) is the one with the actually-current figures.
                                                                ->orderBy('payroll.id', 'desc')
                                                                ->select(
                                                                    'payroll.*', 'ra.first_name', 'ra.last_name', 'ra.profile_picture',
                                                                    'ra.id as admin_id', 'rp.position_title as position', 'rd.name as department', 'e.joining_date',
                                                                    'e.Emp_id', 'psc.total_working_days', 'psc.service_charge_amount', 'pr.earnings_basic',
                                                                    'pr.earnings_allowance', 'pd.ewt', 'pd.staff_shop', 'pd.pension',
                                                                    'pd.attendance_deduction','pd.city_ledger', 'pd.other', 'pd.total_deductions'
                                                                )->first();
            if (!$payroll) {
                return response()->json(['success' => false, 'error' => 'Payroll data not found'], 200);
            }

            
       
                                                        
            $totalAmount                                =   ($payroll->earnings_basic ?? 0) + ($payroll->service_charge_amount ?? 0) + ($payroll->earnings_allowance ?? 0) - ($payroll->total_deductions ?? 0);
            $earningtotalAmount                         =   ($payroll->earnings_basic ?? 0) + ($payroll->earnings_allowance?? 0);
            $payrollNetSalary                           =   ($payroll->earnings_allowance ?? 0) + ($payroll->earnings_basic ?? 0) - ($payroll->total_deductions ?? 0);
           
            $payrollEmpData                             =   [
                'admin_id'                              =>  $payroll->admin_id,
                'Emp_id'                                =>  $payroll->Emp_id,
                'first_name'                            =>  $payroll->first_name,
                'last_name'                             =>  $payroll->last_name,
                'position'                              =>  $payroll->position,
                'department'                            =>  $payroll->department,
                'daywork'                               =>  $payroll->total_working_days,
                'joining_date'                          =>  $payroll->joining_date,
                'start_date'                            =>  $payroll->start_date,
                'end_date'                              =>  $payroll->end_date,
                // 'profile_picture'                       =>  Common::getResortUserPicture($payroll->admin_id),
                'profile_picture'                       =>  $payroll->profile_picture,
            ];

            $bankDetails                                =   [
                'total_amount'                          =>  number_format($totalAmount, 2),
            ];

            $earningDetails                             =   [
                'basic_pay'                             =>  $payroll->earnings_basic,
                'allowance'                             =>  $payroll->earnings_allowance,
                'bonus'                                 =>  '',
                'earning_total_amount'                  =>  number_format($earningtotalAmount,2),
            ];

            $deductionsDetails                          =   [
                'monthly_tax_deduction'                 =>  $payroll->ewt,
                'insurance'                             =>  '',
                'loans'                                 =>  '',
                'city_ledger'                           =>  $payroll->city_ledger,
                'total_deductions'                      =>  $payroll->total_deductions ?? 0,
            ];

            if( isset($payroll->profile_picture) && $payroll->profile_picture)
            {
                $profilePicturePath = public_path(config('settings.ResortProfile_folder') . '/' . $payroll->profile_picture);
    
                if (file_exists($profilePicturePath))
                {
                    $profilePicture = public_path(config('settings.ResortProfile_folder') . '/' . $payroll->profile_picture);
                }
                else
                {
                    $profilePicture = public_path(config('settings.default_picture'));
                }
            }
            else
            {
                $profilePicture = public_path(config('settings.default_picture'));
            }
    
            $type = pathinfo($profilePicture, PATHINFO_EXTENSION);
            $data = file_get_contents($profilePicture);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);


            $payrollArray                               =   [
                'employee'                              =>  $payrollEmpData,
                'bank_details'                          =>  $bankDetails,
                'earning_details'                       =>  $earningDetails,
                'deductions_details'                    =>  $deductionsDetails,
                'net_salary'                            =>  number_format($payrollNetSalary,2),
                'profile_image'                                 =>  $base64,
            ];

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Poppins');


            // Convert Options Object to Array
            $optionsArray                               =   [
                'isRemoteEnabled'                       =>  true,
                'defaultFont'                           =>  'Poppins'
            ];

                        
            $pdf                                        =   Pdf::loadView('pdf.payslippdf', compact('payrollArray'));
            $pdf->setOptions($optionsArray);
            $folderPath = public_path(config('settings.PayslipPdf'));

            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }


            $filePath                                   =   public_path(config('settings.PayslipPdf').'/'. time() . '_payslip.pdf');
                                                            file_put_contents($filePath, $pdf->output());
                        
            $pdfUrl                                     =   asset(config('settings.PayslipPdf').'/'. basename($filePath));

            return response()->json([
                'success'                               => true,
                'pdf_url'                               => $pdfUrl,
            ]);
            
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function shareEmailPayslip(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'month'                                 => 'required',
            'year'                                  => 'required',
           
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $employee_id                                    =   $this->user->GetEmployee->id;
        $month                                          =   $request->month;
        $year                                           =   $request->year;

        try {

            $employee                                       =   Employee::with('resortAdmin')->find($employee_id);
            if (!$employee || !$employee->resortAdmin) {
                return response()->json(['success' => false, 'message' => 'Employee not found.']);
            }

            $email                                          =   $employee->resortAdmin->email;
        
            // Generate Payslip URL
            $payslipUrl                                     =   route('payslip.show', ['employee_id' => $employee_id, 'month' => $month, 'year' => $year]);

            // Send Email (Using Laravel Mail)
            Mail::to($email)->send(new SharePayslipMail($employee, $payslipUrl));

            return response()->json(['success' => true, 'message' => 'Payslip shared successfully.']);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send email.']);
        }

    }

}
