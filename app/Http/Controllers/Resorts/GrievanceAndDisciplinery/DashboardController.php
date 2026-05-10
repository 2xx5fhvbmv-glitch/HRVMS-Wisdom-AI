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
        // The "People Relation" dashboard covers BOTH grievance AND
        // disciplinary records — counting only grievance was why HRs with
        // active disciplinary cases were seeing 0 across the tiles.
        $disciplinary = \App\Models\disciplinarySubmit::where('resort_id', $resort_id)->get();

        // Per-status tile counts. Status enum: pending | in_review | resolved | rejected.
        // Disciplinary uses CamelCase for some statuses (e.g. "In_Review") —
        // strtolower() normalises before comparing.
        $disciplinaryStatuses = $disciplinary->map(fn($d) => strtolower((string) $d->status));

        // Per the Figma, the Open / Pending / Closed tiles show split counts
        // ("Grievance: X | Disciplinary: Y"), while Expired Offense remains a
        // single combined number.
        $openGrievance     = $cases->whereNotIn('status', ['resolved', 'rejected'])->count();
        $openDisciplinary  = $disciplinaryStatuses->filter(fn($s) => !in_array($s, ['resolved', 'rejected'], true))->count();
        $pendingGrievance  = $cases->where('status', 'pending')->count();
        $pendingDisciplinary = $disciplinaryStatuses->filter(fn($s) => $s === 'pending')->count();
        $closedGrievance   = $cases->where('status', 'resolved')->count();
        $closedDisciplinary = $disciplinaryStatuses->filter(fn($s) => $s === 'resolved')->count();

        $totalcase    = $cases->count() + $disciplinary->count();
        $resolvedCase = $closedGrievance + $closedDisciplinary;
        // Legacy aggregate fields kept for any other place in the view that
        // still reads them.
        $openCases    = $openGrievance + $openDisciplinary;
        $pendingCases = $pendingGrievance + $pendingDisciplinary;
        $closedCases  = $resolvedCase;

        // Expired Offense — any open case (grievance OR disciplinary) older
        // than 30 days. Disciplinary uses created_at as the filing date.
        $expiredGrievance = $cases
            ->whereNotIn('status', ['resolved', 'rejected'])
            ->filter(function ($c) {
                return $c->Grivance_date_time
                    && Carbon::parse($c->Grivance_date_time)->lt(Carbon::now()->subDays(30));
            })->count();
        $expiredDisciplinary = $disciplinary
            ->filter(function ($d) {
                $st = strtolower((string) $d->status);
                if (in_array($st, ['resolved', 'rejected'], true)) return false;
                return $d->created_at && Carbon::parse($d->created_at)->lt(Carbon::now()->subDays(30));
            })->count();
        $expiredOffense = $expiredGrievance + $expiredDisciplinary;

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

        // Latest 3 active cases for the "Case Timelines" panel — union of
        // grievance + disciplinary so the panel doesn't sit empty when a
        // resort only has disciplinary cases (and vice versa).
        // Deadline = filed_at + N days, where N comes from the resort's
        // grivance_resoultion_time_line_models row (per-priority SLA).
        // For disciplinary, the row's own Expiry_date wins when it's a
        // real date; otherwise we fall back to the same priority SLA.
        $slaMap = $this->resolveSlaMap($resort_id);

        $grievanceTimelines = GrivanceSubmissionModel::with('category')
            ->where('resort_id', $resort_id)
            ->whereNotIn('status', ['resolved', 'rejected'])
            ->orderByDesc('Grivance_date_time')
            ->limit(5)
            ->get()
            ->map(function ($c) use ($slaMap) {
                $filed = $c->Grivance_date_time ? Carbon::parse($c->Grivance_date_time) : ($c->created_at ?? null);
                if (!$filed) return null;
                $filed = $filed instanceof Carbon ? $filed : Carbon::parse($filed);
                $deadline = $this->slaDeadline($filed, $c->Priority, $slaMap);
                return [
                    'name'         => 'Grievance — ' . (optional($c->category)->Category_Name ?? 'Untitled'),
                    'filed_date'   => $filed->format('d M Y'),
                    'deadline'     => $deadline->format('d M Y'),
                    'progress_pct' => $this->progressPct($filed, $deadline),
                    'priority'     => $c->Priority ?? 'Medium',
                    '_filed_at'    => $filed,
                ];
            })->filter();

        $disciplinaryTimelines = \App\Models\disciplinarySubmit::with(['category', 'offence'])
            ->where('resort_id', $resort_id)
            ->whereNotIn('status', ['resolved', 'rejected'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($d) use ($slaMap) {
                $filed = $d->created_at instanceof Carbon ? $d->created_at : ($d->created_at ? Carbon::parse($d->created_at) : null);
                if (!$filed) return null;
                $deadline = $this->disciplinaryDeadline($filed, $d->Priority, $d->Expiry_date, $slaMap);
                $catName  = optional($d->category)->DisciplinaryCategoryName ?? 'Untitled';
                $offence  = optional($d->offence)->OffensesName;
                $label    = 'Disciplinary — ' . $catName . ($offence ? ' (' . $offence . ')' : '');
                return [
                    'name'         => $label,
                    'filed_date'   => $filed->format('d M Y'),
                    'deadline'     => $deadline->format('d M Y'),
                    'progress_pct' => $this->progressPct($filed, $deadline),
                    'priority'     => $d->Priority ?? 'Medium',
                    '_filed_at'    => $filed,
                ];
            })->filter();

        $caseTimelines = $grievanceTimelines->concat($disciplinaryTimelines)
            ->sortByDesc(fn ($r) => $r['_filed_at']->getTimestamp())
            ->take(3)
            ->map(function ($r) { unset($r['_filed_at']); return $r; })
            ->values();

        $totalPercengate = $totalcase > 0 ? round(($resolvedCase / $totalcase) * 100, 2) : 0;

        // Appeals Section — uses the dedicated grievance_appeals +
        // grievance_appeal_hearings tables. If the resort hasn't filed any
        // appeals yet (new tables, empty), fall back to the legacy
        // SentToGM/Gm_Decision proxy so older installs still see numbers.
        $appealRows = \App\Models\GrievanceAppeal::where('resort_id', $resort_id)->get();
        $useNew = $appealRows->isNotEmpty();

        if ($useNew) {
            $appealsSubmitted = $appealRows->count();
            // A "pending hearing" is any appeal whose status hasn't reached
            // a terminal state. "Resolved" hearings = appeals that ended in
            // a decision.
            $hearingsPending  = $appealRows->whereIn('status', ['Pending', 'In_Hearing'])->count();
            $hearingsResolved = $appealRows->whereIn('status', ['Resolved', 'Rejected'])->count();
            // Group by the grievance's category — load the parent grievance
            // for each appeal in one go to avoid N+1.
            $grievanceIds = $appealRows->pluck('grievance_id')->filter()->unique();
            $catByGrievance = GrivanceSubmissionModel::whereIn('id', $grievanceIds)
                ->pluck('Grivance_Cat_id', 'id');
            $categoryNamesById = GrievanceCategory::where('resort_id', $resort_id)->pluck('Category_Name', 'id');
            $byCat = $appealRows->groupBy(fn($a) => $catByGrievance[$a->grievance_id] ?? 0);
            $appealsByCategoryLabels = [];
            $appealsByCategoryData   = [];
            foreach ($byCat as $catId => $rows) {
                $appealsByCategoryLabels[] = $categoryNamesById[$catId] ?? 'Uncategorised';
                $appealsByCategoryData[]   = $rows->count();
            }
        } else {
            $legacyAppeals = $cases->where('SentToGM', 'Yes');
            $appealsSubmitted = $legacyAppeals->count();
            $hearingsPending  = $legacyAppeals->where('Gm_Decision', 'Pending')->count();
            $hearingsResolved = $legacyAppeals->filter(fn($c) => !empty($c->Gm_Decision) && $c->Gm_Decision !== 'Pending')->count();
            $categoryNamesById = GrievanceCategory::where('resort_id', $resort_id)->pluck('Category_Name', 'id');
            $byCat = $legacyAppeals->groupBy('Grivance_Cat_id');
            $appealsByCategoryLabels = [];
            $appealsByCategoryData   = [];
            foreach ($byCat as $catId => $rows) {
                $appealsByCategoryLabels[] = $categoryNamesById[$catId] ?? 'Uncategorised';
                $appealsByCategoryData[]   = $rows->count();
            }
        }

        // Average Resolution Time across resolved grievance + disciplinary
        // cases — diff between created_at and updated_at, in hours.
        $resolvedDurations = collect();
        foreach ($cases->where('status', 'resolved') as $c) {
            if ($c->created_at && $c->updated_at) {
                $resolvedDurations->push($c->created_at->diffInHours($c->updated_at));
            }
        }
        foreach ($disciplinary as $d) {
            if (strtolower((string)$d->status) === 'resolved' && $d->created_at && $d->updated_at) {
                $resolvedDurations->push($d->created_at->diffInHours($d->updated_at));
            }
        }
        $avgResolutionHours = $resolvedDurations->count() > 0
            ? (int) round($resolvedDurations->avg())
            : 0;

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
            'openGrievance', 'openDisciplinary',
            'pendingGrievance', 'pendingDisciplinary',
            'closedGrievance', 'closedDisciplinary',
            'expiredOffense',
            'retaliationReports',
            'confidentialResolvedPct',
            'confidentialUnresolvedPct',
            'caseTimelines',
            'appealsSubmitted',
            'hearingsPending',
            'hearingsResolved',
            'appealsByCategoryLabels',
            'appealsByCategoryData',
            'avgResolutionHours'
        ));
    }


    public function Hod_dashboard(Request $request)
    {
        // HOD / EXCOM dashboard. Was a no-op before — the function had no
        // body and returned NULL, so Laravel served a blank page. Now scopes
        // grievance + disciplinary counts to the viewer's department so the
        // tiles match the lists they have access to.
        $resort_id = $this->resort->resort_id;
        $deptId = optional($this->resort->GetEmployee)->Dept_id;
        $dashboardLabel = $request->input('dashboard_label', 'HOD');
        $page_header = '<span class="arca-font">'.$dashboardLabel.'</span> Dashboard';
        $page_title = 'People Relation';

        // No dept on the user → render the same view with zero counts so we
        // don't crash, but flag in logs so HR can find the bad employee row.
        if (!$deptId) {
            \Log::warning('GrievanceAndDisciplinery Hod_dashboard: user has no Dept_id', [
                'user_id' => optional($this->resort)->id,
            ]);
        }

        $grievanceQuery = GrivanceSubmissionModel::where('resort_id', $resort_id)
            ->when($deptId, fn($q) => $q->whereHas('GetEmployee', fn($sq) => $sq->where('Dept_id', $deptId)));
        $disciplinaryQuery = \App\Models\disciplinarySubmit::where('resort_id', $resort_id)
            ->when($deptId, fn($q) => $q->whereHas('GetEmployee', fn($sq) => $sq->where('Dept_id', $deptId)));

        $cases        = $grievanceQuery->get();
        $disciplinary = $disciplinaryQuery->get();
        $disciplinaryStatuses = $disciplinary->map(fn($d) => strtolower((string) $d->status));

        $openGrievance       = $cases->whereNotIn('status', ['resolved', 'rejected'])->count();
        $openDisciplinary    = $disciplinaryStatuses->filter(fn($s) => !in_array($s, ['resolved', 'rejected'], true))->count();
        $pendingGrievance    = $cases->where('status', 'pending')->count();
        $pendingDisciplinary = $disciplinaryStatuses->filter(fn($s) => $s === 'pending')->count();
        $closedGrievance     = $cases->where('status', 'resolved')->count();
        $closedDisciplinary  = $disciplinaryStatuses->filter(fn($s) => $s === 'resolved')->count();

        $totalcase    = $cases->count() + $disciplinary->count();
        $resolvedCase = $closedGrievance + $closedDisciplinary;
        $openCases    = $openGrievance + $openDisciplinary;
        $pendingCases = $pendingGrievance + $pendingDisciplinary;
        $closedCases  = $resolvedCase;
        $expiredOffense = $cases
                ->whereNotIn('status', ['resolved', 'rejected'])
                ->filter(fn($c) => $c->Grivance_date_time && Carbon::parse($c->Grivance_date_time)->lt(Carbon::now()->subDays(30)))
                ->count()
            + $disciplinary
                ->filter(function ($d) {
                    $st = strtolower((string) $d->status);
                    if (in_array($st, ['resolved', 'rejected'], true)) return false;
                    return $d->created_at && Carbon::parse($d->created_at)->lt(Carbon::now()->subDays(30));
                })->count();

        $DelegatedCases   = $cases->where('Assigned', 'Yes')->count();
        $PendingApprovals = $cases->where('SentToGM', 'Yes')->where('Gm_Decision', 'Pending')->count();
        $retaliationReports = GrievanceNonRetaliation::where('resort_id', $resort_id)
            ->when($deptId, fn($q) => $q->where('Dept_id', $deptId))
            ->count();

        $confidentialCases   = $cases->where('Request_Identity_Disclosure', 'No');
        $confidentialTotal   = $confidentialCases->count();
        $confidentialResol   = $confidentialCases->where('status', 'resolved')->count();
        $confidentialResolvedPct   = $confidentialTotal > 0 ? round(($confidentialResol / $confidentialTotal) * 100) : 0;
        $confidentialUnresolvedPct = $confidentialTotal > 0 ? 100 - $confidentialResolvedPct : 0;

        $grivanceCategoryWiseCount = [];
        GrievanceCategory::where('resort_id', $resort_id)
            ->get()
            ->each(function ($category) use ($cases, &$grivanceCategoryWiseCount) {
                $grivanceCategoryWiseCount[$category->Category_Name] = $cases->where('Grivance_Cat_id', $category->id)->count();
            });

        // HOD/EXCOM Case Timelines — union of grievance + disciplinary
        // restricted to the dept's own employees so the panel matches
        // the rest of this dashboard's scope. Same SLA source as HR.
        $slaMap = $this->resolveSlaMap($resort_id);

        $grievanceTimelines = $grievanceQuery->with('category')
            ->whereNotIn('status', ['resolved', 'rejected'])
            ->orderByDesc('Grivance_date_time')
            ->limit(5)
            ->get()
            ->map(function ($c) use ($slaMap) {
                $filed = $c->Grivance_date_time ? Carbon::parse($c->Grivance_date_time) : ($c->created_at ?? null);
                if (!$filed) return null;
                $filed = $filed instanceof Carbon ? $filed : Carbon::parse($filed);
                $deadline = $this->slaDeadline($filed, $c->Priority, $slaMap);
                return [
                    'name'         => 'Grievance — ' . (optional($c->category)->Category_Name ?? 'Untitled'),
                    'filed_date'   => $filed->format('d M Y'),
                    'deadline'     => $deadline->format('d M Y'),
                    'progress_pct' => $this->progressPct($filed, $deadline),
                    'priority'     => $c->Priority ?? 'Medium',
                    '_filed_at'    => $filed,
                ];
            })->filter();

        $disciplinaryTimelines = $disciplinaryQuery->with(['category', 'offence'])
            ->whereNotIn('status', ['resolved', 'rejected'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($d) use ($slaMap) {
                $filed = $d->created_at instanceof Carbon ? $d->created_at : ($d->created_at ? Carbon::parse($d->created_at) : null);
                if (!$filed) return null;
                $deadline = $this->disciplinaryDeadline($filed, $d->Priority, $d->Expiry_date, $slaMap);
                $catName  = optional($d->category)->DisciplinaryCategoryName ?? 'Untitled';
                $offence  = optional($d->offence)->OffensesName;
                return [
                    'name'         => 'Disciplinary — ' . $catName . ($offence ? ' (' . $offence . ')' : ''),
                    'filed_date'   => $filed->format('d M Y'),
                    'deadline'     => $deadline->format('d M Y'),
                    'progress_pct' => $this->progressPct($filed, $deadline),
                    'priority'     => $d->Priority ?? 'Medium',
                    '_filed_at'    => $filed,
                ];
            })->filter();

        $caseTimelines = $grievanceTimelines->concat($disciplinaryTimelines)
            ->sortByDesc(fn ($r) => $r['_filed_at']->getTimestamp())
            ->take(3)
            ->map(function ($r) { unset($r['_filed_at']); return $r; })
            ->values();

        $totalPercengate = $totalcase > 0 ? round(($resolvedCase / $totalcase) * 100, 2) : 0;

        // Appeals + average-resolution stats — same approach as
        // HR_Dashobard but the appeals are restricted to grievances filed
        // by employees in this HOD's department.
        $deptGrievanceIds = $cases->pluck('id');
        $appealRows = \App\Models\GrievanceAppeal::where('resort_id', $resort_id)
            ->whereIn('grievance_id', $deptGrievanceIds)
            ->get();
        $useNew = $appealRows->isNotEmpty();

        if ($useNew) {
            $appealsSubmitted = $appealRows->count();
            $hearingsPending  = $appealRows->whereIn('status', ['Pending', 'In_Hearing'])->count();
            $hearingsResolved = $appealRows->whereIn('status', ['Resolved', 'Rejected'])->count();
            $catByGrievance = GrivanceSubmissionModel::whereIn('id', $appealRows->pluck('grievance_id')->unique())
                ->pluck('Grivance_Cat_id', 'id');
            $categoryNamesById = GrievanceCategory::where('resort_id', $resort_id)->pluck('Category_Name', 'id');
            $byCat = $appealRows->groupBy(fn($a) => $catByGrievance[$a->grievance_id] ?? 0);
            $appealsByCategoryLabels = [];
            $appealsByCategoryData   = [];
            foreach ($byCat as $catId => $rows) {
                $appealsByCategoryLabels[] = $categoryNamesById[$catId] ?? 'Uncategorised';
                $appealsByCategoryData[]   = $rows->count();
            }
        } else {
            $legacyAppeals = $cases->where('SentToGM', 'Yes');
            $appealsSubmitted = $legacyAppeals->count();
            $hearingsPending  = $legacyAppeals->where('Gm_Decision', 'Pending')->count();
            $hearingsResolved = $legacyAppeals->filter(fn($c) => !empty($c->Gm_Decision) && $c->Gm_Decision !== 'Pending')->count();
            $categoryNamesById = GrievanceCategory::where('resort_id', $resort_id)->pluck('Category_Name', 'id');
            $byCat = $legacyAppeals->groupBy('Grivance_Cat_id');
            $appealsByCategoryLabels = [];
            $appealsByCategoryData   = [];
            foreach ($byCat as $catId => $rows) {
                $appealsByCategoryLabels[] = $categoryNamesById[$catId] ?? 'Uncategorised';
                $appealsByCategoryData[]   = $rows->count();
            }
        }
        $resolvedDurations = collect();
        foreach ($cases->where('status', 'resolved') as $c) {
            if ($c->created_at && $c->updated_at) {
                $resolvedDurations->push($c->created_at->diffInHours($c->updated_at));
            }
        }
        foreach ($disciplinary as $d) {
            if (strtolower((string)$d->status) === 'resolved' && $d->created_at && $d->updated_at) {
                $resolvedDurations->push($d->created_at->diffInHours($d->updated_at));
            }
        }
        $avgResolutionHours = $resolvedDurations->count() > 0
            ? (int) round($resolvedDurations->avg())
            : 0;

        return view('resorts.GrievanceAndDisciplinery.dashboard.hrdashboard', compact(
            'page_title',
            'grivanceCategoryWiseCount',
            'totalPercengate',
            'DelegatedCases',
            'PendingApprovals',
            'openCases',
            'pendingCases',
            'closedCases',
            'openGrievance', 'openDisciplinary',
            'pendingGrievance', 'pendingDisciplinary',
            'closedGrievance', 'closedDisciplinary',
            'expiredOffense',
            'retaliationReports',
            'confidentialResolvedPct',
            'confidentialUnresolvedPct',
            'caseTimelines',
            'appealsSubmitted',
            'hearingsPending',
            'hearingsResolved',
            'appealsByCategoryLabels',
            'appealsByCategoryData',
            'avgResolutionHours'
        ));
    }

    public function excom_dashboard()
    {
        request()->merge(['dashboard_label' => 'XCOM']);
        return $this->Hod_dashboard(request());
    }

    /**
     * Per-resort SLA in days, keyed by Priority. Source of truth is
     * grivance_resoultion_time_line_models (one row per resort). Returns
     * sane defaults (High 7 / Medium 14 / Low 28) when the resort hasn't
     * configured one yet, so the timeline panel always shows something.
     */
    private function resolveSlaMap(int $resort_id): array
    {
        $row = DB::table('grivance_resoultion_time_line_models')
            ->where('resort_id', $resort_id)
            ->first();

        return [
            'High'   => (int) ($row->HighPriority   ?? 7),
            'Medium' => (int) ($row->MediumPriority ?? 14),
            'Low'    => (int) ($row->LowPriority    ?? 28),
        ];
    }

    private function slaDeadline(Carbon $filed, ?string $priority, array $slaMap): Carbon
    {
        $days = $slaMap[$priority ?? 'Medium'] ?? $slaMap['Medium'];
        return (clone $filed)->addDays(max(1, $days));
    }

    /**
     * Disciplinary deadline. Prefers the row's own Expiry_date when it's a
     * real date; falls back to the priority-based SLA otherwise (older
     * rows without Expiry_date set, or '0000-00-00').
     */
    private function disciplinaryDeadline(Carbon $filed, ?string $priority, $expiry, array $slaMap): Carbon
    {
        if ($expiry && $expiry !== '0000-00-00' && $expiry !== '0000-00-00 00:00:00') {
            try {
                $dt = $expiry instanceof Carbon ? $expiry : Carbon::parse($expiry);
                if ($dt->year > 1) return $dt;
            } catch (\Exception $e) { /* fall through to SLA */ }
        }
        return $this->slaDeadline($filed, $priority, $slaMap);
    }

    private function progressPct(Carbon $filed, Carbon $deadline): int
    {
        $totalDays = max(1, $filed->diffInDays($deadline));
        $usedDays  = max(0, min($totalDays, $filed->diffInDays(Carbon::now())));
        $pct       = (int) round(($usedDays / $totalDays) * 100);
        return max(5, min(100, $pct));
    }
}
