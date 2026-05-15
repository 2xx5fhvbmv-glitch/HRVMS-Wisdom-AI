<?php

namespace App\Http\Controllers\Resorts\Visa;
use DB;
use URL;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\Resorts;
use App\Models\VisaWallets;
use App\Models\PaymentRequest;
use App\Models\ResortPosition;
use App\Models\Employee;
use App\Models\VisaXpactAmounts;
use App\Models\VisaWalletsTransactions;
use  App\Models\VisaNationality;
use App\Models\VisaRenewal;
use App\Models\QuotaSlotRenewal;
use App\Models\EmployeeInsurance;
use App\Models\WorkPermitMedicalRenewal;
use App\Models\VisaRenewalChild;
use  App\Models\ResortBudgetCost;
use App\Models\ResortDepartment;
use App\Models\WorkPermit;
use App\Models\VisaEmployeeExpiryData;

class DashboardController extends Controller
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
   

    public function Admin_Dashobard(Request $request)
    {

    }
    public function HR_Dashobard(Request $request)
    {

        $page_title ="Visa Management";
        $VisaWallets  = VisaWallets::orderBy("id","DESC")->where('resort_id', $this->resort->resort_id)->get();
        $VisaXpactAmounts = VisaXpactAmounts::orderBy("id","DESC")->where('resort_id', $this->resort->resort_id)->get();
        $reconiliation = $this->ReconiliationCheck();
        $DetermineSeverity = $this->DetermineSeverity();
        $Position = ResortPosition::where('resort_id', $this->resort->resort_id)->get();

        $XpatEmployeeCount = Employee::where('resort_id', $this->resort->resort_id)
            ->where('nationality', '!=', 'Maldivian')
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhereIn('status', ['Active', 'Probationary']);
            })
            ->count();

        // Resort name for the Reconciliation card heading (was hardcoded
        // "Four Season's"). Falls back gracefully if the relation is missing.
        $resortName = optional(optional($this->resort)->resort)->resort_name ?? 'Resort';

        return view('resorts.Visa.dashboard.hrdashboard',compact('page_title','Position','VisaWallets','VisaXpactAmounts','reconiliation','DetermineSeverity','XpatEmployeeCount','resortName'));
    }



    public function VisaXpactUpdateAmt(Request $request)
    {
        // Validate the SUBMITTED amount — numeric, allows decimals (0.07 etc.),
        // must not be negative. The previous code guarded on the EXISTING
        // amount being > 0, which silently rejected any edit to a wallet that
        // currently held 0, and also referenced an undefined $html in the
        // failure branch.
        $validator = Validator::make($request->all(), [
            'Xpact_WalletAmt' => ['required', 'numeric', 'min:0'],
        ], [
            'Xpact_WalletAmt.required' => 'Amount is required.',
            'Xpact_WalletAmt.numeric'  => 'Please enter a valid number.',
            'Xpact_WalletAmt.min'      => 'Amount cannot be negative.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => $validator->errors()->first(),
            ], 422);
        }

        $id = base64_decode($request->id);
        // The modal input is shown/entered in the resort's display currency.
        // Xpact_Amt is stored in MVR, so convert display → MVR before saving.
        $WalletAmt = Common::convertToStorageCurrency($request->Xpact_WalletAmt, 'MVR');
        $VisaXpactAmounts = VisaXpactAmounts::where('resort_id', $this->resort->resort_id)->find($id);

        if (!$VisaXpactAmounts) {
            return response()->json([
                'success' => false,
                'msg' => 'Xpat wallet not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
                $VisaXpactAmounts->Xpact_Amt = $WalletAmt;
                $VisaXpactAmounts->save();
                DB::commit();

                $VisaXpactAmounts  = VisaXpactAmounts::where('resort_id', $this->resort->resort_id)->get();
                $html ='';
                if($VisaXpactAmounts->isNotEmpty())
                {
                    foreach($VisaXpactAmounts as $VisaWallet)
                    {
                        $html .= '<div class="col-xl-6 col-lg-12 col-6">
                                    <div class="reconciliation-block">
                                        <div>
                                            <div class="d-flex align-items-center">
                                                <a href="javascript:void(0)"
                                                class="edit-visa-wallet me-2"
                                                data-amt="' . base64_encode(Common::convertToDisplayCurrency($VisaWallet->Xpact_Amt, 'MVR')) . '"
                                                data-name="' . base64_encode($VisaWallet->Xpact_WalletName) . '"
                                                data-id="' . base64_encode($VisaWallet->id) . '">
                                                    <img src="' . URL::asset('resorts_assets/images/edit.svg') . '" alt="icon">
                                                </a>
                                            </div>
                                            <h6>' . e($VisaWallet->Xpact_WalletName) . '</h6>
                                            <strong>' . Common::formatCurrency($VisaWallet->Xpact_Amt, 'MVR') . '</strong>
                                        </div>
                                    </div>
                                </div>';
                    }
                }
                else
                {
                     $html ='<div class="col-12"><p class="text-center">No wallets available.</p> </div>';
                }

                // Re-run the reconciliation so the "Not Reconciled - Difference"
                // rows under the Xpat Portal refresh in the same AJAX response
                // — editing a Xpat wallet changes those differences, and they
                // were previously only rendered server-side on page load.
                $reconciliationHtml = '';
                foreach ($this->ReconiliationCheck() as $r) {
                    $reconciliationHtml .= '<div class="RecoDiff-block mb-1 d-flex align-items-center">'
                        . e($r['status']) . ' In ' . e($r['wallet_name'])
                        . '</div>';
                }

                return response()->json([
                                'success' => true,
                                'msg' => 'Visa Xpat Amount updated successfully.',
                                'html' => $html,
                                'reconciliation_html' => $reconciliationHtml], 200);
            }
        catch (\Exception $e)
        {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later.');
        }

    }
    public function ReconiliationCheck()
    {
        $results = [];

        $VisaWallets      = VisaWallets::where('resort_id', $this->resort->resort_id)->get();
        $VisaXpactAmounts = VisaXpactAmounts::where('resort_id', $this->resort->resort_id)->get();

        // Index Xpat amounts by a normalised wallet name so matching is not
        // broken by stray whitespace / casing, and lookup is O(1).
        $xpactByName = [];
        foreach ($VisaXpactAmounts as $xpact) {
            $key = strtolower(trim((string) $xpact->Xpact_WalletName));
            $xpactByName[$key] = $xpact;
        }

        foreach ($VisaWallets as $wallet) {
            $key = strtolower(trim((string) $wallet->WalletName));
            if (!isset($xpactByName[$key])) {
                continue; // no matching Xpat wallet to reconcile against
            }
            $xpact = $xpactByName[$key];

            // Round BOTH sides to 2 decimals before comparing — the displayed
            // difference is 2-decimal, so comparing raw floats could flag
            // "Not Reconciled" while the shown difference rounds to 0.00.
            $walletAmt = round((float) $wallet->Amt, 2);
            $xpactAmt  = round((float) $xpact->Xpact_Amt, 2);

            if ($walletAmt !== $xpactAmt) {
                $results[] = [
                    'wallet_name' => $xpact->Xpact_WalletName,
                    'status'      => 'Not Reconciled - Difference: '
                        . Common::formatCurrency(round(abs($walletAmt - $xpactAmt), 2), 'MVR'),
                ];
            }
        }

        return $results;
    }

    public function DetermineSeverity()
    {
        $PaymentRequest = PaymentRequest::where('resort_id', $this->resort->resort_id)->get();

        return [
            'Pending'   => $PaymentRequest->where('Status', 'Pending')->count(),
            'Requested' => $PaymentRequest->where('Status', 'SendtoFinance')->count(),
            'Complete'  => $PaymentRequest->where('Status', 'Approved')->count(),
        ];
    }

    public function NatioanlityWiseEmployeeDepositAndCount(Request $request)
    {

        if($request->ajax())
        {
       
            $natioanlity = array();
            VisaNationality::where('resort_id', $this->resort->resort_id)
            ->get()
            ->map(function($ak) use (&$natioanlity){
                $natioanlityWiseEmp_count = Employee::where('resort_id', $this->resort->resort_id)
                                                    ->where('status', 'Active')
                                                    ->where('nationality', $ak->nationality)
                                                    ->get()->count();
                $natioanlity[$ak->nationality] = ['id'=>$ak->id,'DepositAmt'=>$ak->amt,'natioanlity'=>$ak->nationality,'Count'=>$natioanlityWiseEmp_count];
            return $ak;
            });


            // Convert array to collection for datatables.
            // Skip rows where no active employee currently has this nationality
            // — admin can configure a deposit rate for any country (e.g.
            // "Afghan") but the breakdown should only surface nationalities
            // that actually have at least one employee on payroll.
            $nationalityData = collect();
            foreach ($natioanlity as $key => $value) {
                if (($value['Count'] ?? 0) <= 0) {
                    continue;
                }
                $nationalityData->push((object)[
                    'nationality' => $value['natioanlity'],
                    'deposit_amount' => $value['DepositAmt'],
                    'employee_count' => $value['Count'],
                    'id' =>$value['id'] ?? 0
                ]);
            }
            return datatables()->of($nationalityData)
                ->editColumn('Nationality', function ($row) 
                {
                    return $row->nationality;
                })
                ->editColumn('DepositAmount', function ($row)
                {
                    // Total deposit liability = per-person rate × active headcount.
                    // Previously rendered the per-person rate which made
                    // "5 employees × MVR 18,000" look identical to "1 employee".
                    $total = (float) $row->deposit_amount * (int) $row->employee_count;
                    return Common::formatCurrency($total, 'MVR');
                })
                ->editColumn('Employeee', function ($row) {
                    return $row->employee_count;
                })
                ->editColumn('Action', function ($row)
                {
                    $id = base64_encode($row->id);
                    return '<a href="javascript:void(0)" class="a-link OpenNatioanlityWiseEmployee" data-cat-id="' . e($id) . '">View Details</a>';
                })      
                ->rawColumns(['Nationality', 'DepositAmount', 'Employeee', 'Action'])
                ->make(true);
        }
       


    }
    public function NatioanlityWiseEmployeeDepositAndCountDetails(Request $request)
    {
        if ($request->ajax()) {
             $natioanlity = array();
            VisaNationality::where('resort_id', $this->resort->resort_id)
            ->get()
            ->map(function($ak) use (&$natioanlity){
                $natioanlityWiseEmp_count = Employee::where('resort_id', $this->resort->resort_id)
                                                    ->where('status', 'Active')
                                                    ->where('nationality', $ak->nationality)
                                                    ->get()->count();
                $natioanlity[$ak->nationality] = ['id'=>$ak->id,'DepositAmt'=>$ak->amt,'natioanlity'=>$ak->nationality,'Count'=>$natioanlityWiseEmp_count];
            return $ak;
            });

        
           
            $nationalityData = collect();
             foreach ($natioanlity as $key => $value) {
                // Same rule as the dashboard widget: drop configured
                // nationalities with no active employees so the breakdown
                // only surfaces what's actually staffed.
                if (($value['Count'] ?? 0) <= 0) {
                    continue;
                }
                $nationalityData->push((object)[
                    'nationality' => $value['natioanlity'],
                    'deposit_amount' => $value['DepositAmt'],
                    'employee_count' => $value['Count'],
                    'id' =>$value['id'] ?? 0
                ]);
            }

              return datatables()->of($nationalityData)
                ->editColumn('Nationality', function ($row) 
                {
                    return $row->nationality;
                })
                ->editColumn('DepositAmount', function ($row)
                {
                    // Total deposit liability = per-person rate × active headcount.
                    // Previously rendered the per-person rate which made
                    // "5 employees × MVR 18,000" look identical to "1 employee".
                    $total = (float) $row->deposit_amount * (int) $row->employee_count;
                    return Common::formatCurrency($total, 'MVR');
                })
                ->editColumn('Employeee', function ($row) {
                    return $row->employee_count;
                })
                ->editColumn('Action', function ($row)
                {
                    $id = base64_encode($row->id);
                    return '<a href="javascript:void(0)" class="a-link OpenNatioanlityWiseEmployee" data-cat-id="' . e($id) . '">View Details</a>';
                })      
                ->rawColumns(['Nationality', 'DepositAmount', 'Employeee', 'Action'])
                ->make(true);
        }
        $page_title = 'Nationality Wise Employees';

        return view("resorts.Visa.employee.NatioanlityWiseEmployeeDepositAndCountlist",compact('page_title'));
    }

    public function NatioanlityWiseEmployeeList(Request $request)
    {
    
        $id = base64_decode($request->id);
        $VisaNationality = VisaNationality::where('resort_id', $this->resort->resort_id)->where('id', $id)->first();
        $natioanlityWiseEmp_count = Employee::with(['resortAdmin', 'position', 'department',])
                                                    ->where('resort_id', $this->resort->resort_id)
                                                    ->where('status', 'Active')
                                                    ->where('nationality', $VisaNationality->nationality)
                                                    ->get()
                                                    ->map(function($employee) {
                                                        $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                                                        $employee->Emp_id = $employee->Emp_id;
                                                        $employee->Department_name = $employee->department->name ?? 'N/A';
                                                        $employee->Position_name = $employee->position->position_title ?? 'N/A';
                                                        $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id);
                                                        return $employee;
                                                    });
        $html = '';
        if($natioanlityWiseEmp_count->isNotEmpty())
        {
            foreach($natioanlityWiseEmp_count as $employee)
            {
                $html .= '<tr>
                            <td>' . e($employee->Emp_id) . '</td>
                            <td>
                             <div class=" d-flex align-items-center">
                                        <div class="img-circle"><img src="'.e($employee->ProfilePic) .'" alt="user"></div>
                                </div>
                            ' . e($employee->Emp_name) . '</td>
                            <td>'. e($employee->Position_name) .  '</td>
                            <td>' . e($employee->Department_name) .  '</td>
                          </tr>';
            }
        }
        else   
        {   
            $html = '<tr><td colspan="5" class="text-center">No employees found.</td></tr>';
        }
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
                                                  
    }
 

    public function LiabilityBreakDown(Request $request)
    {
        if ($request->ajax()) {
            $resort_id = $this->resort->resort_id;
            $Year = $request->input('NatioanlityWiseBreakDownRang');
            if (!$Year || !is_numeric($Year)) {
                $Year = (int) date('Y');
            }

                $start = Carbon::create($Year, 1, 1)->startOfDay();
                $end = Carbon::create($Year, 12, 31)->endOfDay();

                $months = [];
                $period = $start->copy()->startOfMonth();

                while ($period->lte($end)) {
                    $months[$period->format('Y-m')] = $period->format('M');
                    $period->addMonth();
                }


            $chartData = [
                'labels' => array_values($months),
                'workpermit' => [],
                'slot_fee' => [],
                'insurance' => [],
                'medical' => [],
                'Visa' => [],
            ];

            // Liability Breakdown semantic:
            // Every line is bucketed by the date the liability becomes due / the cost is
            // incurred — Workpermit & Slot by Due_Date, Insurance & Medical by their
            // start_date (since those tables don't carry a separate due/paid date).
            // Status filter intentionally absent on all four so the chart shows total
            // owed (paid + unpaid) per month, matching the "Liability" framing.
            foreach ($months as $monthKey => $monthLabel)
            {
                $chartData['workpermit'][] = (float) WorkPermit::where('resort_id', $resort_id)
                    ->whereRaw("DATE_FORMAT(Due_Date, '%Y-%m') = ?", [$monthKey])
                    ->sum('Amt');

                $chartData['slot_fee'][] = (float) QuotaSlotRenewal::where('resort_id', $resort_id)
                    ->whereRaw("DATE_FORMAT(Due_Date, '%Y-%m') = ?", [$monthKey])
                    ->sum('Amt');

                $chartData['insurance'][] = (float) EmployeeInsurance::where('resort_id', $resort_id)
                    ->whereRaw("DATE_FORMAT(insurance_start_date, '%Y-%m') = ?", [$monthKey])
                    ->sum('Premium');

                $chartData['medical'][] = (float) WorkPermitMedicalRenewal::where('resort_id', $resort_id)
                    ->whereRaw("DATE_FORMAT(start_date, '%Y-%m') = ?", [$monthKey])
                    ->sum('Amt');

                // Visa stack temporarily disabled per UI request.
                // $chartData['Visa'][] = (float) VisaRenewal::where('resort_id', $resort_id)
                //     ->whereRaw("DATE_FORMAT(start_date, '%Y-%m') = ?", [$monthKey])
                //     ->sum('Amt');
                $chartData['Visa'][] = 0.0;
            }

            return response()->json([
                'success' => true,
                'data' => $chartData
            ]);
        }
    }

    public function NatioanlityWiseEmployeeBreakDownChart(Request $request)
    {
        $resort_id = $this->resort->resort_id;

        // "Top 3 Nationalities" reflects the actual expat employee population
        // at the resort, not the configured deposit-rate rows in
        // `visa_nationalities`. The previous implementation iterated the
        // deposit-rate config and skipped any nationality the admin had not
        // explicitly added there — which made the chart blank for resorts
        // that haven't seeded their deposit list.
        $top3 = Employee::where('resort_id', $resort_id)
            ->where('status', 'Active')
            ->whereRaw('LOWER(TRIM(nationality)) != ?', ['maldivian'])
            ->whereNotNull('nationality')
            ->where('nationality', '!=', '')
            ->selectRaw('nationality, COUNT(*) as Count')
            ->groupBy('nationality')
            ->orderByDesc('Count')
            ->limit(3)
            ->get();

        $totalActiveEmployees = Employee::where('resort_id', $resort_id)
            ->where('status', 'Active')
            ->whereRaw('LOWER(TRIM(nationality)) != ?', ['maldivian'])
            ->whereNotNull('nationality')
            ->where('nationality', '!=', '')
            ->count();

        $chartData = [
            'labels' => $top3->pluck('nationality')->all(),
            'data' => $top3->pluck('Count')->map(fn ($c) => (int) $c)->all(),
            'deposit_percent' => $top3->map(function ($r) use ($totalActiveEmployees) {
                return $totalActiveEmployees > 0
                    ? round(((int) $r->Count / $totalActiveEmployees) * 100)
                    : 0;
            })->all(),
        ];

        return response()->json([
            'success' => true,
            'chartData' => $chartData,
        ]);
    }

    public function DasbhoardFlagWiseGetData(Request $request)
    {
        $flag            = $request->triggerPoint;
        $checkYearStatus = $request->checkYearStatus;
        $formattedDate   = $request->formattedDate;
        if(isset($formattedDate))
        {
            $newdate = explode("-",$formattedDate);
            
            try 
            {
                $StartDate = Carbon::createFromFormat('d/m/Y', trim($newdate[0]));
                $EndDate = Carbon::createFromFormat('d/m/Y', trim($newdate[1]));
            } catch (\Exception $e) {
                // If specific format fails, try generic parsing with locale setting
                $StartDate = Carbon::parse(trim($newdate[0]))->locale('en_GB');
                $EndDate = Carbon::parse(trim($newdate[1]))->locale('en_GB');
            }
            $newDate  =  $formattedDate;
        }
        else
        {
            if($checkYearStatus == "Weekly")
            {
                $StartDate = Carbon::now()->startOfWeek();
                $EndDate   = Carbon::now()->endOfWeek();
            }
            elseif($checkYearStatus == "Monthly")
            {
                $StartDate = Carbon::now()->startOfMonth();
                $EndDate   = Carbon::now()->endOfMonth();
            }
            elseif($checkYearStatus == "Quarterly")
            {
                $StartDate = Carbon::now()->startOfQuarter();
                $EndDate   = Carbon::now()->endOfQuarter();
            }
            elseif($checkYearStatus == "Semiannual")
            {
                $StartDate = Carbon::now()->startOfYear()->addMonths(6);
                $EndDate   = Carbon::now()->endOfYear()->addMonths(6);
            }
            else
            {
                $StartDate = Carbon::now()->startOfYear();
                $EndDate   = Carbon::now()->endOfYear();
            }
            $newDate  =  $StartDate->format('d/m/Y') . ' - ' . $EndDate->format('d/m/Y');

        }

            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth   = Carbon::now()->endOfMonth();


        $employee=array();
        $resort_id         = $this->resort->resort_id;
        $ThisWeekStartDate = Carbon::now()->startOfWeek();
        $ThisWeekEndDate   = Carbon::now()->endOfWeek();
        $Today             = Carbon::now();
        if($flag == "WorkPermitFee")
        {
            
            $WorkPermit =    WorkPermit::where('resort_id', $resort_id)->whereBetween("Due_Date",[$StartDate,$EndDate])->get();
            $WorkPermit->map(function($w) use(&$employee)
            {

                 $today = Carbon::now();
                        $dueDate = Carbon::parse($w->Due_Date);
                        $overdueDays = $dueDate->diffInDays($today, false);
                        if ($overdueDays > 0) 
                        {
                            $due = " $overdueDays days overdue.";
                        } else {
                            $due = null; 
                        }
            
                        $w->overdue_status = $due;

                    $emp = Employee::with(['resortAdmin', 'position', 'department'])->where('id', $w->employee_id)->first();
                    $employee[]=[      
                                    "Emp_name"=>$emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name,
                                    "Emp_id" => $emp->Emp_id,
                                    "Department_name" => $emp->department->name ?? 'N/A',
                                    "Position_name"=> $emp->position->position_title ?? 'N/A',
                                    "ProfilePic"  => Common::getResortUserPicture($emp->resortAdmin->id),
                                    'overDue_status' =>  $due,
                                ];
                   

                    return $w;
            });
            
            $TotalPaidAmt         =    Common::formatCurrency($WorkPermit->where('Status', 'Paid')->sum('Amt'), 'MVR');
            $TotalUnpaidAmt       =    Common::formatCurrency($WorkPermit->where('Status', 'Unpaid')->sum('Amt'), 'MVR');
            $Totalemployees       =    $WorkPermit->groupBy('employee_id')->count('employee_id');
            $MonthlyduePayment    =    Common::formatCurrency(WorkPermit::where('resort_id', $resort_id)->whereBetween("Due_Date",[$startOfMonth,$endOfMonth])->where('Status', 'Unpaid')->sum('Amt'), 'MVR');
            $WeekduePayment       =    Common::formatCurrency(WorkPermit::where('resort_id', $resort_id)->whereBetween("Due_Date",[$ThisWeekStartDate,$ThisWeekEndDate])->where('Status', 'Unpaid')->sum('Amt'), 'MVR');
            $TodayduePayment      =    Common::formatCurrency(WorkPermit::where('resort_id', $resort_id)->whereDate("Due_Date",$Today->toDateString())->where('Status', 'Unpaid')->sum('Amt'), 'MVR');


        


        }
        elseif($flag == "QuotaSlot")
        {

            $QuotaSlotRenewal     =   QuotaSlotRenewal::where('resort_id', $resort_id)->whereBetween("Due_Date",[$StartDate,$EndDate])->get();
           
                $QuotaSlotRenewal->map(function($w) use(&$employee)
                {
                     $today = Carbon::now();
                        $dueDate = Carbon::parse($w->Due_Date);
                        $overdueDays = $dueDate->diffInDays($today, false);
                        if ($overdueDays > 0) 
                        {
                            $due = " $overdueDays days overdue.";
                        } else {
                            $due = null; 
                        }
            
                        $w->overdue_status = $due;

                    $emp = Employee::with(['resortAdmin', 'position', 'department'])->where('id', $w->employee_id)->first();
                    $employee[]=[      
                                    "Emp_name"=>$emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name,
                                    "Emp_id" => $emp->Emp_id,
                                    "Department_name" => $emp->department->name ?? 'N/A',
                                    "Position_name"=> $emp->position->position_title ?? 'N/A',
                                    "ProfilePic"  => Common::getResortUserPicture($emp->resortAdmin->id),
                                    'overDue_status' =>  $due,
                                ];
                   

                    return $w;
                });
            $TotalPaidAmt         =    Common::formatCurrency($QuotaSlotRenewal->where('Status', 'Paid')->sum('Amt'), 'MVR');

            $TotalUnpaidAmt       =    Common::formatCurrency($QuotaSlotRenewal->where('Status', 'Unpaid')->sum('Amt'), 'MVR');
            $Totalemployees       =    $QuotaSlotRenewal->groupBy('employee_id')->count('employee_id');
            $MonthlyduePayment    =    Common::formatCurrency(QuotaSlotRenewal::where('resort_id', $resort_id)->whereBetween("Due_Date",[$startOfMonth,$endOfMonth])->where('Status', 'Unpaid')->sum('Amt'), 'MVR');
            $WeekduePayment       =    Common::formatCurrency(QuotaSlotRenewal::where('resort_id', $resort_id)->whereBetween("Due_Date",[$ThisWeekStartDate,$ThisWeekEndDate])->where('Status', 'Unpaid')->sum('Amt'), 'MVR');
            $TodayduePayment      =    Common::formatCurrency(QuotaSlotRenewal::where('resort_id', $resort_id)->whereDate("Due_Date",$Today->toDateString())->where('Status', 'Unpaid')->sum('Amt'), 'MVR');

        }
        elseif($flag == "Insurance")
        {
            $EmployeeInsurance    =   EmployeeInsurance::where('resort_id', $resort_id)->whereBetween("insurance_end_date",[$StartDate,$EndDate])->get();

            $EmployeeInsurance->map(function($w) use(&$employee)
            {
                $today = Carbon::now();
                // EmployeeInsurance has no Due_Date column — use insurance_end_date
                // to compute the overdue label (same column used in the filter above).
                $dueDate = Carbon::parse($w->insurance_end_date);
                $overdueDays = $dueDate->diffInDays($today, false);
                if ($overdueDays > 0) 
                {
                    $due = " $overdueDays days overdue.";
                } else {
                    $due = null; 
                }
    
                $w->overdue_status = $due;

                $emp = Employee::with(['resortAdmin', 'position', 'department'])->where('id', $w->employee_id)->first();
                $employee[]=[      
                                "Emp_name"=>$emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name,
                                "Emp_id" => $emp->Emp_id,
                                "Department_name" => $emp->department->name ?? 'N/A',
                                "Position_name"=> $emp->position->position_title ?? 'N/A',
                                "ProfilePic"  => Common::getResortUserPicture($emp->resortAdmin->id),
                                'overDue_status' =>  $due,
                            ];
               
                return $w;
            });
            // Total Paid = sum of Premium for policies whose start falls in the
            // selected window AND are marked Status='Paid' (column added in
            // 2026_05_14 migration). Existing rows backfilled to Paid.
            $TotalPaidAmt         =    Common::formatCurrency(EmployeeInsurance::where('resort_id', $resort_id)->where('Status', 'Paid')->whereBetween("insurance_start_date",[$StartDate,$EndDate])->sum('Premium'), 'MVR');
            $TotalUnpaidAmt       =    Common::formatCurrency($EmployeeInsurance->where('Status', 'Pending')->sum('Premium'), 'MVR');
            $Totalemployees       =    $EmployeeInsurance->groupBy('employee_id')->count('employee_id');
            // Coming-due totals: only count policies that haven't been paid
            // yet (Status='Pending'). Mirrors the Status='Unpaid' filter the
            // Work Permit / Quota Slot branches already use for parity.
            $MonthlyduePayment    =    Common::formatCurrency(EmployeeInsurance::where('resort_id', $resort_id)->where('Status', 'Pending')->whereBetween("insurance_end_date",[$startOfMonth,$endOfMonth])->sum('Premium'), 'MVR');
            $WeekduePayment       =    Common::formatCurrency(EmployeeInsurance::where('resort_id', $resort_id)->where('Status', 'Pending')->whereBetween("insurance_end_date",[$ThisWeekStartDate,$ThisWeekEndDate])->sum('Premium'), 'MVR');
            $TodayduePayment      =    Common::formatCurrency(EmployeeInsurance::where('resort_id', $resort_id)->where('Status', 'Pending')->whereDate("insurance_end_date",$Today->toDateString())->sum('Premium'), 'MVR');
        }
        elseif($flag == "PermitMedicalFee")
        {
            $WorkPermitMedicalRenewal    =   WorkPermitMedicalRenewal::where('resort_id', $resort_id)->whereBetween("end_date",[$StartDate,$EndDate])->get();
            $WorkPermitMedicalRenewal->map(function($w) use(&$employee)
            {

                $today = Carbon::now();
                // WorkPermitMedicalRenewal has no Due_Date column — use end_date
                // to compute overdue (same column used in the filter above).
                $dueDate = Carbon::parse($w->end_date);
                $overdueDays = $dueDate->diffInDays($today, false);
                if ($overdueDays > 0) 
                {
                    $due = " $overdueDays days overdue.";
                } else {
                    $due = null; 
                }
    
                $w->overdue_status = $due;

                $emp = Employee::with(['resortAdmin', 'position', 'department'])->where('id', $w->employee_id)->first();
                $employee[]=[      
                                "Emp_name"=>$emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name,
                                "Emp_id" => $emp->Emp_id,
                                "Department_name" => $emp->department->name ?? 'N/A',
                                "Position_name"=> $emp->position->position_title ?? 'N/A',
                                "ProfilePic"  => Common::getResortUserPicture($emp->resortAdmin->id),
                                'overDue_status' =>  $due,
                            ];
            });
            // Total Paid = sum Amt for renewals whose start falls in the selected
            // window AND are marked Status='Paid' (column added in 2026_05_14
            // migration). Was using current month + no Status filter.
            $TotalPaidAmt                =    Common::formatCurrency(WorkPermitMedicalRenewal::where('resort_id', $resort_id)->where('Status', 'Paid')->whereBetween("start_date",[$StartDate,$EndDate])->sum('Amt'), 'MVR');
            $TotalUnpaidAmt              =    Common::formatCurrency($WorkPermitMedicalRenewal->where('Status', 'Pending')->sum('Amt'), 'MVR');
            $Totalemployees              =    $WorkPermitMedicalRenewal->groupBy('employee_id')->count('employee_id');
            // Coming-due totals filtered to Status='Pending' for parity with WorkPermit/QuotaSlot.
            $MonthlyduePayment           =    Common::formatCurrency(WorkPermitMedicalRenewal::where('resort_id', $resort_id)->where('Status', 'Pending')->whereBetween("end_date",[$startOfMonth,$endOfMonth])->sum('Amt'), 'MVR');
            $WeekduePayment              =    Common::formatCurrency(WorkPermitMedicalRenewal::where('resort_id', $resort_id)->where('Status', 'Pending')->whereBetween("end_date",[$ThisWeekStartDate,$ThisWeekEndDate])->sum('Amt'), 'MVR');
            $TodayduePayment             =    Common::formatCurrency(WorkPermitMedicalRenewal::where('resort_id', $resort_id)->where('Status', 'Pending')->whereDate("end_date",$Today->toDateString())->sum('Amt'), 'MVR');
        }
        elseif($flag == "WorkVisa")
        {
            $VisaRenewal                 =   VisaRenewal::where('resort_id', $resort_id)->whereBetween("end_date",[$StartDate,$EndDate])->get();
            $VisaRenewal->map(function($w) use(&$employee)
            {

                $today = Carbon::now();
                // VisaRenewal has no Due_Date column — use end_date
                // to compute overdue (same column used in the filter above).
                $dueDate = Carbon::parse($w->end_date);
                $overdueDays = $dueDate->diffInDays($today, false);
                if ($overdueDays > 0) 
                {
                    $due = " $overdueDays days overdue.";
                } else {
                    $due = null; 
                }
    
                $w->overdue_status = $due;

                $emp = Employee::with(['resortAdmin', 'position', 'department'])->where('id', $w->employee_id)->first();
                $employee[]=[      
                                "Emp_name"=>$emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name,
                                "Emp_id" => $emp->Emp_id,
                                "Department_name" => $emp->department->name ?? 'N/A',
                                "Position_name"=> $emp->position->position_title ?? 'N/A',
                                "ProfilePic"  => Common::getResortUserPicture($emp->resortAdmin->id),
                                'overDue_status' =>  $due,
                            ];

                return $w;
            });
            // Total Paid = sum Amt for visas whose start falls in the selected
            // window AND are marked Status='Paid' (column added in 2026_05_14
            // migration).
            $TotalPaidAmt                =    Common::formatCurrency(VisaRenewal::where('resort_id', $resort_id)->where('Status', 'Paid')->whereBetween("start_date",[$StartDate,$EndDate])->sum('Amt'), 'MVR');
            $TotalUnpaidAmt              =    Common::formatCurrency($VisaRenewal->where('Status', 'Pending')->sum('Amt'), 'MVR');
            $Totalemployees              =    $VisaRenewal->groupBy('employee_id')->count('employee_id');
            // Coming-due totals filtered to Status='Pending' for parity with WorkPermit/QuotaSlot.
            $MonthlyduePayment           =    Common::formatCurrency(VisaRenewal::where('resort_id', $resort_id)->where('Status', 'Pending')->whereBetween("end_date",[$startOfMonth,$endOfMonth])->sum('Amt'), 'MVR');
            $WeekduePayment              =    Common::formatCurrency(VisaRenewal::where('resort_id', $resort_id)->where('Status', 'Pending')->whereBetween("end_date",[$ThisWeekStartDate,$ThisWeekEndDate])->sum('Amt'), 'MVR');
            $TodayduePayment             =    Common::formatCurrency(VisaRenewal::where('resort_id', $resort_id)->where('Status', 'Pending')->whereDate("end_date",$Today->toDateString())->sum('Amt'), 'MVR');
        }

        $row1='';
        if(!empty($employee))
        {
            foreach($employee as $emp)
            {
                if($emp['overDue_status'] != null)
                {
                    $row1.='<div class="user-block block-danger  mb-1 d-flex align-items-center">
                        <div class="img-circle">
                            <img src='.$emp['ProfilePic'].' alt="image">
                        </div>
                        <div
                            class="w-100 d-xxl-flex d-xl-inline d-sm-flex align-items-center justify-content-between">
                            <div>
                                <h6>'.$emp['Emp_name'].'<span>'.$emp['Emp_id'].'</span></h6>
                                <p>'.$emp['Department_name'].' - '.$emp['Position_name'].'</p>
                            </div>
                            <div class="overdue-text text-end mt-xxl-0 mt-xl-1 mt-sm-0 mt-2">'.$emp['overDue_status'].' </div>
                        </div>
                    </div>';
                }
                else
                {
                     $row1 = '<div class="user-block block-danger  mb-1 d-flex align-items-center">
                    <h6 class="text-center">No Overdue found.</h6>
                    </div>';
                }
                 
            }
        }
        else
        {
            $row1 = '<div class="user-block block-danger  mb-1 d-flex align-items-center">
                    <h6 class="text-center">No Overdue found.</h6>
                    </div>';
        }
       

        


        $route = route('resort.visa.Expiry');
        // Calendar date picker + per-tab View All hidden — View All now lives in
        // the card title (blade) and the date range filter is paused. Hidden
        // input is still emitted so the post-AJAX daterangepicker init in JS
        // doesn\'t blow up looking for #hiddenInput.
        $row='<div class="tab-pane fade show active" id="'.$flag.'" role="tabpanel" aria-labelledby="'.$flag.'">
                                <input type="hidden" id="hiddenInput" value="'.$newDate.'">
                                <!--
                                <div class="row align-items-center mb-3">
                                    <div class="col">
                                               <div class="dateRangeAb"  id="datapicker">
                                                    <div>
                                                        <input type="text" class="form-control" value="'.$newDate.'" name="hiddenInput" id="hiddenInput">
                                                    </div>
                                                    <p id="startDate" class="d-none">Start Date:</p>
                                                    <p id="endDate" class="d-none">End Date:</p>
                                                </div>
                                    </div>
                                    <div class="col-auto">
                                        <a href="'.$route.'" class="a-link">View All</a>
                                    </div>
                                </div>
                                -->
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="total-incidents-box">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label>Total Xpats:</label>
                                                <Span> '.$Totalemployees.'</Span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label>Total Paid:</label>
                                                <Span>'.$TotalPaidAmt.'</Span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label>Today:</label>
                                                <Span>'.$TodayduePayment.'</Span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label>This Week:</label>
                                                <Span>'.$WeekduePayment.'</Span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label>This Month:</label>
                                                <Span>'.$MonthlyduePayment.'</Span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="bg-themeGrayLight overdue-alerts-box h-100">
                                            <h6 class="mb-3">Overdue Alerts</h6>
                                            '.$row1.'
                                        </div>
                                    </div>
                                </div>
                            </div>';
        return response()->json([
            'success' => true,
                    'StartDate'=>$StartDate,
                    'EndDate'=>$EndDate,
                    'html'=>$row
                ]);
        
       
    }

}
