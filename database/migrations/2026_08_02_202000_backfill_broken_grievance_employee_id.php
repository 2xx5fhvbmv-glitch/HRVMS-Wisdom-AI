<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * grivance_submission_models.Employee_id used to be read straight from the
 * mobile app's request body — the app sends the employee CODE (e.g. "DR-20"),
 * not the numeric employees.id the column expects, so it silently truncated
 * to 0 under non-strict SQL mode. The API no longer accepts a client-supplied
 * Employee_id at all (always the authenticated submitter's own id now), so
 * this can't happen again — but existing rows stuck at 0 need a one-time
 * backfill. created_by (auto-set by the model from the session at submit
 * time, unaffected by the bug) reliably identifies who actually submitted
 * each one, so it's used to resolve the real employee id.
 */
return new class extends Migration
{
    public function up()
    {
        $broken = DB::table('grivance_submission_models')
            ->where('Employee_id', 0)
            ->get(['id', 'created_by']);

        foreach ($broken as $row) {
            if (empty($row->created_by)) {
                continue;
            }
            $employeeId = DB::table('employees')
                ->where('Admin_Parent_id', $row->created_by)
                ->value('id');
            if ($employeeId) {
                DB::table('grivance_submission_models')
                    ->where('id', $row->id)
                    ->update(['Employee_id' => $employeeId]);
            }
        }
    }

    public function down()
    {
        // Not reversible — the original 0 values carried no recoverable
        // information (that's the entire bug being fixed here).
    }
};
