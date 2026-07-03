<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined People Management – Onboarding reports (Option B), generic view.
 *
 * Source: employee_itineraries (the onboarding itinerary per employee) — arrival
 * date/time, airport pickup (pickup_employee_id), medical centre + escort
 * (accompany_medical_employee_id), accommodation (hotel_name). Onboarding status
 * is derived from the arrival date (no dedicated status column on the itinerary).
 */
class OnboardingReportController extends Controller
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
            'employee_onboarding' => [
                'name' => 'Employee Onboarding Report',
                'description' => 'Scheduled joiners with their full onboarding itinerary and coordination details.',
                'filters' => ['duration', 'department', 'position', 'employee', 'onboarding_status'],
                'handler' => 'employeeOnboarding',
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

        $filterDefs = [
            ['filter' => 'department', 'name' => 'department', 'label' => 'Department', 'type' => 'select', 'placeholder' => 'All departments', 'options' => $departments->map(fn($d) => ['value' => $d->id, 'label' => $d->name])->all()],
            ['filter' => 'position', 'name' => 'position', 'label' => 'Position', 'type' => 'select', 'placeholder' => 'All positions', 'options' => $positions->map(fn($p) => ['value' => $p->id, 'label' => $p->position_title])->all()],
            ['filter' => 'employee', 'name' => 'employee', 'label' => 'Employee', 'type' => 'select', 'placeholder' => 'All employees', 'options' => $employees->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->all()],
            ['filter' => 'onboarding_status', 'name' => 'onboarding_status', 'label' => 'Onboarding Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect(['Scheduled', 'Arrived', 'Pending'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'Arrival From', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'Arrival To', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'Onboarding Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.onboarding.run', 'exportRoute' => 'resort.report.onboarding.export', 'insightsRoute' => 'resort.report.onboarding.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect(['department', 'position', 'employee', 'onboarding_status', 'from_date', 'to_date'])
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

    /* --------------------------------------------------------------- report */

    public function employeeOnboarding(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        $today = Carbon::today();

        $rows = DB::table('employee_itineraries as it')
            ->join('employees as e', 'e.id', '=', 'it.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->leftJoin('employees as pu', 'pu.id', '=', 'it.pickup_employee_id')
            ->leftJoin('resort_admins as pura', 'pura.id', '=', 'pu.Admin_Parent_id')
            ->leftJoin('employees as me', 'me.id', '=', 'it.accompany_medical_employee_id')
            ->leftJoin('resort_admins as mera', 'mera.id', '=', 'me.Admin_Parent_id')
            ->where('it.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['position'] ?? null, fn($q) => $q->where('e.Position_id', $f['position']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('it.arrival_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('it.arrival_date', '<=', $f['to_date']))
            ->orderBy('it.arrival_date')
            ->get([
                'e.Emp_id', 'e.nationality',
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept', 'p.position_title',
                'it.arrival_date', 'it.arrival_time', 'it.medical_center_name', 'it.hotel_name', 'it.greeting_message',
                DB::raw("TRIM(CONCAT(COALESCE(pura.first_name,''),' ',COALESCE(pura.last_name,''))) as pickup_name"),
                DB::raw("TRIM(CONCAT(COALESCE(mera.first_name,''),' ',COALESCE(mera.last_name,''))) as escort_name"),
            ])
            ->map(function ($r) use ($today) {
                $status = $r->arrival_date
                    ? (Carbon::parse($r->arrival_date)->gte($today) ? 'Scheduled' : 'Arrived')
                    : 'Pending';
                return [
                    'Employee ID'              => $r->Emp_id ?: 'N/A',
                    'Employee Name'            => trim($r->employee_name) ?: 'N/A',
                    'Position'                 => $r->position_title ?? 'N/A',
                    'Department'               => $r->dept ?? 'N/A',
                    'Nationality'              => $r->nationality ?: 'N/A',
                    'Arrival Date'             => $r->arrival_date ? Carbon::parse($r->arrival_date)->format('d M Y') : 'N/A',
                    'Arrival Time'             => $r->arrival_time ? Carbon::parse($r->arrival_time)->format('H:i') : 'N/A',
                    'Airport Pickup Assigned To' => trim($r->pickup_name) ?: 'N/A',
                    'Medical Center'           => $r->medical_center_name ?: 'N/A',
                    'Medical Escort'           => trim($r->escort_name) ?: 'N/A',
                    'Accommodation/Hotel Name' => $r->hotel_name ?: 'N/A',
                    'Onboarding Status'        => $status,
                    'Remarks'                  => trim((string) $r->greeting_message) ?: 'N/A',
                    '_status'                  => $status,
                ];
            })
            ->filter(fn($row) => !($f['onboarding_status'] ?? null) || $row['_status'] === $f['onboarding_status'])
            ->map(fn($row) => collect($row)->except('_status')->all())
            ->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Position', 'Department', 'Nationality', 'Arrival Date', 'Arrival Time', 'Airport Pickup Assigned To', 'Medical Center', 'Medical Escort', 'Accommodation/Hotel Name', 'Onboarding Status', 'Remarks'],
            'rows'    => $rows,
        ];
    }
}
