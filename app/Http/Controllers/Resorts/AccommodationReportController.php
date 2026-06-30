<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined Accommodation reports (Option B), generic view.
 * Rooms = available_accommodation_models; bed occupancy via assing_accommodations
 * (emp_id>0 = occupied). No dedicated amenities table -> inventory items are the
 * amenities. Maintenance response/SLA timestamps aren't stored -> N/A.
 */
class AccommodationReportController extends Controller
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
            'exec_summary'      => ['name' => 'Accommodation Executive Summary', 'description' => 'Occupancy, availability and maintenance overview.', 'filters' => [], 'handler' => 'execSummary'],
            'building_occupancy' => ['name' => 'Building Occupancy Report', 'description' => 'Occupancy per building.', 'filters' => ['building'], 'handler' => 'buildingOccupancy'],
            'floor_occupancy'   => ['name' => 'Floor Occupancy Report', 'description' => 'Occupancy per floor.', 'filters' => ['building'], 'handler' => 'floorOccupancy'],
            'room_occupancy'    => ['name' => 'Room Occupancy Report', 'description' => 'Occupancy per room.', 'filters' => ['building'], 'handler' => 'roomOccupancy'],
            'bed_occupancy'     => ['name' => 'Bed Occupancy Report', 'description' => 'Who occupies each bed.', 'filters' => ['building'], 'handler' => 'bedOccupancy'],
            'available'         => ['name' => 'Available Accommodation Report', 'description' => 'Vacant beds available.', 'filters' => ['gender'], 'handler' => 'availableAccommodation'],
            'occupied'          => ['name' => 'Occupied Accommodation Report', 'description' => 'Occupied beds and occupants.', 'filters' => ['building'], 'handler' => 'occupiedAccommodation'],
            'employee_register' => ['name' => 'Employee Accommodation Register', 'description' => 'Each employee\'s accommodation.', 'filters' => ['department'], 'handler' => 'employeeRegister'],
            'employee_history'  => ['name' => 'Employee Accommodation History', 'description' => 'Accommodation transfers per employee.', 'filters' => ['employee'], 'handler' => 'employeeHistory'],
            'roommate'          => ['name' => 'Roommate Report', 'description' => 'Who shares each room.', 'filters' => ['building'], 'handler' => 'roommate'],
            'allocation'        => ['name' => 'Accommodation Allocation Report', 'description' => 'Allocations over the period.', 'filters' => ['duration'], 'handler' => 'allocation'],
            'transfer'          => ['name' => 'Accommodation Transfer Report', 'description' => 'Room transfers.', 'filters' => ['duration'], 'handler' => 'transfer'],
            'gender_summary'    => ['name' => 'Gender Accommodation Summary', 'description' => 'Occupancy by gender.', 'filters' => ['building'], 'handler' => 'genderSummary'],
            'category_summary'  => ['name' => 'Employee Category Accommodation Report', 'description' => 'Occupancy by department.', 'filters' => [], 'handler' => 'categorySummary'],
            'capacity'          => ['name' => 'Accommodation Capacity Report', 'description' => 'Capacity utilisation per building.', 'filters' => ['building'], 'handler' => 'capacity'],
            'vacancy'           => ['name' => 'Accommodation Vacancy Report', 'description' => 'Vacant beds.', 'filters' => ['building'], 'handler' => 'vacancy'],
            'amenities_register' => ['name' => 'Amenities Register', 'description' => 'Amenities (inventory) per room.', 'filters' => ['building'], 'handler' => 'amenitiesRegister'],
            'amenities_utilization' => ['name' => 'Amenities Utilization Report', 'description' => 'Amenity assignment.', 'filters' => [], 'handler' => 'amenitiesUtilization'],
            'inventory_register' => ['name' => 'Inventory Register', 'description' => 'Inventory items per room.', 'filters' => ['building'], 'handler' => 'inventoryRegister'],
            'inventory_assignment' => ['name' => 'Inventory Assignment Report', 'description' => 'Inventory assigned to employees.', 'filters' => ['employee'], 'handler' => 'inventoryAssignment'],
            'available_inventory' => ['name' => 'Available Inventory Report', 'description' => 'Unassigned inventory stock.', 'filters' => [], 'handler' => 'availableInventory'],
            'maintenance_register' => ['name' => 'Maintenance Request Register', 'description' => 'All maintenance requests.', 'filters' => ['duration', 'status'], 'handler' => 'maintenanceRegister'],
            'open_maintenance'  => ['name' => 'Open Maintenance Report', 'description' => 'Open maintenance requests.', 'filters' => ['priority'], 'handler' => 'openMaintenance'],
            'high_priority_maintenance' => ['name' => 'High Priority Maintenance Report', 'description' => 'High-priority requests.', 'filters' => ['duration'], 'handler' => 'highPriorityMaintenance'],
            'pending_maintenance' => ['name' => 'Pending Maintenance Report', 'description' => 'Pending requests and ageing.', 'filters' => ['duration'], 'handler' => 'pendingMaintenance'],
            'maintenance_sla'   => ['name' => 'Maintenance SLA Report', 'description' => 'Response/resolution vs SLA.', 'filters' => ['duration'], 'handler' => 'maintenanceSla'],
            'maintenance_ageing' => ['name' => 'Maintenance Ageing Report', 'description' => 'How long requests stay open.', 'filters' => ['duration'], 'handler' => 'maintenanceAgeing'],
            'maintenance_hold'  => ['name' => 'Maintenance Hold Report', 'description' => 'Requests on hold.', 'filters' => ['duration'], 'handler' => 'maintenanceHold'],
            'maintenance_completion' => ['name' => 'Maintenance Completion Report', 'description' => 'Completed maintenance.', 'filters' => ['duration'], 'handler' => 'maintenanceCompletion'],
            'event_calendar'    => ['name' => 'Event Calendar Report', 'description' => 'Accommodation events.', 'filters' => ['month'], 'handler' => 'eventCalendar'],
            'building_event'    => ['name' => 'Building Event Report', 'description' => 'Events by building.', 'filters' => [], 'handler' => 'buildingEvent'],
            'cleaning_schedule' => ['name' => 'Cleaning Schedule Report', 'description' => 'Housekeeping schedule.', 'filters' => ['building'], 'handler' => 'cleaningSchedule'],
        ];
    }

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) return abort(403, 'Unauthorized access');
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        $reports = collect($this->registry())->map(fn($r, $key) => ['key' => $key, 'name' => $r['name'], 'description' => $r['description'], 'filters' => $r['filters']])->values();

        $buildings = DB::table('available_accommodation_models')->where('resort_id', $rid)->whereNotNull('BuildingName')->distinct()->orderBy('BuildingName')->pluck('BuildingName');
        $departments = DB::table('resort_departments')->where('resort_id', $rid)->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))->orderBy('name')->get(['id', 'name']);
        $employees = DB::table('employees as e')->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')->where('e.resort_id', $rid)->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))->orderBy('ra.first_name')->get(['e.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);

        $filterDefs = [
            ['filter' => 'building', 'name' => 'building', 'label' => 'Building', 'type' => 'select', 'placeholder' => 'All buildings', 'options' => $buildings->map(fn($b) => ['value' => $b, 'label' => $b])->all()],
            ['filter' => 'department', 'name' => 'department', 'label' => 'Department', 'type' => 'select', 'placeholder' => 'All departments', 'options' => $departments->map(fn($d) => ['value' => $d->id, 'label' => $d->name])->all()],
            ['filter' => 'employee', 'name' => 'employee', 'label' => 'Employee', 'type' => 'select', 'placeholder' => 'All employees', 'options' => $employees->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->all()],
            ['filter' => 'gender', 'name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'placeholder' => 'All', 'options' => [['value' => 'Male', 'label' => 'Male'], ['value' => 'Female', 'label' => 'Female']]],
            ['filter' => 'status', 'name' => 'status', 'label' => 'Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect(['Open', 'pending', 'On-Hold', 'In-Progress', 'Assigned', 'Closed'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'priority', 'name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'placeholder' => 'All priorities', 'options' => collect(['High', 'Medium', 'Low'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'month', 'name' => 'month', 'label' => 'Month', 'type' => 'select', 'placeholder' => 'All months', 'options' => collect(range(1, 12))->map(fn($m) => ['value' => $m, 'label' => Carbon::create()->month($m)->format('F')])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'Accommodation Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.accommodation.run', 'exportRoute' => 'resort.report.accommodation.export', 'insightsRoute' => 'resort.report.accommodation.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect(['building', 'department', 'employee', 'gender', 'status', 'priority', 'month', 'from_date', 'to_date'])
            ->mapWithKeys(fn($k) => [$k => $request->input($k) ?: null])->all();
    }

    private function compute(string $key, array $filters): ?array
    {
        $registry = $this->registry();
        if (!isset($registry[$key])) return null;
        $res = $this->{$registry[$key]['handler']}($filters);
        return ['name' => $registry[$key]['name'], 'description' => $registry[$key]['description'], 'columns' => $res['columns'], 'rows' => $this->appendTotalsRow($res['columns'], $res['rows'])];
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

    /* helpers */
    private function dmy($d): string { return $d ? Carbon::parse($d)->format('d M Y') : 'N/A'; }
    private function pct($n, $d): string { return $d ? round($n / $d * 100, 1) . '%' : '0%'; }
    private $occSub = "(SELECT COUNT(*) FROM assing_accommodations a WHERE a.available_a_id = aam.id AND a.emp_id > 0)";

    private function roomsBase(array $f)
    {
        $rid = $this->resort->resort_id;
        return DB::table('available_accommodation_models as aam')->where('aam.resort_id', $rid)
            ->when($f['building'] ?? null, fn($q) => $q->where('aam.BuildingName', $f['building']))
            ->when($f['gender'] ?? null, fn($q) => $q->where('aam.blockFor', $f['gender']));
    }

    /** maintanace_requests joined to building + item + raiser + assignee. */
    private function maintBase(array $f)
    {
        $rid = $this->resort->resort_id;
        return DB::table('maintanace_requests as m')
            ->leftJoin('building_models as b', 'b.id', '=', 'm.building_id')
            ->leftJoin('inventory_modules as inv', 'inv.id', '=', 'm.item_id')
            ->leftJoin('employees as re', 're.id', '=', 'm.Raised_By')
            ->leftJoin('resort_admins as rra', 'rra.id', '=', 're.Admin_Parent_id')
            ->leftJoin('employees as ae', 'ae.id', '=', 'm.Assigned_To')
            ->leftJoin('resort_admins as ara', 'ara.id', '=', 'ae.Admin_Parent_id')
            ->where('m.resort_id', $rid)
            ->when($f['status'] ?? null, fn($q) => $q->where('m.Status', $f['status']))
            ->when($f['priority'] ?? null, fn($q) => $q->where('m.priority', $f['priority']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('m.date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('m.date', '<=', $f['to_date']));
    }
    private $raiser = "TRIM(CONCAT(COALESCE(rra.first_name,''),' ',COALESCE(rra.last_name,''))) as raised_by";
    private $assignee = "TRIM(CONCAT(COALESCE(ara.first_name,''),' ',COALESCE(ara.last_name,''))) as assigned_to";

    /* reports */

    public function execSummary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $buildings = DB::table('available_accommodation_models')->where('resort_id', $rid)->distinct('BuildingName')->count('BuildingName');
        $rooms = DB::table('available_accommodation_models')->where('resort_id', $rid)->count();
        $beds = (int) DB::table('available_accommodation_models')->where('resort_id', $rid)->sum('Capacity');
        $occupied = DB::table('assing_accommodations')->where('resort_id', $rid)->where('emp_id', '>', 0)->count();
        $openM = DB::table('maintanace_requests')->where('resort_id', $rid)->where('Status', 'not like', '%clos%')->count();
        return ['columns' => ['Total Buildings', 'Total Rooms', 'Total Beds', 'Occupied Beds', 'Available Beds', 'Occupancy Rate', 'Open Maintenance Requests'],
            'rows' => [['Total Buildings' => $buildings, 'Total Rooms' => $rooms, 'Total Beds' => $beds, 'Occupied Beds' => $occupied, 'Available Beds' => max(0, $beds - $occupied), 'Occupancy Rate' => $this->pct($occupied, $beds), 'Open Maintenance Requests' => $openM]]];
    }

    public function buildingOccupancy(array $f): array
    {
        $rows = $this->roomsBase($f)
            ->groupBy('aam.BuildingName', 'aam.blockFor')
            ->select('aam.BuildingName', 'aam.blockFor', DB::raw('COUNT(*) rooms'), DB::raw('SUM(aam.Capacity) beds'),
                DB::raw("SUM(CASE WHEN aam.RoomStatus='Occupied' THEN 1 ELSE 0 END) occ_rooms"),
                DB::raw("SUM($this->occSub) occ_beds"))
            ->orderBy('aam.BuildingName')->get()
            ->map(fn($r) => [
                'Building Name' => $r->BuildingName ?? 'N/A', 'Employee Category' => 'N/A', 'Gender' => $r->blockFor ?? 'N/A',
                'Total Rooms' => (int) $r->rooms, 'Occupied Rooms' => (int) $r->occ_rooms, 'Available Rooms' => (int) $r->rooms - (int) $r->occ_rooms,
                'Total Beds' => (int) $r->beds, 'Occupied Beds' => (int) $r->occ_beds, 'Available Beds' => max(0, (int) $r->beds - (int) $r->occ_beds),
            ])->all();
        return ['columns' => ['Building Name', 'Employee Category', 'Gender', 'Total Rooms', 'Occupied Rooms', 'Available Rooms', 'Total Beds', 'Occupied Beds', 'Available Beds'], 'rows' => $rows];
    }

    public function floorOccupancy(array $f): array
    {
        $rows = $this->roomsBase($f)->groupBy('aam.BuildingName', 'aam.Floor')
            ->select('aam.BuildingName', 'aam.Floor', DB::raw('COUNT(*) rooms'), DB::raw("SUM(CASE WHEN aam.RoomStatus='Occupied' THEN 1 ELSE 0 END) occ"))
            ->orderBy('aam.BuildingName')->orderBy('aam.Floor')->get()
            ->map(fn($r) => [
                'Building' => $r->BuildingName ?? 'N/A', 'Floor' => $r->Floor, 'Total Rooms' => (int) $r->rooms,
                'Occupied Rooms' => (int) $r->occ, 'Available Rooms' => (int) $r->rooms - (int) $r->occ,
                'Occupancy Percentage' => $this->pct($r->occ, $r->rooms),
            ])->all();
        return ['columns' => ['Building', 'Floor', 'Total Rooms', 'Occupied Rooms', 'Available Rooms', 'Occupancy Percentage'], 'rows' => $rows];
    }

    public function roomOccupancy(array $f): array
    {
        $rows = $this->roomsBase($f)->orderBy('aam.BuildingName')->orderBy('aam.Floor')->orderBy('aam.RoomNo')
            ->select('aam.id', 'aam.BuildingName', 'aam.Floor', 'aam.RoomNo', 'aam.RoomType', 'aam.Capacity', DB::raw("$this->occSub as occ"))->get()
            ->map(fn($r) => [
                'Building' => $r->BuildingName ?? 'N/A', 'Floor' => $r->Floor, 'Room Number' => $r->RoomNo ?? 'N/A',
                'Room Type' => $r->RoomType ?? 'N/A', 'Bed Capacity' => (int) $r->Capacity, 'Occupied Beds' => (int) $r->occ,
                'Available Beds' => max(0, (int) $r->Capacity - (int) $r->occ),
            ])->all();
        return ['columns' => ['Building', 'Floor', 'Room Number', 'Room Type', 'Bed Capacity', 'Occupied Beds', 'Available Beds'], 'rows' => $rows];
    }

    public function bedOccupancy(array $f): array
    {
        $rows = DB::table('assing_accommodations as a')
            ->join('available_accommodation_models as aam', 'aam.id', '=', 'a.available_a_id')
            ->leftJoin('employees as e', 'e.id', '=', 'a.emp_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('a.resort_id', $this->resort->resort_id)
            ->when($f['building'], fn($q) => $q->where('aam.BuildingName', $f['building']))
            ->orderBy('aam.BuildingName')->orderBy('aam.RoomNo')
            ->get(['aam.BuildingName', 'aam.Floor', 'aam.RoomNo', 'a.BedNo', 'a.emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as emp_name")])
            ->map(fn($r) => [
                'Building' => $r->BuildingName ?? 'N/A', 'Floor' => $r->Floor, 'Room Number' => $r->RoomNo ?? 'N/A',
                'Bed Number' => $r->BedNo ?? 'N/A', 'Employee Name' => $r->emp_id > 0 ? (trim($r->emp_name) ?: 'N/A') : '—',
                'Occupancy Status' => $r->emp_id > 0 ? 'Occupied' : 'Vacant',
            ])->all();
        return ['columns' => ['Building', 'Floor', 'Room Number', 'Bed Number', 'Employee Name', 'Occupancy Status'], 'rows' => $rows];
    }

    public function availableAccommodation(array $f): array
    {
        $rows = $this->roomsBase($f)->where('aam.RoomStatus', 'Available')->orderBy('aam.BuildingName')
            ->get(['aam.BuildingName', 'aam.Floor', 'aam.RoomNo', 'aam.RoomType', 'aam.blockFor', 'aam.Capacity', DB::raw("$this->occSub as occ")])
            ->map(fn($r) => [
                'Building' => $r->BuildingName ?? 'N/A', 'Floor' => $r->Floor, 'Room' => $r->RoomNo ?? 'N/A',
                'Bed Number' => max(0, (int) $r->Capacity - (int) $r->occ) . ' free', 'Room Type' => $r->RoomType ?? 'N/A',
                'Employee Category' => $r->blockFor ?? 'N/A', 'Amenities' => 'See inventory',
            ])->all();
        return ['columns' => ['Building', 'Floor', 'Room', 'Bed Number', 'Room Type', 'Employee Category', 'Amenities'], 'rows' => $rows];
    }

    private function occupantBase(array $f)
    {
        return DB::table('assing_accommodations as a')
            ->join('available_accommodation_models as aam', 'aam.id', '=', 'a.available_a_id')
            ->join('employees as e', 'e.id', '=', 'a.emp_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->where('a.resort_id', $this->resort->resort_id)->where('a.emp_id', '>', 0)
            ->when($f['building'] ?? null, fn($q) => $q->where('aam.BuildingName', $f['building']))
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']));
    }
    private $empName = "TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as emp_name";

    public function occupiedAccommodation(array $f): array
    {
        $rows = $this->occupantBase($f)->orderBy('aam.BuildingName')->orderBy('aam.RoomNo')
            ->get(['aam.BuildingName', 'aam.RoomNo', 'a.BedNo', 'd.name as dept', 'p.position_title', 'a.effected_date', DB::raw($this->empName)])
            ->map(fn($r) => [
                'Building' => $r->BuildingName ?? 'N/A', 'Room Number' => $r->RoomNo ?? 'N/A', 'Bed Number' => $r->BedNo ?? 'N/A',
                'Employee Name' => trim($r->emp_name) ?: 'N/A', 'Department' => $r->dept ?? 'N/A', 'Position' => $r->position_title ?? 'N/A',
                'Check-in Date' => $this->dmy($r->effected_date),
            ])->all();
        return ['columns' => ['Building', 'Room Number', 'Bed Number', 'Employee Name', 'Department', 'Position', 'Check-in Date'], 'rows' => $rows];
    }

    public function employeeRegister(array $f): array
    {
        $rows = $this->occupantBase($f)->orderBy('ra.first_name')
            ->get(['e.Emp_id', DB::raw($this->empName), 'd.name as dept', 'p.position_title', 'aam.BuildingName', 'aam.Floor', 'aam.RoomNo', 'a.BedNo', 'aam.RoomType', 'a.effected_date'])
            ->map(fn($r) => [
                'Employee ID' => $r->Emp_id, 'Employee Name' => trim($r->emp_name) ?: 'N/A', 'Department' => $r->dept ?? 'N/A',
                'Position' => $r->position_title ?? 'N/A', 'Building' => $r->BuildingName ?? 'N/A', 'Floor' => $r->Floor,
                'Room' => $r->RoomNo ?? 'N/A', 'Bed' => $r->BedNo ?? 'N/A', 'Room Type' => $r->RoomType ?? 'N/A', 'Check-in Date' => $this->dmy($r->effected_date),
            ])->all();
        return ['columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Building', 'Floor', 'Room', 'Bed', 'Room Type', 'Check-in Date'], 'rows' => $rows];
    }

    public function employeeHistory(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('transfer_accommodations as t')
            ->leftJoin('employees as e', 'e.id', '=', 't.Emp_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('available_accommodation_models as oldr', 'oldr.id', '=', 't.OldAccommodation_id')
            ->leftJoin('available_accommodation_models as newr', 'newr.id', '=', 't.NewAccommodation_id')
            ->where('t.resort_id', $rid)->when($f['employee'], fn($q) => $q->where('t.Emp_id', $f['employee']))
            ->orderByDesc('t.NewdDate')
            ->get([DB::raw($this->empName), 'oldr.BuildingName as ob', 'oldr.RoomNo as orm', 'newr.BuildingName as nb', 'newr.RoomNo as nrm', 't.NewdDate'])
            ->map(fn($r) => [
                'Employee Name' => trim($r->emp_name) ?: 'N/A', 'Previous Building' => $r->ob ?? 'N/A', 'Previous Room' => $r->orm ?? 'N/A',
                'Current Building' => $r->nb ?? 'N/A', 'Current Room' => $r->nrm ?? 'N/A', 'Transfer Date' => $this->dmy($r->NewdDate),
            ])->all();
        return ['columns' => ['Employee Name', 'Previous Building', 'Previous Room', 'Current Building', 'Current Room', 'Transfer Date'], 'rows' => $rows];
    }

    public function roommate(array $f): array
    {
        $rows = $this->occupantBase($f)->orderBy('aam.BuildingName')->orderBy('aam.RoomNo')->orderBy('ra.first_name')
            ->get(['aam.BuildingName', 'aam.RoomNo', 'd.name as dept', 'p.position_title', DB::raw($this->empName)])
            ->map(fn($r) => [
                'Building' => $r->BuildingName ?? 'N/A', 'Room Number' => $r->RoomNo ?? 'N/A',
                'Employee Name' => trim($r->emp_name) ?: 'N/A', 'Department' => $r->dept ?? 'N/A', 'Position' => $r->position_title ?? 'N/A',
            ])->all();
        return ['columns' => ['Building', 'Room Number', 'Employee Name', 'Department', 'Position'], 'rows' => $rows];
    }

    public function allocation(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('assing_accommodations as a')
            ->join('available_accommodation_models as aam', 'aam.id', '=', 'a.available_a_id')
            ->join('employees as e', 'e.id', '=', 'a.emp_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('a.resort_id', $rid)->where('a.emp_id', '>', 0)
            ->when($f['from_date'], fn($q) => $q->whereDate('a.effected_date', '>=', $f['from_date']))
            ->when($f['to_date'], fn($q) => $q->whereDate('a.effected_date', '<=', $f['to_date']))
            ->orderByDesc('a.effected_date')
            ->get([DB::raw($this->empName), 'a.effected_date', 'aam.BuildingName', 'aam.RoomNo', 'a.BedNo'])
            ->map(fn($r) => [
                'Employee Name' => trim($r->emp_name) ?: 'N/A', 'Allocation Date' => $this->dmy($r->effected_date),
                'Building' => $r->BuildingName ?? 'N/A', 'Room' => $r->RoomNo ?? 'N/A', 'Bed' => $r->BedNo ?? 'N/A', 'Allocated By' => 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Allocation Date', 'Building', 'Room', 'Bed', 'Allocated By'], 'rows' => $rows];
    }

    public function transfer(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('transfer_accommodations as t')
            ->leftJoin('employees as e', 'e.id', '=', 't.Emp_id')->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('available_accommodation_models as oldr', 'oldr.id', '=', 't.OldAccommodation_id')
            ->leftJoin('available_accommodation_models as newr', 'newr.id', '=', 't.NewAccommodation_id')
            ->where('t.resort_id', $rid)
            ->when($f['from_date'], fn($q) => $q->whereDate('t.NewdDate', '>=', $f['from_date']))
            ->when($f['to_date'], fn($q) => $q->whereDate('t.NewdDate', '<=', $f['to_date']))
            ->orderByDesc('t.NewdDate')
            ->get([DB::raw($this->empName), 'oldr.RoomNo as orm', 'newr.RoomNo as nrm', 't.NewdDate', 't.Reason'])
            ->map(fn($r) => [
                'Employee Name' => trim($r->emp_name) ?: 'N/A', 'Previous Room' => $r->orm ?? 'N/A', 'New Room' => $r->nrm ?? 'N/A',
                'Transfer Date' => $this->dmy($r->NewdDate), 'Reason' => $r->Reason ?? 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Previous Room', 'New Room', 'Transfer Date', 'Reason'], 'rows' => $rows];
    }

    public function genderSummary(array $f): array
    {
        $rows = $this->roomsBase($f)->groupBy('aam.blockFor')
            ->select('aam.blockFor', DB::raw('SUM(aam.Capacity) beds'), DB::raw("SUM($this->occSub) occ"))
            ->get()->map(fn($r) => [
                'Gender' => $r->blockFor ?? 'N/A', 'Total Employees' => (int) $r->occ,
                'Occupied Beds' => (int) $r->occ, 'Available Beds' => max(0, (int) $r->beds - (int) $r->occ),
            ])->all();
        return ['columns' => ['Gender', 'Total Employees', 'Occupied Beds', 'Available Beds'], 'rows' => $rows];
    }

    public function categorySummary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('assing_accommodations as a')->join('employees as e', 'e.id', '=', 'a.emp_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->where('a.resort_id', $rid)->where('a.emp_id', '>', 0)
            ->groupBy('d.name')->select('d.name as cat', DB::raw('COUNT(*) occ'))->orderBy('d.name')->get()
            ->map(fn($r) => ['Category' => $r->cat ?? 'N/A', 'Total Beds' => (int) $r->occ, 'Occupied Beds' => (int) $r->occ, 'Available Beds' => 'N/A'])->all();
        return ['columns' => ['Category', 'Total Beds', 'Occupied Beds', 'Available Beds'], 'rows' => $rows];
    }

    public function capacity(array $f): array
    {
        $rows = $this->roomsBase($f)->groupBy('aam.BuildingName')
            ->select('aam.BuildingName', DB::raw('SUM(aam.Capacity) cap'), DB::raw("SUM($this->occSub) occ"))
            ->orderBy('aam.BuildingName')->get()
            ->map(fn($r) => [
                'Building' => $r->BuildingName ?? 'N/A', 'Total Capacity' => (int) $r->cap, 'Occupied Capacity' => (int) $r->occ,
                'Available Capacity' => max(0, (int) $r->cap - (int) $r->occ), 'Utilization %' => $this->pct($r->occ, $r->cap),
            ])->all();
        return ['columns' => ['Building', 'Total Capacity', 'Occupied Capacity', 'Available Capacity', 'Utilization %'], 'rows' => $rows];
    }

    public function vacancy(array $f): array
    {
        $rows = $this->roomsBase($f)->whereRaw("$this->occSub < aam.Capacity")->orderBy('aam.BuildingName')
            ->get(['aam.BuildingName', 'aam.Floor', 'aam.RoomNo', 'aam.RoomType', 'aam.Capacity', DB::raw("$this->occSub as occ")])
            ->map(fn($r) => [
                'Building' => $r->BuildingName ?? 'N/A', 'Floor' => $r->Floor, 'Room Number' => $r->RoomNo ?? 'N/A',
                'Bed Number' => max(0, (int) $r->Capacity - (int) $r->occ) . ' free', 'Room Type' => $r->RoomType ?? 'N/A',
            ])->all();
        return ['columns' => ['Building', 'Floor', 'Room Number', 'Bed Number', 'Room Type'], 'rows' => $rows];
    }

    private function amenityBase(array $f)
    {
        $rid = $this->resort->resort_id;
        return DB::table('available_accommodation_inv_items as ai')
            ->join('inventory_modules as inv', 'inv.id', '=', 'ai.Item_id')
            ->join('available_accommodation_models as aam', 'aam.id', '=', 'ai.Available_Acc_id')
            ->where('aam.resort_id', $rid)
            ->when($f['building'] ?? null, fn($q) => $q->where('aam.BuildingName', $f['building']));
    }

    public function amenitiesRegister(array $f): array
    {
        $rows = $this->amenityBase($f)->orderBy('inv.ItemName')
            ->get(['inv.ItemName', 'aam.BuildingName', 'aam.Floor', 'aam.RoomNo', 'aam.RoomStatus', DB::raw("$this->occSub as occ")])
            ->map(fn($r) => [
                'Amenity Name' => $r->ItemName ?? 'N/A', 'Building' => $r->BuildingName ?? 'N/A', 'Floor' => $r->Floor,
                'Room' => $r->RoomNo ?? 'N/A', 'Assigned Employee' => $r->occ > 0 ? 'Occupied room' : 'Vacant room', 'Status' => $r->RoomStatus ?? 'N/A',
            ])->all();
        return ['columns' => ['Amenity Name', 'Building', 'Floor', 'Room', 'Assigned Employee', 'Status'], 'rows' => $rows];
    }

    public function amenitiesUtilization(array $f): array
    {
        $rows = $this->amenityBase($f)->orderBy('inv.ItemName')
            ->get(['inv.ItemName', 'aam.RoomNo', 'aam.created_at', 'aam.RoomStatus'])
            ->map(fn($r) => ['Amenity Name' => $r->ItemName ?? 'N/A', 'Assigned To' => 'Room ' . ($r->RoomNo ?? 'N/A'), 'Assignment Date' => $this->dmy($r->created_at), 'Status' => $r->RoomStatus ?? 'N/A'])->all();
        return ['columns' => ['Amenity Name', 'Assigned To', 'Assignment Date', 'Status'], 'rows' => $rows];
    }

    public function inventoryRegister(array $f): array
    {
        $rows = $this->amenityBase($f)->orderBy('inv.ItemName')
            ->get(['ai.id', 'inv.ItemCode', 'inv.ItemName', 'aam.BuildingName', 'aam.Floor', 'aam.RoomNo', 'aam.created_at', 'aam.RoomStatus', DB::raw("$this->occSub as occ")])
            ->map(fn($r) => [
                'Inventory ID' => $r->ItemCode ?: ('INV-' . $r->id), 'Item Name' => $r->ItemName ?? 'N/A', 'Building' => $r->BuildingName ?? 'N/A',
                'Floor' => $r->Floor, 'Room' => $r->RoomNo ?? 'N/A', 'Assigned Employee' => $r->occ > 0 ? 'Occupied room' : '—',
                'Assignment Date' => $this->dmy($r->created_at), 'Status' => $r->RoomStatus ?? 'N/A',
            ])->all();
        return ['columns' => ['Inventory ID', 'Item Name', 'Building', 'Floor', 'Room', 'Assigned Employee', 'Assignment Date', 'Status'], 'rows' => $rows];
    }

    public function inventoryAssignment(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('available_accommodation_inv_items as ai')
            ->join('inventory_modules as inv', 'inv.id', '=', 'ai.Item_id')
            ->join('available_accommodation_models as aam', 'aam.id', '=', 'ai.Available_Acc_id')
            ->join('assing_accommodations as a', function ($j) { $j->on('a.available_a_id', '=', 'aam.id')->where('a.emp_id', '>', 0); })
            ->join('employees as e', 'e.id', '=', 'a.emp_id')->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('aam.resort_id', $rid)->when($f['employee'], fn($q) => $q->where('e.id', $f['employee']))
            ->orderBy('ra.first_name')
            ->get([DB::raw($this->empName), 'inv.ItemName', 'ai.quantity', 'aam.created_at', 'aam.RoomStatus'])
            ->map(fn($r) => [
                'Employee Name' => trim($r->emp_name) ?: 'N/A', 'Item Name' => $r->ItemName ?? 'N/A', 'Quantity' => (int) $r->quantity,
                'Assignment Date' => $this->dmy($r->created_at), 'Current Status' => $r->RoomStatus ?? 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Item Name', 'Quantity', 'Assignment Date', 'Current Status'], 'rows' => $rows];
    }

    public function availableInventory(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('inventory_modules')->where('resort_id', $rid)->orderBy('ItemName')
            ->get(['ItemName', 'Quantity', 'Occupied'])
            ->map(fn($r) => ['Item Name' => $r->ItemName ?? 'N/A', 'Quantity Available' => max(0, (int) $r->Quantity - (int) $r->Occupied), 'Building' => 'Store', 'Room' => 'N/A'])->all();
        return ['columns' => ['Item Name', 'Quantity Available', 'Building', 'Room'], 'rows' => $rows];
    }

    public function maintenanceRegister(array $f): array
    {
        $rows = $this->maintBase($f)->orderByDesc('m.date')
            ->get(['m.Request_id', DB::raw($this->raiser), 'b.BuildingName', 'm.FloorNo', 'm.RoomNo', 'inv.ItemName', 'm.priority', DB::raw($this->assignee), 'm.Status'])
            ->map(fn($r) => [
                'Request No.' => $r->Request_id ?? 'N/A', 'Requested By' => trim($r->raised_by) ?: 'N/A', 'Building' => $r->BuildingName ?? 'N/A',
                'Floor' => $r->FloorNo ?? 'N/A', 'Room' => $r->RoomNo ?? 'N/A', 'Affected Amenity' => $r->ItemName ?? 'N/A',
                'Priority' => $r->priority ?? 'N/A', 'Assigned To' => trim($r->assigned_to) ?: 'N/A', 'Status' => $r->Status ?? 'N/A',
            ])->all();
        return ['columns' => ['Request No.', 'Requested By', 'Building', 'Floor', 'Room', 'Affected Amenity', 'Priority', 'Assigned To', 'Status'], 'rows' => $rows];
    }

    public function openMaintenance(array $f): array
    {
        $rows = $this->maintBase($f)->where('m.Status', 'not like', '%clos%')->orderBy('m.date')
            ->get(['m.Request_id', 'b.BuildingName', 'm.RoomNo', 'm.priority', 'm.date', DB::raw($this->assignee)])
            ->map(fn($r) => [
                'Request No.' => $r->Request_id ?? 'N/A', 'Building' => $r->BuildingName ?? 'N/A', 'Room' => $r->RoomNo ?? 'N/A',
                'Priority' => $r->priority ?? 'N/A', 'Assigned To' => trim($r->assigned_to) ?: 'N/A',
                'Days Open' => $r->date ? Carbon::parse($r->date)->diffInDays(Carbon::today()) : 'N/A',
            ])->all();
        return ['columns' => ['Request No.', 'Building', 'Room', 'Priority', 'Assigned To', 'Days Open'], 'rows' => $rows];
    }

    public function highPriorityMaintenance(array $f): array
    {
        $rows = $this->maintBase(array_merge($f, ['priority' => 'High']))->orderByDesc('m.date')
            ->get(['m.Request_id', 'b.BuildingName', 'm.RoomNo', 'm.descriptionIssues', 'm.priority', DB::raw($this->assignee)])
            ->map(fn($r) => [
                'Request No.' => $r->Request_id ?? 'N/A', 'Building' => $r->BuildingName ?? 'N/A', 'Room' => $r->RoomNo ?? 'N/A',
                'Issue Description' => $r->descriptionIssues ?? 'N/A', 'Priority' => $r->priority ?? 'N/A', 'Assigned To' => trim($r->assigned_to) ?: 'N/A',
            ])->all();
        return ['columns' => ['Request No.', 'Building', 'Room', 'Issue Description', 'Priority', 'Assigned To'], 'rows' => $rows];
    }

    public function pendingMaintenance(array $f): array
    {
        $rows = $this->maintBase($f)->where('m.Status', 'like', '%pend%')->orderBy('m.date')
            ->get(['m.Request_id', DB::raw($this->raiser), 'm.date'])
            ->map(fn($r) => [
                'Request No.' => $r->Request_id ?? 'N/A', 'Requested By' => trim($r->raised_by) ?: 'N/A',
                'Request Date' => $this->dmy($r->date), 'Pending Days' => $r->date ? Carbon::parse($r->date)->diffInDays(Carbon::today()) : 'N/A',
            ])->all();
        return ['columns' => ['Request No.', 'Requested By', 'Request Date', 'Pending Days'], 'rows' => $rows];
    }

    public function maintenanceSla(array $f): array
    {
        $rows = $this->maintBase($f)->orderByDesc('m.date')
            ->get(['m.Request_id', 'm.priority', 'm.date', 'm.Status', 'm.updated_at'])
            ->map(function ($r) {
                $closed = stripos((string) $r->Status, 'clos') !== false;
                $resTime = $closed && $r->date ? Carbon::parse($r->date)->diffInDays(Carbon::parse($r->updated_at)) . ' day(s)' : 'N/A';
                return ['Request No.' => $r->Request_id ?? 'N/A', 'Priority' => $r->priority ?? 'N/A', 'Response Time' => 'N/A', 'Resolution Time' => $resTime, 'SLA Status' => $closed ? 'Resolved' : 'Open'];
            })->all();
        return ['columns' => ['Request No.', 'Priority', 'Response Time', 'Resolution Time', 'SLA Status'], 'rows' => $rows];
    }

    public function maintenanceAgeing(array $f): array
    {
        $rows = $this->maintBase($f)->where('m.Status', 'not like', '%clos%')->orderBy('m.date')
            ->get(['m.Request_id', 'b.BuildingName', 'm.RoomNo', 'm.date', 'm.priority', 'm.Status'])
            ->map(fn($r) => [
                'Request No.' => $r->Request_id ?? 'N/A', 'Building' => $r->BuildingName ?? 'N/A', 'Room' => $r->RoomNo ?? 'N/A',
                'Open Days' => $r->date ? Carbon::parse($r->date)->diffInDays(Carbon::today()) : 'N/A', 'Priority' => $r->priority ?? 'N/A', 'Status' => $r->Status ?? 'N/A',
            ])->all();
        return ['columns' => ['Request No.', 'Building', 'Room', 'Open Days', 'Priority', 'Status'], 'rows' => $rows];
    }

    public function maintenanceHold(array $f): array
    {
        $rows = $this->maintBase($f)->where('m.Status', 'like', '%hold%')->orderByDesc('m.updated_at')
            ->get(['m.Request_id', 'b.BuildingName', 'm.RoomNo', 'm.ReasonOnHold', 'm.updated_at'])
            ->map(fn($r) => [
                'Request No.' => $r->Request_id ?? 'N/A', 'Building' => $r->BuildingName ?? 'N/A', 'Room' => $r->RoomNo ?? 'N/A',
                'Hold Reason' => $r->ReasonOnHold ?? 'N/A', 'Hold Date' => $this->dmy($r->updated_at),
            ])->all();
        return ['columns' => ['Request No.', 'Building', 'Room', 'Hold Reason', 'Hold Date'], 'rows' => $rows];
    }

    public function maintenanceCompletion(array $f): array
    {
        $rows = $this->maintBase($f)->where('m.Status', 'like', '%clos%')->orderByDesc('m.updated_at')
            ->get(['m.Request_id', 'm.updated_at', 'm.date', DB::raw($this->assignee)])
            ->map(fn($r) => [
                'Request No.' => $r->Request_id ?? 'N/A', 'Completion Date' => $this->dmy($r->updated_at),
                'Assigned Technician' => trim($r->assigned_to) ?: 'N/A',
                'Resolution Time' => $r->date ? Carbon::parse($r->date)->diffInDays(Carbon::parse($r->updated_at)) . ' day(s)' : 'N/A',
            ])->all();
        return ['columns' => ['Request No.', 'Completion Date', 'Assigned Technician', 'Resolution Time'], 'rows' => $rows];
    }

    public function eventCalendar(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('events')->where('resort_id', $rid)
            ->when($f['month'], fn($q) => $q->whereRaw('MONTH(date)=?', [$f['month']]))
            ->orderBy('date')->get(['title', 'location', 'date', 'events_for'])
            ->map(fn($r) => ['Event Title' => $r->title ?? 'N/A', 'Building' => $r->location ?? 'N/A', 'Floor' => 'N/A', 'Event Date' => $this->dmy($r->date), 'Event Type' => $r->events_for ?? 'N/A'])->all();
        return ['columns' => ['Event Title', 'Building', 'Floor', 'Event Date', 'Event Type'], 'rows' => $rows];
    }

    public function buildingEvent(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('events')->where('resort_id', $rid)->orderByDesc('date')->get(['title', 'date', 'location', 'events_for'])
            ->map(fn($r) => ['Event Name' => $r->title ?? 'N/A', 'Event Date' => $this->dmy($r->date), 'Building' => $r->location ?? 'N/A', 'Target Audience' => $r->events_for ?? 'N/A'])->all();
        return ['columns' => ['Event Name', 'Event Date', 'Building', 'Target Audience'], 'rows' => $rows];
    }

    public function cleaningSchedule(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('housekeeping_schedules as h')->leftJoin('employees as e', 'e.id', '=', 'h.Assigned_To')->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('h.resort_id', $rid)->when($f['building'], fn($q) => $q->where('h.BuildingName', $f['building']))
            ->orderByDesc('h.date')
            ->get(['h.BuildingName', 'h.RoomNo', 'h.date', 'h.status', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as staff")])
            ->map(fn($r) => ['Building' => $r->BuildingName ?? 'N/A', 'Room Number' => $r->RoomNo ?? 'N/A', 'Cleaning Date' => $this->dmy($r->date), 'Assigned Staff' => trim($r->staff) ?: 'N/A', 'Status' => $r->status ?? 'N/A'])->all();
        return ['columns' => ['Building', 'Room Number', 'Cleaning Date', 'Assigned Staff', 'Status'], 'rows' => $rows];
    }
}
