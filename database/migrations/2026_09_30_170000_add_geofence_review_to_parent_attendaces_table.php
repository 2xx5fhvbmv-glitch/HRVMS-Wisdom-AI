<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A-04 (decided 2026-09-27): a punch outside the assigned geofence is
 * allowed but flagged for the employee's HOD/EXCOM to confirm or reject.
 * null = never flagged; 'pending' = awaiting review; 'confirmed'/'rejected'
 * = reviewed. Default is paid (Status stays 'Present') unless rejected,
 * which flips Status to 'Absent' — see A-04 in
 * API/TimeAndAttendanceController::reviewGeofenceFlag().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_attendaces', function (Blueprint $table) {
            $table->string('geofence_review_status')->nullable()->after('Status');
            $table->unsignedBigInteger('geofence_reviewed_by')->nullable()->after('geofence_review_status');
        });
    }

    public function down(): void
    {
        Schema::table('parent_attendaces', function (Blueprint $table) {
            $table->dropColumn(['geofence_review_status', 'geofence_reviewed_by']);
        });
    }
};
