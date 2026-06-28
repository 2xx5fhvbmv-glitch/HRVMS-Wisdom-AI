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
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            if($this->resort->GetEmployee) {
                $reporting_to = $this->resort->GetEmployee->id;
                $this->underEmp_id = Common::getSubordinates($reporting_to);
            }
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

                    // Duration filter removed — default to the FULL liability picture
                    // (all months/years), not just the current month, so every
                    // outstanding fee from the xpat employees is included.
                    $filterStart = Carbon::create(2000, 1, 1)->startOfDay();
                    $filterEnd   = Carbon::now()->addYears(5)->endOfYear();

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
                // "Total Xpat Employees" must count every active expat at the
                // resort — the date range filters the LIABILITY rows below
                // (Work Permit Due_Date, insurance/medical end_date, Visa
                // end_date, Quota Slot Due_Date), NOT the employee's joining
                // date. The previous whereBetween('joining_date', ...) was
                // the root cause of the wrong count flagged on this page.
                $employees = Employee::with(['resortAdmin', 'position', 'department', 'VisaRenewal.VisaChild', 'WorkPermitMedicalRenewal.WorkPermitMedicalRenewalChild', 'WorkPermit', 'EmployeeInsurance.InsuranceChild', 'QuotaSlotRenewal'])
                    ->whereRaw('LOWER(TRIM(nationality)) != ?', ['maldivian'])
                    ->where('status','Active')
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
                                $totalVisa += $this->toUsd($visa->Amt, $visa->Currency ?? 'MVR');
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
                                $totalInsurance += $this->toUsd($insurance->Premium, $insurance->Currency ?? 'MVR');
                                $hasAnyFlagData = true;
                                $totalInsuranceEmployee++;
                            
                            }
                        }

                        if (in_array('work_permit', $flags))
                        {
                            // Latest paid work permit in the window (for the expiry-status label).
                            $currentWP = $employee->WorkPermit()
                                ->where('Status', 'Paid')
                                ->whereBetween('Due_Date', [$filterStart, $filterEnd])
                                ->orderByDesc('id')
                                ->first();

                            // Sum of ALL paid work permit fees in the window.
                            $totalWpAmount = $employee->WorkPermit()
                                ->where('Status', 'Paid')
                                ->whereBetween('Due_Date', [$filterStart, $filterEnd])
                                ->sum('Amt');

                            // Was previously two identical if($currentWP) blocks — the
                            // total got the window-sum AND the latest row added again,
                            // double-counting the most recent permit. Now added once.
                            if ($currentWP) {
                                $employee->WorkPermitExpiryDate = '<b>MVR ' . number_format($totalWpAmount, 2) . '</b>' . $this->getFormattedExpiryStatus($currentWP->Due_Date);
                                $totalPermit += $this->toUsd($totalWpAmount, $currentWP->currency ?? 'MVR');
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
                                $totalMedical += $this->toUsd($med->Amt, $med->Currency ?? 'MVR');
                                $hasAnyFlagData = true;
                                $totalMedicalEmployee++;
                            }
                        }

                        if (in_array('slot_payment', $flags)) 
                        {
                            $quotaEntries = $employee->QuotaSlotRenewal->where('Status', 'Paid'); // All paid entries
                            // Filter by Due_Date — quota_slot_renewals has no Expiry_Date
                            // column, so the old Carbon::parse($item->Expiry_Date) parsed
                            // null and the date window never matched correctly.
                            $quotaBetweenDates = $quotaEntries->filter(function($item) use ($filterStart, $filterEnd) {
                                return $item->Due_Date && Carbon::parse($item->Due_Date)->between($filterStart, $filterEnd);
                            });
                            
                            $quotaTotalAmount = $quotaBetweenDates->sum('Amt');
                            $encodedId = base64_encode($employee->id);
                            
                            if ($quotaTotalAmount > 0) 
                            {
                                $latestQuota = $quotaBetweenDates->sortByDesc('id')->first();
                                $employee->QuotaSlotAmtForThisMonth = '<b> MVR ' . number_format($quotaTotalAmount, 2) . '</b>' . $this->getFormattedExpiryStatus($latestQuota->Due_Date);
                                $totalQuota += $this->toUsd($quotaTotalAmount, $latestQuota->Currency ?? 'MVR');
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

                // Total Xpat Employees = every active expat at this resort.
                // Date window applies to the liability rows, not the employee
                // joining date (which made the count regress to "employees
                // who joined this month" — the bug flagged on this page).
                $TotalExpactEmployee       = Employee::where('status','Active')->whereRaw('LOWER(TRIM(nationality)) != ?', ['maldivian'])->where('resort_id', $this->resort->resort_id)->count();
                $TotalExpactEmployeecounts = $TotalExpactEmployee;
                $TotalExpactEmployeecounts = $TotalExpactEmployeecounts > 0 ? $TotalExpactEmployeecounts : 1; // zero can not be used in multiplication, so we set it to 1 if zero


                // Each configured cost is in its own currency (MVR or USD) — convert
                // to USD by its unit before projecting across the expat headcount,
                // so the budget total matches the USD the view renders.
                $TotalBudgetQuotaSlotDeposit               = $this->toUsd($ResortBudgetCost['QUOTA SLOT DEPOSIT']['amount'] ?? 0, $ResortBudgetCost['QUOTA SLOT DEPOSIT']['unit'] ?? 'MVR') * $TotalExpactEmployeecounts;
                $TotalBudgetMedicalInsuranceInternational  = $this->toUsd($ResortBudgetCost['MEDICAL INSURANCE - INTERNATIONAL']['amount'] ?? 0, $ResortBudgetCost['MEDICAL INSURANCE - INTERNATIONAL']['unit'] ?? 'MVR') * $TotalExpactEmployeecounts;
                $TotalBudgetWorkPermitFees                 = $this->toUsd($ResortBudgetCost['WORK PERMIT FEE']['amount'] ?? 0, $ResortBudgetCost['WORK PERMIT FEE']['unit'] ?? 'MVR') * $TotalExpactEmployeecounts;
                $TotalBudgetWorkPermitMedicalTestFee       = $this->toUsd($ResortBudgetCost['WORK VISA MEDICAL TEST FEE']['amount'] ?? 0, $ResortBudgetCost['WORK VISA MEDICAL TEST FEE']['unit'] ?? 'MVR') * $TotalExpactEmployeecounts;
                $TotalBudgetVisaFees                       = $this->toUsd($ResortBudgetCost['VISA FEE']['amount'] ?? 0, $ResortBudgetCost['VISA FEE']['unit'] ?? 'MVR') * $TotalExpactEmployeecounts;

            
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
            
            
            


            $page_title =  'Liabilities';
            return view('resorts.Visa.liabilities.index',compact('page_title'));
    }

    /**
     * Per-employee breakdown for one liability category, so HR can see exactly
     * how the card's Total / Paid / Balance add up (they couldn't reconcile it
     * manually). For every active expat it shows:
     *   - Budgeted : the configured fee (USD) — the same per-employee amount the
     *                card's "Total Liability" projects across the headcount.
     *   - Paid     : that employee's actual settled amount for the category (USD),
     *                computed identically to the Index() totals so they reconcile.
     *   - Balance  : Budgeted − Paid.
     * The column sums equal the card's Total / Paid / Balance.
     */
    public function LiabilityBreakdown(Request $request)
    {
        $flag = $request->flag;
        $rid  = $this->resort->resort_id;

        // Full range — matches the (un-windowed) card totals.
        $filterStart = Carbon::create(2000, 1, 1)->startOfDay();
        $filterEnd   = Carbon::now()->addYears(5)->endOfYear();

        $map = [
            'WorkPermit' => ['label' => 'Work Permit Fee', 'cfg' => 'WORK PERMIT FEE'],
            'QuotaSlot'  => ['label' => 'Slot Payment',    'cfg' => 'QUOTA SLOT DEPOSIT'],
            'Insurance'  => ['label' => 'Insurance',       'cfg' => 'MEDICAL INSURANCE - INTERNATIONAL'],
            'Medical'    => ['label' => 'Medical',         'cfg' => 'WORK VISA MEDICAL TEST FEE'],
        ];
        if (!isset($map[$flag])) {
            return response()->json(['success' => false, 'message' => 'Unknown category'], 422);
        }

        $config       = Common::VisaRenewalCost($rid);
        $cfgKey       = $map[$flag]['cfg'];
        $budgetPerEmp = $this->toUsd($config[$cfgKey]['amount'] ?? 0, $config[$cfgKey]['unit'] ?? 'MVR');

        $employees = Employee::with(['resortAdmin', 'position', 'department', 'WorkPermit', 'EmployeeInsurance', 'WorkPermitMedicalRenewal', 'QuotaSlotRenewal'])
            ->whereRaw('LOWER(TRIM(nationality)) != ?', ['maldivian'])
            ->where('status', 'Active')
            ->where('resort_id', $rid)
            ->get();

        $rows = [];
        $sumBudget = $sumPaid = 0;
        $paidCount = 0;

        foreach ($employees as $employee) {
            $paid = 0.0;

            if ($flag === 'WorkPermit') {
                $amt = $employee->WorkPermit->where('Status', 'Paid')
                    ->filter(fn($w) => $w->Due_Date && Carbon::parse($w->Due_Date)->between($filterStart, $filterEnd))
                    ->sum('Amt');
                $paid = $this->toUsd($amt, 'MVR');
            } elseif ($flag === 'QuotaSlot') {
                $amt = $employee->QuotaSlotRenewal->where('Status', 'Paid')
                    ->filter(fn($w) => $w->Due_Date && Carbon::parse($w->Due_Date)->between($filterStart, $filterEnd))
                    ->sum('Amt');
                $paid = $this->toUsd($amt, 'MVR');
            } elseif ($flag === 'Insurance') {
                // Latest insurance — same source as Index() so the totals reconcile.
                $ins = $employee->EmployeeInsurance()->orderBy('id', 'desc')->first();
                if ($ins && $ins->insurance_end_date && Carbon::parse($ins->insurance_end_date)->between($filterStart, $filterEnd)) {
                    $paid = $this->toUsd($ins->Premium, $ins->Currency ?? 'MVR');
                }
            } elseif ($flag === 'Medical') {
                $med = $employee->WorkPermitMedicalRenewal;
                if ($med && $med->end_date && Carbon::parse($med->end_date)->between($filterStart, $filterEnd)) {
                    $paid = $this->toUsd($med->Amt, $med->Currency ?? 'MVR');
                }
            }

            $balance = $budgetPerEmp - $paid;
            if ($paid > 0) { $paidCount++; }
            $sumBudget += $budgetPerEmp;
            $sumPaid   += $paid;

            $rows[] = [
                'name'    => trim(($employee->resortAdmin->first_name ?? '') . ' ' . ($employee->resortAdmin->last_name ?? '')),
                'emp_id'  => $employee->Emp_id,
                'dept'    => $employee->department->name ?? 'N/A',
                'budget'  => $budgetPerEmp,
                'paid'    => $paid,
                'balance' => $balance,
            ];
        }

        // Paid first (most relevant), then by name.
        usort($rows, function ($a, $b) {
            if (($b['paid'] <=> $a['paid']) !== 0) return $b['paid'] <=> $a['paid'];
            return strcmp($a['name'], $b['name']);
        });

        $sumBalance = $sumBudget - $sumPaid;

        $body = '';
        foreach ($rows as $i => $r) {
            $body .= '<tr>'
                . '<td>' . ($i + 1) . '</td>'
                . '<td><div class="fw-600">' . e($r['name']) . '</div><small class="text-muted">' . e($r['emp_id']) . ' · ' . e($r['dept']) . '</small></td>'
                . '<td class="text-end">' . Common::formatCurrency($r['budget'], 'USD') . '</td>'
                . '<td class="text-end">' . Common::formatCurrency($r['paid'], 'USD') . '</td>'
                . '<td class="text-end">' . Common::formatCurrency($r['balance'], 'USD') . '</td>'
                . '</tr>';
        }

        $html = '<div class="mb-2 small text-muted">Total Liability is the configured fee (<b>'
            . Common::formatCurrency($budgetPerEmp, 'USD') . '</b> per expat) projected across <b>' . count($rows)
            . '</b> active expats. <b>' . $paidCount . '</b> have paid so far.</div>'
            . '<div class="table-responsive"><table class="table table-bordered table-sm align-middle mb-0">'
            . '<thead><tr><th>#</th><th>Employee</th><th class="text-end">Budgeted</th><th class="text-end">Paid</th><th class="text-end">Balance</th></tr></thead>'
            . '<tbody>' . $body . '</tbody>'
            . '<tfoot><tr class="fw-bold table-light"><td colspan="2" class="text-end">Total</td>'
            . '<td class="text-end">' . Common::formatCurrency($sumBudget, 'USD') . '</td>'
            . '<td class="text-end">' . Common::formatCurrency($sumPaid, 'USD') . '</td>'
            . '<td class="text-end">' . Common::formatCurrency($sumBalance, 'USD') . '</td></tr></tfoot>'
            . '</table></div>';

        return response()->json([
            'success' => true,
            'label'   => $map[$flag]['label'],
            'html'    => $html,
        ]);
    }
    /**
     * Convert a stored amount (in its own currency) to USD. The liability
     * summary view renders every figure via formatCurrency(..,'USD'), so all
     * totals must reach it in USD; MVR amounts are derived from DollertoMVR.
     */
    private function toUsd($amount, $currency = 'MVR')
    {
        $cur = strtoupper(trim((string) $currency));
        if (in_array($cur, ['USD', '$'], true)) {
            return (float) $amount;
        }
        return (float) Common::RateConversion('MVRToDoller', (float) $amount, $this->resort->resort_id);
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
        // Duration filter removed — default to the full range so the employee list
        // matches the (un-windowed) liability totals.
        $filterStart = Carbon::create(2000, 1, 1)->startOfDay();
        $filterEnd   = Carbon::now()->addYears(5)->endOfYear();

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
         // Drill-in / per-category employee list also filters liabilities by
         // the date window, NOT the employee's joining_date.
         $employees = Employee::with(['resortAdmin', 'position', 'department', 'VisaRenewal.VisaChild', 'WorkPermitMedicalRenewal.WorkPermitMedicalRenewalChild', 'WorkPermit', 'EmployeeInsurance.InsuranceChild', 'QuotaSlotRenewal'])
                    ->whereRaw('LOWER(TRIM(nationality)) != ?', ['maldivian'])
                    ->where('status','Active')
                    ->where('resort_id', $this->resort->resort_id)
                    ->get()
                    ->map(function ($employee) use ( $filterStart,$filterEnd ,$flags, &$employeeName) {
                        

                        $employeeData = [];
                        $hasAnyFlagData = false;

                        // "Total Xpat Employees" header sends flag=All — list every
                        // active expat unconditionally (no liability-date filter).
                        // Previously there was no 'All' branch so the modal showed
                        // "No Employees Found".
                        if ('All' == $flags)
                        {
                            $employeeName[] = [
                                trim($employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name),
                                $employee->Emp_id,
                                $employee->department->name ?? 'N/A',
                                $employee->position->position_title ?? 'N/A',
                                Common::getResortUserPicture($employee->resortAdmin->id),
                            ];
                        }

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
                            // Filter by Due_Date — quota_slot_renewals has no Expiry_Date
                            // column, so the old Carbon::parse($item->Expiry_Date) parsed
                            // null and the date window never matched correctly.
                            $quotaBetweenDates = $quotaEntries->filter(function($item) use ($filterStart, $filterEnd) {
                                return $item->Due_Date && Carbon::parse($item->Due_Date)->between($filterStart, $filterEnd);
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
                // $employee[4] is ALREADY the resolved picture URL — passing it
                // back through getResortUserPicture() (which expects an id)
                // broke the image. Use it directly.
                $row .= '<tr>
                            <td>'.e($employee[1]).'</td>
                                <td>
                                <div class=" d-flex align-items-center">
                                        <div class="img-circle"><img src="'.e($employee[4]).'" alt="user"></div>
                                </div>
                            </td>
                            <td>'.e($employee[0]).'</td>
                            <td>'.e($employee[2]).'</td>
                            <td>'.e($employee[3]).'</td>
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
