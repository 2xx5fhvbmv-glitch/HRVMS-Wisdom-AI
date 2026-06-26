<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Work Permit "Installment" renewal used to divide the monthly Work Permit
 * fee by 12 (e.g. 350 / 12 = 29.17 per month) instead of charging the full fee
 * each month. That produced bogus, under-priced installment rows in the payment
 * schedule. The renewal logic now charges the full configured fee per month.
 *
 * This removes the leftover under-priced, still-unpaid Work Permit installment
 * rows so the schedule no longer shows the wrong 29.17 entries. Paid rows are
 * never touched (those represent real settled payments). Affected employees can
 * simply re-run the Work Permit renewal to get a correct 12 x full-fee schedule.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('work_permits')) {
            return;
        }

        // A genuine monthly Work Permit fee is in the hundreds of MVR; anything
        // under 100 and unpaid is the divide-by-12 artifact.
        DB::table('work_permits')
            ->where('Status', 'Unpaid')
            ->whereRaw('CAST(Amt AS DECIMAL(12,2)) < 100')
            ->delete();
    }

    public function down(): void
    {
        // Irreversible: the removed rows were invalid duplicates.
    }
};
