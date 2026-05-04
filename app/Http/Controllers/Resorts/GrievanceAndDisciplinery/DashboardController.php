<?php

namespace App\Http\Controllers\Resorts\GrievanceAndDisciplinery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use DB;
use URL;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\GrievanceCategory;
use App\Models\GrivanceSubmissionModel;
use App\Models\GrievanceNonRetaliation;
class DashboardController extends Controller
{
    protected $resort;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = auth()->guard('resort-admin')->user();
        if (!$this->resort) return;

        // Build the under-employees list only for non-master admins. Guard
        // every relation hop — non-master admins without a linked Employee
        // record would otherwise hit "property on null".
        if (($this->resort->is_master_admin ?? 0) == 0) {
            $employee = $this->resort->GetEmployee ?? null;
            if ($employee && isset($employee->id)) {
                $this->underEmp_id = Common::getSubordinates($employee->id);
            }
        }
    }

    public function Admin_dashboard(Request $request)
    {

    }
    public function HR_Dashobard(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $cases = GrivanceSubmissionModel::where('resort_id', $resort_id)->get();

        // Per-status tile counts. Status enum: pending | in_review | resolved | rejected.
        $totalcase        = $cases->count();
        $resolvedCase     = $cases->where('status', 'resolved')->count();
        $openCases        = $cases->whereNotIn('status', ['resolved', 'rejected'])->count();
        $pendingCases     = $cases->where('status', 'pending')->count();
        $closedCases      = $resolvedCase;

        // Expired Offense — open cases whose Grivance_date_time is older than
        // the 30-day SLA. Use a configurable threshold once the SLA is known.
        $expiredOffense = $cases
            ->whereNotIn('status', ['resolved', 'rejected'])
            ->filter(function ($c) {
                return $c->Grivance_date_time
                    && Carbon::parse($c->Grivance_date_time)->lt(Carbon::now()->subDays(30));
            })->count();

        $DelegatedCases   = $cases->where('Assigned', 'Yes')->count();
        $PendingApprovals = $cases->where('SentToGM', 'Yes')->where('Gm_Decision', 'Pending')->count();

        // Retaliation Reports Filed — rows in grievance_non_retaliations for
        // this resort (covers policy submissions filed by employees).
        $retaliationReports = GrievanceNonRetaliation::where('resort_id', $resort_id)->count();

        // Confidential cases split — uses the Request_Identity_Disclosure
        // field on the submission. 'No' means the employee asked to stay
        // anonymous → counted as a confidential case.
        $confidentialCases   = $cases->where('Request_Identity_Disclosure', 'No');
        $confidentialTotal   = $confidentialCases->count();
        $confidentialResol   = $confidentialCases->where('status', 'resolved')->count();
        $confidentialResolvedPct   = $confidentialTotal > 0 ? round(($confidentialResol / $confidentialTotal) * 100) : 0;
        $confidentialUnresolvedPct = $confidentialTotal > 0 ? 100 - $confidentialResolvedPct : 0;

        // Per-category counts for the Grievances widget. Map was empty before
        // so the panel always rendered blank.
        $grivanceCategoryWiseCount = [];
        GrievanceCategory::where('resort_id', $resort_id)
            ->get()
            ->each(function ($category) use ($cases, &$grivanceCategoryWiseCount) {
                $grivanceCategoryWiseCount[$category->Category_Name]
                    = $cases->where('Grivance_Cat_id', $category->id)->count();
            });

        // Latest 3 active cases for the "Case Timelines" panel.
        // Deadline = Grivance_date_time + 28 days as a placeholder SLA.
        $caseTimelines = GrivanceSubmissionModel::with('category')
            ->where('resort_id', $resort_id)
            ->whereNotIn('status', ['resolved', 'rejected'])
            ->orderByDesc('Grivance_date_time')
            ->limit(3)
            ->get()
            ->map(function ($c) {
                $filed    = $c->Grivance_date_time ? Carbon::parse($c->Grivance_date_time) : Carbon::parse($c->created_at);
                $deadline = (clone $filed)->addDays(28);
                $totalDays  = $filed->diffInDays($deadline) ?: 1;
                $usedDays   = max(0, min($totalDays, $filed->diffInDays(Carbon::now())));
                $progressPct = (int) round(($usedDays / $totalDays) * 100);
                return [
                    'name'        => optional($c->category)->Category_Name ?? 'Untitled Case',
                    'filed_date'  => $filed->format('d/m/Y'),
                    'deadline'    => $deadline->format('d/m/Y'),
                    'progress_pct' => max(5, min(100, $progressPct)),
                    'priority'    => $c->Priority ?? 'Medium',
                ];
            });

        $totalPercengate = $totalcase > 0 ? round(($resolvedCase / $totalcase) * 100, 2) : 0;
        $page_title = 'People Relation';

        return view('resorts.GrievanceAndDisciplinery.dashboard.hrdashboard', compact(
            'page_title',
            'grivanceCategoryWiseCount',
            'totalPercengate',
            'DelegatedCases',
            'PendingApprovals',
            'openCases',
            'pendingCases',
            'closedCases',
            'expiredOffense',
            'retaliationReports',
            'confidentialResolvedPct',
            'confidentialUnresolvedPct',
            'caseTimelines'
        ));
    }

    
    public function Hod_dashboard(Request $request)
    {
        $dashboardLabel = request('dashboard_label', 'HOD');
        $page_header = '<span class="arca-font">'.$dashboardLabel.'</span> Dashboard';
    }

    public function excom_dashboard()
    {
        request()->merge(['dashboard_label' => 'XCOM']);
        return $this->Hod_dashboard(request());
    }

}
