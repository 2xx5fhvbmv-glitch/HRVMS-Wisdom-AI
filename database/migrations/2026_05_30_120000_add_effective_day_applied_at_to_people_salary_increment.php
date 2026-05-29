<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Idempotency timestamp for the daily "apply approved salary
     * increment on its effective date" command
     * (App\Console\Commands\ApplyEffectiveSalaryIncrement).
     *
     * Same pattern as EmployeePromotion.effective_day_notified_at —
     * the command only picks up rows where this column is NULL, then
     * stamps it after a successful apply. Lets the command catch up
     * if the scheduler missed a day without re-applying yesterday's
     * increments.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('people_salary_increment', 'effective_day_applied_at')) {
            Schema::table('people_salary_increment', function (Blueprint $table) {
                $table->timestamp('effective_day_applied_at')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('people_salary_increment', 'effective_day_applied_at')) {
            Schema::table('people_salary_increment', function (Blueprint $table) {
                $table->dropColumn('effective_day_applied_at');
            });
        }
    }
};
