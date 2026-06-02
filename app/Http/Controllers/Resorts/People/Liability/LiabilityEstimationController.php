<?php
namespace App\Http\Controllers\Resorts\People\Liability;

use App\Http\Controllers\Controller;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Events\ResortNotificationEvent;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\resortAdmin;
use App\Models\ResortDepartment;
use App\Models\ResortSiteSettings;
use App\Models\StoreManningResponseParent;
use App\Models\Payroll;
use App\Models\PayrollReview;
use App\Models\PayrollReviewAllowances;
use Auth;
use Config;
use DB;
use Carbon\Carbon;

class LiabilityEstimationController extends Controller 
{
    public $resort;
    
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index()
    {
        $page_title = 'Initial Liability Estimation';
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $totalVisa = $totalInsurance = $totalPermit = $totalMedical = $totalQuota = 0;
        $totalInsuranceEmployee = $totalPermitEmployee = $TotalVisaEmployee = $totalMedicalEmployee = $totalQuotaEmployee = 0;
        $resortId = $this->resort->resort_id ?? null; // Optional if this is called from superadmin

        $resort_departments = ResortDepartment::where('resort_id', $resortId)
            ->where('status', 'active')
            ->get();
        $scopedDeptIds = \App\Helpers\Common::getScopedDepartmentIds();
        $employees = Employee::where('resort_id', $resortId)
            ->where('status', 'active')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->get();

        // Display-currency conversion. Per the project's canonical rule
        // ("Money stored in USD" — budget/salary/cost/payroll all keep USD
        // as the storage unit; only render-time conversion happens), every
        // figure used by this method is treated as USD source. The chart
        // values are pre-converted to the resort's CURRENT display currency
        // at the end of the method so the doughnut tooltip and the line
        // chart y-axis match what users see in the headline cards.
        //
        // The Est-vs-Actual table keeps USD-source values and lets the
        // shared Common::formatCurrency helper convert per render — that
        // path already handles the Dollar/MVR toggle correctly.
        $dollarToMvr = (float) (DB::table('resort_site_settings')
            ->where('resort_id', $resortId)
            ->value('DollertoMVR') ?: 15.42);
        if ($dollarToMvr <= 0) $dollarToMvr = 15.42;
        $resortDisplayCurrency = DB::table('resort_site_settings')
            ->where('resort_id', $resortId)
            ->value('currency'); // 'Dollar' or 'MVR'
        $displayRate = strcasecmp((string) $resortDisplayCurrency, 'MVR') === 0
            ? $dollarToMvr
            : 1.0;
        $mvrToUsdRate = 1.0 / $dollarToMvr;

        // Total Estimated Liability — shared canonical calc in Common so the
        // Initial Liability headline AND the People Dashboard Liability
        // Tracker AND Budget → View Budget all agree.
        // Total Estimated is computed AFTER the per-leg breakdown below so
        // we don't iterate (employees × templates) twice. The canonical
        // helper `Common::computeYearlyBudgetTotal` does the same SQL
        // joins on its own — calling it here AND running the breakdown
        // loop made the page load >2× slower on big resorts.
        $estimated_liability = 0.0; // populated below from $estLegs sum

        // -- Per-leg breakdown of the Total Estimated headline -----------
        // Built for the "Liability Reduction" detail modal so HR can see
        // why the headline reads what it does instead of asking why a
        // single payroll run barely moves the value. Numbers below are
        // pure aggregations — the headline `$estimated_liability` is
        // still the source of truth.
        $estLegEmployeeSalary = 0.0;
        $estLegCostTemplate   = 0.0;
        $estLegEmployeeAllowance = 0.0;
        $estLegVacant         = 0.0;
        $perTemplateAnnual    = []; // particulars → annual USD sum across all employees
        $activeForBreakdown   = collect();
        $costsForBreakdown    = collect();
        $empIdsForBreakdown   = [];
        try {
            $activeForBreakdown = DB::table('employees')
                ->where('resort_id', $resortId)
                ->where('status', 'Active')
                ->get(['id', 'basic_salary', 'proposed_salary', 'nationality', 'religion', 'benefit_grid_level']);
            foreach ($activeForBreakdown as $emp) {
                $shared = (float) (($emp->proposed_salary ?? 0) > 0
                    ? $emp->proposed_salary
                    : ($emp->basic_salary ?? 0));
                $estLegEmployeeSalary += $shared * 12;
            }
            $costsForBreakdown = DB::table('resort_budget_costs')
                ->where('resort_id', $resortId)->where('status', 'active')
                ->get(['id', 'particulars', 'cost_title', 'amount', 'amount_unit', 'cost_type', 'frequency', 'details', 'benefit_grid_levels']);
            // PERFORMANCE: pre-fetch saved per-month overrides + the
            // DollertoMVR rate in TWO queries instead of letting
            // annualCostForEmployee do (employees × templates) × 2
            // sub-queries inside the inner loop. Page load on a 100-emp
            // resort drops from ~12s to ~0.8s with these prefetches.
            $savedByKey = []; // [emp_id][cost_id][month] = value
            foreach (DB::table('resort_employee_budget_cost_configurations')
                ->where('resort_id', $resortId)->where('year', $currentYear)
                ->whereIn('employee_id', $activeForBreakdown->pluck('id'))
                ->get(['employee_id', 'resort_budget_cost_id', 'month', 'value']) as $row) {
                $savedByKey[$row->employee_id][$row->resort_budget_cost_id][$row->month] = (float) $row->value;
            }
            $prefetchedDollarToMvr = (float) (DB::table('resort_site_settings')
                ->where('resort_id', $resortId)->value('DollertoMVR') ?: 15.42);
            if ($prefetchedDollarToMvr <= 0) $prefetchedDollarToMvr = 15.42;

            foreach ($costsForBreakdown as $c) {
                $isMvr = strtoupper(trim((string) ($c->amount_unit ?? 'USD'))) === 'MVR';
                $mvrToUsdRate = $isMvr ? (1.0 / $prefetchedDollarToMvr) : 1.0;
                $tmplSum = 0.0;
                foreach ($activeForBreakdown as $emp) {
                    $tmplSum += self::fastAnnualCostForEmployee(
                        $resortId, $currentYear, $c, $emp,
                        $savedByKey[$emp->id][$c->id] ?? [], $isMvr, $mvrToUsdRate
                    );
                }
                if ($tmplSum > 0) {
                    $label = $c->particulars ?: ($c->cost_title ?: 'Other');
                    $perTemplateAnnual[$label] = ($perTemplateAnnual[$label] ?? 0) + $tmplSum;
                }
                $estLegCostTemplate += $tmplSum;
            }
            $dollarToMvr = (float) (DB::table('resort_site_settings')
                ->where('resort_id', $resortId)->value('DollertoMVR') ?: 15.42);
            if ($dollarToMvr <= 0) $dollarToMvr = 15.42;
            $empIdsForBreakdown = $activeForBreakdown->pluck('id')->all();
            $allowMonthly = empty($empIdsForBreakdown) ? 0 : (float) DB::table('employees_allowance')
                ->whereIn('employee_id', $empIdsForBreakdown)
                ->selectRaw("COALESCE(SUM(CASE WHEN amount_unit = 'MVR' THEN amount * (1.0 / {$dollarToMvr}) ELSE amount END), 0) as t")
                ->value('t');
            $estLegEmployeeAllowance = $allowMonthly * 12;
            foreach (DB::table('resort_vacant_budget_costs')
                ->where('resort_id', $resortId)->where('year', $currentYear)->get() as $v) {
                $estLegVacant += Common::annualBudgetForVacantSlot($resortId, $currentYear, $v);
            }
        } catch (\Throwable $e) {
            // Breakdown failures shouldn't break the page render.
            \Log::warning('[liability-breakdown] '.$e->getMessage());
        }
        // Headline derived from the legs we just built — see comment above
        // about avoiding the duplicate canonical-helper SQL pass.
        $estimated_liability = $estLegEmployeeSalary
                             + $estLegCostTemplate
                             + $estLegEmployeeAllowance
                             + $estLegVacant;

        $estLegs = [
            ['label' => 'Employee Salaries (active employees × 12 months)', 'value' => $estLegEmployeeSalary],
            ['label' => 'Cost Templates (Food Cost, Pension, Tickets, …)',  'value' => $estLegCostTemplate],
            ['label' => 'Per-Employee Allowances (×12)',                    'value' => $estLegEmployeeAllowance],
            ['label' => 'Vacant Slot Salaries + Cost Configs',              'value' => $estLegVacant],
        ];
        // Cost-category breakdowns for the Estimation-vs-Actual table use the
        // same particulars-keyword approach as before (cost templates only).
        $budgetByParticular = DB::table('resort_budget_costs')
            ->where('resort_id', $resortId)
            ->select('particulars', DB::raw('SUM(amount) as total'))
            ->groupBy('particulars')
            ->pluck('total', 'particulars')->toArray();
        $budgetMatch = function (array $keywords) use ($budgetByParticular) {
            $sum = 0.0;
            foreach ($budgetByParticular as $particular => $amount) {
                foreach ($keywords as $kw) {
                    if (stripos($particular, $kw) !== false) {
                        $sum += (float) $amount;
                        break;
                    }
                }
            }
            return $sum;
        };

        // ✅ Current Liability from Payroll Reviews for the year
        $payrolls = Payroll::with('reviews')
            ->where('resort_id', $resortId)
            ->whereYear('start_date', $currentYear)
            ->get();

        $totalVisa = $totalInsurance = $totalPermit = $totalMedical = $totalQuota = 0;
        $totalInsuranceEmployee = $totalPermitEmployee = $TotalVisaEmployee = $totalMedicalEmployee = $totalQuotaEmployee = 0;

        // -- Renewal totals — bulk-fetched, not per-employee ------------------
        // The previous build mapped over every non-Maldivian active employee
        // and fired fresh queries against EmployeeInsurance() and WorkPermit()
        // inside the closure (N+1 — eager-loaded relations were ignored
        // because the (...)->where()->first() chain creates a new query).
        // On a 270-person resort that's ~540 extra round trips on one page
        // load. Replace with five GROUP BY SUMs.
        //
        // Date-field choice (correctness fix): the headline + monthly trend
        // previously used end_date (insurance_end_date, end_date for medical
        // / visa) — "renewal expiring in N" mostly identifies last year's
        // payment, not this year's commitment. Switched to start_date /
        // Due_Date so a 2026 renewal really is a 2026 line item.
        //
        // Status filter: dropped for Insurance / Medical / Visa (the
        // previous code didn't filter on Status anyway — it just summed
        // everything with end_date in year). Kept Status='Paid' on Quota /
        // Work Permit to match the existing behaviour. Pending renewals
        // belong in the "still-to-pay" portion of Current Liability, but
        // promoting them is out of scope for this fix.
        //
        // Also: the per-employee Quota filter was comparing
        // `Carbon::parse($item->Expiry_Date)->year == $currentYear`, but
        // QuotaSlotRenewal has no Expiry_Date field — that's NULL on every
        // row, Carbon::parse(null) returns now(), so the filter was a
        // no-op. Replaced with the actual Due_Date column.
        $visaAgg = DB::table('visa_renewals')
            ->where('resort_id', $resortId)
            ->whereYear('start_date', $currentYear)
            ->selectRaw('COUNT(DISTINCT employee_id) as emps, COALESCE(SUM(Amt), 0) as total')
            ->first();
        $totalVisa = (float) ($visaAgg->total ?? 0);
        $TotalVisaEmployee = (int) ($visaAgg->emps ?? 0);

        $insAgg = DB::table('employee_insurances')
            ->where('resort_id', $resortId)
            ->whereYear('insurance_start_date', $currentYear)
            ->selectRaw('COUNT(DISTINCT employee_id) as emps, COALESCE(SUM(CAST(Premium AS DECIMAL(15,2))), 0) as total')
            ->first();
        $totalInsurance = (float) ($insAgg->total ?? 0);
        $totalInsuranceEmployee = (int) ($insAgg->emps ?? 0);

        $wpAgg = DB::table('work_permits')
            ->where('resort_id', $resortId)
            ->where('Status', 'Paid')
            ->whereYear('Due_Date', $currentYear)
            ->selectRaw('COUNT(DISTINCT employee_id) as emps, COALESCE(SUM(CAST(Amt AS DECIMAL(15,2))), 0) as total')
            ->first();
        $totalPermit = (float) ($wpAgg->total ?? 0);
        $totalPermitEmployee = (int) ($wpAgg->emps ?? 0);

        $medAgg = DB::table('work_permit_medical_renewals')
            ->where('resort_id', $resortId)
            ->whereYear('start_date', $currentYear)
            ->selectRaw('COUNT(DISTINCT employee_id) as emps, COALESCE(SUM(Amt), 0) as total')
            ->first();
        $totalMedical = (float) ($medAgg->total ?? 0);
        $totalMedicalEmployee = (int) ($medAgg->emps ?? 0);

        $qAgg = DB::table('quota_slot_renewals')
            ->where('resort_id', $resortId)
            ->where('Status', 'Paid')
            ->whereYear('Due_Date', $currentYear)
            ->selectRaw('COUNT(DISTINCT employee_id) as emps, COALESCE(SUM(CAST(Amt AS DECIMAL(15,2))), 0) as total')
            ->first();
        $totalQuota = (float) ($qAgg->total ?? 0);
        $totalQuotaEmployee = (int) ($qAgg->emps ?? 0);

        // Used downstream by the view; non-Maldivian active employees with
        // at least one renewal row this year. One query, no N+1.
        $employeeIdsWithRenewals = DB::table('visa_renewals')->where('resort_id', $resortId)->whereYear('start_date', $currentYear)->pluck('employee_id')
            ->merge(DB::table('employee_insurances')->where('resort_id', $resortId)->whereYear('insurance_start_date', $currentYear)->pluck('employee_id'))
            ->merge(DB::table('work_permits')->where('resort_id', $resortId)->where('Status', 'Paid')->whereYear('Due_Date', $currentYear)->pluck('employee_id'))
            ->merge(DB::table('work_permit_medical_renewals')->where('resort_id', $resortId)->whereYear('start_date', $currentYear)->pluck('employee_id'))
            ->merge(DB::table('quota_slot_renewals')->where('resort_id', $resortId)->where('Status', 'Paid')->whereYear('Due_Date', $currentYear)->pluck('employee_id'))
            ->unique()
            ->values();
        $employees = Employee::with(['resortAdmin', 'position', 'department'])
            ->where('nationality', '!=', 'Maldivian')
            ->where('status', 'Active')
            ->where('resort_id', $resortId)
            ->whereIn('id', $employeeIdsWithRenewals)
            ->get();

        // Source: SUM(earned_salary + earnings_overtime + earnings_allowance
        // + service_charge) across approved + locked payroll_reviews.
        //
        // Earlier this used $payrolls->sum('total_payroll') (only written
        // by saveSummaryToPayroll → only LOCKED payrolls); then briefly
        // SUM(total_earnings), which silently included an unaccounted
        // residual on rows where total_earnings was hand-adjusted at
        // payroll time without a matching component row (seen on payroll
        // #19 reviews 61 / 66 — $3,197 of phantom earnings not surfaced in
        // any breakdown column). The Cost Distribution chart sums per
        // component, so total_earnings made the headline > chart total.
        //
        // Summing the four visible component columns directly guarantees
        // the headline always equals the chart's grand total. If a payroll
        // adjustment ever needs to appear in the headline, it must be
        // written into one of the component columns (or a new categorised
        // column added) so it can also surface as a chart slice — no more
        // silent "Other".
        //
        // Draft payrolls excluded — drafts aren't committed spend. All
        // values are USD (canonical storage); formatCurrency converts at
        // render time.
        $payrollLiability = (float) DB::table('payroll_reviews')
            ->join('payroll', 'payroll_reviews.payroll_id', '=', 'payroll.id')
            ->where('payroll.resort_id', $resortId)
            ->whereYear('payroll.start_date', $currentYear)
            ->whereIn('payroll.status', ['approved', 'locked'])
            ->sum(DB::raw(
                'COALESCE(payroll_reviews.earned_salary, 0) + '
              . 'COALESCE(payroll_reviews.earnings_overtime, 0) + '
              . 'COALESCE(payroll_reviews.earnings_allowance, 0)'
              // Service Charge intentionally excluded — it's a pass-
              // through (guest-paid → employee distribution), not a
              // resort cost. Including it inflated the reduction by the
              // SC pool size without representing money the resort
              // actually spent off its own books.
            ));

        // ─── Employee Food Cost (YTD) ────────────────────────────────
        // Estimated food cost the resort has burned through this year,
        // computed from the Daily-frequency Food Cost templates in
        // resort_budget_costs (no discrete spend events are logged).
        //
        // Per-employee day count:
        //   effective_start = max(joining_date, year_start)
        //   effective_end   = today  (active employees only — resigned
        //                              ones aren't in this query)
        //   eligible_days   = effective_end − effective_start + 1
        //   contribution    = daily_rate_usd × eligible_days
        //
        // Employees who joined this year contribute only the days from
        // their joining date forward — previously every employee got
        // full YTD days regardless of when they actually started, which
        // overstated food cost for mid-year joiners.
        //
        // Eligibility filters (Locals/Xpat/Muslim + benefit-grade) are
        // inlined from Common::computeBudgetCostMonthlyValue so we can
        // do day-level math without going through its monthly path.
        $foodCostYtd = 0.0;
        $foodCostPerEmployee = []; // populated for the modal sub-rows
        $today              = Carbon::now();
        $currentMonth       = (int) $today->format('n');
        $daysInCurrentMonth = (int) $today->format('t');
        $dayOfMonth         = (int) $today->format('j');
        $yearStart          = Carbon::create($currentYear, 1, 1)->startOfDay();

        // Match any active template whose particulars or cost_title
        // mentions "Food" — case-insensitive on the collations we use.
        // Multiple matches sum (e.g. separate Local + Xpat food rates).
        $foodCostTemplates = DB::table('resort_budget_costs')
            ->where('resort_id', $resortId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('particulars', 'LIKE', '%Food%')
                  ->orWhere('cost_title', 'LIKE', '%Food%');
            })
            ->get();

