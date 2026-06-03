<?php

namespace App\Services;

use App\Models\PayrollConfig;
use App\Models\ParentAttendace;
use App\Models\PublicHoliday;
use App\Models\EmployeeAllowance;
use App\Models\ResortSiteSettings;
use App\Models\PayrollRecoverySchedule;
use App\Models\Employee;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FinalSettlementService
{
    public function calculateFinalMonthData(Employee $employee, $resortId)
    {
        // dd($resortId);
        if($employee->resignation){
             $lastDay = Carbon::parse($employee->resignation->last_working_day);
        }
        else {
            $lastDay = Carbon::now()->endOfMonth();
        }
        $cutoffDay = PayrollConfig::where('resort_id', $resortId)->value('cutoff_day') ?? 15;


        // Payroll period range
        if ($lastDay->day >= $cutoffDay) {
            $payrollStart = $lastDay->copy()->day($cutoffDay);
            $payrollEnd = $lastDay->copy()->addMonth()->day($cutoffDay - 1);
        } else {
            $payrollEnd = $lastDay->copy()->day($cutoffDay - 1);
            $payrollStart = $lastDay->copy()->subMonth()->day($cutoffDay);
        }
        $payment_mode = $employee->payment_mode;

        // ─────────────────────────────────────────────────────────────
        // Effective attendance window for a final settlement.
        //
        // Old logic queried attendance from $payrollStart..$lastDay and
        // used $totalDays = payrollEnd - payrollStart + 1 (always ~30).
        // Two problems for a leaving employee:
        //   1. attendance is looked up over a window that may include
        //      days BEFORE joining_date (junk for new hires) and AFTER
        //      last_working_day (employee wasn't working)
        //   2. $totalDays was the full payroll cycle, even though the
        //      employee was only supposed to work a slice of it — so
        //      `dailySalary = basicMVR / 30` for a 6-day stay produced
        //      a daily rate that's too small.
        //
        // Clamp the window to the employee's actual employment span
        // inside the payroll period. expectedWorkingDays is what HR
        // should compare $daysWorked to — gaps surface as warnings on
        // the UI so HR notices missing attendance before signing off.
        // ─────────────────────────────────────────────────────────────
        $joiningDate = $employee->joining_date ? Carbon::parse($employee->joining_date)->startOfDay() : null;
        $effectiveStart = ($joiningDate && $joiningDate->greaterThan($payrollStart))
            ? $joiningDate->copy()
            : $payrollStart->copy();
        $effectiveEnd = $lastDay->copy(); // already capped to last_working_day or end_of_month
        // Guard against inverted ranges (employee joined after payroll
        // window). 0 expected days → proratedBasic 0; warning will fire.
        $expectedWorkingDays = $effectiveStart->lessThanOrEqualTo($effectiveEnd)
            ? ($effectiveEnd->diffInDays($effectiveStart) + 1)
            : 0;

        $attendance = ParentAttendace::where('Emp_id', $employee->Emp_id)
            ->whereBetween('date', [$effectiveStart, $effectiveEnd])
            ->get();

        // Paid days = Present (with valid check-in) + Day Off — same
        // definition used by PayrollController::generateReview() at
        // line 2173, where `earnedSalary = perDay × (presentCount +
        // dayOffCount)`. Approved paid leaves also count toward paid
        // days because the resort treats them as worked-equivalent
        // for salary purposes. Matching this formula keeps the F&F's
        // Earned Salary number consistent with what would appear in a
        // payroll run for the same period.
        $presentWithCheckIn = $attendance->filter(function ($r) {
            $t = $r->CheckingTime ?? '';
            $t = is_object($t) ? (string) $t : trim((string) $t);
            return $r->Status === 'Present' && $t !== '' && !in_array($t, ['00:00', '00:00:00']);
        });
        $daysPresent = $presentWithCheckIn->pluck('date')->map(function ($d) {
            return is_object($d) ? $d->format('Y-m-d') : $d;
        })->unique()->count();
        $daysOff = $attendance->filter(fn($r) => $r->Status === 'Day Off')
            ->pluck('date')->map(fn($d) => is_object($d) ? $d->format('Y-m-d') : $d)
            ->unique()->count();
        // Total paid days for the worked-salary line. Encashment-side
        // leave is a separate calc and isn't included here.
        $daysWorked = $daysPresent + $daysOff;
        // totalDays remains the full payroll-cycle span — it's the
        // denominator MIRA/payroll uses to derive the daily rate from
        // the monthly basic. proratedBasic then = dailySalary * daysWorked
        // so an employee who actually worked 6 of the 30 cycle days gets
        // 6/30 of the monthly. expectedWorkingDays is reported alongside
        // so HR can spot "worked 0 of 6 expected" gaps.
        $totalDays = $payrollEnd->diffInDays($payrollStart) + 1;

        $settings = ResortSiteSettings::where('resort_id', $resortId)->first();
        $conversionRate = $settings ? ($settings->DollertoMVR ?? 15.42) : 15.42;
        $currency = $employee->basic_salary_currency;

        $basic = floatval($employee->basic_salary);
        $basicMVR = ($currency === 'USD') ? $basic * $conversionRate : $basic;

        $dailySalary = round($basicMVR / $totalDays, 2);
        $leaveBalance = $this->getLeaveBalance($employee, $resortId);
       
        $leaveEncashment = round($leaveBalance['total_days'] * $dailySalary, 2);

        $regularOT = $holidayOT = 0;
        foreach ($attendance as $record) {
            if (!empty($record->OverTime) && str_contains($record->OverTime, ':')) {
                [$h, $m] = explode(':', $record->OverTime);
                $hours = (int)$h + ((int)$m / 60);
                $isHoliday = PublicHoliday::where('holiday_date', date('d M Y', strtotime($record->date)))->exists();
                $isHoliday ? $holidayOT += $hours : $regularOT += $hours;
            }
        }

        $hourlyRate = $basicMVR / ($totalDays * 8);
        $regularOtAmount = round($regularOT * $hourlyRate * 1.25, 2);
        $holidayOtAmount = round($holidayOT * $hourlyRate * 1.5, 2);
        $totalOtAmount = $regularOtAmount + $holidayOtAmount;

        $allowances = EmployeeAllowance::with('allowanceName')
            ->where('employee_id', $employee->id)
            ->get();
        // dd($allowances);
        $allowanceDetails = [];
        $totalAllowance = 0;
        foreach ($allowances as $a) {
            $amount = floatval($a->amount);
            // BUG FIX: convert using the ALLOWANCE's own currency
            // (`amount_unit`), not the employee's basic-salary currency.
            // An employee whose basic is in MVR can still receive an
            // allowance recorded in USD (e.g. Service Charge top-up) and
            // vice-versa — the old code keyed off $employee->basic_salary_currency
            // and silently skipped conversion in that case.
            $allowanceCurrency = strtoupper((string) ($a->amount_unit ?? 'MVR'));
            $convertedAmount = ($allowanceCurrency === 'USD') ? $amount * $conversionRate : $amount;
            $totalAllowance += $convertedAmount;

            $allowanceDetails[] = [
                'id'   => $a->id,
                'name' => $a->allowanceName->particulars ?? 'N/A',
                'original_amount' => $amount,
                'converted_amount' => round($convertedAmount, 2),
                'unit' => $allowanceCurrency,
            ];
        }

        $ramadanBonus = 0;
        $nextMonth = Carbon::parse($payrollStart)->addMonthNoOverflow()->startOfMonth();
        $isNextMonthRamadan = Cache::remember(
            'is_ramadan_' . $nextMonth->format('Y_m'),
            now()->addDays(10),
            function () use ($nextMonth) {
                $response = Http::get("https://api.aladhan.com/v1/gToH", [
                    'date' => $nextMonth->format('d M Y')
                ]);
                return $response->successful() && ($response['data']['hijri']['month']['number'] == 9);
            }
        );

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

        $pension = round($basicMVR * 0.07, 2);
        $taxableIncome = $basicMVR + $totalOtAmount + $totalAllowance + $ramadanBonus - $pension;

        $proratedBasic = round($dailySalary * $daysWorked, 2);
        $earnedSalary = $proratedBasic + $totalOtAmount + $totalAllowance + $ramadanBonus + $leaveEncashment;

        // ─── Notice Period Charge ──────────────────────────────────
        // Pulled from the Notice Period module config
        // (`employee_notice_period.period` = required notice days, e.g.
        // 30). Charge applies when the employee gave less notice than
        // required — typically when they took immediate release.
        //
        //   served_days    = (last_working_day − resignation_date) + 1
        //   shortfall_days = max(0, required_days − served_days)
        //   charge_mvr     = shortfall_days × dailySalary
        //
        // Some resorts have multiple notice configs (one per
        // employment-type / rank). Pick the one whose immediate_release
        // flag matches what the employee actually requested; fall back
        // to the first row when no specific match exists. Zeroes out
        // gracefully when the module isn't configured for this resort.
        $noticeRequiredDays    = 0;
        $noticeServedDays      = 0;
        $noticeShortfallDays   = 0;
        $noticePeriodChargeMvr = 0.0;
        $noticeRuleTitle       = null;
        if ($employee->resignation) {
            $resignationDate = $employee->resignation->resignation_date
                ? Carbon::parse($employee->resignation->resignation_date)
                : null;
            $lastWorkingDay = $employee->resignation->last_working_day
                ? Carbon::parse($employee->resignation->last_working_day)
                : null;

            // Match the notice rule by employee GRADE (HOD / MGR / GM /
            // EXCOM / LINE WORKERS), not by the immediate_release flag.
            // The `immediate_release` column on the config marks which
            // grades are allowed to skip notice (e.g. LINE WORKERS); it
            // is NOT the rule selector. Earlier logic that picked by
            // immediate_release silently grabbed LINE WORKERS for every
            // immediate-release resignation, and LINE WORKERS often has
            // period=NULL → notice charge collapsed to 0 even for HODs
            // with a configured 45-day rule.
            $gradeTitle = config('settings.Position_Rank.' . (int) ($employee->rank ?? 0));
            $noticeRule = null;
            if ($gradeTitle) {
                $noticeRule = \App\Models\EmployeeNoticePeriod::where('resort_id', $resortId)
                    ->whereRaw('LOWER(TRIM(title)) = ?', [strtolower(trim((string) $gradeTitle))])
                    ->first();
            }
            // Fallback to the first rule that actually has a period
            // configured — avoids picking a NULL-period row that would
            // collapse the charge to 0 with no actionable error.
            if (!$noticeRule) {
                $noticeRule = \App\Models\EmployeeNoticePeriod::where('resort_id', $resortId)
                    ->whereNotNull('period')
                    ->where('period', '!=', '')
                    ->first();
            }

            if ($noticeRule && $resignationDate && $lastWorkingDay) {
                // `period` column is a string like "30" or "60". Cast
                // defensively so a stray non-numeric value still
                // produces 0 instead of crashing.
                $noticeRequiredDays  = (int) preg_replace('/\D+/', '', (string) $noticeRule->period);
                $noticeServedDays    = $lastWorkingDay->greaterThanOrEqualTo($resignationDate)
                    ? ($lastWorkingDay->diffInDays($resignationDate) + 1)
                    : 0;
                $noticeShortfallDays = max(0, $noticeRequiredDays - $noticeServedDays);
                $noticePeriodChargeMvr = round($noticeShortfallDays * $dailySalary, 2);
                $noticeRuleTitle     = $noticeRule->title;
            }
        }

        // ─── Loan / Salary-advance outstanding recovery ────────────
        // The "LOAN OR ADVANCE PAYMENT?" field on the F&F page must
        // recover any unpaid installments from BOTH the Salary Advance
        // module AND the Loan module before the employee is settled.
        // Both modules share the same `payroll_advance` parent table
        // (discriminated by `request_type`) and `payroll_recovery_schedule`
        // installment rows (status='Pending' = still owed). Group the
        // outstanding installments by request_type so the UI can show
        // a breakdown (Loan: X, Salary Advance: Y → Total).
        //
        // Currency note: installment amounts are stored in the same
        // currency as the parent advance. Until we add a per-advance
        // currency column, we follow the same convention the old code
        // used — multiply by the FX rate when the employee's basic
        // salary is in USD.
        $recoveryRows = DB::table('payroll_recovery_schedule as rs')
            ->join('payroll_advance as pa', 'pa.id', '=', 'rs.payroll_advance_id')
            ->where('pa.employee_id', $employee->id)
            ->where('rs.status', 'Pending')
            ->select('pa.request_type', 'rs.amount', 'rs.interest_amount')
            ->get();

        $loanOutstanding    = 0.0;
        $advanceOutstanding = 0.0;
        $loanCount          = 0;
        $advanceCount       = 0;
        foreach ($recoveryRows as $r) {
            $amt = (float) ($r->amount ?? 0) + (float) ($r->interest_amount ?? 0);
            $amtMvr = ($currency === 'USD') ? $amt * $conversionRate : $amt;
            // request_type values seeded by the UI are 'loan' /
            // 'salary_advance' / 'advance' — anything containing 'loan'
            // lands in the loan bucket, the rest fall through to
            // salary advance. Be lenient on casing.
            if (stripos((string) $r->request_type, 'loan') !== false) {
                $loanOutstanding += $amtMvr;
                $loanCount++;
            } else {
                $advanceOutstanding += $amtMvr;
                $advanceCount++;
            }
        }
        $recoveryData = $loanOutstanding + $advanceOutstanding;

        // Attendance-gap signal. When the employee was supposed to work
        // N days inside the payroll period (joining_date or
        // payrollStart, whichever is later → last_working_day) but the
        // check-in records show M < N, the UI surfaces a warning under
        // Earning Salary so HR can fix attendance before issuing the
        // payout. proratedBasic was already computed above as
        // `dailySalary × daysWorked` and feeds earned_salary, so the
        // warning is purely UX — it doesn't change the math.
        $attendanceGap = $expectedWorkingDays > 0 && $daysWorked < $expectedWorkingDays;

        // EWT / TIN audit flags. The F&F page surfaces a warning when
        // the employee earns ≥ MVR 30,000 but isn't enrolled for EWT or
        // has no TIN on file — same condition the Employee Details page
        // surfaces. Lets HR catch "EWT = 0 but should have been deducted"
        // before issuing the settlement.
        $ewtThresholdMvr = 30000;
        $ewtEnrolled = strtolower((string) ($employee->ewt_status ?? '')) === 'yes';
        $tinPresent  = !empty(trim((string) ($employee->tin ?? '')));
        $ewtRegistrationNeeded = ($taxableIncome >= $ewtThresholdMvr) && (!$ewtEnrolled || !$tinPresent);

        return [
            'pension' => round($pension,2),
            'ewt' => round(Common::calculateEWT($taxableIncome),2),
            'ewt_enrolled' => $ewtEnrolled,
            'tin_present' => $tinPresent,
            'ewt_registration_needed' => $ewtRegistrationNeeded,
            'taxable_income' => round($taxableIncome, 2),
            // Attendance-driven breakdown so HR can see the earning math
            // and catch missing check-ins before signing off. The labels
            // mirror the new fields on the F&F view.
            'expected_working_days' => $expectedWorkingDays,
            'attendance_gap'        => $attendanceGap,
            'attendance_period_start' => $effectiveStart->format('d M Y'),
            'attendance_period_end'   => $effectiveEnd->format('d M Y'),
            'leave_balance' => $leaveBalance['total_days'],
            // Per-category breakdown so HR sees exactly which buckets
            // make up the leave balance (Annual / Days Off / Public
            // Holiday / Sick, etc.) — was hidden behind a single sum
            // before, which made "the encashment number looks too big"
            // questions impossible to answer without DB access.
            'leave_breakdown' => $leaveBalance['details'] ?? [],
            'leave_encashment' => round($leaveEncashment,2),
            'daily_salary' => round($dailySalary,2),
            'worked_days' => $daysWorked,
            'total_days' => $totalDays,
            'basic_salary_mvr' => round($basicMVR, 2),
            'proratedBasic' => round($proratedBasic, 2),
            'regular_ot_hours' => $regularOT,
            'holiday_ot_hours' => $holidayOT,
            'regular_ot_amount' => round($regularOtAmount,2),
            'holiday_ot_amount' => round($holidayOtAmount,2),
            'total_ot_amount' => round($totalOtAmount,2),
            'ramadan_bonus' => round($ramadanBonus,2),
            'loan_recovery' => round($recoveryData,2),
            // Per-bucket recovery breakdown so HR can see how the field
            // was built — useful when the parent advances + loans come
            // from two different modules. Empty totals when nothing's
            // outstanding (the UI hides the breakdown line in that case).
            'loan_outstanding_mvr'    => round($loanOutstanding, 2),
            'advance_outstanding_mvr' => round($advanceOutstanding, 2),
            'loan_installment_count'  => $loanCount,
            'advance_installment_count' => $advanceCount,
            // Notice Period Charge surfaced from the Notice Period module
            // config. The breakdown lets HR see why the charge is what
            // it is (required vs served vs shortfall days × daily rate).
            'notice_period_charge_mvr' => $noticePeriodChargeMvr,
            'notice_required_days'     => $noticeRequiredDays,
            'notice_served_days'       => $noticeServedDays,
            'notice_shortfall_days'    => $noticeShortfallDays,
            'notice_rule_title'        => $noticeRuleTitle,
            'total_allowances_mvr' => round($totalAllowance, 2),
            'allowances' => $allowanceDetails,
            // ── Earnings ──
            // `earned_salary` mirrors the payroll_reviews column of the
            // same name → just basic-salary-for-paid-days, matching
            // PayrollController::generateReview() at line 2173. Other
            // components (OT, allowance, leave encashment, service
            // charge, ramadan bonus) stay as their own line items in
            // the F&F so the UI table can add them up to a Gross
            // Earning total. `gross_earning` is the bundled sum for
            // convenience.
            'earned_salary' => round($proratedBasic, 2),
            'gross_earning' => round($earnedSalary, 2),
            'payment_mode' => $payment_mode,
            'payroll_start' => $payrollStart->format('d M Y'),


        ];
    }

    public function getLeaveBalance(Employee $employee, $resortId): array
    {
        $emp_grade = Common::getEmpGrade($employee->rank);

        $benefit_grids = DB::table('resort_benifit_grid as rbg')
            ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
            ->join('leave_categories as lc', 'lc.id', '=', 'rbgc.leave_cat_id')
            ->where('rbg.emp_grade', $emp_grade)
            ->where('rbgc.rank', $employee->rank)
            ->where('lc.resort_id', $resortId)
            ->select(
                'lc.id as leave_category_id',
                'lc.leave_type',
                'lc.color',
                'lc.resort_id',
                'lc.carry_forward',
                'lc.carry_max',
                'lc.earned_leave',
                'lc.earned_max',
                'lc.frequency',
                'rbgc.allocated_days'
            )
            ->get();

        // Gender filter — gender-specific leave categories should drop
        // out of the breakdown entirely for the wrong gender so HR
        // doesn't see "Maternity Leave: 8.40 days" against a male
        // employee. Maternity → female only; Paternity → male only;
        // Circumcision → male only (cultural context). Gender lives
        // on resortAdmin via Admin_Parent_id.
        $empGender = strtolower((string) optional($employee->resortAdmin)->gender);
        $benefit_grids = $benefit_grids->reject(function ($g) use ($empGender) {
            $type = strtolower((string) $g->leave_type);
            if (str_contains($type, 'maternity'))   return $empGender !== 'female';
            if (str_contains($type, 'paternity'))   return $empGender !== 'male';
            if (str_contains($type, 'circumcision')) return $empGender !== 'male';
            return false;
        })->values();

        $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
        $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');

        $leaveUsage = DB::table('employees_leaves')
            ->select('leave_category_id', DB::raw('SUM(total_days) as used_days'))
            ->where('emp_id', $employee->emp_id)
            ->where('status', 'Approved')
            ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                    ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
            })
            ->groupBy('leave_category_id')
            ->get()
            ->keyBy('leave_category_id');

        $leaveBalances = [];
        $totalLeaveDays = 0;

        // Tenure-aware accrual base. The previous logic granted the full
        // annual allocated_days on day 1 and used Carbon::now()->month
        // for earned leave — so a 37-day employee inherited an entire
        // year's leave (151 days observed on resort 26 / DR-25). Bound
        // accrual to the period the employee has actually worked this
        // year:
        //   • accrualStart = MAX(joining_date, year_start)
        //   • tenureMonths = months between accrualStart and today (≤ 12)
        // Both the allocated annual entitlement AND the earned-leave drip
        // are then prorated by tenureMonths / 12.
        $now = Carbon::now();
        $yearStart = $now->copy()->startOfYear();
        $joiningDate = $employee->joining_date ? Carbon::parse($employee->joining_date) : null;
        $accrualStart = ($joiningDate && $joiningDate->greaterThan($yearStart))
            ? $joiningDate
            : $yearStart;
        // floatDiffInMonths gives a fractional value so 37 days ≈ 1.23
        // months gets credited proportionally instead of rounding to a
        // full month. Clamp to [0, 12] so we never grant more than one
        // year of accrual, regardless of clock drift.
        $tenureMonths = max(0.0, min(12.0, (float) $accrualStart->floatDiffInMonths($now)));
        $tenureRatio  = $tenureMonths / 12.0;

        foreach ($benefit_grids as $grid) {
            $usedDays = $leaveUsage->get($grid->leave_category_id)->used_days ?? 0;
            // Prorate the annual entitlement by tenure-in-year. An
            // employee who joined mid-year gets a fractional slice of
            // the annual quota; existing employees who started the year
            // here get the full quota (tenureRatio capped at 1.0).
            $available = round($grid->allocated_days * $tenureRatio, 2);

            // Carry forward when leave is eligible (category allows it) and not used by employee (unused from last year).
            // Skip entirely when the employee joined this year — they
            // had no prior-year leave to carry over.
            $carryForwardEnabled = !empty($grid->carry_forward) && $grid->carry_forward != '0';
            $joinedThisYear = $joiningDate && $joiningDate->greaterThanOrEqualTo($yearStart);
            if ($carryForwardEnabled && !$joinedThisYear) {
                $lastYear = Carbon::now()->subYear();
                $lastYearStart = $lastYear->copy()->startOfYear()->format('Y-m-d');
                $lastYearEnd = $lastYear->copy()->endOfYear()->format('Y-m-d');

                $lastYearUsed = DB::table('employees_leaves')
                    ->select(DB::raw('SUM(total_days) as used_days'))
                    ->where('emp_id', $employee->emp_id)
                    ->where('leave_category_id', $grid->leave_category_id)
                    ->where('status', 'Approved')
                    ->where(function ($query) use ($lastYearStart, $lastYearEnd) {
                        $query->whereBetween('from_date', [$lastYearStart, $lastYearEnd])
                            ->orWhereBetween('to_date', [$lastYearStart, $lastYearEnd]);
                    })
                    ->value('used_days') ?? 0;

                $unused = max($grid->allocated_days - $lastYearUsed, 0);
                $carryForward = ($grid->carry_max !== null && $grid->carry_max !== '')
                    ? min($unused, (int) $grid->carry_max)
                    : $unused;
                $available += $carryForward;
            }

            $earnedLeaveEnabled = !empty($grid->earned_leave) && $grid->earned_leave != '0';
            if ($earnedLeaveEnabled) {
                // Earned leave drips monthly. Use tenure-months (since
                // joining/year-start) instead of Carbon::now()->month so
                // a new hire doesn't instantly inherit a full year of
                // drip. Capped at earned_max for long-tenured employees.
                $earnedLeave = min(
                    round($tenureMonths * ($grid->earned_max / 12), 2),
                    (float) $grid->earned_max
                );
                $available += $earnedLeave;
            }

            // Round at the per-category boundary so the running total
            // doesn't accumulate floating-point noise (was producing
            // 20.740000000000002 instead of 20.74 once tenure-prorated
            // and earned-leave drips were summed). Parsley flagged this
            // as "This value seems to be invalid" on the F&F page.
            $finalAvailable = round(max(0, $available - $usedDays), 2);

            // ─── Encashment-eligibility policy ───────────────────────
            // Only ANNUAL LEAVE is encashed at settlement, and only
            // once the employee has completed 1 full year of service.
            // Event-based leaves (Maternity / Paternity / Birthday /
            // Emergency / Circumcision / R&R) carry no encashment
            // value — they only exist if the underlying event happens.
            // Sick Leave is excluded too at this resort's policy; flip
            // the rule per-resort by extending the matcher below.
            //
            // Tenure rule: annual leave vests at the 12-month mark.
            // Anyone with less than 12 months tenure gets 0 encashable
            // leave even though the per-category breakdown still
            // surfaces the prorated number for transparency.
            $isAnnualLeave = (stripos((string) $grid->leave_type, 'annual') !== false);

            $totalEmploymentMonths = $joiningDate
                ? max(0.0, $joiningDate->floatDiffInMonths($now))
                : 0.0;
            $oneYearServed = $totalEmploymentMonths >= 12;

            $encashable    = $isAnnualLeave && $oneYearServed;
            $encashableDays = $encashable ? $finalAvailable : 0;

            $leaveBalances[] = [
                'leave_category_id'    => $grid->leave_category_id,
                'leave_type'           => $grid->leave_type,
                'available_days'       => $finalAvailable,
                'encashable_days'      => $encashableDays,
                'is_encashable'        => $encashable,
                'not_encashable_reason'=> $encashable
                    ? null
                    : ($isAnnualLeave
                        ? 'Annual leave vests after 1 year of service ('
                            . number_format($totalEmploymentMonths, 1) . ' months served)'
                        : 'Event-based — no encashment value'),
            ];

            // Only ENCASHABLE days flow into total_days, which drives
            // the LEAVE ENCASHMENT input + the headline on the F&F.
            $totalLeaveDays += $encashableDays;
        }

        // Final precision pass at the boundary so callers see clean
        // 2-decimal values regardless of FP accumulation upstream.
        return [
            'total_days' => round($totalLeaveDays, 2),
            'details' => $leaveBalances,
        ];
    }
}
