<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class PurgeWrongDepartmentPendingHodTravelPassApprovers extends Migration
{
    /**
     * At least one boarding/island pass (id 492, employee Rani Khan,
     * Dept_id 80) has TWO approver_rank=2 (HOD) rows in
     * employee_travel_pass_status — one correctly for the employee's own
     * department HOD (already Approved), and a second, stray one for an
     * HOD from a completely unrelated department (id 475, Dept_id 112)
     * that never got actioned and sits Pending forever. Since the
     * overall pass "Approved" status requires every status row to be
     * Approved, this stray row keeps the pass stuck on Pending and the
     * HOD dashboard keeps showing Approve/Reject for a pass that was
     * genuinely already approved by the real department HOD.
     *
     * boardingPassAdd()'s current HOD lookup is correctly scoped to
     * Employee::where('rank',2)->where('Dept_id', $employee->Dept_id)
     * (verified by reading the live code), so this can only be leftover
     * data from before that scoping existed — not something the current
     * code can produce again. Delete only the specific anomaly: a
     * Pending, rank=2 approver row whose own department doesn't match
     * the pass owner's department, on a pass where the correct
     * department's rank=2 row is already Approved (so the real approval
     * already happened and this row is pure dead weight, not a
     * legitimate outstanding approval).
     */
    public function up()
    {
        $rows = DB::table('employee_travel_pass_status as s')
            ->join('employee_travel_passes as p', 'p.id', '=', 's.travel_pass_id')
            ->join('employees as owner', 'owner.id', '=', 'p.employee_id')
            ->join('employees as approver', 'approver.id', '=', 's.approver_id')
            ->where('s.approver_rank', 2)
            ->where('s.status', 'Pending')
            ->whereColumn('approver.Dept_id', '!=', 'owner.Dept_id')
            ->select('s.id', 's.travel_pass_id')
            ->get();

        foreach ($rows as $row) {
            $correctDeptHodAlreadyApproved = DB::table('employee_travel_pass_status as s2')
                ->join('employees as approver2', 'approver2.id', '=', 's2.approver_id')
                ->join('employee_travel_passes as p2', 'p2.id', '=', 's2.travel_pass_id')
                ->join('employees as owner2', 'owner2.id', '=', 'p2.employee_id')
                ->where('s2.travel_pass_id', $row->travel_pass_id)
                ->where('s2.approver_rank', 2)
                ->where('s2.status', 'Approved')
                ->whereColumn('approver2.Dept_id', '=', 'owner2.Dept_id')
                ->exists();

            if ($correctDeptHodAlreadyApproved) {
                DB::table('employee_travel_pass_status')->where('id', $row->id)->delete();

                // Deleting the stray row alone doesn't flip the pass's own
                // status — that only happens inside BoardingPassStatusUpdate's
                // $allApproved check when an approver acts, which won't
                // run again for this historical row. Re-evaluate it here:
                // if every remaining status row for this pass is now
                // Approved, the pass itself should be too.
                $stillPending = DB::table('employee_travel_pass_status')
                    ->where('travel_pass_id', $row->travel_pass_id)
                    ->where('status', '!=', 'Approved')
                    ->exists();

                if (!$stillPending) {
                    DB::table('employee_travel_passes')
                        ->where('id', $row->travel_pass_id)
                        ->where('status', 'Pending')
                        ->update(['status' => 'Approved']);
                }
            }
        }
    }

    public function down()
    {
        // Not reversible — the deleted rows were assigned to the wrong
        // department's approver and shouldn't be restored.
    }
}
