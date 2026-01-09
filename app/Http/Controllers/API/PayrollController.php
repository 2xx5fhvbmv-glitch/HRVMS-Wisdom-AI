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
       
            $lastMonth                                  = Carbon::now()->subMonth()->format('m'); // Example: "03" for March
            $currentYear                                = Carbon::now()->format('Y'); // Example: "2025"

            $payroll                                    = Payroll::join('payroll_employees as pe','pe.payroll_id','=','payroll.id')
                                                            ->join('employees as e','e.id','=','pe.employee_id')
                                                            ->join('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
                                                            ->join('resort_positions as rp','rp.id','=','e.Position_id')
                                                            ->join('resort_departments as rd','rd.id','=','e.Dept_id')
                                                            ->join('payroll_deductions as pd','pd.payroll_id','=','payroll.id')
                                                            ->join('payroll_reviews as pr','pr.payroll_id','=','payroll.id')
                                                            ->join('payroll_service_charges as psc','psc.payroll_id','=','payroll.id')
                                                            ->where('pe.employee_id',$employee_id)
                                                            ->where('psc.employee_id',$employee_id)
                                                            ->where('pr.employee_id',$employee_id)
                                                            ->where('pd.employee_id',$employee_id)
                                                            ->whereMonth('payroll.start_date', '01')
                                                            ->whereYear('payroll.start_date', '2025')
                                                            // ->whereMonth('payroll.start_date', $lastMonth)
                                                            // ->whereYear('payroll.start_date', $currentYear)
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

                $totalAmount                            =   ($payroll->earnings_basic ?? 0)+ ($payroll->service_charge_amount ?? 0) + ($payroll->earnings_allowance ?? 0 ) - ($payroll->total_deduction ?? 0);

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
                    'pension_percentage'                => isset($employee->contribution) ? $employee->contribution . '%' : '7%',
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
        $lastMonth                                      =   Carbon::now()->subMonth()->format('m');
        $currentYear                                    =   Carbon::now()->format('Y');

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
             
            // Fetch Last Month's Payroll Data
            $payrollNetSalAndOT                         =   Payroll::join('payroll_employees as pe','pe.payroll_id','=','payroll.id')
                                                            ->join('payroll_time_and_attandance as ptaa','ptaa.payroll_id','=','payroll.id')
                                                            ->join('payroll_reviews as pr','pr.payroll_id','=','payroll.id')
                                                            ->join('payroll_deductions as pd','pd.payroll_id','=','payroll.id')
                                                            ->where('pe.employee_id',$employee_id)
                                                            ->where('ptaa.employee_id',$employee_id)
                                                            ->where('pr.employee_id',$employee_id)
                                                            ->where('pd.employee_id',$employee_id)
                                                            ->whereMonth('payroll.start_date', $lastMonth)
                                                            ->whereYear('payroll.start_date', $currentYear)
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
                                                            
            $payroll                                    =   Payroll::join('payroll_employees as pe','pe.payroll_id','=','payroll.id')
                                                            ->join('employees as e','e.id','=','pe.employee_id')
                                                            ->join('payroll_deductions as pd','pd.payroll_id','=','payroll.id')
                                                            ->join('payroll_reviews as pr','pr.payroll_id','=','payroll.id')
                                                            ->join('payroll_service_charges as psc','psc.payroll_id','=','payroll.id')
                                                            ->where('pe.employee_id',$employee_id)
                                                            ->where('psc.employee_id',$employee_id)
                                                            ->where('pr.employee_id',$employee_id)
                                                            ->where('pd.employee_id',$employee_id)
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
             
            // Fetch Last Month's Payroll Data
            $payroll                                    =   Payroll::join('payroll_employees as pe','pe.payroll_id','=','payroll.id')
                                                                ->join('employees as e','e.id','=','pe.employee_id')
                                                                ->join('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
                                                                ->join('resort_positions as rp','rp.id','=','e.Position_id')
                                                                ->join('resort_departments as rd','rd.id','=','e.Dept_id')
                                                                ->join('payroll_deductions as pd','pd.payroll_id','=','payroll.id')
                                                                ->join('payroll_reviews as pr','pr.payroll_id','=','payroll.id')
                                                                ->join('payroll_service_charges as psc','psc.payroll_id','=','payroll.id')
                                                                ->where('pe.employee_id',$employee_id)
                                                                ->where('psc.employee_id',$employee_id)
                                                                ->where('pr.employee_id',$employee_id)
                                                                ->where('pd.employee_id',$employee_id);
                                                                if($month) {
                                                                    $payroll->whereMonth('payroll.start_date', $month);
                                                                }

            $payroll                                    =   $payroll->whereYear('payroll.start_date', $year)
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
                                                        
            $totalAmount                                =   ($payroll->earnings_basic ?? 0) + ($payroll->service_charge_amount ?? 0) + ($payroll->earnings_allowance ?? 0) - ($payroll->total_deduction);
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
             
            // Fetch Last Month's Payroll Data
            $payroll                                    =   Payroll::join('payroll_employees as pe','pe.payroll_id','=','payroll.id')
                                                                ->join('employees as e','e.id','=','pe.employee_id')
                                                                ->join('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
                                                                ->join('resort_positions as rp','rp.id','=','e.Position_id')
                                                                ->join('resort_departments as rd','rd.id','=','e.Dept_id')
                                                                ->join('payroll_deductions as pd','pd.payroll_id','=','payroll.id')
                                                                ->join('payroll_reviews as pr','pr.payroll_id','=','payroll.id')
                                                                ->join('payroll_service_charges as psc','psc.payroll_id','=','payroll.id')
                                                                ->where('pe.employee_id',$employee_id)
                                                                ->where('psc.employee_id',$employee_id)
                                                                ->where('pr.employee_id',$employee_id)
                                                                ->where('pd.employee_id',$employee_id);
                                                                if($month) {
                                                                    $payroll->whereMonth('payroll.start_date', $month);
                                                                }

            $payroll                                    =   $payroll->whereYear('payroll.start_date', $year)
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

            
       
                                                        
            $totalAmount                                =   ($payroll->earnings_basic ?? 0) + ($payroll->service_charge_amount ?? 0) + ($payroll->earnings_allowance ?? 0) - ($payroll->total_deduction);
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
