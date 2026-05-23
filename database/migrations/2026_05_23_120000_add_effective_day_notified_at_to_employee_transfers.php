<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks when the "transfer takes effect today" reminder notification has
 * been dispatched for a transfer, so the daily scheduler doesn't re-notify
 * the same employee + dept leads every time it runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_transfers', 'effective_day_notified_at')) {
                $table->timestamp('effective_day_notified_at')->nullable()->after('transfer_letter_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('employee_transfers', 'effective_day_notified_at')) {
                $table->dropColumn('effective_day_notified_at');
            }
        });
    }
};