        $activeEmpsForFood = Employee::with('resortAdmin:id,first_name,last_name')
            ->where('resort_id', $resortId)
            ->where('status', 'Active')
            ->get(['id', 'Admin_Parent_id', 'Emp_id', 'basic_salary', 'basic_salary_currency', 'nationality', 'religion', 'benefit_grid_level', 'joining_date']);

        if ($foodCostTemplates->isNotEmpty() && $activeEmpsForFood->isNotEmpty()) {
            foreach ($foodCostTemplates as $cost) {
                // MVR templates convert to USD at the canonical rate.
                $costIsMvr  = strtoupper(trim((string) ($cost->amount_unit ?? 'USD'))) === 'MVR';
                $mvrToUsd   = ($costIsMvr && $mvrToUsdRate > 0) ? $mvrToUsdRate : 1.0;
                $rateUsd    = ((float) ($cost->amount ?? 0)) * $mvrToUsd;
                $details    = trim((string) ($cost->details ?? 'Both'));
                $gridScope  = trim((string) ($cost->benefit_grid_levels ?? ''));
                $allowedGrades = $gridScope !== ''
                    ? array_filter(array_map(fn($v) => (int) trim($v), explode(',', $gridScope)))
                    : [];

                // Skip non-daily templates so a Monthly "Food Allowance"
                // doesn't get multiplied as if it were a daily rate.
                if (stripos((string) ($cost->frequency ?? ''), 'dai') === false) {
                    continue;
                }

                foreach ($activeEmpsForFood as $emp) {
                    $isLocal   = strtolower((string) $emp->nationality) === 'maldivian';
                    $isMuslim  = strtolower((string) $emp->religion)    === 'muslim';
                    $gridLevel = (int) ($emp->benefit_grid_level ?? 0);

                    // Eligibility filters — inlined so a 0-day skip
                    // doesn't get logged as if the employee contributed.
                    if ($details === 'Locals Only' && !$isLocal)  continue;
                    if ($details === 'Xpat Only'   &&  $isLocal)  continue;
                    if ($details === 'Muslim Only' && !$isMuslim) continue;
                    if (!empty($allowedGrades) && !in_array($gridLevel, $allowedGrades, true)) {
                        continue;
                    }

                    // Effective YTD window for this employee. Caps the
                    // start at their joining date so an April joiner
                    // doesn't get charged for January-March food.
                    $jd = $emp->joining_date
                        ? Carbon::parse($emp->joining_date)->startOfDay()
                        : $yearStart;
                    $effectiveStart = $jd->greaterThan($yearStart) ? $jd : $yearStart;
                    if ($effectiveStart->greaterThan($today)) continue; // joined after today

                    $eligibleDays = $effectiveStart->diffInDays($today) + 1;
                    $contribution = $rateUsd * $eligibleDays;
                    $foodCostYtd += $contribution;

                    $key = $emp->id;
                    if (!isset($foodCostPerEmployee[$key])) {
                        $foodCostPerEmployee[$key] = [
                            'emp_id'       => $emp->Emp_id,
                            'name'         => optional($emp->resortAdmin)->first_name . ' ' . optional($emp->resortAdmin)->last_name,
                            'joining_date' => $jd->format('d M Y'),
                            'days'         => $eligibleDays,
                            'amount'       => 0.0,
                        ];
                    }
                    $foodCostPerEmployee[$key]['amount'] += $contribution;
                }
            }
        }
        $foodCostYtd = round($foodCostYtd, 2);
        // Sort biggest contributors first so the modal shows them up top.
        usort($foodCostPerEmployee, fn($a, $b) => $b['amount'] <=> $a['amount']);

