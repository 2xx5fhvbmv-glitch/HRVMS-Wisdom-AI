<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined People Management – Employee Master reports (Option B), generic view.
 *
 * employees is the spine; the person's name/email/phone/gender live on
 * resort_admins (via Admin_Parent_id). reporting_to references employees.id.
 * Employment Type = employees.employment_type; Employment Status = employees.status
 * (Active|Inactive|Onboarding|Offboarding); Location = employees.location.
 * No stored gender column on employees, no phone extension, no explicit separation
 * type / recruitment source -> derived where possible, else N/A.
 */
class EmployeeMasterReportController extends Controller
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
            'master'            => ['name' => 'Employee Master Register', 'description' => 'Comprehensive master directory of employees.', 'filters' => ['duration', 'department', 'position', 'employment_status', 'employment_type', 'nationality', 'gender', 'location'], 'handler' => 'master'],
            'active'            => ['name' => 'Active Employee Register', 'description' => 'All currently active employees.', 'filters' => ['duration', 'department', 'position', 'location'], 'handler' => 'activeRegister'],
            'inactive'          => ['name' => 'Inactive Employee Register', 'description' => 'Employees separated (resignation, termination, etc.).', 'filters' => ['duration', 'separation_type'], 'handler' => 'inactiveRegister'],
            'new_joiners'       => ['name' => 'New Joiners Report', 'description' => 'Employees who joined during the selected period.', 'filters' => ['duration', 'department'], 'handler' => 'newJoiners'],
            'upcoming_joiners'  => ['name' => 'Upcoming Joiners Report', 'description' => 'Employees scheduled to join in the future.', 'filters' => ['duration'], 'handler' => 'upcomingJoiners'],
            'dept_wise'         => ['name' => 'Department-wise Employee Report', 'description' => 'Employees grouped by department.', 'filters' => ['duration', 'department'], 'handler' => 'deptWise'],
            'position_wise'     => ['name' => 'Position-wise Employee Report', 'description' => 'Employees grouped by position.', 'filters' => ['duration', 'position'], 'handler' => 'positionWise'],
            'location_wise'     => ['name' => 'Location-wise Employee Report', 'description' => 'Employees grouped by work location.', 'filters' => ['duration', 'location'], 'handler' => 'locationWise'],
            'nationality_wise'  => ['name' => 'Nationality-wise Employee Report', 'description' => 'Employees grouped by nationality.', 'filters' => ['duration', 'nationality'], 'handler' => 'nationalityWise'],
            'local_vs_expat'    => ['name' => 'Local vs Expatriate Workforce Report', 'description' => 'Distribution between Maldivian and expatriate employees.', 'filters' => ['duration'], 'handler' => 'localVsExpat'],
            'gender_diversity'  => ['name' => 'Gender Diversity Report', 'description' => 'Workforce distribution by gender.', 'filters' => ['duration', 'department'], 'handler' => 'genderDiversity'],
            'employment_type'   => ['name' => 'Employment Type Report', 'description' => 'Employees grouped by employment type.', 'filters' => ['duration', 'employment_type'], 'handler' => 'employmentTypeReport'],
            'reporting_manager' => ['name' => 'Reporting Manager Report', 'description' => 'Employees grouped under their reporting managers.', 'filters' => ['duration', 'manager'], 'handler' => 'reportingManager'],
            'contact_directory' => ['name' => 'Employee Contact Directory', 'description' => 'Internal employee contact directory.', 'filters' => ['department', 'location'], 'handler' => 'contactDirectory'],
            'workforce_distribution' => ['name' => 'Workforce Distribution Report', 'description' => 'Distribution by department, gender, nationality, employment type and location.', 'filters' => ['duration'], 'handler' => 'workforceDistribution'],
            'demographics'      => ['name' => 'Employee Demographics Report', 'description' => 'Demographic statistics of the workforce.', 'filters' => ['duration', 'employment_status'], 'handler' => 'demographics'],
            'birthday'          => ['name' => 'Birthday Report', 'description' => 'Employees whose birthdays fall within the selected period.', 'filters' => ['duration'], 'handler' => 'birthday'],
            'work_anniversary'  => ['name' => 'Work Anniversary Report', 'description' => 'Employees celebrating work anniversaries in the period.', 'filters' => ['duration'], 'handler' => 'workAnniversary'],
            'length_of_service' => ['name' => 'Length of Service Report', 'description' => 'Employees by years of service.', 'filters' => ['duration'], 'handler' => 'lengthOfService'],
            'exec_summary'      => ['name' => 'Executive Workforce Summary', 'description' => 'Management overview of workforce composition and distribution.', 'filters' => ['duration'], 'handler' => 'execSummary'],
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
        $nationalities = DB::table('employees')->where('resort_id', $rid)->whereNotNull('nationality')->where('nationality', '<>', '')->when($scoped !== null, fn($q) => $q->whereIn('Dept_id', $scoped))->distinct()->orderBy('nationality')->pluck('nationality');
        $locations = DB::table('employees')->where('resort_id', $rid)->whereNotNull('location')->where('location', '<>', '')->distinct()->orderBy('location')->pluck('location');
        $empTypes = DB::table('employees')->where('resort_id', $rid)->whereNotNull('employment_type')->where('employment_type', '<>', '')->distinct()->orderBy('employment_type')->pluck('employment_type');
        $managers = DB::table('employees as e')->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $rid)
            ->whereIn('e.id', DB::table('employees')->where('resort_id', $rid)->where('reporting_to', '>', 0)->distinct()->pluck('reporting_to'))
            ->orderBy('ra.first_name')->get(['e.id', DB::raw("TRIM(CONCAT_WS(' ', ra.first_name, ra.last_name)) as name")]);

        $filterDefs = [
            ['filter' => 'department', 'name' => 'department', 'label' => 'Department', 'type' => 'select', 'placeholder' => 'All departments', 'options' => $departments->map(fn($d) => ['value' => $d->id, 'label' => $d->name])->all()],
            ['filter' => 'position', 'name' => 'position', 'label' => 'Position', 'type' => 'select', 'placeholder' => 'All positions', 'options' => $positions->map(fn($p) => ['value' => $p->id, 'label' => $p->position_title])->all()],
            ['filter' => 'employment_status', 'name' => 'employment_status', 'label' => 'Employment Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect(['Active', 'Inactive', 'Onboarding', 'Offboarding'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'employment_type', 'name' => 'employment_type', 'label' => 'Employment Type', 'type' => 'select', 'placeholder' => 'All types', 'options' => $empTypes->map(fn($t) => ['value' => $t, 'label' => $t])->all()],
            ['filter' => 'nationality', 'name' => 'nationality', 'label' => 'Nationality', 'type' => 'select', 'placeholder' => 'All nationalities', 'options' => $nationalities->map(fn($n) => ['value' => $n, 'label' => $n])->all()],
            ['filter' => 'gender', 'name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'placeholder' => 'All genders', 'options' => [['value' => 'male', 'label' => 'Male'], ['value' => 'female', 'label' => 'Female']]],
            ['filter' => 'location', 'name' => 'location', 'label' => 'Location', 'type' => 'select', 'placeholder' => 'All locations', 'options' => $locations->map(fn($l) => ['value' => $l, 'label' => $l])->all()],
            ['filter' => 'separation_type', 'name' => 'separation_type', 'label' => 'Separation Type', 'type' => 'select', 'placeholder' => 'All separations', 'options' => collect(['Resigned', 'Terminated', 'Other'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'manager', 'name' => 'manager', 'label' => 'Reporting Manager', 'type' => 'select', 'placeholder' => 'All managers', 'options' => $managers->map(fn($m) => ['value' => $m->id, 'label' => $m->name])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'Employee Master Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.employee.run', 'exportRoute' => 'resort.report.employee.export', 'insightsRoute' => 'resort.report.employee.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect(['department', 'position', 'employment_status', 'employment_type', 'nationality', 'gender', 'location', 'separation_type', 'manager', 'employee', 'from_date', 'to_date'])
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

    /* --------------------------------------------------------------- helpers */

    private function applyDuration($q, array $f, string $col)
    {
        return $q->when($f['from_date'] ?? null, fn($x) => $x->whereDate($col, '>=', $f['from_date']))
                 ->when($f['to_date'] ?? null, fn($x) => $x->whereDate($col, '<=', $f['to_date']));
    }

    private function base(array $f)
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        return DB::table('employees as e')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->leftJoin('employees as mgr', 'mgr.id', '=', 'e.reporting_to')
            ->leftJoin('resort_admins as mra', 'mra.id', '=', 'mgr.Admin_Parent_id')
            ->where('e.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['position'] ?? null, fn($q) => $q->where('e.Position_id', $f['position']))
            ->when($f['employment_status'] ?? null, fn($q) => $q->where('e.status', $f['employment_status']))
            ->when($f['employment_type'] ?? null, fn($q) => $q->where('e.employment_type', $f['employment_type']))
            ->when($f['nationality'] ?? null, fn($q) => $q->where('e.nationality', $f['nationality']))
            ->when($f['gender'] ?? null, fn($q) => $q->where('ra.gender', $f['gender']))
            ->when($f['location'] ?? null, fn($q) => $q->where('e.location', $f['location']))
            ->when($f['manager'] ?? null, fn($q) => $q->where('e.reporting_to', $f['manager']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']))
            ->select(
                'e.id', 'e.Emp_id', 'e.nationality', 'e.location', 'e.dob', 'e.marital_status', 'e.joining_date',
                'e.employment_type', 'e.contract_type', 'e.status', 'e.confirmation_date', 'e.termination_date',
                'e.resign_effective_date', 'e.last_working_day', 'e.reporting_to',
                'ra.gender', 'ra.email as work_email', 'ra.personal_phone as mobile',
                DB::raw("TRIM(CONCAT_WS(' ', ra.first_name, ra.middle_name, ra.last_name)) as employee_name"),
                DB::raw("ra.first_name as preferred_name"),
                'd.name as dept', 'p.position_title as position',
                DB::raw("TRIM(CONCAT_WS(' ', mra.first_name, mra.last_name)) as manager_name")
            );
    }

    private function dmy($d): string { return $d ? Carbon::parse($d)->format('d M Y') : 'N/A'; }

    private function age($dob): string { return $dob ? (string) Carbon::parse($dob)->age : 'N/A'; }

    private function yearsOfService($join, $end = null): ?float
    {
        if (!$join) return null;
        $end = $end ? Carbon::parse($end) : Carbon::now();
        return round(Carbon::parse($join)->floatDiffInYears($end), 1);
    }

    private function separationType($r): string
    {
        if (!empty($r->termination_date)) return 'Terminated';
        if (!empty($r->resign_effective_date) || !empty($r->last_working_day)) return 'Resigned';
        return 'Other';
    }

    private function empType($r): string { return $r->employment_type ?: 'N/A'; }
    private function isLocal($nat): bool { return strtolower(trim((string) $nat)) === 'maldivian'; }

    /** Does a recurring (month/day) date fall within the [from,to] window? */
    private function inAnnualWindow($date, ?string $from, ?string $to): bool
    {
        if (!$date) return false;
        if (!$from && !$to) return true;
        $md = Carbon::parse($date)->format('m-d');
        $fromMd = $from ? Carbon::parse($from)->format('m-d') : '01-01';
        $toMd = $to ? Carbon::parse($to)->format('m-d') : '12-31';
        return $fromMd <= $toMd ? ($md >= $fromMd && $md <= $toMd) : ($md >= $fromMd || $md <= $toMd);
    }

    /* --------------------------------------------------------------- reports */

    public function master(array $f): array
    {
        $columns = ['Employee ID', 'Full Name', 'Preferred Name', 'Gender', 'Nationality', 'Date of Birth', 'Department', 'Position', 'Employment Type', 'Employment Status', 'Location', 'Date Joined', 'Confirmation Date', 'Reporting Manager', 'Work Email', 'Mobile Number'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->orderBy('ra.first_name')->get()
            ->map(fn($r) => [
                'Employee ID'       => $r->Emp_id ?: 'N/A',
                'Full Name'         => trim($r->employee_name) ?: 'N/A',
                'Preferred Name'    => trim($r->preferred_name) ?: 'N/A',
                'Gender'            => $r->gender ? ucfirst($r->gender) : 'N/A',
                'Nationality'       => $r->nationality ?: 'N/A',
                'Date of Birth'     => $this->dmy($r->dob),
                'Department'        => $r->dept ?: 'N/A',
                'Position'          => $r->position ?: 'N/A',
                'Employment Type'   => $this->empType($r),
                'Employment Status' => $r->status ?: 'N/A',
                'Location'          => $r->location ?: 'N/A',
                'Date Joined'       => $this->dmy($r->joining_date),
                'Confirmation Date' => $this->dmy($r->confirmation_date),
                'Reporting Manager' => trim($r->manager_name) ?: 'N/A',
                'Work Email'        => $r->work_email ?: 'N/A',
                'Mobile Number'     => $r->mobile ?: 'N/A',
            ])->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function activeRegister(array $f): array
    {
        $columns = ['Employee ID', 'Employee Name', 'Department', 'Position', 'Employment Type', 'Joining Date', 'Reporting Manager', 'Location'];
        $rows = $this->base($f)->where('e.status', 'Active')->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->orderBy('ra.first_name')->get()
            ->map(fn($r) => [
                'Employee ID'       => $r->Emp_id ?: 'N/A',
                'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                'Department'        => $r->dept ?: 'N/A',
                'Position'          => $r->position ?: 'N/A',
                'Employment Type'   => $this->empType($r),
                'Joining Date'      => $this->dmy($r->joining_date),
                'Reporting Manager' => trim($r->manager_name) ?: 'N/A',
                'Location'          => $r->location ?: 'N/A',
            ])->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function inactiveRegister(array $f): array
    {
        $columns = ['Employee ID', 'Employee Name', 'Department', 'Position', 'Last Working Date', 'Separation Type', 'Employment Duration'];
        $rows = $this->base($f)->whereIn('e.status', ['Inactive', 'Offboarding'])
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('e.last_working_day', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('e.last_working_day', '<=', $f['to_date']))
            ->orderByDesc('e.last_working_day')->get()
            ->map(fn($r) => [
                'Employee ID'         => $r->Emp_id ?: 'N/A',
                'Employee Name'       => trim($r->employee_name) ?: 'N/A',
                'Department'          => $r->dept ?: 'N/A',
                'Position'            => $r->position ?: 'N/A',
                'Last Working Date'   => $this->dmy($r->last_working_day ?: $r->resign_effective_date ?: $r->termination_date),
                'Separation Type'     => $this->separationType($r),
                'Employment Duration' => ($y = $this->yearsOfService($r->joining_date, $r->last_working_day ?: $r->resign_effective_date ?: $r->termination_date)) !== null ? $y . ' yr(s)' : 'N/A',
            ])->filter(fn($row) => !($f['separation_type'] ?? null) || $row['Separation Type'] === $f['separation_type'])->values()->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function newJoiners(array $f): array
    {
        $columns = ['Employee ID', 'Employee Name', 'Department', 'Position', 'Joining Date', 'Employment Type', 'Reporting Manager'];
        $rows = $this->base($f)->whereNotNull('e.joining_date')->whereDate('e.joining_date', '<=', Carbon::today())
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->orderByDesc('e.joining_date')->get()
            ->map(fn($r) => [
                'Employee ID'       => $r->Emp_id ?: 'N/A',
                'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                'Department'        => $r->dept ?: 'N/A',
                'Position'          => $r->position ?: 'N/A',
                'Joining Date'      => $this->dmy($r->joining_date),
                'Employment Type'   => $this->empType($r),
                'Reporting Manager' => trim($r->manager_name) ?: 'N/A',
            ])->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function upcomingJoiners(array $f): array
    {
        $columns = ['Employee Name', 'Position', 'Department', 'Expected Joining Date', 'Employment Type', 'Recruitment Source'];
        $rows = $this->base($f)->whereDate('e.joining_date', '>', Carbon::today())
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('e.joining_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('e.joining_date', '<=', $f['to_date']))
            ->orderBy('e.joining_date')->get()
            ->map(fn($r) => [
                'Employee Name'         => trim($r->employee_name) ?: 'N/A',
                'Position'              => $r->position ?: 'N/A',
                'Department'            => $r->dept ?: 'N/A',
                'Expected Joining Date' => $this->dmy($r->joining_date),
                'Employment Type'       => $this->empType($r),
                'Recruitment Source'    => 'N/A',
            ])->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function deptWise(array $f): array
    {
        $columns = ['Department', 'Employee ID', 'Employee Name', 'Position', 'Employment Status', 'Joining Date'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->orderBy('d.name')->orderBy('ra.first_name')->get()
            ->map(fn($r) => [
                'Department'        => $r->dept ?: 'N/A',
                'Employee ID'       => $r->Emp_id ?: 'N/A',
                'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                'Position'          => $r->position ?: 'N/A',
                'Employment Status' => $r->status ?: 'N/A',
                'Joining Date'      => $this->dmy($r->joining_date),
            ])->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function positionWise(array $f): array
    {
        $columns = ['Position', 'Employee ID', 'Employee Name', 'Department', 'Employment Status'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->orderBy('p.position_title')->orderBy('ra.first_name')->get()
            ->map(fn($r) => [
                'Position'          => $r->position ?: 'N/A',
                'Employee ID'       => $r->Emp_id ?: 'N/A',
                'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                'Department'        => $r->dept ?: 'N/A',
                'Employment Status' => $r->status ?: 'N/A',
            ])->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function locationWise(array $f): array
    {
        $columns = ['Location', 'Employee ID', 'Employee Name', 'Department', 'Position', 'Employment Status'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->orderBy('e.location')->orderBy('ra.first_name')->get()
            ->map(fn($r) => [
                'Location'          => $r->location ?: 'N/A',
                'Employee ID'       => $r->Emp_id ?: 'N/A',
                'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                'Department'        => $r->dept ?: 'N/A',
                'Position'          => $r->position ?: 'N/A',
                'Employment Status' => $r->status ?: 'N/A',
            ])->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function nationalityWise(array $f): array
    {
        $columns = ['Nationality', 'Employee ID', 'Employee Name', 'Department', 'Position', 'Joining Date'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->orderBy('e.nationality')->orderBy('ra.first_name')->get()
            ->map(fn($r) => [
                'Nationality'   => $r->nationality ?: 'N/A',
                'Employee ID'   => $r->Emp_id ?: 'N/A',
                'Employee Name' => trim($r->employee_name) ?: 'N/A',
                'Department'    => $r->dept ?: 'N/A',
                'Position'      => $r->position ?: 'N/A',
                'Joining Date'  => $this->dmy($r->joining_date),
            ])->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function localVsExpat(array $f): array
    {
        $columns = ['Employee Type', 'Employee ID', 'Employee Name', 'Nationality', 'Department', 'Position', 'Joining Date'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->orderBy('e.nationality')->get()
            ->map(fn($r) => [
                'Employee Type' => $this->isLocal($r->nationality) ? 'Local' : 'Expatriate',
                'Employee ID'   => $r->Emp_id ?: 'N/A',
                'Employee Name' => trim($r->employee_name) ?: 'N/A',
                'Nationality'   => $r->nationality ?: 'N/A',
                'Department'    => $r->dept ?: 'N/A',
                'Position'      => $r->position ?: 'N/A',
                'Joining Date'  => $this->dmy($r->joining_date),
            ])->sortBy('Employee Type')->values()->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function genderDiversity(array $f): array
    {
        $columns = ['Gender', 'Employee ID', 'Employee Name', 'Department', 'Position', 'Employment Status'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->orderBy('ra.gender')->orderBy('ra.first_name')->get()
            ->map(fn($r) => [
                'Gender'            => $r->gender ? ucfirst($r->gender) : 'N/A',
                'Employee ID'       => $r->Emp_id ?: 'N/A',
                'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                'Department'        => $r->dept ?: 'N/A',
                'Position'          => $r->position ?: 'N/A',
                'Employment Status' => $r->status ?: 'N/A',
            ])->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function employmentTypeReport(array $f): array
    {
        $columns = ['Employment Type', 'Employee ID', 'Employee Name', 'Department', 'Position', 'Joining Date'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->orderBy('e.employment_type')->orderBy('ra.first_name')->get()
            ->map(fn($r) => [
                'Employment Type' => $this->empType($r),
                'Employee ID'     => $r->Emp_id ?: 'N/A',
                'Employee Name'   => trim($r->employee_name) ?: 'N/A',
                'Department'      => $r->dept ?: 'N/A',
                'Position'        => $r->position ?: 'N/A',
                'Joining Date'    => $this->dmy($r->joining_date),
            ])->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function reportingManager(array $f): array
    {
        $columns = ['Reporting Manager', 'Employee ID', 'Employee Name', 'Department', 'Position'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->orderBy('manager_name')->orderBy('ra.first_name')->get()
            ->map(fn($r) => [
                'Reporting Manager' => trim($r->manager_name) ?: 'Unassigned',
                'Employee ID'       => $r->Emp_id ?: 'N/A',
                'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                'Department'        => $r->dept ?: 'N/A',
                'Position'          => $r->position ?: 'N/A',
            ])->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function contactDirectory(array $f): array
    {
        $columns = ['Employee ID', 'Employee Name', 'Position', 'Department', 'Extension', 'Mobile Number', 'Work Email'];
        $rows = $this->base($f)->orderBy('ra.first_name')->get()
            ->map(fn($r) => [
                'Employee ID'   => $r->Emp_id ?: 'N/A',
                'Employee Name' => trim($r->employee_name) ?: 'N/A',
                'Position'      => $r->position ?: 'N/A',
                'Department'    => $r->dept ?: 'N/A',
                'Extension'     => 'N/A',
                'Mobile Number' => $r->mobile ?: 'N/A',
                'Work Email'    => $r->work_email ?: 'N/A',
            ])->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function workforceDistribution(array $f): array
    {
        $columns = ['Department', 'Location', 'Gender', 'Nationality', 'Employment Type', 'Employee Count'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->get()
            ->groupBy(fn($r) => ($r->dept ?: 'N/A') . '|' . ($r->location ?: 'N/A') . '|' . ($r->gender ? ucfirst($r->gender) : 'N/A') . '|' . ($r->nationality ?: 'N/A') . '|' . $this->empType($r))
            ->map(function ($grp, $key) {
                [$dept, $loc, $gender, $nat, $type] = explode('|', $key);
                return ['Department' => $dept, 'Location' => $loc, 'Gender' => $gender, 'Nationality' => $nat, 'Employment Type' => $type, 'Employee Count' => $grp->count()];
            })->sortByDesc('Employee Count')->values()->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function demographics(array $f): array
    {
        $columns = ['Employee ID', 'Employee Name', 'Gender', 'Nationality', 'Date of Birth', 'Age', 'Marital Status', 'Department', 'Position'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->orderBy('ra.first_name')->get()
            ->map(fn($r) => [
                'Employee ID'    => $r->Emp_id ?: 'N/A',
                'Employee Name'  => trim($r->employee_name) ?: 'N/A',
                'Gender'         => $r->gender ? ucfirst($r->gender) : 'N/A',
                'Nationality'    => $r->nationality ?: 'N/A',
                'Date of Birth'  => $this->dmy($r->dob),
                'Age'            => $this->age($r->dob),
                'Marital Status' => $r->marital_status ?: 'N/A',
                'Department'     => $r->dept ?: 'N/A',
                'Position'       => $r->position ?: 'N/A',
            ])->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function birthday(array $f): array
    {
        $columns = ['Employee Name', 'Date of Birth', 'Department', 'Position'];
        $rows = $this->base($f)->whereNotNull('e.dob')->orderByRaw('MONTH(e.dob), DAY(e.dob)')->get()
            ->filter(fn($r) => $this->inAnnualWindow($r->dob, $f['from_date'] ?? null, $f['to_date'] ?? null))
            ->map(fn($r) => [
                'Employee Name' => trim($r->employee_name) ?: 'N/A',
                'Date of Birth' => $this->dmy($r->dob),
                'Department'    => $r->dept ?: 'N/A',
                'Position'      => $r->position ?: 'N/A',
            ])->values()->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function workAnniversary(array $f): array
    {
        $columns = ['Employee Name', 'Joining Date', 'Years of Service', 'Department', 'Position'];
        $rows = $this->base($f)->whereNotNull('e.joining_date')->orderByRaw('MONTH(e.joining_date), DAY(e.joining_date)')->get()
            ->filter(fn($r) => $this->inAnnualWindow($r->joining_date, $f['from_date'] ?? null, $f['to_date'] ?? null))
            ->map(fn($r) => [
                'Employee Name'    => trim($r->employee_name) ?: 'N/A',
                'Joining Date'     => $this->dmy($r->joining_date),
                'Years of Service' => ($y = $this->yearsOfService($r->joining_date)) !== null ? $y . ' yr(s)' : 'N/A',
                'Department'       => $r->dept ?: 'N/A',
                'Position'         => $r->position ?: 'N/A',
            ])->values()->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function lengthOfService(array $f): array
    {
        $columns = ['Employee Name', 'Joining Date', 'Years of Service', 'Department', 'Position'];
        $rows = $this->base($f)->whereNotNull('e.joining_date')->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->orderBy('e.joining_date')->get()
            ->map(fn($r) => [
                'Employee Name'    => trim($r->employee_name) ?: 'N/A',
                'Joining Date'     => $this->dmy($r->joining_date),
                'Years of Service' => ($y = $this->yearsOfService($r->joining_date)) !== null ? $y . ' yr(s)' : 'N/A',
                'Department'       => $r->dept ?: 'N/A',
                'Position'         => $r->position ?: 'N/A',
                '_sort'            => $this->yearsOfService($r->joining_date) ?? 0,
            ])->sortByDesc('_sort')->map(fn($row) => collect($row)->except('_sort')->all())->values()->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function execSummary(array $f): array
    {
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'e.joining_date'))->get();
        $today = Carbon::today();
        $local = $rows->filter(fn($r) => $this->isLocal($r->nationality))->count();
        return [
            'columns' => ['Total Employees', 'Active Employees', 'Inactive Employees', 'New Joiners', 'Upcoming Joiners', 'Local Employees', 'Expatriate Employees', 'Male Employees', 'Female Employees', 'Department Count', 'Location Count'],
            'rows' => [[
                'Total Employees'      => $rows->count(),
                'Active Employees'     => $rows->where('status', 'Active')->count(),
                'Inactive Employees'   => $rows->whereIn('status', ['Inactive', 'Offboarding'])->count(),
                'New Joiners'          => $rows->filter(fn($r) => $r->joining_date && Carbon::parse($r->joining_date)->lte($today) && Carbon::parse($r->joining_date)->gte($today->copy()->subDays(30)))->count(),
                'Upcoming Joiners'     => $rows->filter(fn($r) => $r->joining_date && Carbon::parse($r->joining_date)->gt($today))->count(),
                'Local Employees'      => $local,
                'Expatriate Employees' => $rows->count() - $local,
                'Male Employees'       => $rows->filter(fn($r) => strtolower((string) $r->gender) === 'male')->count(),
                'Female Employees'     => $rows->filter(fn($r) => strtolower((string) $r->gender) === 'female')->count(),
                'Department Count'     => $rows->pluck('dept')->filter()->unique()->count(),
                'Location Count'       => $rows->pluck('location')->filter()->unique()->count(),
            ]],
        ];
    }
}
