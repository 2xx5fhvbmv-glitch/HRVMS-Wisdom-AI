<?php

namespace App\Http\Controllers\Resorts\Incident;

use App\Http\Controllers\Controller;
use App\Events\ResortNotificationEvent;
use Illuminate\Http\Request;
use App\Models\ResortAdmin;
use App\Models\Employee;
use App\Models\Incidents;
use App\Models\IncidentCommittee;
use Auth;
use DB;
use Common;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
  
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
        $this->reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:0;
        $this->underEmp_id = Common::getSubordinates($this->reporting_to);
    }

    /**
     * Thin wrapper around the centralised Common::scopeIncidentsForViewer
     * helper so existing call sites in this controller keep working. The
     * helper applies the same role-based visibility rules used by every
     * other incident endpoint (committee membership + reporter dept), so
     * department data can no longer leak across dashboards.
     */
    private function scopeForCurrentViewer($query)
    {
        return Common::scopeIncidentsForViewer($query);
    }

    public function HR_Dashobard()
    {
        $page_title ='Incident';
        $resort_id= $this->resort->resort_id;
        $total_incidents = $this->scopeForCurrentViewer(Incidents::query())->count();
        // "Open" = anything not yet resolved. Excluding 'Reported' here was
        // dropping freshly-filed incidents into a gap where they showed up
        // in Total but not in Open / Under Investigation, so the tile read
        // 0 with 4 incidents on the list.
        $open_incidents = $this->scopeForCurrentViewer(Incidents::query())
            ->where('status', '!=', 'Resolved')
            ->count();
        $under_investigation_incidents = $this->scopeForCurrentViewer(Incidents::query())
            ->where('status', 'Investigation In Progress')
            ->count();
        // Restrict the resolution-days average to the same incident set the
        // viewer can actually see (otherwise non-HR users see resort-wide
        // numbers in tiles that don't match the list page).
        $visibleIncidentIds = $this->scopeForCurrentViewer(Incidents::query())->pluck('id')->all();
        $averageResolutionDays = $visibleIncidentIds
            ? DB::table('incidents as i')
                ->join('incidents_investigation as ii','ii.incident_id','=','i.id')
                ->whereIn('i.id', $visibleIncidentIds)
                ->where('i.status', 'Resolved')
                ->whereNotNull('ii.start_date')
                ->whereColumn('i.updated_at', '>', 'ii.start_date')
                ->select(DB::raw('AVG(DATEDIFF(i.updated_at, ii.start_date)) as avg_days'))
                ->value('avg_days')
            : 0;
        // dd($averageResolutionDays);
        // Committee summary: limit each committee's incident roll-up to the
        // viewer's visibility set (`$visibleIncidentIds`) so cross-department
        // incidents don't leak through committee assignments.
        $committees = IncidentCommittee::where('resort_id', $resort_id)->get();

        $committeeSummary = [];

        foreach ($committees as $committee) {
            $incidents = Incidents::whereJsonContains('assigned_to', (string) $committee->id)
                ->where('resort_id', $resort_id)
                ->whereIn('id', $visibleIncidentIds ?: [0])
                ->get();

            $statusCounts = $incidents->groupBy('status')->map->count();
            // "Open" = not yet Resolved. Excluding 'Reported' here was the
            // same gap-bucket bug the Open Incidents tile had.
            $totalOpen = $incidents->where('status', '!=', 'Resolved')->count();
    
            // Choose a dominant status (you can change this logic)
            $dominantStatus = $statusCounts->sortDesc()->keys()->first() ?? 'No Incidents';
    
            $committeeSummary[] = [
                'name' => $committee->commitee_name,
                'open' => $totalOpen,
                'status' => $dominantStatus
            ];
        }

        $severityCounts = $this->scopeForCurrentViewer(Incidents::query())
            ->select('severity', \DB::raw('count(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity')
            ->toArray();

        // Ensure all severity types are present even if count is 0
        $allSeverities = ['Minor', 'Moderate', 'Severe'];
        foreach ($allSeverities as $severity) {
            if (!isset($severityCounts[$severity])) {
                $severityCounts[$severity] = 0;
            }
        }

        $resolvedCount = $this->scopeForCurrentViewer(Incidents::query())
            ->where('status', 'Resolved')
            ->count();

        $unresolvedCount = $this->scopeForCurrentViewer(Incidents::query())
            ->where('status', '!=', 'Resolved')
            ->count();


        // Reuse $visibleIncidentIds (computed above for the avg-days query) so
        // the category chart only shows categories the viewer is allowed to see.
        $categoryCounts = Incidents::query()
            ->whereIn('incidents.id', $visibleIncidentIds ?: [0])
            ->join('incident_categories', 'incidents.category', '=', 'incident_categories.id')
            ->select('incident_categories.category_name as category_name', \DB::raw('count(*) as total'))
            ->groupBy('incident_categories.category_name')
            ->get();

        $categoryLabels = $categoryCounts->pluck('category_name')->toArray();
        $categoryData = $categoryCounts->pluck('total')->toArray();
        $totalIncidents = array_sum($categoryData);

        $incidentInsights = $this->getCachedIncidentInsights($resort_id);

        return view('resorts.incident.dashboard.hrdashboard',compact('page_title','total_incidents','open_incidents','under_investigation_incidents','averageResolutionDays','committeeSummary','severityCounts','resolvedCount','unresolvedCount','categoryLabels','categoryData','totalIncidents','incidentInsights'));
    }

    /**
     * Cached wrapper around buildIncidentInsights() with a manual 2-day refresh,
     * mirroring the other module dashboards. Cached per resort; "Regenerate"
     * loads ?regenerate_insights=1 and recomputes only once the 48h cooldown has
     * elapsed. Returns a '_meta' key for the card header.
     */
    private function getCachedIncidentInsights($resortId): array
    {
        $cooldownHours = 48;
        $cacheKey = 'incident_insights:' . $resortId;
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
                'insights'     => $this->buildIncidentInsights($resortId),
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
     * Compute the three Incident AI-insight cards for a resort:
     *   1. volume    Incident volume & trend (open/resolved, MoM, priority/severity mix)
     *   2. hotspots  Category & severity hotspots (top categories, severity split, locations)
     *   3. outcomes  Outcomes & preventive actions (outcome/action mix, preventive coverage)
     * Each card is wrapped so one failing query degrades just that card; the
     * deterministic numbers are then narrated by the FastAPI LLM (best-effort).
     */
    private function buildIncidentInsights($resortId): array
    {
        $insights = [
            'volume'   => ['title' => 'Incident Volume & Trend',     'body' => 'No incidents recorded yet.'],
            'hotspots' => ['title' => 'Category & Severity Hotspots', 'body' => 'No incidents recorded yet.'],
            'outcomes' => ['title' => 'Outcomes & Preventive Actions', 'body' => 'No resolved incidents yet to analyse.'],
        ];
        $monthNames = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];

        // --- Card 1: Incident volume & trend -----------------------------------
        try {
            $total = Incidents::where('resort_id', $resortId)->count();
            if ($total > 0) {
                $resolved = Incidents::where('resort_id', $resortId)->where('status', 'Resolved')->count();
                $open = $total - $resolved;
                $byPriority = Incidents::where('resort_id', $resortId)
                    ->select('priority', \DB::raw('count(*) as c'))->groupBy('priority')->pluck('c', 'priority');
                $bySeverity = Incidents::where('resort_id', $resortId)->whereNotNull('severity')->where('severity', '!=', '')
                    ->select('severity', \DB::raw('count(*) as c'))->groupBy('severity')->pluck('c', 'severity');
                // Month-over-month (last 6 months by incident_date).
                $since = \Carbon\Carbon::now()->subMonths(5)->startOfMonth();
                $byMonth = Incidents::where('resort_id', $resortId)
                    ->whereNotNull('incident_date')->whereDate('incident_date', '>=', $since->toDateString())
                    ->select(\DB::raw('YEAR(incident_date) as y'), \DB::raw('MONTH(incident_date) as m'), \DB::raw('count(*) as c'))
                    ->groupBy('y', 'm')->orderBy('y')->orderBy('m')->get();
                $series = [];
                foreach ($byMonth as $r) $series[(int)$r->y . '-' . str_pad((int)$r->m, 2, '0', STR_PAD_LEFT)] = (int) $r->c;
                $thisKey = \Carbon\Carbon::now()->format('Y-m');
                $lastKey = \Carbon\Carbon::now()->subMonth()->format('Y-m');
                $thisM = $series[$thisKey] ?? 0; $lastM = $series[$lastKey] ?? 0;
                $dir = $thisM > $lastM ? 'up' : ($thisM < $lastM ? 'down' : 'flat');
                $high = (int) ($byPriority['High'] ?? 0);
                $trendTxt = $dir === 'flat' ? 'level with last month (' . $thisM . ')' : ($dir === 'up' ? 'up to ' . $thisM . ' from ' . $lastM . ' last month' : 'down to ' . $thisM . ' from ' . $lastM . ' last month');
                $insights['volume']['body'] = $total . ' incidents (' . $open . ' open, ' . $resolved . ' resolved); this month ' . $trendTxt . '; ' . $high . ' high-priority.';
                $monthsLabelled = [];
                foreach ($series as $k => $v) { [$yy,$mm] = explode('-', $k); $monthsLabelled[] = ['month' => ($monthNames[(int)$mm] ?? $mm) . ' ' . $yy, 'count' => $v]; }
                $insights['volume']['details'] = [
                    'total' => $total, 'open' => $open, 'resolved' => $resolved, 'direction' => $dir,
                    'priority' => ['High' => (int)($byPriority['High'] ?? 0), 'Medium' => (int)($byPriority['Medium'] ?? 0), 'Low' => (int)($byPriority['Low'] ?? 0)],
                    'severity' => $bySeverity->toArray(),
                    'months' => $monthsLabelled,
                ];
            }
        } catch (\Throwable $e) {}

        // --- Card 2: Category & severity hotspots ------------------------------
        try {
            $byCat = Incidents::where('incidents.resort_id', $resortId)
                ->leftJoin('incident_categories as c', 'c.id', '=', 'incidents.category')
                ->select(\DB::raw("COALESCE(c.category_name,'Uncategorised') as name"), \DB::raw('count(*) as cnt'))
                ->groupBy('name')->orderByDesc('cnt')->limit(10)->get()
                ->map(fn ($r) => ['category' => $r->name, 'count' => (int) $r->cnt])->all();
            $bySub = Incidents::where('incidents.resort_id', $resortId)
                ->leftJoin('incident_subcategories as s', 's.id', '=', 'incidents.subcategory')
                ->select(\DB::raw("COALESCE(s.subcategory_name,'—') as name"), \DB::raw('count(*) as cnt'))
                ->groupBy('name')->orderByDesc('cnt')->limit(10)->get()
                ->map(fn ($r) => ['subcategory' => $r->name, 'count' => (int) $r->cnt])->all();
            $bySev = Incidents::where('resort_id', $resortId)->whereNotNull('severity')->where('severity', '!=', '')
                ->select('severity', \DB::raw('count(*) as c'))->groupBy('severity')->pluck('c', 'severity');
            $byLoc = Incidents::where('resort_id', $resortId)->whereNotNull('location')->where('location', '!=', '')
                ->select('location', \DB::raw('count(*) as cnt'))->groupBy('location')->orderByDesc('cnt')->limit(10)->get()
                ->map(fn ($r) => ['location' => $r->location, 'count' => (int) $r->cnt])->all();
            if (!empty($byCat)) {
                $tc = $byCat[0]; $tl = $byLoc[0] ?? null;
                $severe = (int) ($bySev['Severe'] ?? 0); $moderate = (int) ($bySev['Moderate'] ?? 0); $minor = (int) ($bySev['Minor'] ?? 0);
                $locTxt = $tl ? ' ' . $tl['location'] . ' is the most-affected location (' . $tl['count'] . ').' : '';
                $insights['hotspots']['body'] = 'Top category: ' . $tc['category'] . ' (' . $tc['count'] . '); severity ' . $severe . ' severe / ' . $moderate . ' moderate / ' . $minor . ' minor.' . $locTxt;
                $insights['hotspots']['details'] = [
                    'categories' => $byCat, 'subcategories' => $bySub,
                    'severity' => ['Severe' => $severe, 'Moderate' => $moderate, 'Minor' => $minor],
                    'locations' => $byLoc,
                ];
            }
        } catch (\Throwable $e) {}

        // --- Card 3: Outcomes & preventive actions -----------------------------
        try {
            $resolved = Incidents::where('resort_id', $resortId)->where('status', 'Resolved')->count();
            if ($resolved > 0) {
                $byOutcome = Incidents::where('incidents.resort_id', $resortId)->where('incidents.status', 'Resolved')
                    ->leftJoin('incident_outcome_types as o', 'o.id', '=', 'incidents.outcome_type')
                    ->select(\DB::raw("COALESCE(o.outcome_type,'Unspecified') as name"), \DB::raw('count(*) as cnt'))
                    ->groupBy('name')->orderByDesc('cnt')->limit(10)->get()
                    ->map(fn ($r) => ['outcome' => $r->name, 'count' => (int) $r->cnt])->all();
                $byAction = Incidents::where('incidents.resort_id', $resortId)->where('incidents.status', 'Resolved')
                    ->leftJoin('incident_actions_taken as a', 'a.id', '=', 'incidents.action_taken')
                    ->select(\DB::raw("COALESCE(a.action_taken,'Unspecified') as name"), \DB::raw('count(*) as cnt'))
                    ->groupBy('name')->orderByDesc('cnt')->limit(10)->get()
                    ->map(fn ($r) => ['action' => $r->name, 'count' => (int) $r->cnt])->all();
                $withPrev = Incidents::where('resort_id', $resortId)->where('status', 'Resolved')
                    ->whereNotNull('preventive_measures')->where('preventive_measures', '!=', '')->count();
                $prevPct = round($withPrev / $resolved * 100, 1);
                $to = $byOutcome[0] ?? null; $ta = $byAction[0] ?? null;
                $parts = [];
                if ($to) $parts[] = 'top outcome ' . $to['outcome'] . ' (' . $to['count'] . ')';
                if ($ta) $parts[] = 'most common action ' . $ta['action'] . ' (' . $ta['count'] . ')';
                $insights['outcomes']['body'] = 'Of ' . $resolved . ' resolved: ' . implode('; ', $parts) . '; ' . $prevPct . '% have preventive measures recorded.';
                $insights['outcomes']['details'] = [
                    'resolved' => $resolved, 'outcomes' => $byOutcome, 'actions' => $byAction,
                    'preventive_recorded' => $withPrev, 'preventive_missing' => $resolved - $withPrev, 'preventive_pct' => $prevPct,
                ];
            }
        } catch (\Throwable $e) {}

        // Narrate the deterministic numbers via the FastAPI LLM (best-effort).
        $insights = \App\Helpers\Common::enrichDashboardInsights(
            $insights, 'workplace incident management & safety', ['volume', 'hotspots', 'outcomes']
        );

        return $insights;
    }

    public function getDepartmentWiseParticipation()
    {
        // Restrict the chart's incident set to what the viewer is allowed
        // to see — without this a HOD could see participation from incidents
        // reported by other departments.
        $visibleIncidentIds = Common::scopeIncidentsForViewer(Incidents::query())->pluck('id')->all();
        if (empty($visibleIncidentIds)) {
            return response()->json(['labels' => [], 'datasets' => []]);
        }

        $data = DB::table('incidents_investigation_meetings as meetings')
            ->join('incidents as i','i.id','=','meetings.incident_id')
            ->join('incidents_investigation_meetings_participants as participants', 'meetings.id', '=', 'participants.meeting_id')
            ->join('employees', 'participants.participant_id', '=', 'employees.id')
            ->join('resort_departments', 'employees.Dept_id', '=', 'resort_departments.id')
            ->select(
                DB::raw("DATE_FORMAT(meetings.meeting_date, '%b %Y') as month"),
                'resort_departments.name as department',
                DB::raw('COUNT(participants.id) as participation_count')
            )
            ->where('i.resort_id',$this->resort->resort_id)
            ->whereIn('i.id', $visibleIncidentIds)
            ->groupBy('month', 'department')
            ->orderBy(DB::raw("STR_TO_DATE(month, '%b %Y')"))
            ->get();

        // Transform to structure compatible with Chart.js
        $grouped = [];
        $months = [];

        foreach ($data as $entry) {
            $grouped[$entry->department][$entry->month] = $entry->participation_count;
            $months[] = $entry->month;
        }

        $months = array_values(array_unique($months));
        $datasets = [];

        $colors = ['#333333', '#7AD45A', '#FF4B4B', '#F5738D', '#53CAFF'];

        $i = 0;
        foreach ($grouped as $department => $monthData) {
            $dataset = [
                'label' => $department,
                'data' => [],
                'backgroundColor' => $colors[$i % count($colors)],
                'borderColor' => '#fff',
                'borderWidth' => 2,
                'borderRadius' => 10,
            ];

            foreach ($months as $month) {
                $dataset['data'][] = $monthData[$month] ?? 0;
            }

            $datasets[] = $dataset;
            $i++;
        }
        // dd($months,$datasets);
        return response()->json([
            'labels' => $months,
            'datasets' => $datasets
        ]);
    }

    public function getIncidentTrends(Request $request)
    {
        $year = $request->year ?? Carbon::now()->year;
        $start = Carbon::createFromDate($year, 1, 1)->startOfMonth();
        $end = Carbon::createFromDate($year, 12, 31)->endOfMonth();
    
        // Initialize all months with 0
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[Carbon::createFromDate($year, $i, 1)->format('M Y')] = 0;
        }
    
        // Same viewer scope as the dashboard tiles so the trend chart
        // matches the counts and doesn't expose other-dept incidents.
        $visibleIncidentIds = Common::scopeIncidentsForViewer(Incidents::query())->pluck('id')->all();
        $incidentCounts = empty($visibleIncidentIds)
            ? collect()
            : DB::table('incidents')
                ->select(DB::raw("DATE_FORMAT(incident_date, '%b %Y') as month"), DB::raw('COUNT(*) as total'))
                ->whereBetween('incident_date', [$start, $end])
                ->where('incidents.resort_id', $this->resort->resort_id)
                ->whereIn('incidents.id', $visibleIncidentIds)
                ->groupBy('month')
                ->get();
    
        foreach ($incidentCounts as $row) {
            $months[$row->month] = $row->total;
        }
    
        return response()->json([
            'labels' => array_keys($months),
            'data' => array_values($months)
        ]);
    }
    
    public function getResolutionTimelineStats()
    {
        $today = Carbon::today();
        $resort_id = $this->resort->resort_id;
        // Restrict every count to the same incident set the viewer can see —
        // raw DB::table() queries without the viewer scope were leaking
        // cross-dept (and cross-resort, on the global $total) numbers into
        // the tile.
        $visibleIncidentIds = $this->scopeForCurrentViewer(Incidents::query())->pluck('id')->all();
        $visibleIds = $visibleIncidentIds ?: [0];

        $nearingDeadline = DB::table('incidents as i')
            ->join('incidents_investigation as ii','ii.incident_id','=','i.id')
            ->whereIn('i.id', $visibleIds)
            ->whereDate('ii.expected_resolution_date', '>=', $today)
            ->whereDate('ii.expected_resolution_date', '<=', $today->copy()->addDays(3))
            ->where('i.status', '!=', 'Resolved')
            ->count();

        $breachedTimelines = DB::table('incidents as i')
            ->join('incidents_investigation as ii','ii.incident_id','=','i.id')
            ->whereIn('i.id', $visibleIds)
            ->whereDate('ii.expected_resolution_date', '<', $today)
            ->where('i.status', '!=', 'Resolved')
            ->count();

        $resolved = DB::table('incidents')
            ->whereIn('id', $visibleIds)
            ->where('status', 'Resolved')
            ->count();

        // Was DB::table('incidents')->count() — counted ALL incidents in
        // every resort across the system, so resolvedPercentage was wildly
        // wrong on small resorts.
        $total = count($visibleIncidentIds);

        $resolvedPercentage = $total > 0 ? round(($resolved / $total) * 100) : 0;

        // Was: ->whereNotIn('status', ['Reported', 'Resolved']) with no
        // resort or viewer scope — same gap-bucket bug as the Open tile,
        // plus a cross-resort leak. Open = anything not yet Resolved,
        // bounded to the viewer's visible set.
        $openInvestigations = DB::table('incidents')
            ->whereIn('id', $visibleIds)
            ->where('status', '!=', 'Resolved')
            ->count();

        return response()->json([
            'nearingDeadline' => $nearingDeadline,
            'breachedTimelines' => $breachedTimelines,
            'resolvedPercentage' => $resolvedPercentage,
            'openInvestigations' => $openInvestigations,
        ]);
    }

    public function getUpcomingMeetings()
    {
        $now = Carbon::now(); // Full current datetime
        // Widget is visible to every resort user: any meeting under the
        // current resort shows up regardless of participation.
        $meetings = DB::table('incidents_investigation_meetings as m')
            ->join('incidents as i', 'i.id', '=', 'm.incident_id')
            ->select(
                'i.incident_name as incident_title',
                'm.meeting_subject',
                'm.meeting_agenda',
                'm.meeting_date',
                'm.meeting_time',
                'm.id'
            )
            ->where('i.resort_id', $this->resort->resort_id)
            ->whereRaw("STR_TO_DATE(CONCAT(m.meeting_date, ' ', m.meeting_time), '%Y-%m-%d %H:%i:%s') >= ?", [$now])
            ->orderByRaw("STR_TO_DATE(CONCAT(m.meeting_date, ' ', m.meeting_time), '%Y-%m-%d %H:%i:%s')")
            ->limit(5)
            ->get()
            ->map(function ($meeting) {
                $datetime = Carbon::parse($meeting->meeting_date . ' ' . $meeting->meeting_time);

                return [
                    'title' => $meeting->meeting_subject,
                    'description' => $meeting->meeting_agenda,
                    'scheduled_time' => $datetime->format('g:i A'), // e.g., "2:00 PM"
                    'day_label' => $datetime->isToday() ? 'Today' : ($datetime->isTomorrow() ? 'Tomorrow' : $datetime->format('d M Y')),
                    'id' => $meeting->id
                ];
            });

        return response()->json($meetings);
    }

    public function getPreventiveActions()
    {
        // Was scoped only by resort_id — non-HR HOD/EXCOM saw preventive
        // measures from other depts. Use the same viewer scope as the rest
        // of the dashboard so the panel matches the surrounding tiles.
        $visibleIncidentIds = $this->scopeForCurrentViewer(Incidents::query())->pluck('id')->all();

        $actions = DB::table('incidents_investigation as ii')
            ->join('incidents as i','i.id','=','ii.incident_id')
            ->whereIn('i.id', $visibleIncidentIds ?: [0])
            ->orderBy('i.incident_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'title' => $item->incident_name,
                    'description' => $item->preventive_measures ?? "No Preventive Measures Added.",
                ];
            });

        return response()->json($actions);
    }

    public function getPendingResolutionApprovals()
    {
        // Was missing both resort_id AND viewer scope — returned approval
        // rows from EVERY resort in the system. Bound to the viewer's
        // visible incidents so the panel can never leak cross-resort data.
        $visibleIncidentIds = $this->scopeForCurrentViewer(Incidents::query())->pluck('id')->all();

        $pendingResolutions = DB::table('incidents_investigation as ii')
            ->join('incidents as i', 'i.id', '=', 'ii.incident_id')
            ->leftJoin('incident_outcome_types as iot','iot.id','=','ii.outcome_type')
            ->leftJoin('incident_actions_taken as iat','iat.id','=','ii.action_taken')
            ->select('ii.id', 'i.incident_name', 'ii.investigation_findings', 'ii.follow_up_actions','iat.action_taken','iot.outcome_type')
            ->whereIn('i.id', $visibleIncidentIds ?: [0])
            ->where('ii.approval', 1)
            ->whereNull('ii.approved_by')
            ->orderBy('ii.created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json($pendingResolutions);
    }

    public function preventiveMeasuresList(Request $request){
        if(Common::checkRouteWisePermission('incident.meeting',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title = 'Preventive Measures List';
        if ($request->ajax()) {
            // Bound to viewer-visible incidents — was previously resort-only.
            $visibleIncidentIds = $this->scopeForCurrentViewer(Incidents::query())->pluck('id')->all();
            $query = DB::table('incidents_investigation as ii')
                ->join('incidents as i', 'i.id', '=', 'ii.incident_id')
                ->whereIn('i.id', $visibleIncidentIds ?: [0])
                ->select('ii.id', 'i.incident_name', 'i.preventive_measures', 'ii.updated_at','ii.created_at');
    
            // Optional: Apply search filter
            if ($request->has('searchTerm') && !empty($request->searchTerm)) {
                $searchTerm = $request->searchTerm;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('i.incident_name', 'like', "%$searchTerm%")
                      ->orWhere('ii.preventive_measures', 'like', "%$searchTerm%");
                });
            }
                
            return datatables()->of($query)
                ->editColumn('updated_at', function ($row) {
                    return Carbon::parse($row->updated_at)->format('d M Y');
                })
                ->make(true);
        }

        return view('resorts.incident.incident.preventive_measures', compact('page_title'));
    }

    public function pendingApprovalsList(Request $request){
        if(Common::checkRouteWisePermission('incident.meeting',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title = 'Pending Approvals List';
        if ($request->ajax()) {
            // Was missing both resort_id AND viewer scope — leaked across
            // every resort in the system.
            $visibleIncidentIds = $this->scopeForCurrentViewer(Incidents::query())->pluck('id')->all();
            $query = DB::table('incidents_investigation as ii')
                ->join('incidents as i', 'i.id', '=', 'ii.incident_id')
                ->leftJoin('incident_outcome_types as iot', 'iot.id', '=', 'ii.outcome_type')
                ->leftJoin('incident_actions_taken as iat', 'iat.id', '=', 'ii.action_taken')
                ->select(
                    'ii.id',
                    'i.incident_name',
                    'ii.investigation_findings',
                    'ii.follow_up_actions',
                    'iat.action_taken',
                    'iot.outcome_type',
                    'ii.updated_at',
                    'ii.created_at'
                )
                ->whereIn('i.id', $visibleIncidentIds ?: [0])
                ->where('ii.approval', 1)
                ->whereNull('ii.approved_by')
                ->orderBy('ii.created_at', 'desc');

            // Optional: Apply search filter
            if ($request->has('searchTerm') && !empty($request->searchTerm)) {
                $searchTerm = $request->searchTerm;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('i.incident_name', 'like', "%$searchTerm%")
                        ->orWhere('ii.investigation_findings', 'like', "%$searchTerm%")
                        ->orWhere('iot.outcome_type', 'like', "%$searchTerm%")
                        ->orWhere('iat.action_taken', 'like', "%$searchTerm%");
                });
            }

            return datatables()->of($query)
                
                ->make(true);
        }

        return view('resorts.incident.incident.pending-approvals',compact('page_title'));
    }

    public function admin_dashboard()
    {
        $page_title ='Incident';
        $resort_id= $this->resort->resort_id;
        $total_incidents = Incidents::where('resort_id',$resort_id)->count();
        // "Open" = anything not yet resolved (matches HR_Dashobard fix —
        // freshly-Reported incidents must count or they vanish from tiles).
        $open_incidents = Incidents::where('resort_id', $resort_id)
        ->where('status', '!=', 'Resolved')
        ->count();
        $under_investigation_incidents = Incidents::where('resort_id', $resort_id)
        ->where('status', 'Investigation In Progress')
        ->count();
        $averageResolutionDays = DB::table('incidents as i')
        ->join('incidents_investigation as ii','ii.incident_id','=','i.id')
        ->where('i.status', 'Resolved')
        ->whereNotNull('ii.start_date')
        ->whereColumn('i.updated_at', '>', 'ii.start_date')
        ->select(DB::raw('AVG(DATEDIFF(i.updated_at, ii.start_date)) as avg_days'))
        ->value('avg_days');
        // dd($averageResolutionDays);
        $committees = IncidentCommittee::where('resort_id', $resort_id)->get();

        $committeeSummary = [];
    
        foreach ($committees as $committee) {
            $incidents = Incidents::whereJsonContains('assigned_to', (string) $committee->id)
                ->where('resort_id', $resort_id)
                ->get();
    
            $statusCounts = $incidents->groupBy('status')->map->count();
            // "Open" = not yet Resolved. Excluding 'Reported' here was the
            // same gap-bucket bug the Open Incidents tile had.
            $totalOpen = $incidents->where('status', '!=', 'Resolved')->count();
    
            // Choose a dominant status (you can change this logic)
            $dominantStatus = $statusCounts->sortDesc()->keys()->first() ?? 'No Incidents';
    
            $committeeSummary[] = [
                'name' => $committee->commitee_name,
                'open' => $totalOpen,
                'status' => $dominantStatus
            ];
        }

        $severityCounts = Incidents::where('resort_id', $resort_id)
        ->select('severity', \DB::raw('count(*) as total'))
        ->groupBy('severity')
        ->pluck('total', 'severity')
        ->toArray();

        // Ensure all severity types are present even if count is 0
        $allSeverities = ['Minor', 'Moderate', 'Severe'];
        foreach ($allSeverities as $severity) {
            if (!isset($severityCounts[$severity])) {
                $severityCounts[$severity] = 0;
            }
        }

        $resolvedCount = Incidents::where('resort_id', $resort_id)
            ->where('status', 'Resolved')
            ->count();

        $unresolvedCount = Incidents::where('resort_id', $resort_id)
            ->where('status', '!=', 'Resolved')
            ->count();


        $categoryCounts = Incidents::where('incidents.resort_id', $resort_id)
            ->join('incident_categories', 'incidents.category', '=', 'incident_categories.id')
            ->select('incident_categories.category_name as category_name', \DB::raw('count(*) as total'))
            ->groupBy('incident_categories.category_name')
            ->get();
        
        $categoryLabels = $categoryCounts->pluck('category_name')->toArray();
        $categoryData = $categoryCounts->pluck('total')->toArray();
        $totalIncidents = array_sum($categoryData);

        // dd($severityCounts);
        return view('resorts.incident.dashboard.admindashboard',compact('page_title','total_incidents','open_incidents','under_investigation_incidents','averageResolutionDays','committeeSummary','severityCounts','resolvedCount','unresolvedCount','categoryLabels','categoryData','totalIncidents'));
    }

    public function hod_dashboard()
    {
        $page_title ='Incident';
        $dashboardLabel = request('dashboard_label', 'HOD');
        $page_header = '<span class="arca-font">'.$dashboardLabel.'</span> Dashboard';
        $resort_id= $this->resort->resort_id;
        $department_id = $this->resort->GetEmployee->Dept_id;
        // dd($department_id);
        $total_incidents = Incidents::whereHas('reporter', function($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->where('resort_id', $resort_id)
        ->count();        
        // dd($total_incidents);
       
        // Was: ->where('status', ['Reported']) — passing an array as the
        // value silently casts to "Array" and matches nothing, so the
        // tile read 0 even when there were Reported incidents.
        $pending_incidents = Incidents::whereHas('reporter', function($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->where('resort_id', $resort_id)
        ->where('status', 'Reported')
        ->count();

        $under_investigation_incidents = Incidents::whereHas('reporter', function($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->where('resort_id', $resort_id)
        ->where('status', 'Investigation In Progress')
        ->count(); 

        $averageResolutionDays = DB::table('incidents as i')
        ->join('incidents_investigation as ii','ii.incident_id','=','i.id')
        ->where('i.status', 'Resolved')
        ->whereNotNull('ii.start_date')
        ->whereColumn('i.updated_at', '>', 'ii.start_date')
        ->select(DB::raw('AVG(DATEDIFF(i.updated_at, ii.start_date)) as avg_days'))
        ->value('avg_days');
        // dd($averageResolutionDays);
        $committees = IncidentCommittee::where('resort_id', $resort_id)->get();

        $committeeSummary = [];
    
        foreach ($committees as $committee) {
            $incidents = Incidents::whereJsonContains('assigned_to', (string) $committee->id)
                ->where('resort_id', $resort_id)
                ->get();
    
            $statusCounts = $incidents->groupBy('status')->map->count();
            // "Open" = not yet Resolved. Excluding 'Reported' here was the
            // same gap-bucket bug the Open Incidents tile had.
            $totalOpen = $incidents->where('status', '!=', 'Resolved')->count();
    
            // Choose a dominant status (you can change this logic)
            $dominantStatus = $statusCounts->sortDesc()->keys()->first() ?? 'No Incidents';
    
            $committeeSummary[] = [
                'name' => $committee->commitee_name,
                'open' => $totalOpen,
                'status' => $dominantStatus
            ];
        }

        $severityCounts = Incidents::whereHas('reporter', function($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->where('resort_id', $resort_id)
        ->select('severity', \DB::raw('count(*) as total'))
        ->groupBy('severity')
        ->pluck('total', 'severity')
        ->toArray();
        // dd($severityCounts);

        // Ensure all severity types are present even if count is 0
        $allSeverities = ['Minor', 'Moderate', 'Severe'];
        foreach ($allSeverities as $severity) {
            if (!isset($severityCounts[$severity])) {
                $severityCounts[$severity] = 0;
            }
        }
        // dd($severityCounts);
        $resolvedCount = Incidents::whereHas('reporter', function($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->where('resort_id', $resort_id)
        ->where('status', 'Resolved')
        ->count();

        $unresolvedCount = Incidents::whereHas('reporter', function($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->where('resort_id', $resort_id)
        ->where('status', '!=', 'Resolved')
        ->count();

        $categoryCounts = Incidents::whereHas('reporter', function($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->where('incidents.resort_id', $resort_id)
        ->join('incident_categories', 'incidents.category', '=', 'incident_categories.id')
        ->select('incident_categories.category_name as category_name', \DB::raw('count(*) as total'))
        ->groupBy('incident_categories.category_name')
        ->get();
        
        $categoryLabels = $categoryCounts->pluck('category_name')->toArray();
        $categoryData = $categoryCounts->pluck('total')->toArray();
        $totalIncidents = array_sum($categoryData);

        // dd($severityCounts);
        return view('resorts.incident.dashboard.hoddashboard', compact(
            'page_title',
            'total_incidents',
            'pending_incidents',
            'under_investigation_incidents',
            'averageResolutionDays',
            'committeeSummary',
            'severityCounts',
            'resolvedCount',
            'unresolvedCount',
            'categoryLabels',
            'categoryData',
            'totalIncidents'
        ));
    }

    public function excom_dashboard()
    {
        request()->merge(['dashboard_label' => 'XCOM']);
        return $this->hod_dashboard();
    }

    public function getIncidentStatusStats(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $department_id = $this->resort->GetEmployee->Dept_id;

        // Get counts by status for reporters in the same department
        $statusCounts = Incidents::where('resort_id', $resort_id)
            ->whereHas('reporter', function ($query) use ($department_id) {
                $query->where('Dept_id', $department_id);
            })
            ->select('status', \DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Normalize counts into 3 categories
        $finalCounts = [
            'Resolved' => $statusCounts['Resolved'] ?? 0,
            'Under Investigation' => $statusCounts['Investigation In Progress'] ?? 0,
            'Pending' => $statusCounts['Reported'] ?? 0, // Or whatever represents unresolved
        ];

        return response()->json([
            'labels' => array_keys($finalCounts),
            'data' => array_values($finalCounts)
        ]);

    }

    public function getIncidentTodoList()
    {
        // Was hard-scoped to the logged-in user's own department, so HR /
        // GM saw zero results (HR-dept employees rarely report operational
        // incidents) and master admins crashed on a null GetEmployee.
        // Now uses the same viewer scope as every other dashboard endpoint:
        // full-access users see all incidents, other-dept HOD/EXCOM see
        // their own dept's reported incidents.
        $incidents = $this->scopeForCurrentViewer(Incidents::query())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                // Combine incident_date and incident_time into full datetime
                $datetime = Carbon::parse($item->incident_date . ' ' . $item->incident_time);
    
                return [
                    'id' => $item->id,
                    'title' => $item->incident_name,
                    'description' => $item->description,
                    'scheduled_time' => $datetime->format('g:i A'), // e.g., "2:00 PM"
                    'day_label' => $datetime->isToday() ? 'Today' : ($datetime->isTomorrow() ? 'Tomorrow' : $datetime->format('d M Y')),
                    'time_ago' => $datetime->diffForHumans(), // now accurate
                ];
            });
    
        return response()->json($incidents);
    }

    public function gethodIncidentTrends(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $department_id = $this->resort->GetEmployee->Dept_id;
        $year = $request->year ?? Carbon::now()->year;
        $start = Carbon::createFromDate($year, 1, 1)->startOfMonth();
        $end = Carbon::createFromDate($year, 12, 31)->endOfMonth();
    
        // Initialize all months with 0
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[Carbon::createFromDate($year, $i, 1)->format('M Y')] = 0;
        }
    
        $incidentCounts = Incidents::where('resort_id', $resort_id)
            ->whereHas('reporter', function ($query) use ($department_id) {
                $query->where('Dept_id', $department_id);
            })
            ->select(DB::raw("DATE_FORMAT(incident_date, '%b %Y') as month"), DB::raw('COUNT(*) as total'))
            ->whereBetween('incident_date', [$start, $end])
            ->groupBy('month')
            ->get();
    
        foreach ($incidentCounts as $row) {
            $months[$row->month] = $row->total;
        }
    
        return response()->json([
            'labels' => array_keys($months),
            'data' => array_values($months)
        ]);
    }

    public function getResolutionTimelineData()
    {
        $today = Carbon::today();
        $resort_id= $this->resort->resort_id;
        $department_id = $this->resort->GetEmployee->Dept_id;
        // dd($department_id);
        $nearingDeadline = Incidents::whereHas('reporter', function ($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->join('incidents_investigation as ii', 'ii.incident_id', '=', 'incidents.id')
        ->where('incidents.resort_id', $resort_id)
        ->whereDate('ii.expected_resolution_date', '>=', $today)
        ->whereDate('ii.expected_resolution_date', '<=', $today->copy()->addDays(3))
        ->where('incidents.status', '!=', 'Resolved')
        ->count();
        // dd($nearingDeadline);
        $breachedTimelines = Incidents::whereHas('reporter', function ($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->join('incidents_investigation as ii','ii.incident_id','=','incidents.id')
        ->where('incidents.resort_id', $resort_id)
        ->whereDate('ii.expected_resolution_date', '<', $today)
        ->where('incidents.status', '!=', 'Resolved')
        ->count();

        $resolved = Incidents::whereHas('reporter', function ($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->where('resort_id', $resort_id)
        ->where('status', 'Resolved')
        ->count();

        $total = DB::table('incidents')->count();

        $resolvedPercentage = $total > 0 ? round(($resolved / $total) * 100) : 0;

        $openInvestigations = Incidents::whereHas('reporter', function ($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->where('resort_id', $resort_id)
        ->whereNotIn('status', ['Reported', 'Resolved'])
        ->count();

        return response()->json([
            'nearingDeadline' => $nearingDeadline,
            'breachedTimelines' => $breachedTimelines,
            'resolvedPercentage' => $resolvedPercentage,
            'openInvestigations' => $openInvestigations,
        ]);
    }

    public function gethodPreventiveActions()
    {
        $resort_id= $this->resort->resort_id;
        $department_id = $this->resort->GetEmployee->Dept_id;
        $actions = Incidents::whereHas('reporter', function ($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->join('incidents_investigation as ii','incidents.id','=','ii.incident_id')
        ->where('incidents.resort_id', $this->resort->resort_id)
        ->orderBy('incidents.incident_date', 'desc')
        ->limit(5)
        ->get()
        ->map(function ($item) {
            return [
                'title' => $item->incident_name,
                'description' => $item->preventive_measures ?? "No Preventive Measures Added.", // Optional: shorten long text
            ];
        });

        return response()->json($actions);
    }

    public function getPendingResolutionApprovalsforHOD()
    {
        $resort_id= $this->resort->resort_id;
        $department_id = $this->resort->GetEmployee->Dept_id;
        $pendingResolutions = Incidents::whereHas('reporter', function ($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->join('incidents_investigation as ii', 'incidents.id', '=', 'ii.incident_id')
        ->leftJoin('incident_outcome_types as iot','iot.id','=','ii.outcome_type')
        ->leftJoin('incident_actions_taken as iat','iat.id','=','ii.action_taken')
        ->select('ii.id', 'incidents.incident_name', 'ii.investigation_findings', 'ii.follow_up_actions','iat.action_taken','iot.outcome_type')
        ->where('ii.approval', 1)
        ->where('incidents.resort_id',$resort_id)
        ->whereNull('ii.approved_by')
        ->orderBy('ii.created_at', 'desc')
        ->limit(5)
        ->get();

        return response()->json($pendingResolutions);
    }

    public function hodpreventiveMeasuresList(Request $request){
        $resort_id= $this->resort->resort_id;
        $department_id = $this->resort->GetEmployee->Dept_id;
        if ($request->ajax()) {
            $query = Incidents::whereHas('reporter', function ($query) use ($department_id) {
                $query->where('Dept_id', $department_id);
            })
            ->join('incidents_investigation as ii', 'incidents.id', '=', 'ii.incident_id')
            ->where('incidents.resort_id', $this->resort->resort_id)
            ->select('ii.id', 'incidents.incident_name', 'ii.preventive_measures', 'ii.updated_at');
    
            // Optional: Apply search filter
            if ($request->has('searchTerm') && !empty($request->searchTerm)) {
                $searchTerm = $request->searchTerm;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('incidents.incident_name', 'like', "%$searchTerm%")
                      ->orWhere('ii.preventive_measures', 'like', "%$searchTerm%");
                });
            }
                
            return datatables()->of($query)
                ->editColumn('updated_at', function ($row) {
                    return Carbon::parse($row->updated_at)->format('d M Y');
                })
                ->make(true);
        }
    
        return view('resorts.incident.incident.preventive_measures');
    }
}