        // Visa Renewals deliberately EXCLUDED from Liability Reduction —
        // the resort doesn't treat visa renewal events as a workforce
        // cost (fees are bundled into Work Permit at this resort and/or
        // recovered from the employee). The `visa_renewals` rows are
        // still read for the People Detail expiry tab and the dashboard
        // Visa cards, but they don't flow into the Reduction headline
        // any more. Removing the row from $currentLegs below mirrors
        // this so the modal and the headline stay consistent.
        $current_liability = $payrollLiability
                        + $foodCostYtd
                        + $totalInsurance
                        + $totalPermit
                        + $totalMedical
                        + $totalQuota;

        $liability_reduction = $estimated_liability - $current_liability;
         // === Earnings ===
        // Same status filter as $payrollLiability above. Without this, draft
        // payrolls' payroll_reviews rows leak into the chart's Salaries /
        // OTA / Service Charge slices, making the Cost Distribution total
        // disagree with the Current Liability headline.
        $payrollReviews = DB::table('payroll_reviews')
            ->join('payroll', 'payroll_reviews.payroll_id', '=', 'payroll.id')
            ->where('payroll.resort_id', $resortId)
            ->whereYear('payroll.start_date', $currentYear)
            ->whereIn('payroll.status', ['approved', 'locked'])
            ->selectRaw('
                SUM(earned_salary) as salaries,
                SUM(earnings_overtime) as ota,
                SUM(earnings_allowance) as allowance,
                SUM(service_charge) as service_charge
            ')
            ->first();
        // === Allowance Breakdown (per type) ===
        // SUM(amount) was ignoring `amount_unit` (ENUM 'MVR','USD'). Rows
        // with amount_unit='MVR' were being added straight to USD rows
        // — the Maldivian Rufiyaa values are ~15.42× the USD equivalent, so
        // any MVR allowance entry inflated the slice by that factor. Convert
        // MVR rows to USD inside the SUM so the chart's allowance segments
        // line up with everything else. $mvrToUsdRate is defined once at the
        // top of this method now. Status filter mirrors the headline source
        // so draft-payroll allowances stay out of the chart.
        $allowanceBreakdown = DB::table('payroll_review_allowances as pra')
            ->join('payroll_reviews as pr', 'pra.payroll_review_id', '=', 'pr.id')
            ->join('payroll as p', 'pr.payroll_id', '=', 'p.id')
            ->where('p.resort_id', $resortId)
            ->whereYear('p.start_date', $currentYear)
            ->whereIn('p.status', ['approved', 'locked'])
            ->select('pra.allowance_type', DB::raw(
                "SUM(CASE WHEN pra.amount_unit = 'MVR' THEN pra.amount * {$mvrToUsdRate}"
              . " ELSE pra.amount END) as total_amount"
            ))
            ->groupBy('pra.allowance_type')
            ->pluck('total_amount', 'pra.allowance_type')
            ->toArray();

        // All chart slices are USD-source — payroll_reviews aggregates,
        // renewal totals, and allowance breakdowns all keep USD as the
        // storage unit (see comment on $payrollLiability above). The doughnut
        // tooltip / line-chart y-axis values get a single display-currency
        // pass at the end of this method so MVR mode actually shows MVR
        // magnitudes; proportions stay correct in both modes because every
        // slice is in the same source currency before that conversion.
        //
        // No 'Recruitment Fee' slice — actual recruitment spend isn't
        // tracked in any table; the Est-vs-Actual row shows the budgeted
        // figure (Estimated column) with $0 Actual by design.
        // === Cost Distribution chart: ANNUAL estimated breakdown ===
        //
        // Previously this chart showed YTD payroll + renewal totals only,
        // which meant cost-template items budgeted but never tracked as
        // discrete spend events (Food Cost, Pension, Tickets, Ramadan
        // Bonus, …) never appeared. HR couldn't see "where the money
        // is budgeted to go" — only "what's been paid so far". Switched
        // to annual estimated distribution so every line item in
        // resort_budget_costs gets a slice, sized by its annual
        // contribution to Total Estimated Liability.
        //
        // Slice sources:
        //   • Salaries       = SUM of active employees' basic_salary × 12
        //   • Per-Template   = SUM of annualCostForEmployee(t, e) over all
        //                       active employees (one slice per template:
        //                       Food Cost, Pension, OT Normal, …)
        //   • Per-Allowance  = SUM of employees_allowance × 12 by type
        //                       (matches the per-employee allowance leg)
        //   • Vacant Slots   = SUM of annualBudgetForVacantSlot
        //
        // Service Charge intentionally absent — pass-through cost.
        $chartData = [];

        // --- Salaries leg (active employees, basic × 12) ---
        $salariesAnnual = 0.0;
        foreach ($activeForBreakdown as $emp) {
            $shared = (float) (($emp->proposed_salary ?? 0) > 0
                ? $emp->proposed_salary
                : ($emp->basic_salary ?? 0));
            $salariesAnnual += $shared * 12;
        }
        if ($salariesAnnual > 0) {
            $chartData['Salaries'] = round($salariesAnnual, 2);
        }

        // --- Per-template legs (REUSED from breakdown section above) ---
        // $perTemplateAnnual was already populated in the breakdown loop
        // (single employee × template iteration for the whole page).
        // Sort, threshold, roll small slices into "Other" for the doughnut.
        $perTemplate = $perTemplateAnnual; // alias for readability
        arsort($perTemplate);
        $threshold = max(($estLegCostTemplate ?? array_sum($perTemplate)) * 0.005, 1.0);
        $other = 0.0;
        foreach ($perTemplate as $label => $val) {
            if ($val < $threshold) {
                $other += $val;
            } else {
                $chartData[$label] = round($val, 2);
            }
        }
        if ($other > 0) {
            $chartData['Other Cost Templates'] = round($other, 2);
        }

        // --- Per-employee allowance leg (employees_allowance × 12) ---
        // employees_allowance.allowance_id → resort_budget_costs.id, so the
        // label comes from joining to resort_budget_costs.particulars.
        // (There is no `allowance_type` column on employees_allowance — an
        // earlier draft of this code assumed there was one and crashed
        // the page on live with a SQL error.)
        if (!empty($empIdsForBreakdown ?? [])) {
            $allowanceByType = DB::table('employees_allowance as ea')
                ->leftJoin('resort_budget_costs as rbc', 'rbc.id', '=', 'ea.allowance_id')
                ->whereIn('ea.employee_id', $empIdsForBreakdown)
                ->selectRaw(
                    "COALESCE(rbc.particulars, 'Allowance') as type_label, "
                  . "COALESCE(SUM(CASE WHEN ea.amount_unit = 'MVR' "
                  . "THEN ea.amount * (1.0 / {$dollarToMvr}) ELSE ea.amount END), 0) as monthly_total"
                )
                ->groupBy('rbc.particulars')
                ->get();
            foreach ($allowanceByType as $row) {
                $annualTotal = (float) $row->monthly_total * 12;
                if ($annualTotal > 0) {
                    $chartData['Allowance - ' . ucfirst($row->type_label)] = round($annualTotal, 2);
                }
            }
        }

        // --- Vacant slots leg ---
        if (($estLegVacant ?? 0) > 0) {
            $chartData['Vacant Slots'] = round($estLegVacant, 2);
        }

        // === YTD actuals (for the Estimation vs Actual table only) ===
        // The Est-vs-Actual table needs YTD spend per category, not the
        // annual estimated values now in $chartData. Build that here.
        $ytdActuals = [
            'Salaries'         => (float) ($payrollReviews->salaries ?? 0),
            'OTA'              => (float) ($payrollReviews->ota ?? 0),
            'Work Permit'      => (float) $totalPermit,
            'Visa'             => (float) $totalVisa,
            'Quota Slot'       => (float) $totalQuota,
            'Medical Permit'   => (float) $totalMedical,
            'Insurance'        => (float) $totalInsurance,
            'Recruitment Fee'  => 0.0, // no actual tracking; estimated only
        ];
        foreach ($allowanceBreakdown as $type => $amount) {
            $ytdActuals['Allowance - ' . ucfirst($type)] = (float) $amount;
        }

        // Monthly buckets — same source as $payrollLiability above (sum of
        // the four component columns across approved + locked payrolls) so
        // the headline, the trend, and the chart all agree on what counts
        // as "spent".
        $monthlyLiability = DB::table('payroll_reviews')
            ->join('payroll', 'payroll_reviews.payroll_id', '=', 'payroll.id')
            ->where('payroll.resort_id', $resortId)
            ->whereYear('payroll.start_date', $currentYear)
            ->whereIn('payroll.status', ['approved', 'locked'])
            ->selectRaw(
                'MONTH(payroll.start_date) as month, '
              . 'SUM('
              . '  COALESCE(payroll_reviews.earned_salary, 0) + '
              . '  COALESCE(payroll_reviews.earnings_overtime, 0) + '
              . '  COALESCE(payroll_reviews.earnings_allowance, 0)'
              // Service Charge excluded — same reasoning as the
              // $payrollLiability sum above. Keeps the monthly trend
              // chart in sync with the headline.
              . ') as total'
            )
            ->groupBy(DB::raw('MONTH(payroll.start_date)'))
            ->pluck('total', 'month')
            ->toArray();

        $monthlyWorkPermit = DB::table('work_permits')
            ->where('resort_id', $resortId)
            ->where('Status', 'Paid')
            ->whereYear('Due_Date', $currentYear)
            ->selectRaw('MONTH(Due_Date) as month, SUM(CAST(Amt AS DECIMAL(15,2))) as total')
            ->groupBy(DB::raw('MONTH(Due_Date)'))
            ->pluck('total', 'month')->toArray();

        $monthlyMedical = DB::table('work_permit_medical_renewals')
            ->where('resort_id', $resortId)
            ->whereYear('start_date', $currentYear)
            ->selectRaw('MONTH(start_date) as month, SUM(Amt) as total')
            ->groupBy(DB::raw('MONTH(start_date)'))
            ->pluck('total', 'month')->toArray();

        $monthlyInsurance = DB::table('employee_insurances')
            ->where('resort_id', $resortId)
            ->whereYear('insurance_start_date', $currentYear)
            ->selectRaw('MONTH(insurance_start_date) as month, SUM(CAST(Premium AS DECIMAL(15,2))) as total')
            ->groupBy(DB::raw('MONTH(insurance_start_date)'))
            ->pluck('total', 'month')->toArray();

        $monthlyQuota = DB::table('quota_slot_renewals')
            ->where('resort_id', $resortId)
            ->where('Status', 'Paid')
            ->whereYear('Due_Date', $currentYear)
            ->selectRaw('MONTH(Due_Date) as month, SUM(CAST(Amt AS DECIMAL(15,2))) as total')
            ->groupBy(DB::raw('MONTH(Due_Date)'))
            ->pluck('total', 'month')->toArray();

        // Visa Renewals deliberately NOT pulled into a monthly bucket —
        // we removed it from the Liability Reduction headline earlier.
        // Keeping it in the trend would diverge the line chart from the
        // headline numbers, which is what HR complained about.

        // ─── Monthly Food Cost buckets ───────────────────────────────
        // Same eligibility window as the YTD aggregator above, but
        // grouped by month so the trend chart can subtract real food
        // spend month-by-month (instead of the headline lump sum).
        $monthlyFood = array_fill(1, 12, 0.0);
        if (isset($foodCostTemplates) && $foodCostTemplates->isNotEmpty()
            && isset($activeEmpsForFood) && $activeEmpsForFood->isNotEmpty()) {
            foreach ($foodCostTemplates as $cost) {
                if (stripos((string) ($cost->frequency ?? ''), 'dai') === false) continue;
                $costIsMvr = strtoupper(trim((string) ($cost->amount_unit ?? 'USD'))) === 'MVR';
                $mvrToUsdLocal = ($costIsMvr && $mvrToUsdRate > 0) ? $mvrToUsdRate : 1.0;
                $rateUsd = ((float) ($cost->amount ?? 0)) * $mvrToUsdLocal;
                $details = trim((string) ($cost->details ?? 'Both'));
                $gridScope = trim((string) ($cost->benefit_grid_levels ?? ''));
                $allowedGrades = $gridScope !== ''
                    ? array_filter(array_map(fn($v) => (int) trim($v), explode(',', $gridScope)))
                    : [];

                foreach ($activeEmpsForFood as $emp) {
                    $isLocal  = strtolower((string) $emp->nationality) === 'maldivian';
                    $isMuslim = strtolower((string) $emp->religion)    === 'muslim';
                    $gradeLvl = (int) ($emp->benefit_grid_level ?? 0);
                    if ($details === 'Locals Only' && !$isLocal)  continue;
                    if ($details === 'Xpat Only'   &&  $isLocal)  continue;
                    if ($details === 'Muslim Only' && !$isMuslim) continue;
                    if (!empty($allowedGrades) && !in_array($gradeLvl, $allowedGrades, true)) continue;

                    $jd = $emp->joining_date
                        ? Carbon::parse($emp->joining_date)->startOfDay()
                        : $yearStart;

                    for ($m = 1; $m <= 12; $m++) {
                        $monthStart = Carbon::create($currentYear, $m, 1)->startOfDay();
                        $monthEnd   = (clone $monthStart)->endOfMonth();
                        // Overlap [jd, monthEnd] ∩ [monthStart, monthEnd]
                        $winStart = $jd->greaterThan($monthStart) ? $jd : $monthStart;
                        if ($winStart->greaterThan($monthEnd)) continue;
                        $days = $winStart->diffInDays($monthEnd) + 1;
                        $monthlyFood[$m] += $rateUsd * $days;
                    }
                }
            }
        }

        // Step 3: Build the monthly remaining-liability series.
        //
        // Past + current months use ACTUAL spend; future months are
        // projected to burn the residual REMAINING balance evenly
        // through Dec so the line:
        //   • matches the Remaining Liability headline exactly at the
        //     current month's data point (audit consistency)
        //   • drifts to zero by Dec (visually answers "if we hit the
        //     budget exactly, what's our trajectory")
        //   • never goes flat after the current month (the original
        //     "wrong data" complaint)
        // Going flat was the bug. Flat-rate-1/12 (the alternative)
        // would have ended with a residual mismatch at Dec, which read
        // as a different bug to HR.
        $today        = Carbon::now();
        $currentMonth = (int) $today->format('n');

        // Sum every component's per-month actuals for the YTD slice.
        $actualByMonth = array_fill(1, 12, 0.0);
        for ($m = 1; $m <= $currentMonth; $m++) {
            $actualByMonth[$m] =
                ($monthlyLiability[$m]  ?? 0) +
                ($monthlyWorkPermit[$m] ?? 0) +
                ($monthlyMedical[$m]    ?? 0) +
                ($monthlyInsurance[$m]  ?? 0) +
                ($monthlyQuota[$m]      ?? 0) +
                ($monthlyFood[$m]       ?? 0);
        }
        $actualSpentYtd = array_sum($actualByMonth);

        // Remaining-to-burn across the months after the current one.
        // Negative residual (over-budget) is clamped to 0 so the line
        // doesn't dip below zero on the chart.
        $remainingAfterYtd = max(0, $estimated_liability - $actualSpentYtd);
        $monthsRemaining   = max(0, 12 - $currentMonth);
        $projectedPerMonth = $monthsRemaining > 0 ? $remainingAfterYtd / $monthsRemaining : 0;

        $liabilityRemaining = $estimated_liability;
        $labels = [];
        $reductionData = [];
        for ($m = 1; $m <= 12; $m++) {
            // Month abbreviation only — the chart is always the current
            // year so the year suffix was redundant noise on the x-axis.
            $monthName = Carbon::create($currentYear, $m)->format('M');

            $monthlyPaid = $m <= $currentMonth
                ? $actualByMonth[$m]
                : $projectedPerMonth;

            $liabilityRemaining -= $monthlyPaid;
            $liabilityRemaining = max($liabilityRemaining, 0);

            $labels[] = $monthName;
            $reductionData[] = round($liabilityRemaining, 2);
        }

        // Allowance column set — DERIVED from the same resort_budget_costs
        // catalog that getLiabilityData() uses to build per-employee column
        // values. Anything classified as OT / Insurance / Recruitment falls
        // into its own dedicated column; everything else is treated as a
        // named allowance. Both the table <th> headers and the JS column
        // generator (`allowanceColumns` in index.blade.php) read $allowanceTypes,
        // so the labels MUST match the data keys produced server-side or
        // DataTables raises "Requested unknown parameter".
        $rawCosts = DB::table('resort_budget_costs')
            ->where('resort_id', $resortId)
            ->where('status', 'active')
            ->get(['particulars', 'cost_title']);
        // Exclusion rules — must stay in sync with getLiabilityData()'s
        // $classify (the table's dedicated columns are OT, Service Charge,
        // Insurance, Work Permit, Visa, Medical, Quota, Recruitment Fee).
        // Anything not covered by those becomes a dynamic allowance column.
        $isNamedColumnCost = function ($n) {
            $s = strtolower((string) $n);
            if (stripos($s, 'service charge') !== false)                                   return true;
            if (stripos($s, 'overtime') !== false || preg_match('/\bot\b/i', $s))          return true;
            if (stripos($s, 'work permit') !== false)                                      return true;
            if (stripos($s, 'work visa') !== false || stripos($s, 'visa') !== false)       return true;
            if (stripos($s, 'medical') !== false)                                          return true;
            if (stripos($s, 'insurance') !== false)                                        return true;
            if (stripos($s, 'quota') !== false)                                            return true;
            if (stripos($s, 'recruitment') !== false)                                      return true;
            return false;
        };
        $allowanceTypes = collect($rawCosts)
            ->map(fn($c) => $c->particulars ?: ($c->cost_title ?: 'Other'))
            ->reject(fn($n) => $isNamedColumnCost($n))
            ->unique()
            ->values();

        // Pre-compute the Estimation vs Actual table rows on the server.
        //
        // Estimated column was using $budgetMatch which only summed the raw
        // `amount` column from resort_budget_costs (one-time template
        // figure, no annualisation, no per-employee multiplication). That
        // made Medical show $3,200, Insurance $3,200 etc. — single-template
        // base amounts, not real annual budgets. Replaced with the SAME
        // per-employee × per-cost × 12-month aggregation that powers the
        // consolidated/view-budget pages so the Estimated column reflects
        // actual annual budget commitments per category.
        $estimatedByCategory = [
            'Salaries'        => 0.0,
            'Overtime'        => 0.0,
            'Service Charge'  => 0.0,
            'Work Permit'     => 0.0,
            'Medical'         => 0.0,
            'Insurance'       => 0.0,
            'Quota'           => 0.0,
            'Visa'            => 0.0,
            'Recruitment Fee' => 0.0,
        ];
        // Allowance buckets — keyed by the same display label used in
        // $allowanceBreakdown / $chartData / $allowanceTypes.
        $allowanceEstimated = [];

        $classifyForEstVsActual = function (string $name): string {
            $n = strtolower($name);
            if (stripos($n, 'service charge') !== false)                return 'Service Charge';
            if (stripos($n, 'overtime') !== false || preg_match('/\bot\b/i', $n)) return 'Overtime';
            if (stripos($n, 'work permit') !== false)                   return 'Work Permit';
            if (stripos($n, 'work visa') !== false || stripos($n, 'visa') !== false) return 'Visa';
            if (stripos($n, 'medical') !== false)                       return 'Medical';
            if (stripos($n, 'insurance') !== false)                     return 'Insurance';
            if (stripos($n, 'quota') !== false)                         return 'Quota';
            if (stripos($n, 'recruitment') !== false)                   return 'Recruitment Fee';
            if (stripos($n, 'salary') !== false || stripos($n, 'payroll') !== false) return 'Salaries';
            return 'Allowance'; // becomes "Allowance - <type>" below
        };

        // Salary leg first — use the same chain Common::computeYearlyBudgetTotal
        // walks (monthly override → proposed/current → fallback to
        // employees row). Sums to the Salaries row.
        $activeEmployees = DB::table('employees')
            ->where('resort_id', $resortId)
            ->where('status', 'Active')
            ->get(['id', 'basic_salary', 'proposed_salary', 'nationality', 'religion', 'benefit_grid_level']);
        $empMonthlyOverrides = DB::table('resort_employee_monthly_salaries')
            ->where('resort_id', $resortId)
            ->where('year', $currentYear)
            ->get(['employee_id', 'month', 'current_salary', 'proposed_salary'])
            ->groupBy('employee_id');
        foreach ($activeEmployees as $emp) {
            $sharedFallback = (float) ($emp->proposed_salary > 0
                ? $emp->proposed_salary
                : ($emp->basic_salary ?? 0));
            $monthsByMonth = $empMonthlyOverrides->get($emp->id, collect())->keyBy('month');
            for ($m = 1; $m <= 12; $m++) {
                $row = $monthsByMonth->get($m);
                $effective = $row
                    ? (float) ($row->proposed_salary > 0
                        ? $row->proposed_salary
                        : ($row->current_salary > 0
                            ? $row->current_salary
                            : $sharedFallback))
                    : $sharedFallback;
                $estimatedByCategory['Salaries'] += $effective;
            }
        }

        // Cost leg — for each active employee × each cost template, sum
        // annualCostForEmployee into the right bucket. Fetch cost
        // templates locally so this block doesn't depend on a variable
        // defined later in the controller.
        $resortCostsForEst = DB::table('resort_budget_costs')
            ->where('resort_id', $resortId)
            ->where('status', 'active')
            ->get(['id', 'particulars', 'cost_title', 'amount', 'amount_unit', 'cost_type', 'frequency', 'details']);
        foreach ($activeEmployees as $emp) {
            foreach ($resortCostsForEst as $cost) {
                $annual = Common::annualCostForEmployee($resortId, $currentYear, $cost, $emp);
                if ($annual <= 0) continue;
                $label = $cost->particulars ?: ($cost->cost_title ?: 'Other');
                $cat   = $classifyForEstVsActual($label);
                if ($cat === 'Allowance') {
                    $allowanceEstimated[$label] = ($allowanceEstimated[$label] ?? 0) + $annual;
                } else {
                    $estimatedByCategory[$cat] += $annual;
                }
            }
        }

        // Service Charge row removed — SC is a pass-through (guest-paid
        // → employee distribution), neither part of the budgeted
        // commitment nor a real resort cost, so showing it in this
        // table conflated two different ledgers.
        // Est-vs-Actual table — Estimated column reads from the annual
        // budget categorisation ($estimatedByCategory); Actual column
        // reads from $ytdActuals (YTD spend). $chartData is now the
        // annual doughnut data and is intentionally not used here.
        $estVsActualRows = [
            ['label' => 'Salaries',        'estimated' => $estimatedByCategory['Salaries'],        'actual' => $ytdActuals['Salaries']        ?? 0],
            ['label' => 'Overtime',        'estimated' => $estimatedByCategory['Overtime'],        'actual' => $ytdActuals['OTA']             ?? 0],
            ['label' => 'Work Permit',     'estimated' => $estimatedByCategory['Work Permit'],     'actual' => $ytdActuals['Work Permit']     ?? 0],
            ['label' => 'Medical',         'estimated' => $estimatedByCategory['Medical'],         'actual' => $ytdActuals['Medical Permit']  ?? 0],
            ['label' => 'Insurance',       'estimated' => $estimatedByCategory['Insurance'],       'actual' => $ytdActuals['Insurance']       ?? 0],
            ['label' => 'Quota',           'estimated' => $estimatedByCategory['Quota'],           'actual' => $ytdActuals['Quota Slot']      ?? 0],
            ['label' => 'Visa',            'estimated' => $estimatedByCategory['Visa'],            'actual' => $ytdActuals['Visa']            ?? 0],
            // Recruitment Fee has no actual-spend source (no recruitment
            // ledger in this codebase), so actual stays 0 by design — the
            // estimated column still reflects the budget commitment.
            ['label' => 'Recruitment Fee', 'estimated' => $estimatedByCategory['Recruitment Fee'], 'actual' => $ytdActuals['Recruitment Fee'] ?? 0],
        ];
        // Case-insensitive lookup: allowanceEstimated is keyed by the cost
        // template's `particulars` field ("Language Allowance"), while
        // $type comes from payroll_review_allowances.allowance_type which
        // can be stored lower-cased ("language allowance"). The previous
        // direct lookup ($allowanceEstimated[$type]) silently fell to 0
        // whenever the cases didn't match — so a perfectly-budgeted
        // allowance read as "$0 estimated" against the actual spend.
        $allowanceEstimatedCI = [];
        foreach ($allowanceEstimated as $k => $v) {
            $allowanceEstimatedCI[strtolower($k)] = $v;
        }
        foreach ($allowanceBreakdown as $type => $amount) {
            $estVsActualRows[] = [
                'label'     => 'Allowance - ' . ucfirst($type),
                'estimated' => $allowanceEstimatedCI[strtolower($type)] ?? 0,
                'actual'    => $amount,
            ];
        }

        // Chart.js gets a display-currency version of every numeric series
        // (doughnut slice values + line-chart reductionData) so MVR-mode
        // tooltips and y-axis labels reflect the user's currency choice.
        // $chartData / $reductionData themselves stay USD because
        // estVsActualRows reads $chartData and the blade routes those
        // through Common::formatCurrency (which does its own conversion).
        $chartDataDisplay = [];
        foreach ($chartData as $k => $v) {
            $chartDataDisplay[$k] = round(((float) $v) * $displayRate, 2);
        }
        $reductionDataDisplay = array_map(
            fn($v) => round(((float) $v) * $displayRate, 2),
            $reductionData
        );
        $displayCurrencySymbol = Common::GetResortCurrencySymbol();

        // Liability Reduction per-source breakdown for the detail modal.
        // Mirrors the actual aggregation done at the top of the method;
        // Service Charge is intentionally excluded (pass-through). Visa
        // Renewals row removed — not a resort workforce cost at this
        // resort (handled via Work Permit and/or recovered from
        // employee), keeping it in the modal would mislead HR into
        // expecting it to appear in the reduction headline.
        $currentLegs = [
            ['label' => 'Payroll — Salaries (earned_salary)',     'value' => (float) ($payrollReviews->salaries ?? 0)],
            ['label' => 'Payroll — Overtime (earnings_overtime)', 'value' => (float) ($payrollReviews->ota ?? 0)],
            ['label' => 'Payroll — Allowances (earnings_allowance)', 'value' => (float) ($payrollReviews->allowance ?? 0)],
            // Food cost is template-driven (no discrete spend events
            // logged) — daily rate × eligible employees × YTD days.
            // The breakdown line shows the formula so HR sees how it's
            // derived rather than treating it as opaque.
            [
                'label' => 'Employee Food Cost (daily × eligible days since joining)',
                'value' => (float) $foodCostYtd,
                'breakdown' => [
                    'templates' => $foodCostTemplates->map(function ($c) {
                        return [
                            'particulars' => $c->particulars ?: $c->cost_title,
                            'rate'        => (float) ($c->amount ?? 0),
                            'unit'        => $c->amount_unit ?? 'USD',
                            'frequency'   => $c->frequency ?? '—',
                            'details'     => $c->details ?? 'Both',
                        ];
                    })->all(),
                    'active_employees'    => (int) $activeEmpsForFood->count(),
                    'eligible_employees'  => count($foodCostPerEmployee),
                    'year_start'          => $yearStart->format('d M Y'),
                    'today'               => $today->format('d M Y'),
                    'per_employee'        => $foodCostPerEmployee,
                ],
            ],
            ['label' => 'Insurance (insurance_start_date in year)','value' => (float) $totalInsurance],
            ['label' => 'Work Permit (Paid, Due_Date in year)',   'value' => (float) $totalPermit],
            ['label' => 'Medical (start_date in year)',           'value' => (float) $totalMedical],
            ['label' => 'Quota Slot (Paid, Due_Date in year)',    'value' => (float) $totalQuota],
        ];

        // Per-allowance-type breakdown so the modal can show HR exactly
        // which allowance buckets make up the "Payroll — Allowances"
        // YTD row (e.g. Service Charge top-up, OT meal, Transport,
        // R&R, etc.) instead of a single opaque total. Sorted descending
        // by amount so the biggest spenders show first.
        $allowanceBreakdownForView = collect($allowanceBreakdown)
            ->map(fn($amount, $type) => ['type' => $type, 'amount' => (float) $amount])
            ->sortByDesc('amount')
            ->values()
            ->all();

        return view('resorts.people.liability.index', compact(
            'page_title',
            'resortId', 'current_liability',
            'resort_departments','employees','estimated_liability',
            'liability_reduction','chartData',
            'chartDataDisplay','reductionDataDisplay','displayCurrencySymbol',
            'labels',
            'reductionData','allowanceTypes',
            'estLegs','currentLegs',
            'allowanceBreakdownForView',
            'estVsActualRows'
        ));
    }

