<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Line Worker (rank 6) / Supervisor (rank 5) leave requests should only ever
 * get ONE approval stage (their reporting manager) — this is the current,
 * deliberate rule (see the "Leave (rank) -> approver chain" comment in
 * API\LeaveController::leaveAdd()). An older version of that chain-building
 * code instead appended extra stages (EXCOM/HOD/GM-style escalation) even
 * for rank 5/6 requesters, so some historical leave requests still carry a
 * second (or third) approver who was never supposed to be in the chain at
 * all — one who has no real reason to ever act on it, permanently stalling
 * the request even after the employee's actual reporting manager approved.
 *
 * Confirmed against real data (resort 26): a maternity leave request had
 * its rightful approver (the employee's HOD) approve correctly, but stayed
 * stuck on a second, unrelated-department HOD who was never a legitimate
 * part of that chain.
 *
 * For every Pending rank-5/6 leave with more than one stage: keep only the
 * first (earliest) stage — the real reporting-manager approval — and drop
 * the rest. If that first stage was already Approved, the leave itself is
 * now fully approved (its one required approver already said yes), so flip
 * its status too. If the first stage is still Pending, leave the parent
 * status alone — it'll resolve normally once the real approver acts, now
 * without a bogus second stage to get stuck on afterwards.
 */
return new class extends Migration
{
    public function up()
    {
        $leaves = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->whereIn('e.rank', ['5', '6'])
            ->where('el.status', 'Pending')
            ->select('el.id')
            ->get();

        foreach ($leaves as $leave) {
            $stages = DB::table('employees_leaves_status')
                ->where('leave_request_id', $leave->id)
                ->orderBy('id')
                ->get();

            if ($stages->count() <= 1) {
                continue;
            }

            $firstStage = $stages->first();
            $extraStageIds = $stages->slice(1)->pluck('id');

            DB::table('employees_leaves_status')->whereIn('id', $extraStageIds)->delete();

            if ($firstStage->status === 'Approved') {
                DB::table('employees_leaves')
                    ->where('id', $leave->id)
                    ->where('status', 'Pending')
                    ->update(['status' => 'Approved']);
            }
        }
    }

    public function down()
    {
        // The removed stages carried no information worth preserving (they
        // were never legitimate approvers for these ranks) — not reversible.
    }
};
