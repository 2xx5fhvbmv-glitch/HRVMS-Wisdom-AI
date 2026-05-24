<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Transfer detail page renders an "Acted On" column from
 * employee_transfers_approval.approved_at, but the column was never created.
 * The controller silently set ['approved_at' => now()] on update — Eloquent
 * dropped the unknown attribute, so the column stayed missing and the UI
 * always showed "—".
 *
 * Add the column and backfill from updated_at for rows whose status is no
 * longer Pending (so historical approvals get a sensible timestamp instead
 * of NULL).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('employee_transfers_approval', 'approved_at')) {
            Schema::table('employee_transfers_approval', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('remarks');
            });
        }

        // Backfill — anything not Pending must have been acted on; the
        // closest signal we have is updated_at.
        DB::table('employee_transfers_approval')
            ->whereNotIn('status', ['Pending'])
            ->whereNull('approved_at')
            ->update(['approved_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('employee_transfers_approval', 'approved_at')) {
            Schema::table('employee_transfers_approval', function (Blueprint $table) {
                $table->dropColumn('approved_at');
            });
        }
    }
};
