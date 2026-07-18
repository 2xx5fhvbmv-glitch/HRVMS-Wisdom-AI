<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class PurgeStrayWrongDepartmentHodTravelPassApprovers extends Migration
{
    /**
     * Generalizes 2026_07_16_010000_purge_wrong_department_pending_hod_travel_pass_approvers.php,
     * which only purged a stray wrong-department rank=2 row when the
     * correct department's rank=2 row was already Approved. A newer
     * case (pass id 495, employee Rani Khan, Dept_id 80/F&B) has BOTH
     * rows still Pending — the real HOD (177, Dept 80) never approved
     * because the stray row (475, Dept 112) sits in the same approval
     * chain, and BoardingPassController::BoardingPassStatusUpdate()'s
     * completion check (`where('status','!=','Approved')->doesntExist()`)
     * requires EVERY status row resolved, so this pass could never
     * reach 'Approved' even if the real HOD acted — and its earlier
     * approve-lookup step (grabbing the first unresolved status row)
     * can hand the real HOD's approve action the wrong row entirely,
     * rejecting it with "must first be approved by [wrong rank]".
     *
     * Delete any Pending, rank=2 approver row whose own department
     * doesn't match the pass owner's department, as long as the
     * correct department's rank=2 row also exists for that pass
     * (regardless of ITS status this time) — the stray row is always
     * illegitimate dead weight, never something the wrong-department
     * "approver" is actually meant to act on.
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
            $correctDeptHodRowExists = DB::table('employee_travel_pass_status as s2')
                ->join('employees as approver2', 'approver2.id', '=', 's2.approver_id')
                ->join('employee_travel_passes as p2', 'p2.id', '=', 's2.travel_pass_id')
                ->join('employees as owner2', 'owner2.id', '=', 'p2.employee_id')
                ->where('s2.travel_pass_id', $row->travel_pass_id)
                ->where('s2.approver_rank', 2)
                ->where('s2.id', '!=', $row->id)
                ->whereColumn('approver2.Dept_id', '=', 'owner2.Dept_id')
                ->exists();

            if ($correctDeptHodRowExists) {
                DB::table('employee_travel_pass_status')->where('id', $row->id)->delete();
            }
        }
    }

    public function down()
    {
        // Not reversible — the deleted rows were assigned to the wrong
        // department's approver and shouldn't be restored.
    }
}
