<?php

namespace App\Http\Controllers\Resorts\Visa;

use DB;
use Str;
use Auth;
use Validator;
use Excel;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\Resorts;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VisaRenewal;
use App\Models\Employee;
use App\Models\EmployeeResignation;
use App\Models\VisaWallets;
use App\Models\TotalExpensessSinceJoing;
use App\Models\VisaTransectionHistory;

class LiabilitiesController extends Controller
{

    protected $resort;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $reporting_to = $this->globalUser->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }
    
    public function Index(Request $request)
    {

            if($request->ajax())
            {
                $date = $request->date;
            
                $flags =  ['all'];
                
                if (in_array('all', $flags)) 
                {
                    $flags = ['visa', 'insurance', 'work_permit', 'MedicalReport', 'slot_payment'];
                }

                    $filterStart = Carbon::now()->startOfMonth();
                    $filterEnd = Carbon::now()->endOfMonth();

                if($date) 
                {
                
                    $date = $request->input('date');

   
                        if (! $date || ! Str::contains($date, ' - ')) {
                            return response()->json([
                                'success' => false,
                                'errors' => ['date' => ['Invalid date range format']],
                            ], 422);
                        }

                        [$from, $to] = explode(' - ', $date);

                    try {
                            $filterStart = Carbon::createFromFormat('d/m/Y', trim($from))->startOfDay();
                            $filterEnd   = Carbon::createFromFormat('d/m/Y', trim($to))->endOfDay();
                        } catch (\Exception $e) 
                        {
                            try {
                                $filterStart = Carbon::createFromFormat('Y-m-d', trim($from))->startOfDay();
                                $filterEnd   = Carbon::createFromFormat('Y-m-d', trim($to))->endOfDay();
                            } catch (\Exception $e) {
                                $filterStart = Carbon::parse(trim($from))->startOfDay();
                                $filterEnd   = Carbon::parse(trim($to))->endOfDay();
                            }
                        }

                    $fromYear = Carbon::parse($filterStart)->year;
                    $currentYear = Carbon::now()->year;

                    if ($fromYear < $currentYear) 
                    {
                        $fromMin = Carbon::create($fromYear, 1, 1)->format('Y-m-d');
                        $fromMax = Carbon::create($fromYear, 12, 31)->format('Y-m-d');
                    } else {
                        // Current year: allow up to today
                        $fromMin = Carbon::create($currentYear, 1, 1)->format('Y-m-d');
                        $fromMax = Carbon::now()->format('Y-m-d');
                    }

                    // To date should be between from date and today
                    $toMax = Carbon::now()->addDay()->format('Y-m-d');

                        $validator = Validator::make([
                            'from' => $filterStart,
                            'to'   => $filterEnd,
                        ], [
                            'from' => [
                                'required',
                                'date',
                                'after_or_equal:' . $fromMin,
                                'before_or_equal:' . $fromMax,
                            ],
                            'to' => [
                                'required',
                                'date',
                                'after_or_equal:from',
                                'before_or_equal:' . $toMax, // allows current date and one day in future
                            ],
                        ], [
                            'from.after_or_equal'    => 'The start date is too early for the selected year.',
                            'from.before_or_equal'   => 'The start date cannot be beyond today.',
                            'to.after_or_equal'      => 'The end date must be on or after the start date.',
                            'to.before_or_equal'     => 'The end date must be today, tomorrow, or earlier.',
                        ]);
                        if ($validator->fails()) {
                            return response()->json([
                                'success' => false,
                                'errors'  => $validator->errors(),
                            ], 422);
                        }


                        
                
                }
        
                $totalVisa = $totalInsurance = $totalPermit = $totalMedical = $totalQuota = $totalChecked = 0;
                $totalInsuranceEmployee = $totalPermitEmployee = $TotalVisaEmployee=$totalMedicalEmployee = $totalQuotaEmployee = 0;
                $employees = Employee::with(['resortAdmin', 'position', 'department', 'VisaRenewal.VisaChild', 'WorkPermitMedicalRenewal.WorkPermitMedicalRenewalChild', 'WorkPermit', 'EmployeeInsurance.InsuranceChild', 'QuotaSlotRenewal'])
                    ->where("nationality", '!=', "Maldivian")
                    ->where('status','Active')
                    ->whereBetween('joining_date', [$filterStart, $filterEnd])
                    ->where('resort_id', $this->resort->resort_id)
                    ->get()
                    ->map(function ($employee) use (&$totalPermitEmployee,&$totalMedicalEmployee,&$totalQuotaEmployee,&$totalInsuranceEmployee,&$TotalVisaEmployee,&$totalChecked, $flags, $filterStart, $filterEnd, &$totalVisa, &$totalInsurance, &$totalPermit, &$totalMedical, &$totalQuota) {
                        $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                        $employee->Emp_id = $employee->Emp_id;
                        $employee->Department_name = $employee->department->name ?? 'N/A';
                        $employee->Position_name = $employee->position->position_title ?? 'N/A';
                        $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id);
                        $employee->VisaExpiryExpiryDate = $employee->InsuranceExpiryDate = $employee->WorkPermitExpiryDate = $employee->WorkPermitMedicalPermitExpiryDate = $employee->QuotaSlotAmtForThisMonth = 'N/A';

                        $employeeData = [];
                        $hasAnyFlagData = false;

                        if (in_array('visa', $flags)) 
                        {
                            $visa = $employee->VisaRenewal;
                            if ($visa && Carbon::parse($visa->end_date)->between($filterStart, $filterEnd)) 
                            {
                                $employee->VisaExpiryExpiryDate = '<b>MVR ' . number_format($visa->Amt, 2) . '</b>' . $this->getFormattedExpiryStatus($visa->end_date);
                                $totalVisa += $visa->Amt;
                                $hasAnyFlagData = true;
                                $employeeData[base64_encode($employee->id)]['VisaAmt'] = $visa->Amt;
                                $employeeData[base64_encode($employee->id)]['VisaExpiry'] = $visa->end_date;
                                $lastvisaExpiry  = $employee->VisaRenewal->VisaChild()->orderBy("id","desc")->first();
                                $lastvisaExpiry = $lastvisaExpiry->end_date ?? 'N/A';
                                $employeeData[base64_encode($employee->id)]['LastVisaExpiry'] = $lastvisaExpiry;
                                $TotalVisaEmployee++;
                            }
                        }

                        if (in_array('insurance', $flags)) 
                        {
                            $insurance = $employee->EmployeeInsurance()->where('employee_id', $employee->id)->where('resort_id', $this->resort->resort_id)->orderBy('id', 'desc')->first();
                            if ($insurance && Carbon::parse($insurance->insurance_end_date)->between($filterStart, $filterEnd)) 
                            {
                                $employee->InsuranceExpiryDate = '<b>MVR ' . number_format($insurance->Premium, 2) . '</b>' . $this->getFormattedExpiryStatus($insurance->insurance_end_date);
                                $totalInsurance += $insurance->Premium;
                                $hasAnyFlagData = true;
                                $totalInsuranceEmployee++;
                            
                            }
                        }

                        if (in_array('work_permit', $flags)) 
                        {
                            $wpEntries = $employee->WorkPermit->sortByDesc('id')->where('Status','Paid'); // all records sorted by id DESC
                            $currentWP = $employee->WorkPermit()
                                ->where('Status', 'Paid')
                                ->whereBetween('Due_Date', [$filterStart, $filterEnd])
                                ->orderByDesc('id')
                                ->first();

                            $totalWpAmount = $employee->WorkPermit()
                                ->where('Status', 'Paid')
                                ->whereBetween('Due_Date', [$filterStart, $filterEnd])
                                ->sum('Amt');

                            if ($currentWP) {
                                $employee->WorkPermitExpiryDate = '<b>MVR ' . number_format($totalWpAmount, 2) . '</b>' . $this->getFormattedExpiryStatus($currentWP->Due_Date);
                                $totalPermit += $totalWpAmount;
                                $hasAnyFlagData = true;
                           
                            }
                            $encodedId = base64_encode($employee->id);
                            if ($currentWP)
                            {
                                $employee->WorkPermitExpiryDate = '<b>MVR ' . number_format($currentWP->Amt, 2) . '</b>' . $this->getFormattedExpiryStatus($currentWP->Due_Date);
                                $totalPermit += $currentWP->Amt;
                                $hasAnyFlagData = true;
                                $totalPermitEmployee++;
                            }
                        }

                        if (in_array('MedicalReport', $flags)) 
                        {
                            $med = $employee->WorkPermitMedicalRenewal;
                            if ($med && Carbon::parse($med->end_date)->between($filterStart, $filterEnd)) 
                            {
                                $employee->WorkPermitMedicalPermitExpiryDate = '<b>MVR ' . number_format($med->Amt, 2) . '</b>' . $this->getFormattedExpiryStatus($med->end_date);
                                $totalMedical += $med->Amt;
                                $hasAnyFlagData = true;
                                $totalMedicalEmployee++;
                            }
                        }

                        if (in_array('slot_payment', $flags)) 
                        {
                            $quotaEntries = $employee->QuotaSlotRenewal->where('Status', 'Paid'); // All paid entries
                            // Get total amount for quota entries between filter dates
                            $quotaBetweenDates = $quotaEntries->filter(function($item) use ($filterStart, $filterEnd) {
                                return Carbon::parse($item->Expiry_Date)->between($filterStart, $filterEnd);
                            });
                            
                            $quotaTotalAmount = $quotaBetweenDates->sum('Amt');
                            $encodedId = base64_encode($employee->id);
                            
                            if ($quotaTotalAmount > 0) 
                            {
                                $latestQuota = $quotaBetweenDates->sortByDesc('id')->first();
                                $employee->QuotaSlotAmtForThisMonth = '<b> MVR ' . number_format($quotaTotalAmount, 2) . '</b>' . $this->getFormattedExpiryStatus($latestQuota->Due_Date);
                                $totalQuota += $quotaTotalAmount;
                                $hasAnyFlagData = true;
                                $totalQuotaEmployee++;
                            }
                        }
                        $employee->extra= json_encode($employeeData);
                        return $hasAnyFlagData ? $employee : null;
                    })->filter();

                    $TotalLiabilites= 0;
                    $ResortBudgetCost = Common::VisaRenewalCost($this->resort->resort_id);
                    $NewResosrBudgetCost = collect($ResortBudgetCost)
                        ->filter(function($item, $key) {
                            return [
                                        "TotalQuotaSlotDeposit" => in_array($key, ['QUOTA SLOT DEPOSIT']),
                                        "TotalMedicalInsuranceInternational" => in_array($key, ['MEDICAL INSURANCE - INTERNATIONAL']),
                                        "TotalWorkPermitFees" => in_array($key, ['WORK PERMIT FEE']),
                                        "TotalWorkPermitMedicalTestFee" => in_array($key, ['WORK VISA MEDICAL TEST FEE']),
                                        "TotalVisaFees" => in_array($key, ['VISA FEE']),
                                    ];
                        });

                $TotalExpactEmployee       = Employee::whereBetween('joining_date', [$filterStart, $filterEnd])->where('status','Active')->where("nationality", '!=', "Maldivian")->where('resort_id', $this->resort->resort_id)->get()->count();
                $TotalExpactEmployeecounts = $TotalExpactEmployee;
                $TotalExpactEmployeecounts = $TotalExpactEmployeecounts > 0 ? $TotalExpactEmployeecounts : 1; // zero can not be used in multiplication, so we set it to 1 if zero


                $TotalBudgetQuotaSlotDeposit               = $NewResosrBudgetCost->has('QUOTA SLOT DEPOSIT') ? $NewResosrBudgetCost['QUOTA SLOT DEPOSIT']['amount']  * $TotalExpactEmployeecounts : 0;
                $TotalBudgetMedicalInsuranceInternational  = $NewResosrBudgetCost->has('MEDICAL INSURANCE - INTERNATIONAL') ? $NewResosrBudgetCost['MEDICAL INSURANCE - INTERNATIONAL']['amount'] *  $TotalExpactEmployeecounts : 0;
                $TotalBudgetWorkPermitFees                 = $NewResosrBudgetCost->has('WORK PERMIT FEE') ? $NewResosrBudgetCost['WORK PERMIT FEE']['amount']*  $TotalExpactEmployeecounts  : 0;
                $TotalBudgetWorkPermitMedicalTestFee       = $NewResosrBudgetCost->has('WORK VISA MEDICAL TEST FEE') ? $NewResosrBudgetCost['WORK VISA MEDICAL TEST FEE']['amount'] *  $TotalExpactEmployeecounts  : 0;
                $TotalBudgetVisaFees                       = $NewResosrBudgetCost->has('VISA FEE') ? $NewResosrBudgetCost['VISA FEE']['amount']*  $TotalExpactEmployeecounts  : 0;

            
                $TotalBudgetCost    = $TotalBudgetQuotaSlotDeposit+
                                    $TotalBudgetMedicalInsuranceInternational +
                                    $TotalBudgetWorkPermitFees +
                                    $TotalBudgetWorkPermitMedicalTestFee +
                                    $TotalBudgetVisaFees;
                
                $TotalLiabilites = $TotalBudgetCost ;  // multiply by total Xpct employee's 
                $TotalPaidLaibilites = $totalVisa + $totalInsurance + $totalPermit + $totalMedical + $totalQuota;
                $TotalBalanceLiability = $TotalLiabilites - $TotalPaidLaibilites;

                //  resort Cost Wise totals
                $TotalBalanceWorkPermitfees                = $TotalBudgetWorkPermitFees - $totalPermit;
                $TotalBalanceQuotaSlotDeposit              = $TotalBudgetQuotaSlotDeposit - $totalQuota;
                $TotalBalanceBudgetVisaFees                = $TotalBudgetVisaFees - $totalVisa;
                $TotalBalanceMedicalInsuranceInternational = $TotalBudgetMedicalInsuranceInternational - $totalInsurance; 
                $TotalBalanceWorkPermitMedicalTestFee      = $TotalBudgetWorkPermitMedicalTestFee - $totalMedical;

                
                $html =  view('resorts.renderfiles.liabilities',
                            compact('TotalExpactEmployee',
                            'TotalLiabilites','TotalPaidLaibilites','TotalBalanceLiability',
                            'TotalBudgetWorkPermitFees',
                            'TotalBalanceWorkPermitfees',
                            'totalPermit',
                            'TotalBalanceQuotaSlotDeposit',
                            'totalQuota',
                            'TotalBudgetQuotaSlotDeposit',
                            'TotalBalanceBudgetVisaFees',
                            'totalVisa',
                            'TotalBudgetVisaFees',
                            'TotalBudgetMedicalInsuranceInternational','totalInsurance','TotalBalanceMedicalInsuranceInternational',
                            'TotalBudgetWorkPermitMedicalTestFee','totalMedical','TotalBalanceWorkPermitMedicalTestFee',
                            'totalInsuranceEmployee','totalPermitEmployee','TotalVisaEmployee','totalMedicalEmployee','totalQuotaEmployee'
                            ))->render();

                return response()->json([
                    'html' => $html,
                    'status',true
                    ]);
                    
            }
            
            
            


            $page_title =  'Liabilities List';
            return view('resorts.Visa.liabilities.index',compact('page_title'));
    }
    public function getFormattedExpiryStatus($endDate)
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
            return $end->format('d M Y')."  (Expires in " . ($daysDiff + 1) . " days)";
        }


    }
    public function FetchTotalEmployees(Request $request)
    {
        $date = $request->date;
        $filterStart = Carbon::now()->startOfMonth();
        $filterEnd = Carbon::now()->endOfMonth();

        if($date) 
        {
            [$from, $to] = explode(' - ', $date);
            try {
                $filterStart = Carbon::createFromFormat('d/m/Y', trim($from))->startOfDay();
                $filterEnd   = Carbon::createFromFormat('d/m/Y', trim($to))->endOfDay();
            } catch (\Exception $e) {
                try {
                    $filterStart = Carbon::createFromFormat('Y-m-d', trim($from))->startOfDay();
                    $filterEnd   = Carbon::createFromFormat('Y-m-d', trim($to))->endOfDay();
                } catch (\Exception $e) {
                    $filterStart = Carbon::parse(trim($from))->startOfDay();
                    $filterEnd   = Carbon::parse(trim($to))->endOfDay();
                }
            }
        }

        $flags = $request->flag;
        $employeeName=array();
         $employees = Employee::with(['resortAdmin', 'position', 'department', 'VisaRenewal.VisaChild', 'WorkPermitMedicalRenewal.WorkPermitMedicalRenewalChild', 'WorkPermit', 'EmployeeInsurance.InsuranceChild', 'QuotaSlotRenewal'])
                    ->where("nationality", '!=', "Maldivian")
                    ->where('status','Active')
                    ->whereBetween('joining_date', [$filterStart, $filterEnd])
                    ->where('resort_id', $this->resort->resort_id)
                    ->get()
                    ->map(function ($employee) use ( $filterStart,$filterEnd ,$flags, &$employeeName) {
                        

                        $employeeData = [];
                        $hasAnyFlagData = false;

                        if ('Visa'== $flags) 
                        {
                            $visa = $employee->VisaRenewal;
                            if ($visa && Carbon::parse($visa->end_date)->between($filterStart, $filterEnd)) 
                            {
                                $employeeName[]=[
                                    $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name,
                                    $employee->Emp_id = $employee->Emp_id,
                                    $employee->Department_name = $employee->department->name ?? 'N/A',
                                    $employee->Position_name = $employee->position->position_title ?? 'N/A',
                                    $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id),
                                    $employee->VisaExpiryExpiryDate = $employee->InsuranceExpiryDate = $employee->WorkPermitExpiryDate = $employee->WorkPermitMedicalPermitExpiryDate = $employee->QuotaSlotAmtForThisMonth = 'N/A',
                                ];
                            }
                        }

                        if ('insurance'== $flags) 
                        {
                            $insurance = $employee->EmployeeInsurance()->where('employee_id', $employee->id)->where('resort_id', $this->resort->resort_id)->orderBy('id', 'desc')->first();
                            if ($insurance && Carbon::parse($insurance->insurance_end_date)->between($filterStart, $filterEnd)) 
                            {
                               $employeeName[]=[
                                    $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name,
                                    $employee->Emp_id = $employee->Emp_id,
                                    $employee->Department_name = $employee->department->name ?? 'N/A',
                                    $employee->Position_name = $employee->position->position_title ?? 'N/A',
                                    $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id),
                                    $employee->VisaExpiryExpiryDate = $employee->InsuranceExpiryDate = $employee->WorkPermitExpiryDate = $employee->WorkPermitMedicalPermitExpiryDate = $employee->QuotaSlotAmtForThisMonth = 'N/A',
                                ];
                            
                            }
                        }

                        if ('WorkPermit'== $flags) 
                        {
                            $wpEntries = $employee->WorkPermit->sortByDesc('id')->where('Status','Paid'); // all records sorted by id DESC
                            $currentWP = $employee->WorkPermit()
                                ->where('Status', 'Paid')
                                ->whereBetween('Due_Date', [$filterStart, $filterEnd])
                                ->orderByDesc('id')
                                ->first();

                         
                            if ($currentWP) {
                                $employeeName[]=[
                                    $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name,
                                    $employee->Emp_id = $employee->Emp_id,
                                    $employee->Department_name = $employee->department->name ?? 'N/A',
                                    $employee->Position_name = $employee->position->position_title ?? 'N/A',
                                    $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id),
                                    $employee->VisaExpiryExpiryDate = $employee->InsuranceExpiryDate = $employee->WorkPermitExpiryDate = $employee->WorkPermitMedicalPermitExpiryDate = $employee->QuotaSlotAmtForThisMonth = 'N/A',
                                ];
                            }
                            
                        }

                        if ('Medical'== $flags) 
                        {
                            $med = $employee->WorkPermitMedicalRenewal;
                            if ($med && Carbon::parse($med->end_date)->between($filterStart, $filterEnd)) 
                            {
                                $employeeName[]=[
                                    $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name,
                                    $employee->Emp_id = $employee->Emp_id,
                                    $employee->Department_name = $employee->department->name ?? 'N/A',
                                    $employee->Position_name = $employee->position->position_title ?? 'N/A',
                                    $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id),
                                    $employee->VisaExpiryExpiryDate = $employee->InsuranceExpiryDate = $employee->WorkPermitExpiryDate = $employee->WorkPermitMedicalPermitExpiryDate = $employee->QuotaSlotAmtForThisMonth = 'N/A',
                                ];
                            }
                        }

                        if ('QuotaSlot'== $flags) 
                        {
                            $quotaEntries = $employee->QuotaSlotRenewal->where('Status', 'Paid'); // All paid entries
                            // Get total amount for quota entries between filter dates
                            $quotaBetweenDates = $quotaEntries->filter(function($item) use ($filterStart, $filterEnd) {
                                return Carbon::parse($item->Expiry_Date)->between($filterStart, $filterEnd);
                            });
                            
                            $quotaTotalAmount = $quotaBetweenDates->sum('Amt');
                            $encodedId = base64_encode($employee->id);
                            
                            if ($quotaTotalAmount > 0) 
                            {
                                $employeeName[]=[
                                    $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name,
                                    $employee->Emp_id = $employee->Emp_id,
                                    $employee->Department_name = $employee->department->name ?? 'N/A',
                                    $employee->Position_name = $employee->position->position_title ?? 'N/A',
                                    $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id),
                                ];
                            }
                        }
                        $employee->extra= json_encode($employeeData);
                        return $hasAnyFlagData ? $employee : null;
                    })->filter();
        $TotalExpactEmployee  = Employee::with(['resortAdmin','department','position'])->whereBetween('joining_date', [$filterStart, $filterEnd])->where('status','Active')->get();
      
        $row='';
        if( !empty($employeeName))
        {
            foreach($employeeName as $employee)
            {
                $row .= '<tr>
                            <td>'.$employee[1].'</td>
                                <td>
                                <div class=" d-flex align-items-center">
                                        <div class="img-circle"><img src="'.Common::getResortUserPicture($employee[4]).'" alt="user"></div>
                                </div>
                            </td>
                            <td>'.$employee[0].'</td>
                            <td>'.$employee[2].'</td>
                            <td>'.$employee[3].'</td>
                         </tr>';
            }
        }
        else
        {
            $row = '<tr><td colspan="5" class="text-center">No Employees Found</td></tr>';
        }
        return response()->json([
            'success' => true,
            'html' => $row,
        ]);
    }

}
