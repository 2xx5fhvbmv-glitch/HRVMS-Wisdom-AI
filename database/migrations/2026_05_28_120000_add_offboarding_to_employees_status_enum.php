<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add 'Offboarding' to the `employees.status` enum and backfill any
     * employee who's currently 'Terminated' but still has an open exit
     * clearance (`employee_resignation.status` not 'Completed').
     *
     * Background: probation failure used to flip status straight to
     * 'Terminated' the instant probation was marked Failed — but the
     * actual exit clearance (HOD/HR sign-off, F&F settlement, etc.) only
     * runs AFTER that. So the employee was being shown as fully Terminated
     * while their offboarding was still in flight. Adding 'Offboarding'
     * gives that period its own status; the controllers will be updated
     * to set 'Offboarding' on probation-fail/resignation-approve, and
     * flip to 'Terminated' only when the resignation is marked Completed.
     */
    public function up(): void
    {
        // 1. Widen the enum. ALTER TABLE … MODIFY COLUMN is the only way
        //    Laravel migrations can change ENUM values on MariaDB/MySQL.
        DB::statement(
            "ALTER TABLE employees MODIFY COLUMN status ENUM("
            . "'Active','Inactive','Terminated','Resigned','On Leave',"
            . "'Suspended','Onboarding','Offboarding'"
            . ") NOT NULL DEFAULT 'Active'"
        );

        // 2. Backfill: anyone who's Terminated AND has an open resignation
        //    (i.e. exit clearance hasn't been marked Completed) belongs in
        //    the new 'Offboarding' bucket. This rescues rows that were
        //    flipped to Terminated by the old probation flow before this
        //    migration ran.
        $openResignationEmpIds = DB::table('employee_resignation')
            ->whereNotIn('status', ['Completed'])
            ->pluck('employee_id')
            ->unique()
            ->all();

        if (!empty($openResignationEmpIds)) {
            DB::table('employees')
                ->whereIn('id', $openResignationEmpIds)
                ->where('status', 'Terminated')
                ->update(['status' => 'Offboarding']);
        }
    }

    /**
     * Reverse the enum widening. Any rows currently set to 'Offboarding'
     * are folded back into 'Terminated' first so the ALTER doesn't fail
     * with "Data truncated for column 'status'".
     */
    public function down(): void
    {
        DB::table('employees')
            ->where('status', 'Offboarding')
            ->update(['status' => 'Terminated']);

        DB::statement(
            "ALTER TABLE employees MODIFY COLUMN status ENUM("
            . "'Active','Inactive','Terminated','Resigned','On Leave',"
            . "'Suspended','Onboarding'"
            . ") NOT NULL DEFAULT 'Active'"
        );
    }
};
