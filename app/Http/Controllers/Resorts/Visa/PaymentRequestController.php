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
use App\Models\EmployeeVisaExpiryDetails;
use App\Models\EmployeeQuotaSlotRenewal;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestChild; 
use App\Exports\PaymentRequestExport;
use App\Models\WorkPermitMedicalRenewal;
use App\Models\EmployeeInsurance;
use App\Models\QuotaSlotRenewal;
use App\Models\WorkPermit;

class PaymentRequestController extends Controller
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

    public function Create(Request $request)
    {

        // if ($request->ajax()) 
        // {
        //     $flags  = (array) $request->flag; // Support multiple flags
        //     $search = $request->search;
        //     $date   = $request->date;

        //     // Parse date range
        //     $filterStart = Carbon::now()->startOfMonth();
        //     $filterEnd   = Carbon::now()->endOfMonth();

        //     if ($date && strpos($date, '-') !== false) {
        //         try {
        //             $parts = explode(' - ', $date);
        //             $filterStart = Carbon::createFromFormat('d-m-Y', trim($parts[0]))->startOfDay();
        //             $filterEnd   = Carbon::createFromFormat('d-m-Y', trim($parts[1]))->endOfDay();
        //         } catch (\Exception $e) {
        //             // fallback remains current month
        //         }
        //     }

        //     $employees = Employee::with([
        //         'resortAdmin', 'position', 'department',
        //         'VisaRenewal', 'WorkPermitMedicalRenewal', 
        //         'WorkPermit', 'EmployeeInsurance', 'QuotaSlotRenewal'
        //     ])
        //     ->when($search, function ($query) use ($search) {
        //         $query->orWhereHas('resortAdmin', function ($q) use ($search) {
        //             $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
        //                 ->orWhere('first_name', 'LIKE', "%{$search}%")
        //                 ->orWhere('last_name', 'LIKE', "%{$search}%");
        //         });
        //     })
        //     ->where("nationality", '!=', "Maldivian")
        //     ->where('resort_id', $this->resort->resort_id)
        //     ->get()
        //     ->map(function ($employee) use ($flags, $filterStart, $filterEnd) {

        //         $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
        //         $employee->Emp_id = $employee->Emp_id;
        //         $employee->Department_name = $employee->department->name ?? 'N/A';
        //         $employee->Position_name = $employee->position->position_title ?? 'N/A';
        //         $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id);

        //         // Default values
        //         $employee->VisaExpiryExpiryDate = 'N/A';
        //         $employee->InsuranceExpiryDate = 'N/A';
        //         $employee->WorkPermitExpiryDate = 'N/A';
        //         $employee->WorkPermitMedicalPermitExpiryDate = 'N/A';
        //         $employee->QuotaSlotAmtForThisMonth = 'N/A';

        //         $hasAnyFlagData = false;

        //         // Work Permit
        //         if (in_array('work_permit', $flags)) {
        //             $wp = $employee->WorkPermit->filter(fn($item) =>
        //                 Carbon::parse($item->Due_Date)->between($filterStart, $filterEnd)
        //             )->sortByDesc('id')->first();

        //             if ($wp) {
        //                 $employee->WorkPermitExpiryDate = '<b>MVR ' . number_format($wp->Amt, 2) . '</b> ' . $this->getFormattedExpiryStatus($wp->Due_Date);
        //                 $hasAnyFlagData = true;
        //             }
        //         }

        //         // Visa
        //         if (in_array('visa', $flags)) {
        //             $visa = $employee->VisaRenewal;
        //             if ($visa && Carbon::parse($visa->end_date)->between($filterStart, $filterEnd)) {
        //                 $employee->VisaExpiryExpiryDate = '<b>MVR ' . $visa->Amt . '</b> ' . $this->getFormattedExpiryStatus($visa->end_date);
        //                 $hasAnyFlagData = true;
        //             }
        //         }

        //         // Insurance
        //         if (in_array('insurance', $flags)) {
        //             $insurance = $employee->EmployeeInsurance()
        //                             ->where('employee_id', $employee->id)
        //                             ->where('resort_id', $this->resort->resort_id)
        //                             ->orderBy('id', 'desc')->first();
        //             if ($insurance && Carbon::parse($insurance->insurance_end_date)->between($filterStart, $filterEnd)) {
        //                 $employee->InsuranceExpiryDate = '<b>MVR ' . $insurance->Premium . '</b> ' . $this->getFormattedExpiryStatus($insurance->insurance_end_date);
        //                 $hasAnyFlagData = true;
        //             }
        //         }

        //         // Medical
        //         if (in_array('MedicalReport', $flags)) {
        //             $med = $employee->WorkPermitMedicalRenewal;
        //             if ($med && Carbon::parse($med->end_date)->between($filterStart, $filterEnd)) {
        //                 $employee->WorkPermitMedicalPermitExpiryDate = '<b>MVR ' . $med->Amt . '</b> ' . $this->getFormattedExpiryStatus($med->end_date);
        //                 $hasAnyFlagData = true;
        //             }
        //         }

        //         // Quota Slot
        //         if (in_array('slot_payment', $flags)) {
        //             $quota = $employee->QuotaSlotRenewal
        //                         ->filter(fn($item) => Carbon::parse($item->Expiry_Date)->between($filterStart, $filterEnd))
        //                         ->sortByDesc('id')->first();
        //             if ($quota) {
        //                 $employee->QuotaSlotAmtForThisMonth = '<b>MVR ' . number_format($quota->Amount, 2) . '</b> ' . $this->getFormattedExpiryStatus($quota->Expiry_Date);
        //                 $hasAnyFlagData = true;
        //             }
        //         }

        //         return $hasAnyFlagData ? $employee : null;

        //     })->filter();


        //     return datatables()->of($employees)
        //         ->addIndexColumn()
        //         ->editColumn('EmployeeID', fn($row) => $row->Emp_id)
        //         ->addColumn('EmployeeName', fn($row) => $row->Emp_name)
        //         ->addColumn('Position', fn($row) => $row->Position_name)
        //         ->addColumn('Department', fn($row) => $row->Department_name)
        //         ->addColumn('VisaExpiry', fn($row) => $row->VisaExpiryExpiryDate ?? 'N/A')
        //         ->addColumn('WorkPermit', fn($row) => $row->WorkPermitExpiryDate ?? 'N/A')
        //         ->addColumn('Insurance', fn($row) => $row->InsuranceExpiryDate ?? 'N/A')
        //         ->addColumn('Medical', fn($row) => $row->WorkPermitMedicalPermitExpiryDate ?? 'N/A')
        //         ->addColumn('SlotFees', fn($row) => $row->QuotaSlotAmtForThisMonth ?? 'N/A')
        //         ->editColumn('CheckBox', fn($row) => '<input type="checkbox" class="form-check-input" name="employee_ids[]" value="' . $row->Emp_id . '">')
        //         ->rawColumns(['EmployeeID','CheckBox','SlotFees','VisaExpiry','Medical','Insurance','WorkPermit','Department','Position','EmployeeName'])
        //         ->make(true);
        // }

                
        if ($request->ajax()) 
        {
            $isChecked = $request->isChecked;
            $flags = (array) $request->flag;
            $search = $request->search;
            $date = $request->date;

            // No payment type selected (default view) → list EVERY active foreign
            // employee, not only those with a due this period. We still compute all
            // fee types below so any current dues show against them.
            $showAll = empty($flags);
            if ($showAll || in_array('all', $flags)) {
                // 'visa' intentionally excluded — the Visa payment-type checkbox
                // has been removed from the UI. Even "All" must not pull visa.
                $flags = ['insurance', 'work_permit', 'MedicalReport', 'slot_payment'];
            }

            // By default we show the next OUTSTANDING (unpaid) due per fee type,
            // regardless of when it falls — the date window only narrows results
            // when the user explicitly picks a range.
            $hasDateFilter = false;
            $filterStart = Carbon::now()->startOfMonth();
            $filterEnd = Carbon::now()->endOfMonth();

            if ($date && strpos($date, '-') !== false) {
                try
                {
                    $parts = explode(' - ', $date);
                    $filterStart = Carbon::createFromFormat('d-m-Y', trim($parts[0]))->startOfDay();
                    $filterEnd = Carbon::createFromFormat('d-m-Y', trim($parts[1]))->endOfDay();
                    $hasDateFilter = true;
                } catch (\Exception $e)
                {
                    // fallback
                }
            }

            $totalVisa = $totalInsurance = $totalPermit = $totalMedical = $totalQuota = $totalChecked = 0;

            $employees = Employee::with(['resortAdmin', 'position', 'department', 'VisaRenewal.VisaChild', 'WorkPermitMedicalRenewal.WorkPermitMedicalRenewalChild', 'WorkPermit', 'EmployeeInsurance.InsuranceChild', 'QuotaSlotRenewal'])
                ->when($search, function ($query) use ($search) {
                    $query->orWhereHas('resortAdmin', function ($q) use ($search) {
                        $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                        ->orWhere('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                    });
                })
                ->where('status','Active')
                ->where("nationality", '!=', "Maldivian")
                ->where('resort_id', $this->resort->resort_id)
                ->get()
                ->map(function ($employee) use (&$totalChecked, $isChecked, $flags, $showAll, $hasDateFilter, $filterStart, $filterEnd, &$totalVisa, &$totalInsurance, &$totalPermit, &$totalMedical, &$totalQuota) {
                    $employee->isChecked = $isChecked;
                    $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                    $employee->Emp_id = $employee->Emp_id;
                    $employee->Department_name = $employee->department->name ?? 'N/A';
                    $employee->Position_name = $employee->position->position_title ?? 'N/A';
                    $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id);
                    $employee->VisaExpiryExpiryDate = $employee->InsuranceExpiryDate = $employee->WorkPermitExpiryDate = $employee->WorkPermitMedicalPermitExpiryDate = $employee->QuotaSlotAmtForThisMonth = 'N/A';

                    $employeeData = [];
                    $hasAnyFlagData = false;

                    // Visa payment-type removed from the UI — block disabled.
                    // Kept for reference in case Visa is re-enabled later.
                    /*
                    if (in_array('visa', $flags)) {
                        $visa = $employee->VisaRenewal;


                        if ($visa && Carbon::parse($visa->end_date)->between($filterStart, $filterEnd)) {
                            $employee->VisaExpiryExpiryDate = '<b>MVR ' . number_format($visa->Amt, 2) . '</b> ' . $this->getFormattedExpiryStatus($visa->end_date);
                            $totalVisa += $visa->Amt;
                            $hasAnyFlagData = true;
                            $employeeData[base64_encode($employee->id)]['VisaAmt'] = $visa->Amt;
                            $employeeData[base64_encode($employee->id)]['VisaExpiry'] = $visa->end_date;

                            $lastvisaExpiry  = $employee->VisaRenewal->VisaChild()->orderBy("id","desc")->first();

                            $lastvisaExpiry = $lastvisaExpiry->end_date ?? 'N/A';
                            $employeeData[base64_encode($employee->id)]['LastVisaExpiry'] = $lastvisaExpiry;

                        }
                    }
                    */

                    if (in_array('insurance', $flags)) {
                        $insurance = $employee->EmployeeInsurance()->where('employee_id', $employee->id)->where('resort_id', $this->resort->resort_id)->orderBy('insurance_end_date', 'desc')->orderBy('id', 'desc')->first();
                        // Outstanding = current policy not yet paid. Shown regardless of
                        // the window; the window only narrows when explicitly picked.
                        $insOutstanding = $insurance && strtolower((string) $insurance->Status) !== 'paid';
                        $insInWindow = $insurance && (!$hasDateFilter || Carbon::parse($insurance->insurance_end_date)->between($filterStart, $filterEnd));
                        if ($insOutstanding && $insInWindow) {
                            $employee->InsuranceExpiryDate = '<b class="fee-amt">MVR ' . number_format($insurance->Premium, 2) . '</b> ' . $this->getFormattedExpiryStatus($insurance->insurance_end_date);
                            $totalInsurance += $insurance->Premium;
                            $hasAnyFlagData = true;
                            $employeeData[base64_encode($employee->id)]['InsuranceAmt'] = $insurance->Premium;
                            $employeeData[base64_encode($employee->id)]['InsuranceExpiry'] = $insurance->insurance_end_date;

                            $lastInsuranceExpiry  = $employee->EmployeeInsurance->InsuranceChild()->orderBy("id","desc")->first();
                            $lastInsuranceExpiry = $lastInsuranceExpiry->insurance_end_date ?? 'N/A';
                            $employeeData[base64_encode($employee->id)]['lastInsuranceExpiry'] = $lastInsuranceExpiry;
                        }
                    }

                    if (in_array('work_permit', $flags))
                    {
                        // Outstanding work-permit dues: every Unpaid month with a real
                        // fee, earliest first. HR can pay the next N of them at once.
                        $wpEntries = $employee->WorkPermit->sortBy('Due_Date');
                        $unpaidWP = $wpEntries->filter(fn($item) => $item->Due_Date
                            && strtolower((string) $item->Status) !== 'paid'
                            && (float) $item->Amt > 0);
                        if ($hasDateFilter) {
                            $unpaidWP = $unpaidWP->filter(fn($item) => Carbon::parse($item->Due_Date)->between($filterStart, $filterEnd));
                        }
                        $wpDues = $unpaidWP->take(12)->map(fn($i) => [
                            'id'   => $i->id,
                            'amt'  => (float) $i->Amt,
                            'date' => Carbon::parse($i->Due_Date)->format('Y-m-d'),
                        ])->values()->all();
                        $currentWP = $unpaidWP->first();
                        $encodedId = base64_encode($employee->id);
                        if ($currentWP)
                        {
                            $employee->WorkPermitExpiryDate = $this->buildMultiMonthFeeCell($this->buildPayableSchedule($wpDues), 'wp');
                            $totalPermit += $currentWP->Amt;
                            $hasAnyFlagData = true;
                            $employeeData[$encodedId]['WorkPermitAmt'] = $currentWP->Amt;
                            $employeeData[$encodedId]['WorkPermitExpiry'] = $currentWP->Due_Date;

                            $previousWP = $wpEntries->filter(fn($item) => Carbon::parse($item->Due_Date)->lt(Carbon::parse($currentWP->Due_Date)))->last();
                            $employeeData[$encodedId]['LastWorkPermitExpiry'] = $previousWP ? $previousWP->Due_Date : 'N/A';
                        }
                    }

                    if (in_array('MedicalReport', $flags))
                    {
                        $med = $employee->WorkPermitMedicalRenewal;
                        $medOutstanding = $med && strtolower((string) $med->Status) !== 'paid';
                        $medInWindow = $med && (!$hasDateFilter || Carbon::parse($med->end_date)->between($filterStart, $filterEnd));
                        if ($medOutstanding && $medInWindow)
                        {
                            $employee->WorkPermitMedicalPermitExpiryDate = '<b class="fee-amt">MVR ' . number_format($med->Amt, 2) . '</b> ' . $this->getFormattedExpiryStatus($med->end_date);
                            $totalMedical += $med->Amt;
                            $hasAnyFlagData = true;
                            $employeeData[base64_encode($employee->id)]['MedicalAmt'] = $med->Amt;
                            $employeeData[base64_encode($employee->id)]['MedicalExpiry'] = $med->end_date;

                            $LastWorkPermitMedicalExpiry  = $employee->WorkPermitMedicalRenewal->WorkPermitMedicalRenewalChild()->orderBy("id","desc")->first();
                            $LastWorkPermitMedicalExpiry = $LastWorkPermitMedicalExpiry->end_date ?? 'N/A';
                            $employeeData[base64_encode($employee->id)]['LastWorkPermitMedicalExpiry'] = $LastWorkPermitMedicalExpiry;
                        }
                    }

                    if (in_array('slot_payment', $flags)) {
                        // Outstanding quota-slot dues: every Unpaid month with a real
                        // fee, earliest first. HR can pay the next N of them at once.
                        $quotaEntries = $employee->QuotaSlotRenewal->sortBy('Due_Date');
                        $unpaidQuota = $quotaEntries->filter(fn($item) => $item->Due_Date
                            && strtolower((string) $item->Status) !== 'paid'
                            && (float) $item->Amt > 0);
                        if ($hasDateFilter) {
                            $unpaidQuota = $unpaidQuota->filter(fn($item) => Carbon::parse($item->Due_Date)->between($filterStart, $filterEnd));
                        }
                        $quotaDues = $unpaidQuota->take(12)->map(fn($i) => [
                            'id'   => $i->id,
                            'amt'  => (float) $i->Amt,
                            'date' => Carbon::parse($i->Due_Date)->format('Y-m-d'),
                        ])->values()->all();
                        $currentQuota = $unpaidQuota->first();
                        $encodedId = base64_encode($employee->id);
                        if ($currentQuota)
                        {
                            $employee->QuotaSlotAmtForThisMonth = $this->buildMultiMonthFeeCell($this->buildPayableSchedule($quotaDues), 'slot');
                            $totalQuota += $currentQuota->Amt;
                            $hasAnyFlagData = true;

                            $employeeData[$encodedId]['QuotaAmt'] = $currentQuota->Amt;
                            $employeeData[$encodedId]['QuotaExpiry'] = $currentQuota->Due_Date;

                            $previousQuota = $quotaEntries->filter(fn($item) => Carbon::parse($item->Due_Date)->lt(Carbon::parse($currentQuota->Due_Date)))->last();
                            $employeeData[$encodedId]['LastQuotaExpiry'] = $previousQuota ? $previousQuota->Due_Date : 'N/A';
                        }
                    }

                    // Per-employee fee total (in the resort display currency) for the
                    // live "selected total" on top — summed client-side from data-total.
                    $encId = base64_encode($employee->id);
                    $rowTotalMvr = 0.0;
                    foreach (['WorkPermitAmt','QuotaAmt','InsuranceAmt','MedicalAmt','VisaAmt'] as $kk) {
                        $rowTotalMvr += (float) ($employeeData[$encId][$kk] ?? 0);
                    }
                    $employee->RowTotalDisplay = round($rowTotalMvr, 2); // MVR (fees are MVR — no conversion)

                    $employee->extra= json_encode($employeeData);
                    if ($hasAnyFlagData && $employee->isChecked === "true") {
                        $totalChecked++;
                    }

                    // Default view ($showAll) lists every foreign employee, even
                    // with no due this period; a specific filter keeps only matches.
                    return ($showAll || $hasAnyFlagData) ? $employee : null;
                })->filter();

            $overallTotal = $totalVisa + $totalInsurance + $totalPermit + $totalMedical + $totalQuota;

            return datatables()->of($employees)
                ->addIndexColumn()
                ->editColumn('EmployeeID', fn($row) => $row->Emp_id)
                ->addColumn('EmployeeName', function($row) {
                
                    return '<div class="tableUser-block">
                                <div class="img-circle"><img src="'.$row->ProfilePic.'" alt="user"></div>
                                <span class="userApplicants-btn">'.$row->Emp_name.'</span>
                            </div>';
                })
                ->addColumn('Position', fn($row) => $row->Position_name)
                ->addColumn('Department', fn($row) => $row->Department_name)
                ->addColumn('VisaExpiry', fn($row) => $row->VisaExpiryExpiryDate ?? 'N/A')
                ->addColumn('WorkPermit', fn($row) => $row->WorkPermitExpiryDate ?? 'N/A')
                ->addColumn('Insurance', fn($row) => $row->InsuranceExpiryDate ?? 'N/A')
                ->addColumn('Medical', fn($row) => $row->WorkPermitMedicalPermitExpiryDate ?? 'N/A')
                ->addColumn('SlotFees', fn($row) => $row->QuotaSlotAmtForThisMonth ?? 'N/A')
                ->addColumn('CheckBox', function ($row) {
                    $isChecked = ($row->isChecked == "true") ? "checked" : "";
                    $id = base64_encode($row->id);
                    $fields = '';
                  
                    return "<div class=\"form-check no-label\">" .
                     
                        "<input class=\"form-check-input ChildCheck\" data-total=\"".($row->RowTotalDisplay ?? 0)."\" data-emp=\"".$id."\" name=\"employee_ids[".$id."]\" type=\"checkbox\"  value=".$row->extra." ".$isChecked.">" .
                        "</div>";
                })
                ->rawColumns(['EmployeeID','CheckBox','SlotFees','VisaExpiry','Medical','Insurance','WorkPermit','Department','Position','EmployeeName'])
                ->with([
                    'totals' => [
                        'visa' => 'MVR ' . number_format($totalVisa, 2),
                        'insurance' => 'MVR ' . number_format($totalInsurance, 2),
                        'work_permit' => 'MVR ' . number_format($totalPermit, 2),
                        'medical' => 'MVR ' . number_format($totalMedical, 2),
                        'slot_payment' => 'MVR ' . number_format($totalQuota, 2),
                        'overall' => 'MVR ' . number_format($overallTotal, 2),
                        'totalChecked' => $totalChecked,
                    ]
                ])
                ->make(true);
        }


        $page_title = 'Create Payment Request';
        return view('resorts.Visa.PaymentRequest.create',compact('page_title'));
       
    }
    /**
     * Build a Work Permit / Slot fee cell that lets HR choose how many upcoming
     * months to pay. $dues is the ordered list of unpaid dues
     * ([{id,amt,date}, ...]); the cell shows the 1-month amount by default plus a
     * "Months" input carrying the full list so the page can re-total client-side
     * (and the server re-derives the exact rows on submit).
     */
    /**
     * Extend the existing unpaid dues into a full payable schedule of up to 12
     * upcoming months (current + 11 ahead), synthesising months that don't have
     * a fee row yet using the monthly fee + due-day of the first due. Synthesised
     * entries carry id=null; the matching rows are created on submit. This is
     * what lets HR pay months in advance even when only the current month exists.
     */
    private function buildPayableSchedule(array $dues): array
    {
        if (empty($dues)) {
            return [];
        }
        $start   = Carbon::parse($dues[0]['date']);
        $dueDay  = (int) $start->format('d');
        $monthly = (float) $dues[0]['amt'];

        $existingByYm = [];
        foreach ($dues as $d) {
            $existingByYm[Carbon::parse($d['date'])->format('Y-m')] = $d;
        }

        $schedule  = [];
        $cursor    = $start->copy()->startOfMonth();
        $endCursor = $start->copy()->addMonths(11)->startOfMonth();
        while ($cursor->lte($endCursor)) {
            $ym = $cursor->format('Y-m');
            if (isset($existingByYm[$ym])) {
                $schedule[] = $existingByYm[$ym];
            } else {
                $day = min($dueDay, (int) $cursor->copy()->endOfMonth()->format('d'));
                $schedule[] = ['id' => null, 'amt' => $monthly, 'date' => $cursor->copy()->day($day)->format('Y-m-d')];
            }
            $cursor->addMonth();
        }
        return $schedule;
    }

    /**
     * Resolve the next $months fee rows for an employee, creating the upcoming
     * (advance) month rows that don't exist yet. Returns [ids[], totalAmount].
     */
    private function resolveAdvanceFeeRows(string $type, int $employeeId, int $months): array
    {
        $resortId = $this->resort->resort_id;
        $model    = $type === 'wp' ? WorkPermit::class : QuotaSlotRenewal::class;

        $existing = $model::where('employee_id', $employeeId)->where('resort_id', $resortId)
            ->whereNotNull('Due_Date')->where('Status', '!=', 'Paid')->where('Amt', '>', 0)
            ->orderBy('Due_Date', 'asc')->get();
        if ($existing->isEmpty()) {
            return [[], 0.0];
        }

        $first   = $existing->first();
        $monthly = (float) $first->Amt;
        $start   = Carbon::parse($first->Due_Date);
        $dueDay  = (int) $start->format('d');

        $existingByYm = [];
        foreach ($existing as $r) {
            $existingByYm[Carbon::parse($r->Due_Date)->format('Y-m')] = $r;
        }

        $ids = []; $sum = 0.0; $taken = 0;
        $cursor    = $start->copy()->startOfMonth();
        $endCursor = $start->copy()->addMonths(11)->startOfMonth();
        while ($cursor->lte($endCursor) && $taken < $months) {
            $ym = $cursor->format('Y-m');
            if (isset($existingByYm[$ym])) {
                $row = $existingByYm[$ym];
            } else {
                $day = min($dueDay, (int) $cursor->copy()->endOfMonth()->format('d'));
                $row = new $model();
                $row->resort_id   = $resortId;
                $row->employee_id = $employeeId;
                $row->Month       = $cursor->format('F Y');
                $row->Currency    = 'MVR';
                $row->Amt         = $monthly;
                $row->Due_Date    = $cursor->copy()->day($day)->format('Y-m-d');
                $row->Status      = 'Unpaid';
                $row->save();
            }
            $ids[] = (int) $row->id;
            $sum  += (float) $row->Amt;
            $taken++;
            $cursor->addMonth();
        }
        return [$ids, round($sum, 2)];
    }

    /**
     * Whether a payment-request child is fully settled, derived from the ACTUAL
     * fee rows (not the fragile OngoingSteps counter). For each fee type the
     * child requested, the linked rows must all be Paid. This stays correct no
     * matter how the fee was paid (renewal page, bulk page, or another request
     * that shares the same fee rows).
     */
    private function isChildFullyPaid($child): bool
    {
        $allPaid = function ($modelClass, $idsCsv) {
            $ids = array_filter(array_map('intval', explode(',', (string) $idsCsv)));
            if (empty($ids)) {
                return null; // no linked rows — caller falls back to the Step flag
            }
            return !$modelClass::whereIn('id', $ids)->where('Status', '!=', 'Paid')->exists();
        };

        if (strtolower((string) $child->WorkPermitShow) === 'yes') {
            $paid = $allPaid(WorkPermit::class, $child->WorkPermitIds);
            if ($paid === false) return false;
            if ($paid === null && ($child->WorkPermitStep ?? '') !== 'Yes') return false;
        }
        if (strtolower((string) $child->QuotaslotShow) === 'yes') {
            $paid = $allPaid(QuotaSlotRenewal::class, $child->QuotaslotIds);
            if ($paid === false) return false;
            if ($paid === null && ($child->QuotaslotStep ?? '') !== 'Yes') return false;
        }
        // Insurance / Medical / Visa aren't row-tracked here — rely on their Step flag.
        if (strtolower((string) $child->InsuranceShow) === 'yes' && ($child->InsuranceStep ?? '') !== 'Yes') return false;
        if (strtolower((string) $child->MedicalReportShow) === 'yes' && ($child->MedicalReportStep ?? '') !== 'Yes') return false;
        if (strtolower((string) $child->VisaShow) === 'yes' && ($child->VisaStep ?? '') !== 'Yes') return false;

        return true;
    }

    private function buildMultiMonthFeeCell(array $dues, string $fee)
    {
        if (empty($dues)) {
            return 'N/A';
        }
        $first    = $dues[0];
        $count    = count($dues);
        $duesJson = htmlspecialchars(json_encode($dues), ENT_QUOTES);
        $maxNote = $count > 1
            ? 'Pay 1 (current) or up to ' . $count . ' months in advance.'
            : 'Only the current month is due.';
        $html  = '<div class="fee-cell" data-fee="' . $fee . '">';
        $html .= '<b class="fee-amt">MVR ' . number_format($first['amt'], 2) . '</b> ';
        $html .= '<span class="fee-status">' . $this->getFormattedExpiryStatus($first['date']) . '</span>';
        $html .= '<div class="fee-months-wrap mt-1"><label class="me-1 mb-0 fw-500">Months to pay:</label>';
        $html .= '<input type="number" class="form-control form-control-sm fee-months d-inline-block" style="width:64px" min="1" max="' . $count . '" value="1" data-fee="' . $fee . '" data-dues="' . $duesJson . '" title="1 = current month due. Enter 2+ to pay upcoming months in advance.">';
        $html .= '<small class="text-muted d-block fee-months-note">' . $maxNote . '</small></div>';
        $html .= '</div>';
        return $html;
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


    function PaymentRequestSubmit(Request $request)
    {
        $employee_ids = $request->employee_ids;

          $validator = Validator::make($request->all(), [
            'employee_ids' => 'required',
        ],
            [
            'employee_ids.required' => 'Please Select at least one employee.',
        ]);

        if ($validator->fails()) 
        {
            return response()->json($validator->errors(), 400);
        }

        if (empty($employee_ids)) 
        {
            return response()->json(['error' => 'No employees selected'], 400);
        }
        else
        {


                $parts = explode(' ', $this->resort->resort->resort_name);
                $initials = '';
            foreach ($parts as $part) 
            {
                $initials .= Str::substr($part, 0, 1);
            }
            $initials = 'PR-'. $initials.'-'.Common::PaymentRequest($this->resort->resort_id);

            DB::beginTransaction();
            try
            {
                $PaymentRequest = PaymentRequest::create(['resort_id' =>$this->resort->resort_id,'Requestd_id' => $initials,'Request_date' => Carbon::now(),'Status' => 'Pending']);
                foreach($employee_ids as $id => $data)
                {
                    $data = json_decode($data, true);
                
                    foreach($data as $key => $value) 
                    {
                        $visaAmt                       = isset($value['VisaAmt']) ? $value['VisaAmt'] : 0;
                        $VisaExpiry                    = isset($value['VisaExpiry']) ? $value['VisaExpiry'] : null;
                       

                        $LastVisaExpiry                = isset($value['LastVisaExpiry']) ? $value['LastVisaExpiry'] : null;
                        $LastInsuranceExpiry           = isset($value['lastInsuranceExpiry']) ? $value['lastInsuranceExpiry'] : null;
                        $LastWorkPermitExpiry          = isset($value['LastWorkPermitExpiry']) ? $value['LastWorkPermitExpiry'] : null;
                        $LastWorkPermitMedicalExpiry   = isset($value['LastWorkPermitMedicalExpiry']) ? $value['LastWorkPermitMedicalExpiry'] : null;
                        $LastQuotaExpiry               = isset($value['LastQuotaExpiry']) ? $value['LastQuotaExpiry'] : null;

                        $insuranceAmt                  = isset($value['InsuranceAmt']) ? $value['InsuranceAmt'] : 0;
                        $insuranceExpiry               = isset($value['InsuranceExpiry']) ? $value['InsuranceExpiry']:0 ;
                        $WorkpermitAmt                 = isset($value['WorkPermitAmt']) ? $value['WorkPermitAmt'] : 0;
                        $WorkpermitExpiry              = isset($value['WorkPermitExpiry']) ? $value['WorkPermitExpiry'] : null;
                        $medicalAmt                    = isset($value['MedicalAmt']) ? $value['MedicalAmt'] : 0;
                        $medicalExpiry                 = isset($value['MedicalExpiry']) ? $value['MedicalExpiry'] : null;
                        $quotaAmt                      = isset($value['QuotaAmt']) ? $value['QuotaAmt'] : 0;
                        $quotaExpiry                   = isset($value['QuotaExpiry']) ? $value['QuotaExpiry'] : null;
                        $employee_id                   = base64_decode($key);

                        // ── Multi-month WP / Slot ──────────────────────────────
                        // HR may opt to pay several upcoming months at once. Re-derive
                        // the next N unpaid dues from the live schedule (authoritative),
                        // sum them, and record the exact row ids so fulfillment can
                        // mark all of them Paid in one action.
                        $monthsMap     = (array) $request->input('months', []);
                        $wpMonths      = max(1, (int) ($monthsMap[$key]['wp']   ?? 1));
                        $slotMonths    = max(1, (int) ($monthsMap[$key]['slot'] ?? 1));
                        $workPermitIds = null;
                        $quotaIds      = null;

                        if (isset($value['WorkPermitAmt'])) {
                            [$wpIdsArr, $wpSum] = $this->resolveAdvanceFeeRows('wp', (int) $employee_id, $wpMonths);
                            if (!empty($wpIdsArr)) {
                                $WorkpermitAmt    = $wpSum;
                                $workPermitIds    = implode(',', $wpIdsArr);
                                $wpMonths         = count($wpIdsArr);
                                $lastWp           = WorkPermit::find(end($wpIdsArr));
                                $WorkpermitExpiry = $lastWp ? Carbon::parse($lastWp->Due_Date)->format('Y-m-d') : $WorkpermitExpiry;
                            }
                        }
                        if (isset($value['QuotaAmt'])) {
                            [$qIdsArr, $qSum] = $this->resolveAdvanceFeeRows('slot', (int) $employee_id, $slotMonths);
                            if (!empty($qIdsArr)) {
                                $quotaAmt    = $qSum;
                                $quotaIds    = implode(',', $qIdsArr);
                                $slotMonths  = count($qIdsArr);
                                $lastQ       = QuotaSlotRenewal::find(end($qIdsArr));
                                $quotaExpiry = $lastQ ? Carbon::parse($lastQ->Due_Date)->format('Y-m-d') : $quotaExpiry;
                            }
                        }

                        $countrequest = [ $WorkpermitAmt > 0 ? 'yes' : 'no',
                                          $quotaExpiry > 0 ? 'yes' : 'no',
                                          $insuranceExpiry > 0 ? 'yes' : 'no',
                                          $medicalAmt > 0 ? 'yes' : 'no',
                                          $visaAmt > 0 ? 'yes' : 'no'
                                        ];
                                        
                        // Count how many 'yes' values are in the array
                        $countrequest = count(array_filter($countrequest, function($value) {
                            return $value === 'yes';
                        }));
                        PaymentRequestChild::create([
                                                    'Requested_Id' => $PaymentRequest->id,
                                                    'Employee_id' => $employee_id,
                                                    'WorkPermitDate'=> $WorkpermitExpiry,
                                                    'WorkPermitAmt' => $WorkpermitAmt,
                                                    'WorkPermitMonths' => $wpMonths,
                                                    'WorkPermitIds' => $workPermitIds,
                                                    'QuotaslotDate' => $quotaExpiry,
                                                    'QuotaslotAmt' => $quotaAmt,
                                                    'QuotaslotMonths' => $slotMonths,
                                                    'QuotaslotIds' => $quotaIds,
                                                    'InsuranceDate' => $insuranceExpiry,
                                                    'InsurancePrimume' => $insuranceAmt,
                                                    'MedicalReportDate' => $medicalExpiry, 
                                                    'MedicalReportFees' => $medicalAmt,
                                                    'VisaDate' => $VisaExpiry,
                                                    'VisaAmt' => $visaAmt, 
                                                    'LastVisaDate'=>  $LastVisaExpiry,
                                                    'LastMedicalReportDate'=> $LastWorkPermitMedicalExpiry,
                                                    'LastInsuranceDate'=> $LastInsuranceExpiry,
                                                    'LastQuotaslotDate'=> $LastQuotaExpiry,
                                                    'LastWorkPermitDate'=>  $LastWorkPermitExpiry,
                                                    'WorkPermitShow'=> $WorkpermitAmt > 0 ? 'yes' : 'no',
                                                    'QuotaslotShow'=> $quotaExpiry > 0 ? 'yes' : 'no',
                                                    'InsuranceShow'=> $insuranceExpiry > 0 ? 'yes' : 'no',
                                                    'MedicalReportShow'=> $medicalAmt > 0 ? 'yes' : 'no',
                                                    'VisaShow'=> $visaAmt > 0 ? 'yes' : 'no',
                                                    'OverallSteps'=> $countrequest,
                                                    'ChildStatus' => 'Pending',
                                                    'OngoingSteps'=> 0,
                                                ]);
                    
                    }

                }

                // Notify the Finance department — they handle the actual payments
                // for visa payment requests.
                $financeIds = Common::getResortFinanceEmployeeIds($this->resort->resort_id);
                if (!empty($financeIds)) {
                    $title   = 'New Visa Payment Request';
                    $message = 'Payment request ' . $PaymentRequest->Requestd_id . ' has been created and needs Finance action.';
                    foreach ($financeIds as $financeId) {
                        try {
                            event(new \App\Events\ResortNotificationEvent(Common::nofitication(
                                $this->resort->resort_id, 10, $title, $message, $PaymentRequest->id, $financeId, 'Visa'
                            )));
                        } catch (\Throwable $e) {
                            \Log::warning('Finance payment-request notify failed (resort ' . $this->resort->resort_id . ', emp ' . $financeId . '): ' . $e->getMessage());
                        }
                    }
                }

                DB::commit();
                $route = route('resort.visa.PaymentRequestIndex');
                    return response()->json([
                                        'success' => true,
                                        'msg' => 'Payment request created successfully',
                                        'redirect' => $route,
                                    ], 200);
            }
            catch (\Exception $e) 
            {
                DB::rollBack();
                return response()->json([ 'success' => false,'error' => 'Failed to create payment request: ' . $e->getMessage()], 500);
            }
        }

    }

    public function index(Request $request)
    {

        if($request->ajax())
        {
                $date = $request->date;
                // Keep paid (Approved) requests on the list too — they're no longer
                // removed once settled, just shown as Paid. Rejected ones drop off.
                $PaymentRequest = PaymentRequest::where('resort_id', $this->resort->resort_id)
                    ->whereIn('Status', ['Pending', 'Approved'])
                    ->when($date, function ($query) use ($date)
                    {
                        $query->whereDate('Request_date', carbon::parse($date)->format('Y-m-d'));
                    })
                    ->orderBy('id', 'desc')
                    ->get(['id', 'Requestd_id', 'Request_date', 'Status', 'created_at']);

                    // Derive the real paid state from the actual fee rows (robust no
                    // matter how the fees were settled), and keep the stored Status in
                    // sync so it doesn't drift.
                    $PaymentRequest->each(function ($pr) {
                        $children = PaymentRequestChild::where('Requested_Id', $pr->id)->get();
                        $pr->IsPaid = $children->isNotEmpty() && $children->every(fn ($c) => $this->isChildFullyPaid($c));
                        $derived = $pr->IsPaid ? 'Approved' : 'Pending';
                        if ($pr->Status !== $derived) {
                            PaymentRequest::where('id', $pr->id)->update(['Status' => $derived]);
                            $pr->Status = $derived;
                        }
                    });

                    $edit_class = '';
                    if(Common::checkRouteWisePermission('resort.visa.PaymentRequestIndex',config('settings.resort_permissions.edit')) == false){
                        $edit_class = 'd-none';
                    }
                    return datatables()
                                ->of($PaymentRequest)
                                ->addIndexColumn()
                                ->editColumn('PaymentRequestID', function ($row) 
                                {
                                    return $row->Requestd_id ?? 'N/A';
                                })
                                ->editColumn('PaymentRequestedDate', function ($row) 
                                {
                                    return $row->Request_date ? Carbon::parse($row->Request_date)->format('d M Y') : 'N/A';
                                })
                                ->editColumn('Status', function ($row)
                                {
                                    // Paid only when every employee's fees are settled.
                                    if (!empty($row->IsPaid)) {
                                        return '<span class="badge badge-themeSuccess">Paid</span>';
                                    }
                                    return '<span class="badge badge-warning">Pending</span>';
                                })
                                ->addColumn('Action', function ($row) use ($edit_class)
                                {
                                    $encodedId = base64_encode($row->id);
                                    $viewUrl2 = route('resort.visa.PaymentRequestDetails', ['id' => $encodedId]);
                                    $view = '<a target="_blank" href="' . $viewUrl2 . '" class="btn-tableIcon btnIcon-blue   btn-sm"><i class="fa-regular fa-eye"></i></a>';
                                    // Once paid only View remains — no reject action.
                                    if (!empty($row->IsPaid)) {
                                        return $view;
                                    }
                                    return $view . '
                                                        <a  href="javascript:void(0)" class="btn-tableIcon eb-icon-critical PaymentRequestRejected   btn-sm '.$edit_class.'" data-id="'.$encodedId.'"><i class="fa-solid fa-xmark"></i></a>';
                                })
                                ->rawColumns(['Status', 'Action']) 
                                ->make(true);
        }
        $page_title = 'Payment Request List';
        return view('resorts.Visa.PaymentRequest.index', compact('page_title'));
    }

    public function PaymentRequestRejected(Request $request)
    {
       $reason = $request->reason;
       $id = base64_decode($request->Payment_id);


         $PaymentRequest = PaymentRequest::where('id', $id)->where('resort_id', $this->resort->resort_id)->first();
        if (!$PaymentRequest)
        {
            return response()->json(['success' => false,'msg' => 'Payment Request not found'], 404); 
        }   
        else{
            $PaymentRequest->Status = 'Reject';
            $PaymentRequest->Reason = $reason;
            $PaymentRequest->save();

            return response()->json(['success' => true, 'msg' => 'Payment Request Rejected Successfully'], 200);
        }
    }

    public function PaymentRequestDetails($id)
    {
      
        if(Common::checkRouteWisePermission('resort.visa.PaymentRequestIndex',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        
        $PaymentRequest = PaymentRequest::where('id', base64_decode($id))->where('resort_id', $this->resort->resort_id)->first();
        if (!$PaymentRequest)
        {
            return redirect()->route('resort.Visa.PaymentRequestIndex')->with('error', 'Payment Request not found');
        }
        else
        {
        $GrandTotal = 0;
        $PaymentRequestChildren = PaymentRequestChild::where('Requested_Id', $PaymentRequest->id)
                            ->with(['RequestedEmployees.resortAdmin', 'RequestedEmployees.position', 'RequestedEmployees.department'])
                            ->get()->map(function ($employee) use(&$GrandTotal) 
                                {
                                    $sum= $employee->WorkPermitAmt + $employee->QuotaslotAmt + $employee->InsurancePrimume + $employee->MedicalReportFees + $employee->VisaAmt;
                                    // Keep the RAW numeric sum — the view passes it through
                                    // Common::formatMvr(). Pre-formatting with number_format()
                                    // inserted a thousands comma ("1,032.00") which (float)-cast
                                    // back to 1.0, so the row showed "MVR 1.00".
                                    $employee->TotalAmount = $sum;
                                    $employee->Emp_name = $employee->RequestedEmployees->resortAdmin->first_name .'  '. $employee->RequestedEmployees->resortAdmin->last_name;
                                    $employee->Emp_id = $employee->RequestedEmployees->Emp_id;
                                    $employee->Department_name = $employee->RequestedEmployees->department->name ?? 'N/A';
                                    $employee->Position_name = $employee->RequestedEmployees->position->position_title ?? 'N/A';
                                    $employee->ProfilePic = Common::getResortUserPicture( $employee->RequestedEmployees->resortAdmin->id);
                                    $GrandTotal += $sum;

                                    // Renewal status + who settled it (Finance), per fee type.
                                    // Derived from the linked fee rows; falls back to the
                                    // child's Step flag for legacy single-month requests.
                                    $employee->WorkPermitPaid = false; $employee->WorkPermitPaidBy = null; $employee->WorkPermitPaidAt = null;
                                    $wpIds = array_filter(array_map('intval', explode(',', (string) $employee->WorkPermitIds)));
                                    if (!empty($wpIds)) {
                                        $wpRows = WorkPermit::whereIn('id', $wpIds)->get(['Status','paid_by','Payment_Date']);
                                        if ($wpRows->count() && $wpRows->every(fn($r) => strtolower((string) $r->Status) === 'paid')) {
                                            $employee->WorkPermitPaid   = true;
                                            $paid = $wpRows->firstWhere('paid_by', '!=', null) ?: $wpRows->first();
                                            $employee->WorkPermitPaidBy = $paid->paid_by;
                                            $employee->WorkPermitPaidAt = $paid->Payment_Date;
                                        }
                                    } elseif (($employee->WorkPermitStep ?? '') === 'Yes') {
                                        $employee->WorkPermitPaid = true;
                                    }

                                    $employee->QuotaslotPaid = false; $employee->QuotaslotPaidBy = null; $employee->QuotaslotPaidAt = null;
                                    $qIds = array_filter(array_map('intval', explode(',', (string) $employee->QuotaslotIds)));
                                    if (!empty($qIds)) {
                                        $qRows = QuotaSlotRenewal::whereIn('id', $qIds)->get(['Status','paid_by','Payment_Date']);
                                        if ($qRows->count() && $qRows->every(fn($r) => strtolower((string) $r->Status) === 'paid')) {
                                            $employee->QuotaslotPaid   = true;
                                            $paid = $qRows->firstWhere('paid_by', '!=', null) ?: $qRows->first();
                                            $employee->QuotaslotPaidBy = $paid->paid_by;
                                            $employee->QuotaslotPaidAt = $paid->Payment_Date;
                                        }
                                    } elseif (($employee->QuotaslotStep ?? '') === 'Yes') {
                                        $employee->QuotaslotPaid = true;
                                    }

                                    // Whole-child settled? Derived from the actual fee rows.
                                    $employee->FullyPaid = $this->isChildFullyPaid($employee);

                                return $employee;
                            });
                         
            if ($PaymentRequestChildren->isEmpty()) 
            {
                return redirect()->route('resort.Visa.PaymentRequestIndex')->with('error', 'No payment request details found');
            }
            
            $PaymentRequest->children = $PaymentRequestChildren;
        }

        $page_title = 'Payment Request Details';
        return view('resorts.Visa.PaymentRequest.details', compact('page_title','id','GrandTotal','PaymentRequestChildren'));
    }

    public function DownloadPymentRequest($id)
    {

        $PaymentRequest = PaymentRequest::where('id', base64_decode($id))->where('resort_id', $this->resort->resort_id)->first();
        if (!$PaymentRequest)
        {
            return redirect()->back()->with('error', 'Payment Request not found');
        }
        else
        {
            $PaymentRequestChildren = PaymentRequestChild::where('Requested_Id', $PaymentRequest->id)
                ->with(['RequestedEmployees.resortAdmin', 'RequestedEmployees.position', 'RequestedEmployees.department'])
                ->get()->map(function ($employee) 
                {
                    $employee->Emp_name = $employee->RequestedEmployees->resortAdmin->first_name .'  '. $employee->RequestedEmployees->resortAdmin->last_name;
                    $employee->Emp_id = $employee->RequestedEmployees->Emp_id;
                    $employee->Department_name = $employee->RequestedEmployees->department->name ?? 'N/A';
                    $employee->Position_name = $employee->RequestedEmployees->position->position_title ?? 'N/A';
                    $employee->ProfilePic = Common::getResortUserPicture( $employee->RequestedEmployees->resortAdmin->id);
                    return $employee;
                });
           
            $PaymentRequest->children = $PaymentRequestChildren;
            return Excel::download(new PaymentRequestExport($PaymentRequest, $PaymentRequestChildren), $PaymentRequest->Requestd_id.'.xlsx');

        }
    }
    public function PaymentRequestThrowRenewal($id, $childId)
    {
        $page_title = 'Renewal Payment Request';
        
        $ResortBudgetCost = Common::VisaRenewalCost($this->resort->resort_id);
        $PaymentRequest = PaymentRequest::where('resort_id', $this->resort->resort_id)->find(base64_decode($id));
        $child = PaymentRequestChild::whereHas('RequestedEmployees', function ($q) {
            $q->where('resort_id', $this->resort->resort_id);
        })->find(base64_decode($childId));
        $start_date= carbon::now()->format('Y-m-d');
        $start = Carbon::parse($start_date);
        if($child)
        {
            $Employees = Employee::with(['resortAdmin', 'position', 'department'])
                                    ->where('id', $child->Employee_id)
                                    ->where('resort_id', $this->resort->resort_id)
                                    ->first();
            
            if($Employees) 
            {
                $Employees->Emp_name = $Employees->resortAdmin->first_name . ' ' . $Employees->resortAdmin->last_name;
                $Employees->Emp_id = $Employees->Emp_id;
                $Employees->Department_name = $Employees->department->name ?? 'N/A';
                $Employees->Position_name = $Employees->position->position_title ?? 'N/A';
                $Employees->ProfilePic = Common::getResortUserPicture($Employees->resortAdmin->id);
            }
            $PaymentRequest_id = $PaymentRequest ? $PaymentRequest->Requestd_id : '';



            $WorkPermitMedicalRenewal = WorkPermitMedicalRenewal::where('employee_id',$child->Employee_id)->where('resort_id',$this->resort->resort_id)->first(['employee_id','Reference_Number','Amt','Currency','Medical_Center_name','start_date','end_date','medical_file']);
            
            if($WorkPermitMedicalRenewal) 
            {
                $work_permit_amt =  $ResortBudgetCost['WORK VISA MEDICAL TEST FEE'];

                $medical_end_date = Carbon::parse($WorkPermitMedicalRenewal->end_date);
                $medical_months_diff = $start->diffInMonths($medical_end_date);
                if ($medical_months_diff < 1) 
                {
                    $days_diff = $start->diffInDays($medical_end_date);
                    $WorkPermitMedicalRenewal->MedicalRenewalTime = "Expires in $days_diff days";
                } 
                else
                {
                    $WorkPermitMedicalRenewal->MedicalRenewalTime = "$medical_months_diff month(s) remaining";
                } 
                $WorkPermitMedicalRenewal->workpermitcost =  $WorkPermitMedicalRenewal->Amt.' MVR' ?? $work_permit_amt['amount'].' '.$work_permit_amt['unit'];
                $WorkPermitMedicalRenewal->employee_id =base64_encode($WorkPermitMedicalRenewal->employee_id);
                $WorkPermitMedicalRenewal->medical_end_date = Carbon::parse($WorkPermitMedicalRenewal->medical_end_date)->format('d M Y');
            }
              $EmployeeInsurance = EmployeeInsurance::where('employee_id', $child->Employee_id)->where('resort_id', $this->resort->resort_id)
                                ->first([
                                        'insurance_company',
                                        'insurance_policy_number',
                                        'insurance_coverage',
                                        'insurance_start_date',
                                        'insurance_end_date',
                                        'insurance_file',
                                        'Currency',
                                        'Premium',
                                        'employee_id',
                                    ]);
                $start = Carbon::now();
                if ($EmployeeInsurance) 
                {
                
                    $insurance_end_date = Carbon::parse($EmployeeInsurance->insurance_end_date);
                    $insurance_months_diff = $start->diffInMonths($insurance_end_date);

                    if ($insurance_months_diff < 1) 
                    {
                        $days_diff = $start->diffInDays($insurance_end_date);
                        $EmployeeInsurance->InsuranceRenewalTime = "Expires in $days_diff days";
                    } 
                    else 
                    {
                        $EmployeeInsurance->InsuranceRenewalTime = "$insurance_months_diff month(s) remaining";
                    }
                    $medical_amt =  $ResortBudgetCost['MEDICAL INSURANCE - INTERNATIONAL'];
                    $EmployeeInsurance->insurance_policy_number =isset( $EmployeeInsurance->insurance_policy_number) ? $EmployeeInsurance->insurance_policy_number :  'N/A';
                    $EmployeeInsurance->cost =   $EmployeeInsurance->Premium.' '.$EmployeeInsurance->Currency ??  $medical_amt ['amount'].' '.$medical_amt['unit'];
                    $EmployeeInsurance->employee_id =base64_encode($EmployeeInsurance->employee_id);
                    $EmployeeInsurance->insurance_end_date = Carbon::parse($EmployeeInsurance->insurance_end_date)->format('d M Y');

                }


                if($child->QuotaslotShow == 'yes')
                {
                

                    $QuotaSlotRenewalPaid =  QuotaSlotRenewal::where('employee_id', $child->Employee_id)
                                                            ->where('resort_id', $this->resort->resort_id)
                                                            ->orderBy('Month', 'ASC')
                                                            ->get();
                    $QuotaSlotPaidAmt = $QuotaSlotRenewalPaid->where("Status","Paid")->sum('Amt');
                    $QuotaSlotUnPaidAmt = $QuotaSlotRenewalPaid->where("Status","Unpaid")->sum('Amt');
                    $QuotaSlotVariable = QuotaSlotRenewal::where('Status','Unpaid')->whereDate('Due_date',$child->QuotaslotDate)->where('employee_id', $child->Employee_id)->where("Status","Unpaid")->where('resort_id', $this->resort->resort_id)->orderBy('Month', 'ASC')->first();
                    // Multi-month: show the full amount + month count for this request.
                    if ($QuotaSlotVariable) {
                        $QuotaSlotVariable->Months = (int) ($child->QuotaslotMonths ?? 1);
                        $QuotaSlotVariable->Amt    = $child->QuotaslotAmt;
                    }

                    $QuotaSlotPayableAmt = $QuotaSlotPaidAmt + $QuotaSlotUnPaidAmt;
                }
                else
                {
                    $QuotaSlotVariable        = '';
                    $QuotaSlotPaidAmt         = 0.00;
                    $QuotaSlotUnPaidAmt       = 0.00;
                    $QuotaSlotPayableAmt      = 0.00;
                }

            
            if($child->WorkPermitShow == 'yes')
            {
               

                $WorkPermit =  WorkPermit::where('employee_id', $child->Employee_id)->where('resort_id', $this->resort->resort_id)->orderBy('Month', 'ASC')
                                                        ->get();

                    $WorkPermitPaidAmt = $WorkPermit->where("Status","Paid")->sum('Amt');
                    $WorkPermitUnPaidAmt = $WorkPermit->where("Status","Unpaid")->sum('Amt');

                    $WorkPermitCommonVariable = WorkPermit::where('employee_id', $child->Employee_id)->whereDate('Due_date',$child->WorkPermitDate)->where("Status","Unpaid")->where('resort_id', $this->resort->resort_id)->orderBy('Month', 'ASC')->first();
                    // Multi-month: show the full amount + month count for this request.
                    if ($WorkPermitCommonVariable) {
                        $WorkPermitCommonVariable->Months = (int) ($child->WorkPermitMonths ?? 1);
                        $WorkPermitCommonVariable->Amt    = $child->WorkPermitAmt;
                    }

                    $WorkPermitPayableAmt = $WorkPermitPaidAmt + $WorkPermitUnPaidAmt;
            }
            else
            {
                $WorkPermitCommonVariable = '';
                $WorkPermitPayableAmt    = 0.00;
                $WorkPermitPaidAmt       = 0.00;
                $WorkPermitUnPaidAmt     = 0.00;
            }
             
            $VisaRenewal = VisaRenewal::where('employee_id',$child->Employee_id)->where('resort_id',$this->resort->resort_id)->first(['employee_id','Visa_Number','WP_No','start_date','end_date','visa_file']);
            if($VisaRenewal) 
            {
                $Visaend = Carbon::parse($VisaRenewal->end_date);
                $months_diff = $start->diffInMonths($Visaend);
                if ($months_diff < 1) 
                {
                    $days_diff = $start->diffInDays($Visaend);
                    $VisaRenewal->VisaRenewalTime = "Expires in $days_diff days";
                } 
                else
                {
                    $VisaRenewal->VisaRenewalTime = "$months_diff month(s) remaining";
                }
                
                $visa_amt =  $ResortBudgetCost['VISA FEE'];
                
                $VisaRenewal->Amt = number_format($ResortBudgetCost['VISA FEE']['amount'],2).' '.$ResortBudgetCost['VISA FEE']['unit'] ?? $visa_amt['amount'].' '.$visa_amt['unit'];
                $VisaRenewal->end_date = Carbon::parse($VisaRenewal->end_date)->format('d M Y');
                $VisaRenewal->employee_id =base64_encode($VisaRenewal->employee_id);
                $VisaRenewal->Validitydate = "Form  ".Carbon::parse($VisaRenewal->start_date)->format('d M Y') .' To '.Carbon::parse($VisaRenewal->end_date)->format('d M Y');
            }
            else
            {
                $VisaRenewal = null;
            }
              
        }
        else
        {
            $PaymentRequest_id        = '';
            $Employees                = null;
            $child                    = null;
            $WorkPermitMedicalRenewal = null;
            $EmployeeInsurance        = null;
            $QuotaSlotPaidAmt         = 0.00;
            $QuotaSlotUnPaidAmt       = 0.00;
            $QuotaSlotPayableAmt      = 0.00;
            $WorkPermitPayableAmt     = 0.00;
            $WorkPermitPaidAmt        = 0.00;
            $WorkPermitUnPaidAmt      = 0.00;
        }
        
        

           
        return view('resorts.Visa.PaymentRequest.renewal', compact('page_title','VisaRenewal','WorkPermitCommonVariable','WorkPermitPayableAmt','WorkPermitPaidAmt','WorkPermitUnPaidAmt','QuotaSlotVariable','QuotaSlotPayableAmt','QuotaSlotPaidAmt','QuotaSlotUnPaidAmt','WorkPermitMedicalRenewal','EmployeeInsurance','child', 'Employees', 'PaymentRequest_id'));
    }

    /**
     * Finance bulk-payment page: lists EVERY unpaid Work Permit / Slot fee for
     * the resort's active expat employees so Finance can tick many and settle
     * them in one action (one shared receipt).
     */
    public function BulkRenewal(Request $request)
    {
        if ($request->ajax()) {
            $resortId = $this->resort->resort_id;
            $unified  = collect();

            $pushRow = function ($r, $type, $label) use (&$unified) {
                $emp = $r->employee;
                if (!$emp) return;
                if (strtolower(trim((string) $emp->nationality)) === 'maldivian') return;
                if (!empty($emp->status) && !in_array($emp->status, ['Active', 'Probationary'], true)) return;

                $name = $emp->resortAdmin
                    ? trim($emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name)
                    : ('Employee #' . $emp->id);

                $unified->push((object) [
                    'key'      => $type . ':' . $r->id,
                    'emp_name' => $name,
                    'emp_id'   => $emp->Emp_id,
                    'fee_type' => $label,
                    'month'    => $r->Month,
                    'due_date' => $r->Due_Date ? Carbon::parse($r->Due_Date)->format('d M Y') : '-',
                    'due_ts'   => $r->Due_Date ? Carbon::parse($r->Due_Date)->timestamp : 0,
                    'amount'   => (float) $r->Amt,
                ]);
            };

            WorkPermit::with('employee.resortAdmin')->where('resort_id', $resortId)
                ->where('Status', '!=', 'Paid')->where('Amt', '>', 0)->whereNotNull('Due_Date')
                ->get()->each(fn($r) => $pushRow($r, 'WP', 'Work Permit'));

            QuotaSlotRenewal::with('employee.resortAdmin')->where('resort_id', $resortId)
                ->where('Status', '!=', 'Paid')->where('Amt', '>', 0)->whereNotNull('Due_Date')
                ->get()->each(fn($r) => $pushRow($r, 'SLOT', 'Slot Fee'));

            $unified = $unified->sortBy('due_ts')->values();

            return datatables()->of($unified)
                ->addColumn('CheckBox', fn($row) => '<input type="checkbox" class="form-check-input BulkCheck" data-amt="' . $row->amount . '" value="' . e($row->key) . '">')
                ->addColumn('EmployeeName', fn($row) => e($row->emp_name) . ' <span class="badge badge-themeNew">' . e($row->emp_id) . '</span>')
                ->addColumn('FeeType', fn($row) => e($row->fee_type))
                ->addColumn('Month', fn($row) => e($row->month))
                ->addColumn('DueDate', fn($row) => e($row->due_date))
                ->addColumn('Amount', fn($row) => Common::formatMvr($row->amount))
                ->rawColumns(['CheckBox', 'EmployeeName'])
                ->make(true);
        }

        $page_title = 'Bulk Visa Fee Payment';
        return view('resorts.Visa.PaymentRequest.bulk_renewal', compact('page_title'));
    }

    /**
     * Settle a batch of Work Permit / Slot fees Finance ticked on the bulk page,
     * all under one receipt. Mirrors the single mark-as-paid: records who paid,
     * rolls totals into TotalExpensessSinceJoing, and notifies HR once.
     */
    public function BulkRenewalPay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items'   => 'required|array|min:1',
            'receipt' => 'required|string|max:191',
        ], [
            'items.required'   => 'Please select at least one fee to mark as paid.',
            'receipt.required' => 'Receipt number is required.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'msg' => $validator->errors()->first()], 422);
        }

        $resortId  = $this->resort->resort_id;
        $receipt   = $request->receipt;
        $payer     = $this->resort;
        $payerName = trim(($payer->first_name ?? '') . ' ' . ($payer->last_name ?? ''));
        if ($payerName === '') {
            $payerName = optional($payer)->email ?: 'Finance';
        }

        $wpIds = []; $slotIds = [];
        foreach ((array) $request->items as $key) {
            [$t, $id] = array_pad(explode(':', (string) $key, 2), 2, null);
            if ($t === 'WP' && ctype_digit((string) $id))   $wpIds[]   = (int) $id;
            if ($t === 'SLOT' && ctype_digit((string) $id)) $slotIds[] = (int) $id;
        }

        DB::beginTransaction();
        try {
            $paidCount = 0; $paidTotal = 0.0; $byEmpWp = []; $byEmpSlot = [];
            $today = Carbon::now()->format('Y-m-d');

            foreach (WorkPermit::whereIn('id', $wpIds)->where('resort_id', $resortId)->get() as $r) {
                if (strtolower((string) $r->Status) === 'paid') continue;
                $r->Status = 'Paid'; $r->ReceiptNumber = $receipt; $r->Payment_Date = $today; $r->paid_by = $payerName; $r->save();
                $byEmpWp[$r->employee_id] = ($byEmpWp[$r->employee_id] ?? 0) + (float) $r->Amt;
                $paidCount++; $paidTotal += (float) $r->Amt;
            }
            foreach (QuotaSlotRenewal::whereIn('id', $slotIds)->where('resort_id', $resortId)->get() as $r) {
                if (strtolower((string) $r->Status) === 'paid') continue;
                $r->Status = 'Paid'; $r->ReceiptNumber = $receipt; $r->Payment_Date = $today; $r->paid_by = $payerName; $r->save();
                $byEmpSlot[$r->employee_id] = ($byEmpSlot[$r->employee_id] ?? 0) + (float) $r->Amt;
                $paidCount++; $paidTotal += (float) $r->Amt;
            }

            foreach ($byEmpWp as $empId => $sum) {
                $t = \App\Models\TotalExpensessSinceJoing::where('resort_id', $resortId)->where('employees_id', $empId)->first();
                if ($t) { $t->Total_work_permit = $t->Total_work_permit + $sum; $t->save(); }
            }
            foreach ($byEmpSlot as $empId => $sum) {
                $t = \App\Models\TotalExpensessSinceJoing::where('resort_id', $resortId)->where('employees_id', $empId)->first();
                if ($t) { $t->Total_slot_Payment = $t->Total_slot_Payment + $sum; $t->save(); }
            }

            DB::commit();

            // Notify HR once for the whole batch.
            $hrIds = Common::getResortHrEmployeeIds($resortId);
            if (!empty($hrIds) && $paidCount > 0) {
                $title   = 'Visa Payments Completed';
                $message = $paidCount . ' visa fee(s) totalling MVR ' . number_format($paidTotal, 2) . ' marked Paid by ' . $payerName . ' (receipt ' . $receipt . ').';
                foreach ($hrIds as $hrId) {
                    try {
                        event(new \App\Events\ResortNotificationEvent(Common::nofitication($resortId, 10, $title, $message, 0, (int) $hrId, 'Visa')));
                    } catch (\Throwable $e) {
                        \Log::warning('Bulk-pay HR notify failed (resort ' . $resortId . ', hr ' . $hrId . '): ' . $e->getMessage());
                    }
                }
            }

            return response()->json(['success' => true, 'msg' => $paidCount . ' fee(s) marked as paid under receipt ' . $receipt . '.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'msg' => 'Failed to process payment: ' . $e->getMessage()], 500);
        }
    }
 



   
}
