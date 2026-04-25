<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track the currency a KPI budget was entered in. Without this, the display
 * pipeline assumed every value was MVR — so a USD entry like $20M was being
 * displayed back as $1.3M (20M × MVR→USD rate).
 *
 * NULL = legacy row with unknown currency — display layer falls back to the
 * resort's currently-active display currency (no conversion).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performance_kpi_parents', function (Blueprint $t) {
            if (!Schema::hasColumn('performance_kpi_parents', 'budget_currency')) {
                $t->string('budget_currency', 16)->nullable()->after('PropertyGoalbudget');
            }
            if (!Schema::hasColumn('performance_kpi_parents', 'response_budget_currency')) {
                $t->string('response_budget_currency', 16)->nullable()->after('response_budget');
            }
        });

        if (Schema::hasTable('performance_kpi_children')) {
            Schema::table('performance_kpi_children', function (Blueprint $t) {
                if (!Schema::hasColumn('performance_kpi_children', 'budget_currency')) {
                    $t->string('budget_currency', 16)->nullable()->after('budget');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('performance_kpi_parents', function (Blueprint $t) {
            foreach (['budget_currency', 'response_budget_currency'] as $col) {
                if (Schema::hasColumn('performance_kpi_parents', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
        if (Schema::hasTable('performance_kpi_children')) {
            Schema::table('performance_kpi_children', function (Blueprint $t) {
                if (Schema::hasColumn('performance_kpi_children', 'budget_currency')) {
                    $t->dropColumn('budget_currency');
                }
            });
        }
    }
};
