<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['employee_pip_plans', 'employee_pdp_plans'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (!Schema::hasColumn($table, 'response_data')) {
                    $t->longText('response_data')->nullable()->after('factors');
                }
                if (!Schema::hasColumn($table, 'submitted_at')) {
                    $t->timestamp('submitted_at')->nullable()->after('response_data');
                }
                if (!Schema::hasColumn($table, 'submitted_by')) {
                    $t->unsignedBigInteger('submitted_by')->nullable()->after('submitted_at');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['employee_pip_plans', 'employee_pdp_plans'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (['response_data', 'submitted_at', 'submitted_by'] as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }
    }
};
