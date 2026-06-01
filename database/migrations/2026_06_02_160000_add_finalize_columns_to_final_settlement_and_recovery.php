<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the audit + lock columns the F&F finalize flow writes to:
 *
 *   final_settlement
 *     • finalized_at   — timestamp when the review-page Submit fired
 *     • finalized_by   — resort_admin who clicked it
 *
 *   payroll_recovery_schedule
 *     • recovery_date         — when the installment was marked Recovered
 *     • recovered_via         — 'final_settlements' / 'payroll_run' / 'manual'
 *     • final_settlement_id   — FK back to final_settlement (NULL for
 *                               installments recovered via normal payroll)
 *
 * Without these, the controller falls back to bare status updates
 * (Schema::hasColumn guards make that safe), but the audit trail is
 * the whole point of finalization — apply this migration in any
 * environment that runs the F&F flow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('final_settlements', function (Blueprint $table) {
            if (!Schema::hasColumn('final_settlements', 'finalized_at')) {
                $table->timestamp('finalized_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('final_settlements', 'finalized_by')) {
                $table->unsignedInteger('finalized_by')->nullable()->after('finalized_at');
            }
        });

        Schema::table('payroll_recovery_schedule', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_recovery_schedule', 'recovery_date')) {
                $table->timestamp('recovery_date')->nullable()->after('status');
            }
            if (!Schema::hasColumn('payroll_recovery_schedule', 'recovered_via')) {
                $table->string('recovered_via', 32)->nullable()->after('recovery_date');
            }
            if (!Schema::hasColumn('payroll_recovery_schedule', 'final_settlement_id')) {
                $table->unsignedBigInteger('final_settlement_id')->nullable()->after('recovered_via');
                $table->index('final_settlement_id', 'prs_final_settlement_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('final_settlements', function (Blueprint $table) {
            if (Schema::hasColumn('final_settlements', 'finalized_by')) {
                $table->dropColumn('finalized_by');
            }
            if (Schema::hasColumn('final_settlements', 'finalized_at')) {
                $table->dropColumn('finalized_at');
            }
        });

        Schema::table('payroll_recovery_schedule', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_recovery_schedule', 'final_settlement_id')) {
                $table->dropIndex('prs_final_settlement_idx');
                $table->dropColumn('final_settlement_id');
            }
            if (Schema::hasColumn('payroll_recovery_schedule', 'recovered_via')) {
                $table->dropColumn('recovered_via');
            }
            if (Schema::hasColumn('payroll_recovery_schedule', 'recovery_date')) {
                $table->dropColumn('recovery_date');
            }
        });
    }
};
