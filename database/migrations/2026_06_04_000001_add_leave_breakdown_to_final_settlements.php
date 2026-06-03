<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persist the per-row leave breakdown HR locked in on the F&F page.
 *
 * Without this column, the review page re-runs FinalSettlementService::getLeaveBalance()
 * every time it loads — which means a Day Off row HR manually trimmed from 8
 * back to 7 reappears as 8 the next day (the service ticks +1 weekly accrual
 * day every passing week of the employee's anniversary cycle).
 *
 * Storing a JSON snapshot of the table the moment HR clicks Submit makes the
 * review a frozen audit record: it shows exactly what HR signed off on, no
 * recomputation.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('final_settlements')) {
            return;
        }
        if (Schema::hasColumn('final_settlements', 'leave_breakdown_json')) {
            return;
        }
        Schema::table('final_settlements', function (Blueprint $table) {
            // JSON array: [{leave_type, available_days, encashable_days, ...}, ...]
            // TEXT type instead of native JSON for broader MariaDB compatibility.
            $table->text('leave_breakdown_json')->nullable()->after('leave_encashment');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('final_settlements')) {
            return;
        }
        if (!Schema::hasColumn('final_settlements', 'leave_breakdown_json')) {
            return;
        }
        Schema::table('final_settlements', function (Blueprint $table) {
            $table->dropColumn('leave_breakdown_json');
        });
    }
};