    public function addCost()
    {
        $page_title = 'Add Liability Cost';
        $resort_id = $this->resort->resort_id;  
        return view('resorts.people.liability.add-cost', compact(
            'page_title', 
            'resort_id', 
        ));     
    }

    public function getLiabilityData(Request $request)
    {
        $resortId = $this->resort->resort_id;
        $currentYear = now()->year;

        // ── Cost-template column buckets ─────────────────────────────────
        // The Employees Tab now mirrors the Estimation-vs-Actual table and
        // the doughnut chart: each renewal category (Work Permit, Visa,
        // Medical, Quota, Recruitment Fee, Insurance) gets its own column
        // rather than being collapsed into one "Recruitment" aggregate.
        // That fixes the classifier divergence with $classifyForEstVsActual
        // in index() — a "Work Visa" cost template now lands in the same
        // Visa bucket in both places.
        //
        // The classifier here uses the SAME ruleset as index()'s
        // $classifyForEstVsActual so the two cannot drift apart again.
        // OT / Service Charge / Insurance / Work Permit / Visa / Medical /
        // Quota / Recruitment Fee are named buckets; anything else falls
        // into a dynamic "allowance" column keyed by the cost particulars.
        $resortCosts = DB::table('resort_budget_costs')
            ->where('resort_id', $resortId)
            ->where('status', 'active')
            ->get(['id', 'particulars', 'cost_title', 'amount', 'amount_unit', 'cost_type', 'frequency', 'details']);

        $classify = function ($name) {
            $n = strtolower($name ?? '');
            if (strpos($n, 'service charge') !== false)                  return 'service_charge';
            if (strpos($n, 'overtime') !== false || preg_match('/\bot\b/i', $n)) return 'ot';
            if (strpos($n, 'work permit') !== false)                     return 'work_permit';
            if (strpos($n, 'work visa') !== false || strpos($n, 'visa') !== false) return 'visa';
            if (strpos($n, 'medical') !== false)                         return 'medical';
            if (strpos($n, 'insurance') !== false)                       return 'insurance';
            if (strpos($n, 'quota') !== false)                           return 'quota';
            if (strpos($n, 'recruitment') !== false)                     return 'recruitment_fee';
            return 'allowance';
        };

        $otCosts = $insuranceCosts = $workPermitCosts = $visaCosts =
            $medicalCosts = $quotaCosts = $recruitmentCosts = $serviceChargeCosts = [];
        $allowanceCostsByLabel = []; // ['Language Allowance' => [costObj, ...]]

        foreach ($resortCosts as $c) {
            $label = $c->particulars ?: ($c->cost_title ?: 'Other');
            switch ($classify($label)) {
                case 'service_charge':  $serviceChargeCosts[] = $c; break;
                case 'ot':              $otCosts[]            = $c; break;
                case 'work_permit':     $workPermitCosts[]    = $c; break;
                case 'visa':            $visaCosts[]          = $c; break;
                case 'medical':         $medicalCosts[]       = $c; break;
                case 'insurance':       $insuranceCosts[]     = $c; break;
                case 'quota':           $quotaCosts[]         = $c; break;
                case 'recruitment_fee': $recruitmentCosts[]   = $c; break;
                default:
                    $allowanceCostsByLabel[$label][] = $c;
            }
        }
        $allowanceLabels = array_keys($allowanceCostsByLabel);

        $scopedDeptIds = \App\Helpers\Common::getScopedDepartmentIds();
        $searchTerm    = trim((string) $request->input('search_term', ''));
        $departmentId  = $request->input('department_id');

        $query = Employee::with(['resortAdmin', 'department', 'position'])
            ->where('resort_id', $resortId)
            ->where('status', 'active')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->when(!empty($departmentId), fn($q) => $q->where('Dept_id', $departmentId))
            ->when($searchTerm !== '', function ($q) use ($searchTerm) {
                $q->where(function ($w) use ($searchTerm) {
                    $w->where('Emp_id', 'like', "%{$searchTerm}%")
                      ->orWhereHas('resortAdmin', function ($qa) use ($searchTerm) {
                          $qa->where('first_name', 'like', "%{$searchTerm}%")
                             ->orWhere('last_name', 'like', "%{$searchTerm}%")
                             ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$searchTerm}%");
                      });
                });
            })
            ->select('id', 'Admin_Parent_id', 'Emp_id', 'Dept_id', 'Position_id', 'nationality', 'religion', 'basic_salary', 'proposed_salary', 'joining_date', 'benefit_grid_level');

