<?php

namespace App\Http\Controllers\Resorts\GrievanceAndDisciplinery;

use App\Http\Controllers\Controller;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\GrievanceAppeal;
use App\Models\GrievanceAppealHearing;
use App\Models\GrievanceCategory;
use App\Models\GrivanceSubmissionModel;
use App\Events\ResortNotificationEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class GrievanceAppealController extends Controller
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if (!$this->resort) {
            abort(401, 'Unauthenticated.');
        }
    }

    /**
     * Visibility rule for the appeals module:
     *  - HR / GM / master admin → all appeals in the resort
     *  - Submitter → their own appeals only
     *  - Everyone else → 403 (the page is permission-gated anyway)
     */
    private function visibleAppealsQuery()
    {
        $resortId = $this->resort->resort_id;
        $q = GrievanceAppeal::where('resort_id', $resortId);

        $emp = $this->resort->GetEmployee ?? $this->resort->getEmployee ?? null;
        if (!$emp || ($this->resort->is_master_admin ?? 0)) return $q;

        $rank = (int) ($emp->rank ?? 0);
        $rankMap = config('settings.Position_Rank');
        $available = $rankMap[$emp->rank ?? null] ?? '';

        // HR + GM see everything.
        if ($rank === 3 || $available === 'HR' || $rank === 8 || $available === 'GM') {
            return $q;
        }
        // HR-dept HOD/EXCOM also see everything (matches the L&D/incident
        // pattern Common::isHRDepartment).
        if (in_array($rank, [1, 2], true) && Common::isHRDepartment($emp->Dept_id ?? null)) {
            return $q;
        }
        // Otherwise restrict to appeals the user submitted themselves.
        return $q->where('submitted_by', $emp->id);
    }

    public function index()
    {
        if (Common::checkRouteWisePermission('GrievanceAndDisciplinery.Appeals.Index', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }
        $page_title = 'Grievance Appeals';
        return view('resorts.GrievanceAndDisciplinery.appeals.index', compact('page_title'));
    }

    public function list(Request $request)
    {
        if (!$request->ajax()) return response()->json([]);
        if (Common::checkRouteWisePermission('GrievanceAndDisciplinery.Appeals.Index', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }

        $rows = $this->visibleAppealsQuery()
            ->with(['grievance.category', 'submitter.resortAdmin', 'hearings'])
            ->orderByDesc('id')
            ->get();

        return datatables()->of($rows)
            ->addColumn('AppealNo', fn($r) => $r->appeal_no ?? '—')
            ->addColumn('GrievanceId', fn($r) => optional($r->grievance)->Grivance_id ?? '—')
            ->addColumn('Category', fn($r) => optional(optional($r->grievance)->category)->Category_Name ?? '—')
            ->addColumn('Submitter', function ($r) {
                $admin = optional(optional($r->submitter)->resortAdmin);
                $name = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));
                return $name !== '' ? $name : '—';
            })
            ->addColumn('SubmittedAt', fn($r) => $r->submitted_at ? $r->submitted_at->format('d M Y') : ($r->created_at ? $r->created_at->format('d M Y') : '—'))
            ->addColumn('Status', function ($r) {
                $cls = [
                    'Pending'    => 'badge-warning',
                    'In_Hearing' => 'badge-info',
                    'Resolved'   => 'badge-success',
                    'Rejected'   => 'badge-danger',
                    'Withdrawn'  => 'badge-secondary',
                ];
                $css = $cls[$r->status] ?? 'badge-secondary';
                return '<span class="badge ' . $css . '">' . str_replace('_', ' ', $r->status) . '</span>';
            })
            ->addColumn('Hearings', fn($r) => $r->hearings->count())
            ->addColumn('Decision', fn($r) => $r->decision ?? '—')
            ->addColumn('Action', function ($r) {
                $url = route('GrievanceAndDisciplinery.Appeals.Show', base64_encode($r->id));
                return '<a href="' . $url . '" class="btn btn-info btn-sm" title="Open"><i class="fa fa-eye"></i></a>';
            })
            ->rawColumns(['Status', 'Action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'grievance_id' => 'required|integer|exists:grivance_submission_models,id',
            'reason'       => 'required|string|max:2000',
        ]);

        $resortId = $this->resort->resort_id;
        $grievance = GrivanceSubmissionModel::where('id', $request->grievance_id)
            ->where('resort_id', $resortId)
            ->first();
        if (!$grievance) {
            return response()->json(['success' => false, 'message' => 'Grievance not found.'], 404);
        }

        $emp = $this->resort->GetEmployee ?? $this->resort->getEmployee ?? null;

        // Idempotency: a single grievance has at most one *active* appeal at
        // a time. If one exists in a non-terminal state, bounce.
        $existing = GrievanceAppeal::where('grievance_id', $grievance->id)
            ->where('resort_id', $resortId)
            ->whereIn('status', ['Pending', 'In_Hearing'])
            ->first();
        if ($existing) {
            return response()->json([
                'success'   => false,
                'message'   => 'An active appeal (' . $existing->appeal_no . ') already exists for this grievance.',
                'appeal_id' => base64_encode($existing->id),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $appeal = GrievanceAppeal::create([
                'resort_id'    => $resortId,
                'grievance_id' => $grievance->id,
                'submitted_by' => optional($emp)->id,
                'submitted_at' => now(),
                'status'       => 'Pending',
                'reason'       => $request->reason,
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Appeal store failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not file appeal.'], 500);
        }

        $this->notifyHrAndGm($appeal, 'Appeal Filed', 'A new appeal (' . $appeal->appeal_no . ') has been filed.');

        return response()->json([
            'success'   => true,
            'message'   => 'Appeal filed successfully (' . $appeal->appeal_no . ').',
            'appeal_id' => base64_encode($appeal->id),
            'redirect_url' => route('GrievanceAndDisciplinery.Appeals.Show', base64_encode($appeal->id)),
        ]);
    }

    public function show($id)
    {
        if (Common::checkRouteWisePermission('GrievanceAndDisciplinery.Appeals.Index', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }
        $decoded = base64_decode($id, true);
        if ($decoded === false || !is_numeric($decoded)) abort(404);

        $appeal = $this->visibleAppealsQuery()
            ->with(['grievance.category', 'grievance.GetEmployee.resortAdmin', 'submitter.resortAdmin', 'hearings'])
            ->where('id', (int) $decoded)
            ->first();
        if (!$appeal) abort(404, 'Appeal not found or not accessible.');

        $page_title = 'Appeal ' . $appeal->appeal_no;
        $employees = Employee::where('resort_id', $this->resort->resort_id)
            ->with('resortAdmin')
            ->get();

        return view('resorts.GrievanceAndDisciplinery.appeals.show', compact('page_title', 'appeal', 'employees'));
    }

    public function storeHearing(Request $request, $id)
    {
        $request->validate([
            'hearing_date' => 'required|date_format:d/m/Y',
            'hearing_time' => 'nullable|string|max:8',
            'location'     => 'nullable|string|max:255',
            'participants' => 'nullable|array',
            'participants.*' => 'integer|exists:employees,id',
        ]);
        $appeal = $this->loadAppealForWrite($id);
        if (in_array($appeal->status, ['Resolved', 'Rejected', 'Withdrawn'], true)) {
            return response()->json(['success' => false, 'message' => 'Appeal is already closed.'], 422);
        }

        $hearing = GrievanceAppealHearing::create([
            'appeal_id'    => $appeal->id,
            'hearing_date' => Carbon::createFromFormat('d/m/Y', $request->hearing_date)->format('Y-m-d'),
            'hearing_time' => $request->hearing_time,
            'location'     => $request->location,
            'status'       => 'Scheduled',
            'participants' => array_values(array_filter($request->participants ?? [])),
        ]);

        // Move the appeal into "In_Hearing" once the first hearing is scheduled.
        if ($appeal->status === 'Pending') {
            $appeal->update(['status' => 'In_Hearing']);
        }

        // Notify each scheduled participant.
        $msg = 'You are scheduled for hearing #' . $hearing->sequence_no . ' of appeal ' . $appeal->appeal_no
            . ' on ' . Carbon::parse($hearing->hearing_date)->format('d M Y') . '.';
        foreach ($hearing->participants ?? [] as $empId) {
            $this->fireBellNotification($empId, 'Hearing Scheduled', $msg, $appeal->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Hearing scheduled.',
            'hearing' => $hearing->fresh(),
        ]);
    }

    public function updateHearingStatus(Request $request, $id)
    {
        $request->validate([
            'hearing_id' => 'required|integer|exists:grievance_appeal_hearings,id',
            'status'     => 'required|in:Scheduled,Completed,Cancelled,Rescheduled',
            'outcome_notes' => 'nullable|string|max:5000',
        ]);
        $appeal = $this->loadAppealForWrite($id);
        $hearing = GrievanceAppealHearing::where('id', $request->hearing_id)
            ->where('appeal_id', $appeal->id)
            ->firstOrFail();

        $patch = ['status' => $request->status];
        if ($request->filled('outcome_notes')) $patch['outcome_notes'] = $request->outcome_notes;
        if ($request->status === 'Completed') $patch['completed_at'] = now();
        $hearing->update($patch);

        return response()->json(['success' => true, 'message' => 'Hearing updated.']);
    }

    public function decide(Request $request, $id)
    {
        $request->validate([
            'decision'       => 'required|in:Upheld,Overturned,Modified',
            'final_status'   => 'required|in:Resolved,Rejected',
            'decision_notes' => 'nullable|string|max:5000',
        ]);
        $appeal = $this->loadAppealForWrite($id);
        if (in_array($appeal->status, ['Resolved', 'Rejected', 'Withdrawn'], true)) {
            return response()->json(['success' => false, 'message' => 'Appeal is already closed.'], 422);
        }

        $appeal->update([
            'status'         => $request->final_status,
            'decision'       => $request->decision,
            'decision_at'    => now(),
            'decided_by'     => optional(Auth::guard('resort-admin')->user())->id,
            'decision_notes' => $request->decision_notes,
        ]);

        // Notify the submitter + HR/GM.
        if ($appeal->submitted_by) {
            $this->fireBellNotification(
                $appeal->submitted_by,
                'Appeal Decision',
                'Your appeal ' . $appeal->appeal_no . ' has been ' . $appeal->status . ' (' . $appeal->decision . ').',
                $appeal->id
            );
        }
        $this->notifyHrAndGm($appeal, 'Appeal Closed', 'Appeal ' . $appeal->appeal_no . ' has been ' . $appeal->status . '.');

        return response()->json(['success' => true, 'message' => 'Decision recorded.']);
    }

    public function withdraw($id)
    {
        $appeal = $this->loadAppealForWrite($id);
        if (in_array($appeal->status, ['Resolved', 'Rejected', 'Withdrawn'], true)) {
            return response()->json(['success' => false, 'message' => 'Appeal is already closed.'], 422);
        }
        // Only the submitter (or HR-equivalent) may withdraw.
        $emp = $this->resort->GetEmployee ?? $this->resort->getEmployee ?? null;
        $isSubmitter = $emp && $appeal->submitted_by == $emp->id;
        if (!$isSubmitter && !$this->isHrEquivalent()) {
            return response()->json(['success' => false, 'message' => 'Only the submitter or HR can withdraw an appeal.'], 403);
        }

        $appeal->update(['status' => 'Withdrawn', 'decision_at' => now()]);
        $this->notifyHrAndGm($appeal, 'Appeal Withdrawn', 'Appeal ' . $appeal->appeal_no . ' has been withdrawn.');

        return response()->json(['success' => true, 'message' => 'Appeal withdrawn.']);
    }

    /**
     * Helper: load an appeal by base64 id, scoped to the viewer + write
     * permission. Aborts with the right HTTP code on failure.
     */
    private function loadAppealForWrite($id): GrievanceAppeal
    {
        if (Common::checkRouteWisePermission('GrievanceAndDisciplinery.Appeals.Index', config('settings.resort_permissions.edit')) == false) {
            abort(403, 'Unauthorized to modify appeals.');
        }
        $decoded = base64_decode($id, true);
        if ($decoded === false || !is_numeric($decoded)) abort(404);
        $appeal = GrievanceAppeal::where('resort_id', $this->resort->resort_id)
            ->where('id', (int) $decoded)
            ->first();
        if (!$appeal) abort(404, 'Appeal not found.');
        return $appeal;
    }

    private function isHrEquivalent(): bool
    {
        $emp = $this->resort->GetEmployee ?? $this->resort->getEmployee ?? null;
        if (!$emp) return (bool) ($this->resort->is_master_admin ?? 0);
        $rank = (int) ($emp->rank ?? 0);
        $rankMap = config('settings.Position_Rank');
        $available = $rankMap[$emp->rank ?? null] ?? '';
        if ($rank === 3 || $available === 'HR' || $rank === 8 || $available === 'GM') return true;
        if (in_array($rank, [1, 2], true) && Common::isHRDepartment($emp->Dept_id ?? null)) return true;
        return false;
    }

    private function fireBellNotification($empId, $title, $message, $requestId = null): void
    {
        try {
            event(new ResortNotificationEvent(Common::nofitication(
                $this->resort->resort_id,
                10,
                $title,
                $message,
                $requestId,
                $empId,
                'Grievance Appeal'
            )));
        } catch (\Exception $e) {
            \Log::warning('Appeal notify failed (emp ' . $empId . '): ' . $e->getMessage());
        }
    }

    private function notifyHrAndGm(GrievanceAppeal $appeal, $title, $message): void
    {
        // Fan out to HR (rank 3) and GM (rank 8) employees in this resort.
        $recipients = Employee::where('resort_id', $appeal->resort_id)
            ->whereIn('rank', [3, 8])
            ->where('status', 'Active')
            ->pluck('id')
            ->toArray();
        foreach (array_unique($recipients) as $rid) {
            $this->fireBellNotification($rid, $title, $message, $appeal->id);
        }
    }
}
