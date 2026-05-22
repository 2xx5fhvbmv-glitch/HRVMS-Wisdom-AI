<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds an 'Onboarding' value to employees.status.
 *
 * A candidate auto-converted into an Employee during onboarding has not
 * physically joined yet — they shouldn't count as 'Active' (which would pull
 * them into payroll / attendance / headcount). 'Onboarding' is a distinct
 * pre-joining state; HR flips it to 'Active' (and sets the joining date) only
 * once onboarding is complete.
 *
 * Adding an enum value is backward-safe: existing `status = 'Active'` filters
 * simply never match an Onboarding employee, which is exactly the intent.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `employees`
            MODIFY COLUMN `status`
            ENUM('Active','Inactive','Terminated','Resigned','On Leave','Suspended','Onboarding')
            NULL DEFAULT 'Active'");
    }

    public function down(): void
    {
        // Move any Onboarding rows to Inactive before dropping the value, so
        // the ALTER doesn't fail on out-of-range data.
        DB::table('employees')->where('status', 'Onboarding')->update(['status' => 'Inactive']);

        DB::statement("ALTER TABLE `employees`
            MODIFY COLUMN `status`
            ENUM('Active','Inactive','Terminated','Resigned','On Leave','Suspended')
            NULL DEFAULT 'Active'");
    }
};
