<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined People Management – Probation reports (Option B), generic view.
 *
 * Source: employees.probation_status/probation_end_date/probation_review_date/
 * probation_remarks (see People\Probation\ProbationController, the live
 * probation module these reports mirror). Onboarding-training and monthly
 * check-in status reuse that module's exact bucket logic (training_participants
 * + training_attendance; monthly_checking_models, date stored as d/m/Y string).
 */
class ProbationReportController extends Controller
{
    use PredefinedReportActions;

    protected $resort;

    public function __construct()
    {
        $this->resort = auth()->guard('resort-admin')->user();
    }

    private function registry(): array
    {
        return [
            'probation_register' => [
                'name' => 'Probation Register',
                'description' => 'All employees currently on probation together with their probation progress, onboarding completion, monthly check-ins, and review status. Master probation report.',
                'filters' => ['duration', 'department', 'position', 'reporting_manager', 'probation_status'],
                'handler' => 'probationRegister',
            ],
            'upcoming_probation_expiry' => [
                'name' => 'Upcoming Probation Expiry Report',
                'description' => 'Employees whose probation period is approaching its end, enabling managers to complete reviews and confirmation decisions on time.',
                'filters' => ['duration', 'department', 'reporting_manager'],
                'handler' => 'upcomingProbationExpiry',
            ],
            'probation_progress' => [
                'name' => 'Probation Progress Report',
                'description' => 'Progress of each employee through the probation process, including mandatory onboarding activities, compulsory learning programs, monthly check-ins, and final review completion.',
                'filters' => ['duration', 'department', 'employee'],
                'handler' => 'probationProgress',
            ],
            'pending_probation_review' => [
                'name' => 'Pending Probation Review Report',
                'description' => 'Employees whose probation reviews are pending or overdue, helping managers complete evaluations before probation expires.',
                'filters' => ['duration', 'department', 'reporting_manager'],
                'handler' => 'pendingProbationReview',
            ],
            'probation_outcome' => [
                'name' => 'Probation Outcome Report',
                'description' => 'Final outcome of completed probation reviews, showing whether employees were confirmed, extended, or unsuccessful after the probation period.',
                'filters' => ['duration', 'department'],
                'handler' => 'probationOutcome',
            ],
            'probation_executive_summary' => [
                'name' => 'Probation Executive Summary',
                'description' => 'Overall view of probation activities, including employees currently on probation, upcoming confirmations, overdue reviews, and probation outcomes.',
                'filters' => ['duration'],
                'handler' => 'probationExecutiveSummary',
            ],
        ];
    }

