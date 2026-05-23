<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the bookkeeping a temporary transfer needs to auto-revert when
 * temporary_to passes:
 *
 *   - pre_transfer_snapshot — JSON of the employee's Dept / Section /
 *     Position / Division / Rank / Reporting_to AT THE MOMENT the temp
 *     transfer was applied. Captured by applyTransferToEmployee(). The
 *     existing current_* columns on this row track the snapshot at submit
 *     time, but reporting_to + division + rank are not stored there; the
 *     snapshot gives us a complete picture for the revert.
 *
 *   - reverted_at — when the temp transfer was reverted by the daily
 *     scheduler. Doubles as the "revert notifications sent" flag so
 *     scheduler re-runs don't fire duplicate notifications.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_transfers', 'pre_transfer_snapshot')) {
                $table->json('pre_transfer_snapshot')->nullable()->after('effective_day_notified_at');
            }
            if (!Schema::hasColumn('employee_transfers', 'reverted_at')) {
                $table->timestamp('reverted_at')->nullable()->after('pre_transfer_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_transfers', function (Blueprint $table) {
            foreach (['pre_transfer_snapshot', 'reverted_at'] as $col) {
                if (Schema::hasColumn('employee_transfers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