        // Salary leg helper — mirrors the canonical helper's salary leg
        // (per-month override → proposed/current → employees-row fallback).
        // Used only by the Salary column; the Total column delegates to the
        // canonical helper so the per-employee number always equals what
        // /resort/budget/view-budget shows.
        $annualSalaryFor = function ($empRow) use ($resortId, $currentYear) {
            $sharedFallback = (float) ($empRow->proposed_salary > 0
                ? $empRow->proposed_salary
                : ($empRow->basic_salary ?? 0));

            $monthlyOverrides = DB::table('resort_employee_monthly_salaries')
                ->where('employee_id', $empRow->id)
                ->where('resort_id', $resortId)
                ->where('year', $currentYear)
                ->get(['month', 'current_salary', 'proposed_salary'])
                ->keyBy('month');

            $total = 0.0;
            for ($m = 1; $m <= 12; $m++) {
                $row = $monthlyOverrides->get($m);
                $total += $row
                    ? (float) ($row->proposed_salary > 0
                        ? $row->proposed_salary
                        : ($row->current_salary > 0 ? $row->current_salary : $sharedFallback))
                    : $sharedFallback;
            }
            return $total;
        };

        // Per-employee per-bucket cost helper — sums Common::annualCostForEmployee
        // across all costs in the bucket so saved overrides AND live
        // template-fallback values are included (same rule view-budget uses).
        $costBucketTotal = function ($empRow, array $costs) use ($resortId, $currentYear) {
            if (empty($costs)) return 0.0;
            $sum = 0.0;
            foreach ($costs as $cost) {
                $sum += Common::annualCostForEmployee($resortId, $currentYear, $cost, $empRow);
            }
            return $sum;
        };

