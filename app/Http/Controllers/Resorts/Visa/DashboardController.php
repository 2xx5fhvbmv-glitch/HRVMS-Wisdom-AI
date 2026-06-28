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

        $visaInsights = $this->getCachedVisaInsights($this->resort->resort_id);

        return view('resorts.Visa.dashboard.hrdashboard',compact('page_title','Position','VisaWallets','VisaXpactAmounts','reconiliation','DetermineSeverity','XpatEmployeeCount','resortName','visaInsights'));
    }

    /**
     * Cached wrapper around buildVisaInsights() with a manual 2-day refresh,
     * mirroring the other module dashboards. Cached per resort; "Regenerate"
     * loads ?regenerate_insights=1 and recomputes only once the 48h cooldown has
     * elapsed. Returns a '_meta' key for the card header.
     */
    private function getCachedVisaInsights($resortId): array
    {
        $cooldownHours = 48;
        $cacheKey = 'visa_insights:' . $resortId;
        $now = \Carbon\Carbon::now();

        $cached = \Cache::get($cacheKey);
        $regenerate = request()->boolean('regenerate_insights');

        $stale = !is_array($cached) || empty($cached['generated_at']);
        if (!$stale) {
            $generatedAt = \Carbon\Carbon::parse($cached['generated_at']);
            if ($regenerate && $generatedAt->diffInHours($now) >= $cooldownHours) {
                $stale = true;
            }
        }

        if ($stale) {
            $cached = [
                'insights'     => $this->buildVisaInsights($resortId),
                'generated_at' => $now->toIso8601String(),
            ];
            \Cache::put($cacheKey, $cached, $now->copy()->addDays(30));
        }

        $generatedAt = \Carbon\Carbon::parse($cached['generated_at']);
        $insights = $cached['insights'];
        $insights['_meta'] = [
            'generated_at'   => $generatedAt,
            'can_regenerate' => $generatedAt->diffInHours($now) >= $cooldownHours,
            'next_available' => $generatedAt->copy()->addHours($cooldownHours),
        ];

        return $insights;
    }

    /**
     * Compute the four Visa AI-insight cards for a resort:
     *   1. payments   Quota & work-permit fee payment compliance (paid/unpaid/overdue)
     *   2. liability  Expat deposit liability by nationality (rate x active headcount)
     *   3. renewal    Renewal backlog (visa renewals pending/paid + due soon)
     *   4. expiry     Visa/permit expiry pipeline (expired, <=30/60/90 days)
     * Each card is wrapped so one failing query degrades just that card; the
     * deterministic numbers are then narrated by the FastAPI LLM (best-effort).
     */
    private function buildVisaInsights($resortId): array
    {
        $insights = [
            'payments'  => ['title' => 'Quota & Work-Permit Payments', 'body' => 'No quota or work-permit payments recorded yet.'],
            'liability' => ['title' => 'Expat Liability by Nationality', 'body' => 'No nationality deposit rates configured yet.'],
            'renewal'   => ['title' => 'Renewal Backlog',               'body' => 'No renewal records yet.'],
            'expiry'    => ['title' => 'Visa & Permit Expiry Pipeline',  'body' => 'No visa records with expiry dates yet.'],
        ];
        $today = \Carbon\Carbon::now();
        $num = function ($v) { return (float) preg_replace('/[^0-9.\-]/', '', (string) $v); };

        // --- Card 1: Quota & work-permit payment compliance --------------------
        try {
            $rows = collect();
            foreach (['quota_slot_renewals' => 'Quota slot', 'work_permits' => 'Work permit'] as $table => $label) {
                foreach (\DB::table($table)->where('resort_id', $resortId)->get(['Status', 'Due_Date', 'Amt']) as $r) {
                    $rows->push(['kind' => $label, 'status' => $r->Status, 'due' => $r->Due_Date, 'amt' => $num($r->Amt)]);
                }
            }
            if ($rows->isNotEmpty()) {
                $total = $rows->count();
                $unpaid = $rows->where('status', 'Unpaid');
                $unpaidCount = $unpaid->count();
                $paidCount = $total - $unpaidCount;
                $overdue = $unpaid->filter(fn ($r) => $r['due'] && $r['due'] < $today->toDateString())->count();
                $outstanding = round($unpaid->sum('amt'), 0);
                $insights['payments']['body'] = $unpaidCount . ' of ' . $total . ' quota/work-permit payments unpaid (' . $paidCount . ' paid); ' . $overdue . ' overdue; ~' . number_format($outstanding, 0) . ' outstanding.';
                $insights['payments']['details'] = [
                    'total' => $total, 'paid' => $paidCount, 'unpaid' => $unpaidCount, 'overdue' => $overdue, 'outstanding' => $outstanding,
                    'by_kind' => $rows->groupBy('kind')->map(fn ($g) => [
                        'total' => $g->count(), 'unpaid' => $g->where('status', 'Unpaid')->count(),
                        'outstanding' => round($g->where('status', 'Unpaid')->sum('amt'), 0),
                    ])->toArray(),
                ];
            }
        } catch (\Throwable $e) {}

        // --- Card 2: Expat liability by nationality ----------------------------
        try {
            $nats = VisaNationality::where('resort_id', $resortId)->get(['nationality', 'amt']);
            $rows = []; $totalLiability = 0; $totalHeads = 0;
            foreach ($nats as $n) {
                $count = Common::applyNationalityMatch(
                        Employee::where('resort_id', $resortId)->where('status', 'Active'),
                        $n->nationality
                    )->count();
                if ($count === 0) continue;
                $rate = $num($n->amt);
                $liab = $rate * $count;
                $totalLiability += $liab; $totalHeads += $count;
                $rows[] = ['nationality' => $n->nationality ?: '—', 'headcount' => $count, 'rate' => $rate, 'liability' => round($liab, 0)];
            }
            if (!empty($rows)) {
                usort($rows, fn ($a, $b) => $b['liability'] <=> $a['liability']);
                $top = $rows[0];
                $insights['liability']['body'] = '~' . number_format(round($totalLiability, 0), 0) . ' total deposit liability across ' . $totalHeads . ' expats in ' . count($rows) . ' nationalities; ' . $top['nationality'] . ' leads (~' . number_format($top['liability'], 0) . ').';
                $insights['liability']['details'] = ['total_liability' => round($totalLiability, 0), 'total_heads' => $totalHeads, 'rows' => array_slice($rows, 0, 15)];
            }
        } catch (\Throwable $e) {}

        // --- Card 3: Renewal backlog -------------------------------------------
        try {
            $totalRenewals = VisaRenewal::where('resort_id', $resortId)->count();
            if ($totalRenewals > 0) {
                $pending = VisaRenewal::where('resort_id', $resortId)->where('Status', 'Pending')->count();
                $paid = $totalRenewals - $pending;
                $dueSoon = VisaRenewal::where('resort_id', $resortId)
                    ->whereDate('end_date', '>=', $today->toDateString())
                    ->whereDate('end_date', '<=', $today->copy()->addDays(30)->toDateString())->count();
                $wpUnpaid = (int) \DB::table('work_permits')->where('resort_id', $resortId)->where('Status', 'Unpaid')->count();
                $quotaUnpaid = (int) \DB::table('quota_slot_renewals')->where('resort_id', $resortId)->where('Status', 'Unpaid')->count();
                $insights['renewal']['body'] = $totalRenewals . ' visa renewals (' . $pending . ' pending, ' . $paid . ' paid); ' . $dueSoon . ' due within 30 days; ' . $wpUnpaid . ' work-permit + ' . $quotaUnpaid . ' quota payments pending.';
                $insights['renewal']['details'] = [
                    'visa_total' => $totalRenewals, 'visa_pending' => $pending, 'visa_paid' => $paid, 'due_soon' => $dueSoon,
                    'work_permit_pending' => $wpUnpaid, 'quota_pending' => $quotaUnpaid,
                ];
            }
        } catch (\Throwable $e) {}

        // --- Card 4: Visa & permit expiry pipeline -----------------------------
        try {
            $base = VisaRenewal::where('resort_id', $resortId)->whereNotNull('end_date');
            $total = (clone $base)->count();
            if ($total > 0) {
                $d = fn ($n) => $today->copy()->addDays($n)->toDateString();
                $expired = (clone $base)->whereDate('end_date', '<', $today->toDateString())->count();
                $in30 = (clone $base)->whereDate('end_date', '>=', $today->toDateString())->whereDate('end_date', '<=', $d(30))->count();
                $in60 = (clone $base)->whereDate('end_date', '>', $d(30))->whereDate('end_date', '<=', $d(60))->count();
                $in90 = (clone $base)->whereDate('end_date', '>', $d(60))->whereDate('end_date', '<=', $d(90))->count();
                $insights['expiry']['body'] = $expired . ' expired; ' . $in30 . ' expiring within 30 days, ' . $in60 . ' in 31-60, ' . $in90 . ' in 61-90 days.';
                $insights['expiry']['details'] = ['expired' => $expired, 'in_30' => $in30, 'in_60' => $in60, 'in_90' => $in90, 'total' => $total];
            }
        } catch (\Throwable $e) {}

        // Narrate the deterministic numbers via the FastAPI LLM (best-effort).
        $insights = \App\Helpers\Common::enrichDashboardInsights(
            $insights, 'expat visa, work-permit & immigration compliance', ['payments', 'liability', 'renewal', 'expiry']
        );

        return $insights;
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
        // The visa module is MVR end-to-end: the modal input is entered in MVR
        // and Xpact_Amt is stored in MVR, so save the raw value (no conversion).
        $WalletAmt = (float) $request->Xpact_WalletAmt;
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
                                                data-amt="' . base64_encode($VisaWallet->Xpact_Amt) . '"
                                                data-name="' . base64_encode($VisaWallet->Xpact_WalletName) . '"
                                                data-id="' . base64_encode($VisaWallet->id) . '">
                                                    <img src="' . URL::asset('resorts_assets/images/edit.svg') . '" alt="icon">
                                                </a>
                                            </div>
                                            <h6>' . e($VisaWallet->Xpact_WalletName) . '</h6>
                                            <strong>' . Common::formatMvr($VisaWallet->Xpact_Amt) . '</strong>
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
                        . Common::formatMvr(round(abs($walletAmt - $xpactAmt), 2)),
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
                $natioanlityWiseEmp_count = Common::applyNationalityMatch(
                        Employee::where('resort_id', $this->resort->resort_id)->where('status', 'Active'),
                        $ak->nationality
                    )->count();
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
                    return Common::formatMvr($total);
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
                $natioanlityWiseEmp_count = Common::applyNationalityMatch(
                        Employee::where('resort_id', $this->resort->resort_id)->where('status', 'Active'),
                        $ak->nationality
                    )->count();
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
                    return Common::formatMvr($total);
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
        $natioanlityWiseEmp_count = Common::applyNationalityMatch(
                                                    Employee::with(['resortAdmin', 'position', 'department',])
                                                        ->where('resort_id', $this->resort->resort_id)
                                                        ->where('status', 'Active'),
                                                    $VisaNationality->nationality
                                                )
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

        // Top Nationalities reflects the actual expat employee population at
        // the resort (not the configured deposit-rate rows in
        // visa_nationalities). All nationalities are fetched ranked by count;
        // the "top 3" cutoff is tie-inclusive — see below.
        $allCounts = Employee::where('resort_id', $resort_id)
            ->where('status', 'Active')
            ->whereRaw('LOWER(TRIM(nationality)) != ?', ['maldivian'])
            ->whereNotNull('nationality')
            ->where('nationality', '!=', '')
            ->selectRaw('nationality, COUNT(*) as Count')
            ->groupBy('nationality')
            ->orderByDesc('Count')
            ->get();

        // Tie-inclusive "top 3": if the 3rd-ranked count is shared by other
        // nationalities, include all of them (so e.g. three nationalities
        // tied at 3 employees all appear, instead of MySQL arbitrarily
        // dropping one). When there are 3 or fewer nationalities, show all.
        if ($allCounts->count() > 3) {
            $cutoff = (int) $allCounts[2]->Count;
            $top = $allCounts->filter(fn ($r) => (int) $r->Count >= $cutoff)->values();
        } else {
            $top = $allCounts->values();
        }

        $totalActiveEmployees = Employee::where('resort_id', $resort_id)
            ->where('status', 'Active')
            ->whereRaw('LOWER(TRIM(nationality)) != ?', ['maldivian'])
            ->whereNotNull('nationality')
            ->where('nationality', '!=', '')
            ->count();

        $chartData = [
            'labels' => $top->pluck('nationality')->all(),
            'data'   => $top->pluck('Count')->map(fn ($c) => (int) $c)->all(),
            // Percentage = share of the TOTAL active expat workforce
            // (count / total), 2-decimal. Slices may not sum to 100% — the
            // remainder is nationalities below the cutoff.
            'deposit_percent' => $top->map(function ($r) use ($totalActiveEmployees) {
                return $totalActiveEmployees > 0
                    ? round(((int) $r->Count / $totalActiveEmployees) * 100, 2)
                    : 0;
            })->all(),
        ];

        return response()->json([
            'success' => true,
            'chartData' => $chartData,
        ]);
    }

    /**
     * Build the per-row employee entries the Overdue Alerts list renders from a
     * collection of fee rows — WITHOUT the per-row Employee query that made tab
     * switching slow. The old code ran `Employee::with(...)->first()` AND a
     * profile-picture lookup for EVERY fee row, so an employee with 12 monthly
     * installments was fetched 12× (a classic N+1). This loads every employee in
     * one query and caches each profile picture once.
     *
     * @param  \Illuminate\Support\Collection $rows    Fee rows (need employee_id + $dateCol)
     * @param  string                         $dateCol Date column used for the overdue calc
     */
    private function buildDashboardOverdueEntries($rows, string $dateCol): array
    {
        if ($rows->isEmpty()) {
            return [];
        }
        $empIds = $rows->pluck('employee_id')->filter()->unique()->values();
        $emps = Employee::with(['resortAdmin', 'position', 'department'])
            ->whereIn('id', $empIds)->get()->keyBy('id');

        $now = Carbon::now();
        $picCache = [];
        $entries = [];
        foreach ($rows as $w) {
            $emp = $emps->get($w->employee_id);
            if (!$emp) {
                continue;
            }
            $overdueDays = $w->$dateCol ? Carbon::parse($w->$dateCol)->diffInDays($now, false) : 0;
            $due = $overdueDays > 0 ? " $overdueDays days overdue." : null;

            $adminId = $emp->resortAdmin->id ?? null;
            if ($adminId !== null && !array_key_exists($adminId, $picCache)) {
                $picCache[$adminId] = Common::getResortUserPicture($adminId);
            }

            $entries[] = [
                "Emp_name"        => trim(($emp->resortAdmin->first_name ?? '') . ' ' . ($emp->resortAdmin->last_name ?? '')),
                "Emp_id"          => $emp->Emp_id,
                "Department_name" => $emp->department->name ?? 'N/A',
                "Position_name"   => $emp->position->position_title ?? 'N/A',
                "ProfilePic"      => $adminId !== null ? $picCache[$adminId] : null,
                'overDue_status'  => $due,
            ];
        }
        return $entries;
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
            $employee = $this->buildDashboardOverdueEntries($WorkPermit, 'Due_Date');

            $TotalPaidAmt         =    Common::formatMvr($WorkPermit->where('Status', 'Paid')->sum('Amt'));
            $TotalUnpaidAmt       =    Common::formatMvr($WorkPermit->where('Status', 'Unpaid')->sum('Amt'));
            $Totalemployees       =    $WorkPermit->groupBy('employee_id')->count('employee_id');
            $MonthlyduePayment    =    Common::formatMvr(WorkPermit::where('resort_id', $resort_id)->whereBetween("Due_Date",[$startOfMonth,$endOfMonth])->where('Status', 'Unpaid')->sum('Amt'));
            $WeekduePayment       =    Common::formatMvr(WorkPermit::where('resort_id', $resort_id)->whereBetween("Due_Date",[$ThisWeekStartDate,$ThisWeekEndDate])->where('Status', 'Unpaid')->sum('Amt'));
            $TodayduePayment      =    Common::formatMvr(WorkPermit::where('resort_id', $resort_id)->whereDate("Due_Date",$Today->toDateString())->where('Status', 'Unpaid')->sum('Amt'));


        


        }
        elseif($flag == "QuotaSlot")
        {

            $QuotaSlotRenewal     =   QuotaSlotRenewal::where('resort_id', $resort_id)->whereBetween("Due_Date",[$StartDate,$EndDate])->get();
            $employee = $this->buildDashboardOverdueEntries($QuotaSlotRenewal, 'Due_Date');
            $TotalPaidAmt         =    Common::formatMvr($QuotaSlotRenewal->where('Status', 'Paid')->sum('Amt'));

            $TotalUnpaidAmt       =    Common::formatMvr($QuotaSlotRenewal->where('Status', 'Unpaid')->sum('Amt'));
            $Totalemployees       =    $QuotaSlotRenewal->groupBy('employee_id')->count('employee_id');
            $MonthlyduePayment    =    Common::formatMvr(QuotaSlotRenewal::where('resort_id', $resort_id)->whereBetween("Due_Date",[$startOfMonth,$endOfMonth])->where('Status', 'Unpaid')->sum('Amt'));
            $WeekduePayment       =    Common::formatMvr(QuotaSlotRenewal::where('resort_id', $resort_id)->whereBetween("Due_Date",[$ThisWeekStartDate,$ThisWeekEndDate])->where('Status', 'Unpaid')->sum('Amt'));
            $TodayduePayment      =    Common::formatMvr(QuotaSlotRenewal::where('resort_id', $resort_id)->whereDate("Due_Date",$Today->toDateString())->where('Status', 'Unpaid')->sum('Amt'));

        }
        elseif($flag == "Insurance")
        {
            $EmployeeInsurance    =   EmployeeInsurance::where('resort_id', $resort_id)->whereBetween("insurance_end_date",[$StartDate,$EndDate])->get();
            $employee = $this->buildDashboardOverdueEntries($EmployeeInsurance, 'insurance_end_date');
            // Total Paid = sum of Premium for policies whose start falls in the
            // selected window AND are marked Status='Paid' (column added in
            // 2026_05_14 migration). Existing rows backfilled to Paid.
            $TotalPaidAmt         =    Common::formatMvr(EmployeeInsurance::where('resort_id', $resort_id)->where('Status', 'Paid')->whereBetween("insurance_start_date",[$StartDate,$EndDate])->sum('Premium'));
            $TotalUnpaidAmt       =    Common::formatMvr($EmployeeInsurance->where('Status', 'Pending')->sum('Premium'));
            $Totalemployees       =    $EmployeeInsurance->groupBy('employee_id')->count('employee_id');
            // Coming-due totals: only count policies that haven't been paid
            // yet (Status='Pending'). Mirrors the Status='Unpaid' filter the
            // Work Permit / Quota Slot branches already use for parity.
            $MonthlyduePayment    =    Common::formatMvr(EmployeeInsurance::where('resort_id', $resort_id)->where('Status', 'Pending')->whereBetween("insurance_end_date",[$startOfMonth,$endOfMonth])->sum('Premium'));
            $WeekduePayment       =    Common::formatMvr(EmployeeInsurance::where('resort_id', $resort_id)->where('Status', 'Pending')->whereBetween("insurance_end_date",[$ThisWeekStartDate,$ThisWeekEndDate])->sum('Premium'));
            $TodayduePayment      =    Common::formatMvr(EmployeeInsurance::where('resort_id', $resort_id)->where('Status', 'Pending')->whereDate("insurance_end_date",$Today->toDateString())->sum('Premium'));
        }
        elseif($flag == "PermitMedicalFee")
        {
            $WorkPermitMedicalRenewal    =   WorkPermitMedicalRenewal::where('resort_id', $resort_id)->whereBetween("end_date",[$StartDate,$EndDate])->get();
            $employee = $this->buildDashboardOverdueEntries($WorkPermitMedicalRenewal, 'end_date');
            // Total Paid = sum Amt for renewals whose start falls in the selected
            // window AND are marked Status='Paid' (column added in 2026_05_14
            // migration). Was using current month + no Status filter.
            $TotalPaidAmt                =    Common::formatMvr(WorkPermitMedicalRenewal::where('resort_id', $resort_id)->where('Status', 'Paid')->whereBetween("start_date",[$StartDate,$EndDate])->sum('Amt'));
            $TotalUnpaidAmt              =    Common::formatMvr($WorkPermitMedicalRenewal->where('Status', 'Pending')->sum('Amt'));
            $Totalemployees              =    $WorkPermitMedicalRenewal->groupBy('employee_id')->count('employee_id');
            // Coming-due totals filtered to Status='Pending' for parity with WorkPermit/QuotaSlot.
            $MonthlyduePayment           =    Common::formatMvr(WorkPermitMedicalRenewal::where('resort_id', $resort_id)->where('Status', 'Pending')->whereBetween("end_date",[$startOfMonth,$endOfMonth])->sum('Amt'));
            $WeekduePayment              =    Common::formatMvr(WorkPermitMedicalRenewal::where('resort_id', $resort_id)->where('Status', 'Pending')->whereBetween("end_date",[$ThisWeekStartDate,$ThisWeekEndDate])->sum('Amt'));
            $TodayduePayment             =    Common::formatMvr(WorkPermitMedicalRenewal::where('resort_id', $resort_id)->where('Status', 'Pending')->whereDate("end_date",$Today->toDateString())->sum('Amt'));
        }
        elseif($flag == "WorkVisa")
        {
            $VisaRenewal                 =   VisaRenewal::where('resort_id', $resort_id)->whereBetween("end_date",[$StartDate,$EndDate])->get();
            $employee = $this->buildDashboardOverdueEntries($VisaRenewal, 'end_date');
            // Total Paid = sum Amt for visas whose start falls in the selected
            // window AND are marked Status='Paid' (column added in 2026_05_14
            // migration).
            $TotalPaidAmt                =    Common::formatMvr(VisaRenewal::where('resort_id', $resort_id)->where('Status', 'Paid')->whereBetween("start_date",[$StartDate,$EndDate])->sum('Amt'));
            $TotalUnpaidAmt              =    Common::formatMvr($VisaRenewal->where('Status', 'Pending')->sum('Amt'));
            $Totalemployees              =    $VisaRenewal->groupBy('employee_id')->count('employee_id');
            // Coming-due totals filtered to Status='Pending' for parity with WorkPermit/QuotaSlot.
            $MonthlyduePayment           =    Common::formatMvr(VisaRenewal::where('resort_id', $resort_id)->where('Status', 'Pending')->whereBetween("end_date",[$startOfMonth,$endOfMonth])->sum('Amt'));
            $WeekduePayment              =    Common::formatMvr(VisaRenewal::where('resort_id', $resort_id)->where('Status', 'Pending')->whereBetween("end_date",[$ThisWeekStartDate,$ThisWeekEndDate])->sum('Amt'));
            $TodayduePayment             =    Common::formatMvr(VisaRenewal::where('resort_id', $resort_id)->where('Status', 'Pending')->whereDate("end_date",$Today->toDateString())->sum('Amt'));
        }

        $row1='';
        $seenOverdue = [];
        if(!empty($employee))
        {
            foreach($employee as $emp)
            {
                if($emp['overDue_status'] != null)
                {
                    // De-duplicate: an employee with several overdue installments
                    // (e.g. multiple unpaid monthly quota / work-permit rows) must
                    // appear only ONCE here — keep the first card, which is the most
                    // overdue (records come oldest-due first).
                    $dupKey = (string) ($emp['Emp_id'] ?? ($emp['Emp_name'] ?? ''));
                    if ($dupKey !== '' && isset($seenOverdue[$dupKey])) { continue; }
                    $seenOverdue[$dupKey] = true;

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
            }
        }

        // Show the "No Overdue found." placeholder ONLY when nothing overdue was
        // collected. Previously the loop's else-branch RE-ASSIGNED $row1 to the
        // placeholder on every non-overdue row, which wiped already-collected
        // overdue cards whenever a not-yet-due installment came later in the list
        // (Work Permit / Quota Slot carry future monthly rows after the overdue
        // one) — so their overdue alerts silently vanished.
        if ($row1 === '')
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
        // "Total Xpats" = total active expat headcount for the resort, consistent
        // across all fee tabs (it previously showed the per-fee row count which
        // changed tab-to-tab and did not match the actual expat total).
        $Totalemployees = Employee::where('resort_id', $resort_id)
            ->where('status', 'Active')
            ->where('nationality', '!=', 'Maldivian')
            ->count();

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
                                            <div class="overdue-alerts-scroll" style="max-height:300px;overflow-y:auto;padding-right:6px;">'.$row1.'</div>
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
