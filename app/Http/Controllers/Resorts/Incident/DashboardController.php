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
    
    public function HR_Dashobard()
    {
        $page_title ='Incident';
        $resort_id= $this->resort->resort_id;
        $total_incidents = Incidents::where('resort_id',$resort_id)->count();
        $open_incidents = Incidents::where('resort_id', $resort_id)
        ->whereNotIn('status', ['Reported', 'Resolved'])
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
            $totalOpen = $incidents->whereNotIn('status', ['Resolved', 'Reported'])->count();
    
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
        return view('resorts.incident.dashboard.hrdashboard',compact('page_title','total_incidents','open_incidents','under_investigation_incidents','averageResolutionDays','committeeSummary','severityCounts','resolvedCount','unresolvedCount','categoryLabels','categoryData','totalIncidents'));
    }

    public function getDepartmentWiseParticipation()
    {
        $data = DB::table('incidents_investigation_meetings as meetings')
            ->join('incidents as i','i.id','=','meetings.incident_id')
            ->join('incidents_investigation_meetings_participants as participants', 'meetings.id', '=', 'participants.meeting_id')
            ->join('employees', 'participants.participant_id', '=', 'employees.id')
            ->join('resort_departments', 'employees.Dept_id', '=', 'resort_departments.id') // adjust if your schema is different
            ->select(
                DB::raw("DATE_FORMAT(meetings.meeting_date, '%b %Y') as month"),
                'resort_departments.name as department',
                DB::raw('COUNT(participants.id) as participation_count')
            )
            ->where('i.resort_id',$this->resort->resort_id)
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
    
        $incidentCounts = DB::table('incidents')
            ->select(DB::raw("DATE_FORMAT(incident_date, '%b %Y') as month"), DB::raw('COUNT(*) as total'))
            ->whereBetween('incident_date', [$start, $end])
            ->where('incidents.resort_id', $this->resort->resort_id)
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
        $nearingDeadline = DB::table('incidents as i')
            ->join('incidents_investigation as ii','ii.incident_id','=','i.id')
            ->where('i.resort_id', $resort_id)
            ->whereDate('ii.expected_resolution_date', '>=', $today)
            ->whereDate('ii.expected_resolution_date', '<=', $today->copy()->addDays(3))
            ->where('i.status', '!=', 'Resolved')
            ->count();

        $breachedTimelines = DB::table('incidents as i')
            ->join('incidents_investigation as ii','ii.incident_id','=','i.id')
            ->where('i.resort_id', $resort_id)
            ->whereDate('ii.expected_resolution_date', '<', $today)
            ->where('i.status', '!=', 'Resolved')
            ->count();

        $resolved = DB::table('incidents')
            ->where('resort_id', $resort_id)
            ->where('status', 'Resolved')
            ->count();

        $total = DB::table('incidents')->count();

        $resolvedPercentage = $total > 0 ? round(($resolved / $total) * 100) : 0;

        $openInvestigations = DB::table('incidents')
            ->whereNotIn('status', ['Reported', 'Resolved'])
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
                    'day_label' => $datetime->isToday() ? 'Today' : ($datetime->isTomorrow() ? 'Tomorrow' : $datetime->format('M d, Y')),
                    'id' => $meeting->id
                ];
            });

        return response()->json($meetings);
    }

    public function getPreventiveActions()
    {
        $actions = DB::table('incidents_investigation as ii')
            ->join('incidents as i','i.id','=','ii.incident_id')
            ->where('i.resort_id', $this->resort->resort_id)
            ->orderBy('i.incident_date', 'desc')
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

    public function getPendingResolutionApprovals()
    {
        $pendingResolutions = DB::table('incidents_investigation as ii')
            ->join('incidents as i', 'i.id', '=', 'ii.incident_id')
            ->leftJoin('incident_outcome_types as iot','iot.id','=','ii.outcome_type')
            ->leftJoin('incident_actions_taken as iat','iat.id','=','ii.action_taken')
            ->select('ii.id', 'i.incident_name', 'ii.investigation_findings', 'ii.follow_up_actions','iat.action_taken','iot.outcome_type')
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
            $query = DB::table('incidents_investigation as ii')
                ->join('incidents as i', 'i.id', '=', 'ii.incident_id')
                ->where('i.resort_id', $this->resort->resort_id)
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
                    return Carbon::parse($row->updated_at)->format('M d, Y');
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
        $open_incidents = Incidents::where('resort_id', $resort_id)
        ->whereNotIn('status', ['Reported', 'Resolved'])
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
            $totalOpen = $incidents->whereNotIn('status', ['Resolved', 'Reported'])->count();
    
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
        $resort_id= $this->resort->resort_id;
        $department_id = $this->resort->GetEmployee->Dept_id;
        // dd($department_id);
        $total_incidents = Incidents::whereHas('reporter', function($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->where('resort_id', $resort_id)
        ->count();        
        // dd($total_incidents);
       
        $pending_incidents = Incidents::whereHas('reporter', function($query) use ($department_id) {
            $query->where('Dept_id', $department_id);
        })
        ->where('resort_id', $resort_id)
        ->where('status', ['Reported'])
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
            $totalOpen = $incidents->whereNotIn('status', ['Resolved', 'Reported'])->count();
    
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
        return view('resorts.incident.dashboard.hoddashboard',compact('page_title','total_incidents','pending_incidents','under_investigation_incidents','averageResolutionDays','committeeSummary','severityCounts','resolvedCount','unresolvedCount','categoryLabels','categoryData','totalIncidents'));
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
        $resort_id = $this->resort->resort_id;
        $department_id = $this->resort->GetEmployee->Dept_id;
        $incidents =  Incidents::where('resort_id', $resort_id)
            ->whereHas('reporter', function ($query) use ($department_id) {
                $query->where('Dept_id', $department_id);
            })
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
                    'day_label' => $datetime->isToday() ? 'Today' : ($datetime->isTomorrow() ? 'Tomorrow' : $datetime->format('M d, Y')),
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
                    return Carbon::parse($row->updated_at)->format('M d, Y');
                })
                ->make(true);
        }
    
        return view('resorts.incident.incident.preventive_measures');
    }
}