        // Per-employee allowance leg from employees_allowance × 12. The
        // canonical helper includes this in its annual total; surface it as
        // its own column so the Total reconciles transparently. MVR rows
        // are normalised to USD via the FX rate the rest of the page uses.
        $dollarToMvr = (float) (DB::table('resort_site_settings')
            ->where('resort_id', $resortId)
            ->value('DollertoMVR') ?: 15.42);
        if ($dollarToMvr <= 0) $dollarToMvr = 15.42;
        $employeeAllowanceFor = function ($empRow) use ($dollarToMvr) {
            $monthly = (float) DB::table('employees_allowance')
                ->where('employee_id', $empRow->id)
                ->selectRaw(
                    "COALESCE(SUM(CASE WHEN amount_unit = 'MVR' "
                  . "THEN amount * (1.0 / {$dollarToMvr}) ELSE amount END), 0) as total"
                )
                ->value('total');
            return $monthly * 12;
        };

        $datatable = datatables()->of($query)
            ->addColumn('employee_name', fn($row) => optional($row->resortAdmin)->full_name ?? 'N/A')
            ->addColumn('department',    fn($row) => optional($row->department)->name ?? 'N/A')
            ->addColumn('position',      fn($row) => optional($row->position)->position_title ?? 'N/A')
            ->addColumn('salary',        fn($row) => Common::formatCurrency($annualSalaryFor($row), 'USD'))
            ->addColumn('ot',              fn($row) => Common::formatCurrency($costBucketTotal($row, $otCosts),              'USD'))
            ->addColumn('insurance',       fn($row) => Common::formatCurrency($costBucketTotal($row, $insuranceCosts),       'USD'))
            ->addColumn('work_permit',     fn($row) => Common::formatCurrency($costBucketTotal($row, $workPermitCosts),      'USD'))
            ->addColumn('visa',            fn($row) => Common::formatCurrency($costBucketTotal($row, $visaCosts),             'USD'))
            ->addColumn('medical',         fn($row) => Common::formatCurrency($costBucketTotal($row, $medicalCosts),          'USD'))
            ->addColumn('quota',           fn($row) => Common::formatCurrency($costBucketTotal($row, $quotaCosts),            'USD'))
            ->addColumn('recruitment_fee', fn($row) => Common::formatCurrency($costBucketTotal($row, $recruitmentCosts),      'USD'))
            ->addColumn('service_charge',  fn($row) => Common::formatCurrency($costBucketTotal($row, $serviceChargeCosts),   'USD'))
            ->addColumn('employee_allowance', fn($row) => Common::formatCurrency($employeeAllowanceFor($row), 'USD'));

