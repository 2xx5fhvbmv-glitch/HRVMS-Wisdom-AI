<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill existing data: employees that were auto-created from a Talent
 * Acquisition applicant (applicant_id IS NOT NULL) but have never been given
 * a joining date were previously created as 'Active'. They have not actually
 * joined — move them to the new 'Onboarding' status so they drop out of
 * payroll / attendance until HR formally activates them.
 *
 * Scope is deliberately narrow (applicant-converted AND no joining date AND
 * currently Active) so it can't touch genuine active staff whose joining
 * date simply wasn't recorded through a different path.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')
            ->whereNotNull('applicant_id')
            ->whereNull('joining_date')
            ->where('status', 'Active')
            ->update(['status' => 'Onboarding']);
    }

    public function down(): void
    {
        // Reverting would wrongly re-activate them — leave as Onboarding.
    }
};
