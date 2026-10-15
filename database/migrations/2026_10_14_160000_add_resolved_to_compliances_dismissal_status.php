<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ComplianceController::resolveStaleBreaches() and
 * JobDescriptionEmployeeRecord::syncCompliance() write
 * Dismissal_status = 'Resolved' (auto-closed, distinct from a manual
 * 'Rejected'), but the enum only allowed Pending/Rejected — non-strict
 * MySQL stored '' instead. Add the value and repair those rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE compliances MODIFY Dismissal_status ENUM('Pending','Rejected','Resolved') NULL DEFAULT 'Pending'");
        DB::table('compliances')->where('status', 'Resolved')->where('Dismissal_status', '')->update(['Dismissal_status' => 'Resolved']);
    }

    public function down(): void
    {
        DB::table('compliances')->where('Dismissal_status', 'Resolved')->update(['Dismissal_status' => '']);
        DB::statement("ALTER TABLE compliances MODIFY Dismissal_status ENUM('Pending','Rejected') NULL DEFAULT 'Pending'");
    }
};
