<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two columns the promotion scheduler needs to mirror the transfer flow:
 *   - effective_day_notified_at: idempotency timestamp so promotions:notify-effective
 *     only applies + notifies once per promotion.
 *   - pre_promotion_snapshot: JSON capture of the employee's pre-promotion
 *     state (position/rank/salary). Useful for audit and any future undo.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('employee_promotions', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_promotions', 'effective_day_notified_at')) {
                $table->timestamp('effective_day_notified_at')->nullable()->after('letter_dispatched');
            }
            if (!Schema::hasColumn('employee_promotions', 'pre_promotion_snapshot')) {
                $table->json('pre_promotion_snapshot')->nullable()->after('effective_day_notified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_promotions', function (Blueprint $table) {
            if (Schema::hasColumn('employee_promotions', 'pre_promotion_snapshot')) {
                $table->dropColumn('pre_promotion_snapshot');
            }
            if (Schema::hasColumn('employee_promotions', 'effective_day_notified_at')) {
                $table->dropColumn('effective_day_notified_at');
            }
        });
    }
};