    /* --------------------------------------------------------------- plumbing */

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) return abort(403, 'Unauthorized access');
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $reports = collect($this->registry())->map(fn($r, $key) => [
            'key' => $key, 'name' => $r['name'], 'description' => $r['description'],
            'filters' => array_values(array_unique(array_merge($r['filters'], ['duration']))),
        ])->values();

        $departments = DB::table('resort_departments')->where('resort_id', $rid)->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))->orderBy('name')->get(['id', 'name']);
        $positions = DB::table('resort_positions')->where('resort_id', $rid)->orderBy('position_title')->get(['id', 'position_title']);
        $employees = DB::table('employees as e')->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $rid)->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->orderBy('ra.first_name')->get(['e.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);
        $managers = DB::table('employees as e')->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $rid)->whereIn('e.rank', [2, 4, 5])
            ->orderBy('ra.first_name')->get(['e.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);

        $filterDefs = [
            ['filter' => 'department', 'name' => 'department', 'label' => 'Department', 'type' => 'select', 'placeholder' => 'All departments', 'options' => $departments->map(fn($d) => ['value' => $d->id, 'label' => $d->name])->all()],
            ['filter' => 'position', 'name' => 'position', 'label' => 'Position', 'type' => 'select', 'placeholder' => 'All positions', 'options' => $positions->map(fn($p) => ['value' => $p->id, 'label' => $p->position_title])->all()],
            ['filter' => 'employee', 'name' => 'employee', 'label' => 'Employee Name', 'type' => 'select', 'placeholder' => 'All employees', 'options' => $employees->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->all()],
            ['filter' => 'reporting_manager', 'name' => 'reporting_manager', 'label' => 'Reporting Manager', 'type' => 'select', 'placeholder' => 'All managers', 'options' => $managers->map(fn($m) => ['value' => $m->id, 'label' => $m->name])->all()],
            ['filter' => 'probation_status', 'name' => 'probation_status', 'label' => 'Probation Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect(['Active', 'Extended', 'Confirmed', 'Failed'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'Probation Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.probation.run', 'exportRoute' => 'resort.report.probation.export', 'insightsRoute' => 'resort.report.probation.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect(['department', 'position', 'employee', 'reporting_manager', 'probation_status', 'from_date', 'to_date'])
            ->mapWithKeys(fn($k) => [$k => $request->input($k) ?: null])->all();
    }

    private function compute(string $key, array $filters): ?array
    {
        $registry = $this->registry();
        if (!isset($registry[$key])) return null;
        $res = $this->{$registry[$key]['handler']}($filters);
        return ['name' => $registry[$key]['name'], 'description' => $registry[$key]['description'],
            'columns' => $res['columns'], 'rows' => $this->appendTotalsRow($res['columns'], $res['rows'])];
    }

    public function run(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        $c = $this->compute((string) $request->input('report'), $this->filtersFrom($request));
        if (!$c) return response()->json(['success' => false, 'message' => 'Unknown report.'], 422);
        $html = view('resorts.renderfiles.ReportFilterData', ['report' => (object) ['name' => $c['name']], 'columns' => $c['columns'], 'data' => $c['rows']])->render();
        return response()->json(['success' => true, 'html' => $html, 'count' => count($c['rows'])]);
    }

    public function export(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) return abort(403, 'Unauthorized access');
        $c = $this->compute((string) $request->input('report'), $this->filtersFrom($request));
        if (!$c) return abort(404, 'Unknown report');
        return $this->exportComputedReport($c['name'], $c['description'], $c['columns'], $c['rows'], $request->input('format', 'pdf'));
    }

    public function insights(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) return response()->json(['status' => false], 403);
        $c = $this->compute((string) $request->input('report'), $this->filtersFrom($request));
        if (!$c) return response()->json(['status' => false, 'message' => 'Unknown report.'], 422);
        return response()->json(['status' => true, 'data' => $this->computeAiInsightsText($c['name'], $c['description'], $c['columns'], $c['rows'])]);
    }

    /* --------------------------------------------------------------- shared helpers */

    private function baseQuery(int $rid, ?array $scoped)
    {
        return DB::table('employees as e')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->leftJoin('employees as rm', 'rm.id', '=', 'e.reporting_to')
            ->leftJoin('resort_admins as rmra', 'rmra.id', '=', 'rm.Admin_Parent_id')
            ->where('e.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped));
    }

    private function daysRemaining($endDate): ?int
    {
        if (!$endDate) return null;
        return (int) Carbon::today()->diffInDays(Carbon::parse($endDate), false);
    }

    /**
     * employees.probation_end_date is stored/derived as an end-EXCLUSIVE
     * boundary (joining_date + 3 months, e.g. Jun 1 -> Sep 1 — Sep 1 is the
     * first day AFTER probation, not the last day of it). People\Probation\
     * ProbationController's details() and list pages both subtract a day
     * for display; this report was showing the raw exclusive boundary,
     * one day later than the live module. daysRemaining()/whereDate
     * filtering above stay on the raw column — only the display label
     * needs the adjustment.
     */
    private function probationEndDateLabel($endDate): string
    {
        return $endDate ? Carbon::parse($endDate)->subDay()->format('d M Y') : 'N/A';
    }

    /** Same bucket logic as People\Probation\ProbationController::resolveOnboardingTraining(). */
    private function onboardingTrainingStatus(int $resortId, int $employeeId): string
    {
        $statuses = DB::table('training_participants as tp')
            ->join('training_schedules as ts', 'ts.id', '=', 'tp.training_schedule_id')
            ->leftJoin('training_attendance as ta', function ($j) {
                $j->on('ta.training_schedule_id', '=', 'tp.training_schedule_id')
                  ->on('ta.employee_id', '=', 'tp.employee_id');
            })
            ->where('ts.resort_id', $resortId)
            ->where('tp.employee_id', $employeeId)
            ->selectRaw('COALESCE(ta.status, tp.status) as status')
            ->pluck('status');

        if ($statuses->isEmpty()) return 'Not Started';
        if ($statuses->contains('Pending')) return 'In Progress';
        return $statuses->every(fn($s) => in_array($s, ['Present', 'Late'], true)) ? 'Completed' : 'Absent';
    }

    /** Same monthly check-in lookup as the live probation module, for the current month. */
    private function monthlyCheckinStatus(int $employeeId): string
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $checkin = DB::table('monthly_checking_models')
            ->where('emp_id', $employeeId)
            ->whereRaw("STR_TO_DATE(date_discussion, '%d/%m/%Y') BETWEEN ? AND ?", [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->exists();

        if ($checkin) return 'Up to date';
        $today = Carbon::now();
        if ($today->between($monthStart, $monthEnd)) return 'Due';
        return $monthEnd->isFuture() ? 'Upcoming' : 'Missed';
    }

    /* --------------------------------------------------------------- reports */

    public function probationRegister(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->whereIn('e.probation_status', ['Active', 'Extended'])
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['position'] ?? null, fn($q) => $q->where('e.Position_id', $f['position']))
            ->when($f['reporting_manager'] ?? null, fn($q) => $q->where('e.reporting_to', $f['reporting_manager']))
            ->when($f['probation_status'] ?? null, fn($q) => $q->where('e.probation_status', $f['probation_status']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('e.probation_end_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('e.probation_end_date', '<=', $f['to_date']))
            ->orderBy('e.probation_end_date')
            ->get([
                'e.id', 'e.Emp_id', 'e.joining_date', 'e.probation_end_date', 'e.probation_status',
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept', 'p.position_title',
                DB::raw("TRIM(CONCAT(COALESCE(rmra.first_name,''),' ',COALESCE(rmra.last_name,''))) as manager_name"),
            ])
            ->map(function ($r) use ($rid) {
                // employees.probation_end_date is frequently unset — same as
                // People\Probation\ProbationController's list column, derive
                // it as joining_date + 3 months when the explicit column is
                // empty, instead of falling straight to 'N/A'.
                $effectiveEnd = $r->probation_end_date
                    ?: ($r->joining_date ? Carbon::parse($r->joining_date)->addMonths(3)->format('Y-m-d') : null);
                $days = $this->daysRemaining($effectiveEnd);
                return [
                    'Employee ID'                => $r->Emp_id ?: 'N/A',
                    'Employee Name'              => trim($r->employee_name) ?: 'N/A',
                    'Department'                 => $r->dept ?? 'N/A',
                    'Position'                   => $r->position_title ?? 'N/A',
                    'Joining Date'               => $r->joining_date ? Carbon::parse($r->joining_date)->format('d M Y') : 'N/A',
                    'Probation Start Date'       => $r->joining_date ? Carbon::parse($r->joining_date)->format('d M Y') : 'N/A',
                    'Probation End Date'         => $this->probationEndDateLabel($effectiveEnd),
                    'Days Remaining'             => $days !== null ? $days : 'N/A',
                    'Reporting Manager'          => trim($r->manager_name) ?: 'N/A',
                    'Onboarding Training Status' => $this->onboardingTrainingStatus($rid, $r->id),
                    'Monthly Check-in Status'    => $this->monthlyCheckinStatus($r->id),
                    'Probation Review Status'    => $r->probation_status ?: 'N/A',
                    'Current Status'             => $r->probation_status ?: 'N/A',
                ];
            })->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Joining Date', 'Probation Start Date', 'Probation End Date', 'Days Remaining', 'Reporting Manager', 'Onboarding Training Status', 'Monthly Check-in Status', 'Probation Review Status', 'Current Status'],
            'rows'    => $rows,
        ];
    }

    public function upcomingProbationExpiry(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        $today = Carbon::today();
        $defaultWindowEnd = $today->copy()->addDays(30);

        // Same gap as probationRegister(): e.probation_end_date is null for
        // most employees (derived on the live probation page as
        // joining_date + 3 months instead). Filtering on the raw column
        // alone excluded virtually every row — apply the same fallback via
        // SQL so the date-range filter matches what the live page shows.
        $effectiveEndExpr = 'COALESCE(e.probation_end_date, DATE_ADD(e.joining_date, INTERVAL 3 MONTH))';
        // from_date/to_date are calendar dates the user typed, meaning "end
        // date shown on screen" — but effectiveEndExpr is the raw
        // end-EXCLUSIVE boundary (one day later than the displayed date, see
        // probationEndDateLabel()). Comparing the user's range directly
        // against the raw value silently excluded anyone whose displayed
        // end date fell exactly on the range boundary (e.g. to_date=31 Aug
        // excluded someone displayed as ending 31 Aug, because their raw
        // value is 1 Sep). Only the user-facing range comparisons need the
        // -1 day adjustment; the "today" floor and Days Remaining
        // intentionally stay on the raw value per the existing design.
        $displayEndExpr = "DATE_SUB($effectiveEndExpr, INTERVAL 1 DAY)";

        $rows = $this->baseQuery($rid, $scoped)
            ->whereIn('e.probation_status', ['Active', 'Extended'])
            ->whereRaw("$effectiveEndExpr >= ?", [$today->toDateString()])
            ->whereRaw("$displayEndExpr <= ?", [$f['to_date'] ?? $defaultWindowEnd->toDateString()])
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['reporting_manager'] ?? null, fn($q) => $q->where('e.reporting_to', $f['reporting_manager']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereRaw("$displayEndExpr >= ?", [$f['from_date']]))
            ->orderByRaw($effectiveEndExpr)
            ->get([
                'e.Emp_id', 'e.joining_date', 'e.probation_end_date', 'e.probation_status',
                DB::raw("$effectiveEndExpr as effective_end_date"),
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept', 'p.position_title',
                DB::raw("TRIM(CONCAT(COALESCE(rmra.first_name,''),' ',COALESCE(rmra.last_name,''))) as manager_name"),
            ])
            ->map(fn($r) => [
                'Employee ID'             => $r->Emp_id ?: 'N/A',
                'Employee Name'           => trim($r->employee_name) ?: 'N/A',
                'Department'              => $r->dept ?? 'N/A',
                'Position'                => $r->position_title ?? 'N/A',
                'Joining Date'            => $r->joining_date ? Carbon::parse($r->joining_date)->format('d M Y') : 'N/A',
                'Probation End Date'      => $this->probationEndDateLabel($r->effective_end_date),
                'Days Remaining'          => $this->daysRemaining($r->effective_end_date) ?? 'N/A',
                'Reporting Manager'       => trim($r->manager_name) ?: 'N/A',
                'Probation Review Status' => $r->probation_status ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Joining Date', 'Probation End Date', 'Days Remaining', 'Reporting Manager', 'Probation Review Status'],
            'rows'    => $rows,
        ];
    }

    public function probationProgress(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->whereIn('e.probation_status', ['Active', 'Extended'])
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('e.probation_end_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('e.probation_end_date', '<=', $f['to_date']))
            ->orderBy('e.probation_end_date')
            ->get([
                'e.id', 'e.Emp_id', 'e.joining_date', 'e.probation_status',
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept', 'p.position_title',
            ])
            ->map(function ($r) use ($rid) {
                $checkins = DB::table('monthly_checking_models')->where('emp_id', $r->id)->count();
                return [
                    'Employee ID'                    => $r->Emp_id ?: 'N/A',
                    'Employee Name'                  => trim($r->employee_name) ?: 'N/A',
                    'Department'                     => $r->dept ?? 'N/A',
                    'Position'                        => $r->position_title ?? 'N/A',
                    'Compulsory Training Completion' => $this->onboardingTrainingStatus($rid, $r->id),
                    'Monthly Check-in 1'              => $checkins >= 1 ? 'Completed' : 'Pending',
                    'Monthly Check-in 2'              => $checkins >= 2 ? 'Completed' : 'Pending',
                    'Monthly Check-in 3'              => $checkins >= 3 ? 'Completed' : 'Pending',
                    'Final Probation Review'          => in_array($r->probation_status, ['Confirmed', 'Failed'], true) ? $r->probation_status : 'Pending',
                    'Overall Progress Status'         => $r->probation_status ?: 'N/A',
                ];
            })->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Compulsory Training Completion', 'Monthly Check-in 1', 'Monthly Check-in 2', 'Monthly Check-in 3', 'Final Probation Review', 'Overall Progress Status'],
            'rows'    => $rows,
        ];
    }

    public function pendingProbationReview(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        $today = Carbon::today()->toDateString();

        $rows = $this->baseQuery($rid, $scoped)
            ->whereIn('e.probation_status', ['Active', 'Extended'])
            ->whereNotNull('e.probation_end_date')
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['reporting_manager'] ?? null, fn($q) => $q->where('e.reporting_to', $f['reporting_manager']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('e.probation_end_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('e.probation_end_date', '<=', $f['to_date']))
            ->orderBy('e.probation_end_date')
            ->get([
                'e.Emp_id', 'e.probation_end_date', 'e.probation_status',
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept', 'p.position_title',
                DB::raw("TRIM(CONCAT(COALESCE(rmra.first_name,''),' ',COALESCE(rmra.last_name,''))) as manager_name"),
            ])
            ->map(function ($r) use ($today) {
                $days = $this->daysRemaining($r->probation_end_date);
                $reviewStatus = $r->probation_end_date && $r->probation_end_date < $today ? 'Overdue' : 'Pending';
                return [
                    'Employee ID'        => $r->Emp_id ?: 'N/A',
                    'Employee Name'      => trim($r->employee_name) ?: 'N/A',
                    'Department'         => $r->dept ?? 'N/A',
                    'Position'           => $r->position_title ?? 'N/A',
                    'Probation End Date' => $this->probationEndDateLabel($r->probation_end_date),
                    'Days Remaining'     => $days !== null ? $days : 'N/A',
                    'Reporting Manager'  => trim($r->manager_name) ?: 'N/A',
                    'Review Status'      => $reviewStatus,
                ];
            })->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Probation End Date', 'Days Remaining', 'Reporting Manager', 'Review Status'],
            'rows'    => $rows,
        ];
    }

    public function probationOutcome(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $outcomeLabel = ['Confirmed' => 'Confirmed', 'Failed' => 'Unsuccessful', 'Extended' => 'Extended'];

        $rows = $this->baseQuery($rid, $scoped)
            ->whereIn('e.probation_status', ['Confirmed', 'Failed', 'Extended'])
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('e.probation_review_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('e.probation_review_date', '<=', $f['to_date']))
            ->orderByDesc('e.probation_review_date')
            ->get([
                'e.Emp_id', 'e.joining_date', 'e.probation_end_date', 'e.probation_review_date', 'e.probation_status', 'e.probation_remarks',
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept', 'p.position_title',
            ])
            ->map(fn($r) => [
                'Employee ID'        => $r->Emp_id ?: 'N/A',
                'Employee Name'      => trim($r->employee_name) ?: 'N/A',
                'Department'         => $r->dept ?? 'N/A',
                'Position'           => $r->position_title ?? 'N/A',
                'Joining Date'       => $r->joining_date ? Carbon::parse($r->joining_date)->format('d M Y') : 'N/A',
                'Probation End Date' => $this->probationEndDateLabel($r->probation_end_date),
                'Review Date'        => $r->probation_review_date ? Carbon::parse($r->probation_review_date)->format('d M Y') : 'N/A',
                'Probation Outcome'  => $outcomeLabel[$r->probation_status] ?? ($r->probation_status ?: 'N/A'),
                'Remarks'            => $r->probation_remarks ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Joining Date', 'Probation End Date', 'Review Date', 'Probation Outcome', 'Remarks'],
            'rows'    => $rows,
        ];
    }

    public function probationExecutiveSummary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        $today = Carbon::today();

        $base = fn() => $this->baseQuery($rid, $scoped)
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate(DB::raw('COALESCE(e.probation_review_date, e.probation_end_date)'), '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate(DB::raw('COALESCE(e.probation_review_date, e.probation_end_date)'), '<=', $f['to_date']));

        $totalOnProbation = (clone $base())->whereIn('e.probation_status', ['Active', 'Extended'])->count();
        $upcomingExpiry = (clone $base())->whereIn('e.probation_status', ['Active', 'Extended'])
            ->whereDate('e.probation_end_date', '>=', $today->toDateString())
            ->whereDate('e.probation_end_date', '<=', $today->copy()->addDays(30)->toDateString())->count();
        $pendingReviews = (clone $base())->whereIn('e.probation_status', ['Active', 'Extended'])
            ->whereDate('e.probation_end_date', '>=', $today->toDateString())->count();
        $overdueReviews = (clone $base())->whereIn('e.probation_status', ['Active', 'Extended'])
            ->whereNotNull('e.probation_end_date')->whereDate('e.probation_end_date', '<', $today->toDateString())->count();
        $confirmed = (clone $base())->where('e.probation_status', 'Confirmed')->count();
        $extended = (clone $base())->where('e.probation_status', 'Extended')->count();
        $unsuccessful = (clone $base())->where('e.probation_status', 'Failed')->count();

        $topDept = (clone $base())->whereIn('e.probation_status', ['Active', 'Extended'])
            ->select('d.name', DB::raw('COUNT(*) as cnt'))->groupBy('d.name')->orderByDesc('cnt')->first();

        $rows = [[
            'Total Employees on Probation' => $totalOnProbation,
            'Upcoming Probation Expiry'    => $upcomingExpiry,
            'Pending Reviews'              => $pendingReviews,
            'Overdue Reviews'              => $overdueReviews,
            'Confirmed Employees'          => $confirmed,
            'Extended Probations'          => $extended,
            'Unsuccessful Probations'      => $unsuccessful,
            'Department with Most Probationers' => $topDept->name ?? 'N/A',
        ]];

        return [
            'columns' => ['Total Employees on Probation', 'Upcoming Probation Expiry', 'Pending Reviews', 'Overdue Reviews', 'Confirmed Employees', 'Extended Probations', 'Unsuccessful Probations', 'Department with Most Probationers'],
            'rows'    => $rows,
        ];
    }
}
