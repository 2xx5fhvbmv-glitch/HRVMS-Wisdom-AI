<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * applicant_inter_view_details.Status was an ENUM that never listed
 * 'Pending Review' (written by InterviewRequest) and has no 'Cancelled'
 * (written by reschedule/remove-from-shortlist). Widen to a plain string so
 * the app's statuses all fit; existing values are preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE applicant_inter_view_details MODIFY COLUMN Status VARCHAR(30) NOT NULL DEFAULT 'Slot Not Booked'");
    }

    public function down(): void
    {
        // Not reversible without losing 'Pending Review'/'Cancelled' rows; left as a no-op.
    }
};