        // One DataTable column per dynamic allowance label.
        foreach ($allowanceLabels as $label) {
            $columnKey = strtolower(str_replace(' ', '_', $label));
            $costs = $allowanceCostsByLabel[$label];
            $datatable->addColumn($columnKey, function ($row) use ($costs, $costBucketTotal) {
                return Common::formatCurrency($costBucketTotal($row, $costs), 'USD');
            });
        }

        // Total = canonical helper. Single source of truth across
        // /resort/budget/view-budget, /resort/budget/consolidated-budget,
        // the Liability headline, and this table. Per-column figures above
        // are the breakdown; their sum equals this Total because the
        // canonical helper aggregates salary + cost-template legs + the
        // per-employee allowance leg the same way the columns do.
        $datatable->addColumn('total', function ($row) use ($resortId, $currentYear) {
            return Common::formatCurrency(
                Common::annualBudgetForEmployee($resortId, (int) $currentYear, $row),
                'USD'
            );
        });

        $datatable->addColumn('details', fn($row) => '');
        $datatable->rawColumns(['details']);

        return $datatable->make(true);
    }

    public function getLiabilityEmployeeData($empId)
    {
        $employee = Employee::findOrFail($empId);
        $currentYear = now()->year;

        // Fetch all distinct allowance types
        $allowanceTypes = PayrollReviewAllowances::whereHas('payrollReview', function ($q) use ($employee, $currentYear) {
            $q->where('employee_id', $employee->id)
            ->whereYear('created_at', $currentYear);
        })->select('allowance_type')->distinct()->pluck('allowance_type');

        $html = '';

        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($currentYear, $month)->format('F');

            $payrollReview = PayrollReview::where('employee_id', $employee->id)
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $currentYear)
                ->first();

            $allowances = PayrollReviewAllowances::whereHas('payrollReview', function ($q) use ($employee, $month, $currentYear) {
                $q->where('employee_id', $employee->id)
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $currentYear);
            })->get();

            $insurance = $employee->EmployeeInsurance()
                ->whereMonth('insurance_end_date', $month)
                ->whereYear('insurance_end_date', $currentYear)
                ->sum('Premium');

            $visa = $employee->VisaRenewal()
                ->whereMonth('end_date', $month)
                ->whereYear('end_date', $currentYear)
                ->sum('Amt');

            $workPermit = $employee->WorkPermit()
                ->where('Status', 'Paid')
                ->whereMonth('Due_Date', $month)
                ->whereYear('Due_Date', $currentYear)
                ->sum('Amt');

            $medical = $employee->WorkPermitMedicalRenewal()
                ->whereMonth('end_date', $month)
                ->whereYear('end_date', $currentYear)
                ->sum('Amt');

            $quota = $employee->QuotaSlotRenewal()
                ->where('Status', 'Paid')
                ->whereMonth('Payment_Date', $month)
                ->whereYear('Payment_Date', $currentYear)
                ->sum('Amt');

            $budgetAllowances = DB::table('resort_budget_costs')
                ->whereYear('created_at', $currentYear)
                ->select('particulars', DB::raw('SUM(amount) as total'))
                ->groupBy('particulars')
                ->get();

            $html .= view('resorts.renderfiles.employee_monthly_row', [
                'month'       => $monthName,
                'salary'      => $payrollReview->earned_salary ?? 0,
                'ot'          => ($payrollReview->regularOTPay ?? 0) + ($payrollReview->holidayOTPay ?? 0),
                'allowances'  => $allowances,
                'insurance'   => $insurance,
                'visa'        => $visa,
                'work_permit' => $workPermit,
                'medical'     => $medical,
                'quota'       => $quota,
                'budget_allowances' => $budgetAllowances,
                'allowance_types' => $allowanceTypes,
            ])->render();
        }

        return response()->json(['html' => $html]);
    }

    // Removed: a stale private computeYearlyBudgetTotal() copy used to live
    // here. It missed the per-employee allowance leg, the stale-vacant
    // headcount filter, and the religion/nationality fields needed for the
    // cost-template Locals/Xpat/Muslim filter — so it returned a different
    // number than the canonical helper that view-budget and consolidated
    // both use. index() now calls Common::computeYearlyBudgetTotal directly
    // (line 70-ish above), and that's the single source of truth.

    /**
     * Bulk-friendly variant of Common::annualCostForEmployee. Takes
     * pre-fetched saved overrides + MVR rate as inputs so the per-call
     * DB queries are eliminated. Drop-in for the page's breakdown loop —
     * not exported because it depends on the caller having already
     * pre-fetched everything.
     */
    private static function fastAnnualCostForEmployee(
        $resortId, int $year, $cost, $employee,
        array $savedByMonth, bool $isMvrTemplate, float $mvrToUsdRate
    ): float {
        $isLocal  = strtolower(trim((string) ($employee->nationality ?? ''))) === 'maldivian';
        $isMuslim = strtolower(trim((string) ($employee->religion    ?? ''))) === 'muslim';
        $basicForPercent = (float) ($employee->basic_salary ?? 0);
        $benefitGridLevel = isset($employee->benefit_grid_level) ? (int) $employee->benefit_grid_level : null;

        $total = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            if (isset($savedByMonth[$m])) {
                $total += $savedByMonth[$m];
            } else {
                $val = \App\Helpers\Common::computeBudgetCostMonthlyValue(
                    $cost, $m, $year, $isLocal, $isMuslim, $basicForPercent, $benefitGridLevel
                );
                if ($isMvrTemplate) $val *= $mvrToUsdRate;
                $total += $val;
            }
        }
        return $total;
    }

}