<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Leave\LeaveController;
use App\Models\EmployeeOvertime;
use App\Services\LeaderDashboard;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** JSON endpoints behind the leader master dashboard's duty-roster card and action inbox. */
class LeaderDashboardController extends Controller
{
    /** GET ?date=YYYY-MM-DD → {rows: [...]} — see LeaderDashboard::roster(). */
    public function roster(Request $request)
    {
        $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        $dashboard = LeaderDashboard::forCurrentUser();
        if (!$dashboard) {
            return response()->json(['rows' => []]);
        }
        return response()->json(['rows' => $dashboard->roster($request->input('date', Carbon::today()->toDateString()))]);
    }

    /**
     * POST {id} — approve/reject one inbox request through the module's own endpoint
     * (so the approval chain, notifications and permission checks are the module's),
     * answered as {success, message}.
     */
    public function decide(Request $request, string $type, string $decision)
    {
        $request->validate(['id' => 'required|integer', 'reason' => 'nullable|string|max:1000']);
        $approve = $decision === 'approve';

        if ($type === 'leave') {
            $inner = Request::create($request->url(), 'POST', [
                'leave_id' => $request->id, 'action' => $approve ? 'Approved' : 'Rejected', 'reason' => $request->input('reason'),
            ]);
            $result = app(LeaveController::class)->handleLeaveAction($inner);
            $body = $result->getData(true);
            $ok = ($body['status'] ?? null) === 'success' || ($body['success'] ?? false) === true;
            return response()->json(['success' => $ok, 'message' => $body['message'] ?? ($ok ? 'Done.' : 'Could not update this leave request.')], $ok ? 200 : 422);
        }

        // overtime — UpdateOvertimeStatus() skips an entry the user may not manage and still
        // answers success, so check the row actually changed.
        $target = $approve ? 'approved' : 'rejected';
        $inner = Request::create($request->url(), 'POST', [
            'entries' => [['id' => $request->id, 'status' => $target, 'rejection_reason' => $request->input('reason')]],
        ]);
        app(TimeandAttendanceDashboardController::class)->UpdateOvertimeStatus($inner);
        $ok = EmployeeOvertime::where('id', $request->id)->where('resort_id', $this->resortId())->value('status') === $target;
        return response()->json(['success' => $ok, 'message' => $ok ? 'Overtime ' . $target . '.' : 'You cannot decide this overtime request.'], $ok ? 200 : 403);
    }

    private function resortId(): int
    {
        return (int) \Auth::guard('resort-admin')->user()->resort_id;
    }
}
