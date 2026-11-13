<?php

namespace App\Services\Wisdom;

use App\Helpers\Common;
use Illuminate\Support\Facades\DB;

/**
 * Row providers for the catalog's computed datasets (config/wisdom_catalog.php, 'source' => [self::class, method]).
 * Each method gets the resort id and the department scope (null = whole resort) and must return only those rows,
 * keyed by the dataset's field names. They reuse the app's own engines, so WAI's numbers match the screens.
 */
class ComputedDatasets
{
    /**
     * One row per active employee per leave type, from Common::getEmployeeLeaveBalance (the engine behind the
     * Leave dashboard and leave applications). Liability = balance × daily rate, daily rate = basic salary in MVR
     * ÷ 30 (USD salaries × the resort's DollertoMVR), the final-settlement encashment formula on a 30-day month.
     */
    public static function leaveBalances(int $resortId, ?array $deptIds): array
    {
        // ponytail: one balance lookup per employee (a few queries each); fine for a resort's few hundred staff.
        $rate = (float) (DB::table('resort_site_settings')->where('resort_id', $resortId)->value('DollertoMVR') ?: 15.42);
        $emps = DB::table('employees as e')
            ->leftJoin('resort_admins as ra', fn ($j) => $j->on('ra.id', '=', 'e.Admin_Parent_id')->where('ra.resort_id', '=', $resortId))
            ->leftJoin('resort_departments as d', fn ($j) => $j->on('d.id', '=', 'e.Dept_id')->where('d.resort_id', '=', $resortId))
            ->where('e.resort_id', $resortId)->whereNull('e.deleted_at')->where('e.status', 'Active')
            ->when($deptIds !== null, fn ($q) => $q->whereIn('e.Dept_id', array_map('intval', $deptIds) ?: [0]))
            ->get(['e.id', 'e.Emp_id', 'e.basic_salary', 'e.basic_salary_currency', 'd.name as department',
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);

        $rows = [];
        foreach ($emps as $e) {
            $basicMvr = strtoupper((string) $e->basic_salary_currency) === 'USD' ? (float) $e->basic_salary * $rate : (float) $e->basic_salary;
            $daily = round($basicMvr / 30, 2);
            foreach (Common::getEmployeeLeaveBalance($e->id, $resortId) as $b) {
                $rows[] = [
                    'employee_id'    => $e->Emp_id,
                    'name'           => $e->name,
                    'department'     => $e->department,
                    'leave_type'     => $b['leave_type'],
                    'allocated'      => (float) $b['allocated'],
                    'earned_so_far'  => (float) $b['prorated'],
                    'used'           => (float) $b['used'],
                    'carry_forward'  => (float) $b['carry_forward'],
                    'balance'        => (float) $b['balance'],
                    'daily_rate_mvr' => $daily,
                    'liability_mvr'  => round(max((float) $b['balance'], 0) * $daily, 2),
                ];
            }
        }
        return $rows;
    }

    /** Employees of the resort (and department scope) with name and department, keyed by employees.id. */
    private static function people(int $resortId, ?array $deptIds, bool $expatsOnly = false): \Illuminate\Support\Collection
    {
        return DB::table('employees as e')
            ->leftJoin('resort_admins as ra', fn ($j) => $j->on('ra.id', '=', 'e.Admin_Parent_id')->where('ra.resort_id', '=', $resortId))
            ->leftJoin('resort_departments as d', fn ($j) => $j->on('d.id', '=', 'e.Dept_id')->where('d.resort_id', '=', $resortId))
            ->where('e.resort_id', $resortId)->whereNull('e.deleted_at')->where('e.status', 'Active')
            ->when($expatsOnly, fn ($q) => $q->where(fn ($w) => $w->whereNull('e.nationality')->orWhere('e.nationality', '!=', 'Maldivian')))
            ->when($deptIds !== null, fn ($q) => $q->whereIn('e.Dept_id', array_map('intval', $deptIds) ?: [0]))
            ->get(['e.id', 'e.Emp_id', 'e.nationality', 'd.name as department',
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")])
            ->keyBy('id');
    }

    /**
     * One row per active expatriate employee with each immigration document's current expiry, by the SAME rules as
     * the Visa › Xpat employee screen (XpactEmployeeController): visa = "Visa Expiry Date" of the employee's
     * AI-extracted immigration document; work permit = earliest unpaid work-permit fee (Common::visaNextDue), else
     * the document's permit expiry; insurance / medical = Common::visaNextDue (insurance falls back to the document).
     * Only a missing or expired visa affects whether someone can work; insurance / medical are renewals.
     */
    public static function expatDocuments(int $resortId, ?array $deptIds): array
    {
        $people = self::people($resortId, $deptIds, true);
        $blobs = DB::table('visa_employee_expiry_data')->where('resort_id', $resortId)->where('DocumentName', 'Other')
            ->whereIn('employee_id', $people->keys()->all() ?: [0])->orderByDesc('id')->get(['employee_id', 'Ai_extracted_data'])
            ->unique('employee_id')->mapWithKeys(fn ($r) => [$r->employee_id => (json_decode((string) $r->Ai_extracted_data, true)['extracted_fields'] ?? [])]);
        $date = function ($v) {
            if (empty($v)) {
                return null;
            }
            try {
                return \Carbon\Carbon::parse($v)->toDateString();
            } catch (\Throwable $e) {
                return null;
            }
        };
        $today = now()->toDateString();

        $rows = [];
        foreach ($people as $id => $p) {
            $f = $blobs[$id] ?? [];
            $docs = [
                'visa'        => $date($f['Visa Expiry Date'] ?? null),
                'work_permit' => $date(Common::visaNextDue(\App\Models\WorkPermit::class, $resortId, $id, 'Due_Date', 'asc') ?: ($f['Work Permit Expiry Date (Expiry On)'] ?? null)),
                'insurance'   => $date(Common::visaNextDue(\App\Models\EmployeeInsurance::class, $resortId, $id, 'insurance_end_date', 'desc') ?: ($f['Insurance Expiry Date'] ?? null)),
                'medical'     => $date(Common::visaNextDue(\App\Models\WorkPermitMedicalRenewal::class, $resortId, $id, 'end_date', 'desc')),
            ];
            $missing = array_keys(array_filter($docs, fn ($d) => $d === null));
            $expired = array_keys(array_filter($docs, fn ($d) => $d !== null && $d < $today));
            $upcoming = array_filter($docs, fn ($d) => $d !== null && $d >= $today);
            asort($upcoming);
            $rows[] = [
                'employee_id'          => $p->Emp_id,
                'name'                 => $p->name,
                'department'           => $p->department,
                'nationality'          => $p->nationality,
                'visa_expiry'          => $docs['visa'],
                'work_permit_expiry'   => $docs['work_permit'],
                'insurance_expiry'     => $docs['insurance'],
                'medical_expiry'       => $docs['medical'],
                'missing_documents'    => implode(', ', $missing),
                'missing_count'        => count($missing),
                'expired_documents'    => implode(', ', $expired),
                'can_work_legally'     => $docs['visa'] === null ? 'unknown (no visa on record)' : ($docs['visa'] >= $today ? 'yes' : 'no (visa expired)'),
                'visa_days_left'       => $docs['visa'] === null ? null : (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($docs['visa']), false),
                'next_renewal_document'=> array_key_first($upcoming),
                'next_renewal_date'    => $upcoming ? reset($upcoming) : null,
            ];
        }
        return $rows;
    }

    /**
     * Outstanding immigration renewals (unpaid work-permit and quota-slot fees, insurance / medical / visa renewals not
     * yet paid) with their due or expiry date and amount (MVR), from the same lookup as the Visa expiry screens.
     * blocks_work: only a visa (or an unpaid work permit) can stop someone working; insurance / medical / slot fees do not.
     */
    public static function immigrationRenewals(int $resortId, ?array $deptIds): array
    {
        $people = self::people($resortId, $deptIds);
        return WisdomTools::visaDocs($resortId, 'all', now()->subYears(5), now()->addYears(2))
            ->filter(fn ($d) => isset($people[$d['employee_id']]))
            ->map(function ($d) use ($people) {
                $p = $people[$d['employee_id']];
                return [
                    'employee_id' => $p->Emp_id,
                    'name'        => $p->name,
                    'department'  => $p->department,
                    'document'    => $d['type'],
                    'due_or_expiry_date' => $d['expiry'],
                    'amount_mvr'  => (float) $d['amount'],
                    'overdue'     => $d['expiry'] < now()->toDateString() ? 'yes' : 'no',
                    'blocks_work' => in_array($d['type'], ['Visa', 'Work Permit'], true) ? 'yes' : 'no',
                ];
            })->values()->all();
    }

    /**
     * Next 12 months: staff the occupancy-based model needs (the Workforce Planning dashboard's own forecast, reused
     * as-is, incl. its occupancy fallback and cache) vs the approved headcount in the manning plan for that month.
     */
    public static function staffingForecast(int $resortId, ?array $deptIds): array
    {
        if ($deptIds !== null) {
            return [];   // resort-wide figures only
        }
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $months[] = now()->startOfMonth()->addMonths($i);
        }
        $labels = array_map(fn ($m) => $m->format('M Y'), $months);
        $forecast = [];
        $note = null;
        try {
            // The dashboard's action answers with lists aligned to the months asked for.
            $json = app(\App\Http\Controllers\Resorts\WorkforcePlanningDashboardController::class)
                ->getAiInsights(\Illuminate\Http\Request::create('/', 'GET', ['months' => $labels]))->getData(true);
            foreach ($labels as $i => $label) {
                if (isset($json['hiringData'][$i])) {
                    $forecast[$label] = ['required' => (int) $json['hiringData'][$i], 'reason' => $json['reasons'][$i] ?? null, 'occupancy' => $json['occupancyRates'][$i] ?? null];
                }
            }
            if (!$forecast) {
                $note = 'The staffing model returned no forecast.';
            }
        } catch (\Throwable $e) {
            $note = 'The staffing model is not reachable right now.';
        }
        $approved = DB::table('position_monthly_data as pm')->join('manning_responses as m', 'm.id', '=', 'pm.manning_response_id')
            ->where('m.resort_id', $resortId)->whereIn('m.year', array_unique(array_map(fn ($m) => $m->year, $months)))
            ->groupBy('m.year', 'pm.month')->selectRaw('m.year, pm.month, SUM(pm.headcount) as hc, SUM(pm.filledcount) as filled')
            ->get()->keyBy(fn ($r) => $r->year . '-' . (int) $r->month);
        $active = DB::table('employees')->where('resort_id', $resortId)->whereNull('deleted_at')->where('status', 'Active')->count();

        return array_map(function ($m) use ($forecast, $approved, $active, $note) {
            $f = $forecast[$m->format('M Y')] ?? null;
            $need = $f['required'] ?? null;
            $a = $approved[$m->year . '-' . $m->month] ?? null;
            return [
                'month'             => $m->format('Y-m'),
                'expected_occupancy_pct' => $f['occupancy'] ?? null,
                'required_staff'    => $need === null ? null : (int) $need,
                'model_reason'      => $f['reason'] ?? $note,
                'approved_headcount'=> $a ? (int) $a->hc : null,
                'filled_in_plan'    => $a ? (int) $a->filled : null,
                'active_employees_now' => $active,
                'gap_vs_approved'   => ($need !== null && $a) ? (int) $need - (int) $a->hc : null,
            ];
        }, $months);
    }

    /**
     * Normal weekly hours per employee for the last 8 weeks (Mon–Sat, breaks and approved overtime excluded:
     * Common::weeklyNormalMinutes, the same rule as the T&A dashboard and the 48-hour compliance check) against
     * the employee's grade limit (benefit grid working_hrs_per_week, default 48).
     */
    public static function weeklyHours(int $resortId, ?array $deptIds): array
    {
        $people = self::people($resortId, $deptIds);
        if ($people->isEmpty()) {
            return [];
        }
        $emps = DB::table('employees')->whereIn('id', $people->keys())->get(['id', 'rank', 'benefit_grid_level'])->keyBy('id');
        $limits = DB::table('resort_benifit_grid')->where('resort_id', $resortId)->whereNotNull('working_hrs_per_week')->pluck('working_hrs_per_week', 'emp_grade');
        $start = now()->startOfWeek()->subWeeks(7);
        $rows = [];
        foreach (Common::weeklyNormalMinutes($resortId, $people->keys()->all(), $start->toDateString(), now()->endOfWeek()->toDateString()) as $eid => $byWeek) {
            $p = $people[$eid] ?? null;
            if (!$p) {
                continue;
            }
            $e = $emps[$eid];
            $limit = (int) ($limits[Common::resolveEmpGrade($resortId, $e->rank, $e->benefit_grid_level)] ?? 48) ?: 48;
            foreach ($byWeek as $week => $minutes) {
                $hours = round($minutes / 60, 1);
                $rows[] = ['employee_id' => $p->Emp_id, 'name' => $p->name, 'department' => $p->department, 'week' => (string) $week,
                    'normal_hours' => $hours, 'weekly_limit' => $limit, 'over_limit' => $hours > $limit ? 'yes' : 'no'];
            }
        }
        return $rows;
    }

    /**
     * Required training per employee: mandatory programs (by department / position) and probationary programs (due
     * within completion_days of joining). Completed = marked Present at a session of that program.
     */
    public static function trainingCompliance(int $resortId, ?array $deptIds): array
    {
        $people = self::people($resortId, $deptIds);
        if ($people->isEmpty()) {
            return [];
        }
        $emps = DB::table('employees')->whereIn('id', $people->keys())->get(['id', 'Dept_id', 'Position_id', 'joining_date', 'probation_status'])->keyBy('id');
        $programs = DB::table('learning_programs')->where('resort_id', $resortId)->pluck('name', 'id');
        $done = DB::table('training_attendance as ta')->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
            ->where('ts.resort_id', $resortId)->where('ta.status', 'Present')->whereIn('ta.employee_id', $people->keys())
            ->groupBy('ta.employee_id', 'ts.training_id')->selectRaw('ta.employee_id, ts.training_id, MAX(ta.attendance_date) as d')
            ->get()->keyBy(fn ($r) => $r->employee_id . '|' . $r->training_id);
        $rows = [];
        $add = function ($eid, $pid, $type, $due) use (&$rows, $people, $programs, $done) {
            $p = $people[$eid];
            $c = $done[$eid . '|' . $pid] ?? null;
            $rows[] = ['employee_id' => $p->Emp_id, 'name' => $p->name, 'department' => $p->department, 'program' => $programs[$pid] ?? ('Program #' . $pid),
                'requirement' => $type, 'due_date' => $due, 'completed' => $c ? 'yes' : 'no', 'completed_on' => $c->d ?? null,
                'overdue' => (!$c && $due && $due < now()->toDateString()) ? 'yes' : 'no'];
        };
        foreach (DB::table('mandatory_learning_programs')->where('resort_id', $resortId)->get() as $m) {
            foreach ($emps as $e) {
                if ((!$m->department_id || (int) $m->department_id === (int) $e->Dept_id) && (!$m->position_id || (int) $m->position_id === (int) $e->Position_id)) {
                    $add($e->id, $m->program_id, 'mandatory', null);
                }
            }
        }
        foreach (DB::table('probationary_learning_programs')->where('resort_id', $resortId)->get() as $pp) {
            foreach ($emps as $e) {
                if (in_array($e->probation_status, ['Active', 'Extended'], true)) {
                    $due = $e->joining_date && $e->joining_date > '1971-01-01' ? \Carbon\Carbon::parse($e->joining_date)->addDays((int) $pp->completion_days)->toDateString() : null;
                    $add($e->id, $pp->program_id, 'probation', $due);
                }
            }
        }
        return $rows;
    }

    /**
     * Joiners (status Onboarding, or a joining date from today on): is each readiness item in place — immigration
     * documents (expats), a bed assigned, an arrival itinerary, onboarding acknowledgements signed.
     */
    public static function onboardingReadiness(int $resortId, ?array $deptIds): array
    {
        $rows = DB::table('employees as e')
            ->leftJoin('resort_admins as ra', fn ($j) => $j->on('ra.id', '=', 'e.Admin_Parent_id')->where('ra.resort_id', '=', $resortId))
            ->leftJoin('resort_departments as d', fn ($j) => $j->on('d.id', '=', 'e.Dept_id')->where('d.resort_id', '=', $resortId))
            ->where('e.resort_id', $resortId)->whereNull('e.deleted_at')
            ->where(fn ($q) => $q->where('e.status', 'Onboarding')->orWhere('e.joining_date', '>=', now()->toDateString()))
            ->when($deptIds !== null, fn ($q) => $q->whereIn('e.Dept_id', array_map('intval', $deptIds) ?: [0]))
            ->get(['e.id', 'e.Emp_id', 'e.nationality', 'e.joining_date', 'e.status', 'd.name as department',
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);
        $ids = $rows->pluck('id')->all() ?: [0];
        $bed = DB::table('assing_accommodations')->where('resort_id', $resortId)->whereIn('emp_id', $ids)->pluck('emp_id')->flip();
        $itin = DB::table('employee_itineraries')->where('resort_id', $resortId)->whereIn('employee_id', $ids)->get(['employee_id', 'arrival_date'])->keyBy('employee_id');
        $acks = DB::table('employee_onboarding_acknowledgements')->where('resort_id', $resortId)->whereIn('employee_id', $ids)
            ->groupBy('employee_id')->selectRaw("employee_id, SUM(LOWER(status) IN ('signed','acknowledged','completed')) as ok, COUNT(*) as n")->get()->keyBy('employee_id');
        $visaDocs = DB::table('visa_employee_expiry_data')->where('resort_id', $resortId)->whereIn('employee_id', $ids)->pluck('employee_id')->flip();
        return $rows->map(function ($e) use ($bed, $itin, $acks, $visaDocs) {
            $expat = $e->nationality && strcasecmp($e->nationality, 'Maldivian') !== 0;
            $a = $acks[$e->id] ?? null;
            $items = [
                'visa_ready'          => $expat ? (isset($visaDocs[$e->id]) ? 'yes' : 'no') : 'not needed',
                'accommodation_ready' => isset($bed[$e->id]) ? 'yes' : 'no',
                'itinerary_ready'     => isset($itin[$e->id]) ? 'yes' : 'no',
                'onboarding_ready'    => $a && $a->n > 0 && (int) $a->ok === (int) $a->n ? 'yes' : 'no',
            ];
            return ['employee_id' => $e->Emp_id, 'name' => $e->name, 'department' => $e->department, 'status' => $e->status,
                'joining_date' => $e->joining_date > '1971-01-01' ? $e->joining_date : null, 'arrival_date' => $itin[$e->id]->arrival_date ?? null]
                + $items + ['all_ready' => in_array('no', $items, true) ? 'no' : 'yes'];
        })->values()->all();
    }
}
