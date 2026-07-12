<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ComplianceController's TIN-requirement check (People Module section) ran
 * `Employee::with([...])->get()` with no resort_id filter — the only one of
 * six identical blocks in that file missing it. Every resort's scheduled
 * compliance run pulled EVERY employee in the whole database and notified
 * its own HR about salary/TIN details for employees belonging to OTHER
 * resorts (code fix landed separately). This purges the already-sent
 * cross-tenant notifications and the matching compliance-breach rows they
 * created — safe to wipe entirely, the next scheduled run regenerates them
 * correctly scoped to each resort's own employees.
 */
class PurgeLeakedTinComplianceNotifications extends Migration
{
    public function up()
    {
        $notifications = DB::table('resort_notifications')
            ->where('module', 'People Management (TIN Requirement)')
            ->delete();

        $compliances = DB::table('compliances')
            ->where('module_name', 'People Management')
            ->where('compliance_breached_name', 'TIN Requirement')
            ->delete();

        \Log::info("PurgeLeakedTinComplianceNotifications: deleted {$notifications} notification rows, {$compliances} compliance rows.");
    }

    public function down()
    {
        // Not reversible — purged rows were cross-tenant leaked data with no
        // clean way to distinguish which were legitimately this resort's own
        // vs. leaked from another resort, and the next scheduled compliance
        // run regenerates correct ones anyway.
    }
}
