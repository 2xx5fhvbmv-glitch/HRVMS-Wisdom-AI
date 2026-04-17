<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKpiResponseWorkflow extends Migration
{
    public function up()
    {
        // Response / approval workflow on parent KPI
        Schema::table('performance_kpi_parents', function (Blueprint $table) {
            if (!Schema::hasColumn('performance_kpi_parents', 'status')) {
                $table->string('status', 30)->default('pending')->after('PropertyGoalscore');
                // pending = waiting for HOD/XCOM response
                // responded = someone responded
                // approved = GM approved
                // rejected = GM rejected
            }
            if (!Schema::hasColumn('performance_kpi_parents', 'responded_by')) {
                $table->unsignedBigInteger('responded_by')->nullable()->after('status');
            }
            if (!Schema::hasColumn('performance_kpi_parents', 'responded_at')) {
                $table->timestamp('responded_at')->nullable()->after('responded_by');
            }
            if (!Schema::hasColumn('performance_kpi_parents', 'individual_goal')) {
                $table->text('individual_goal')->nullable()->after('responded_at');
            }
            if (!Schema::hasColumn('performance_kpi_parents', 'response_budget')) {
                $table->string('response_budget')->nullable()->after('individual_goal');
            }
            if (!Schema::hasColumn('performance_kpi_parents', 'response_weightage')) {
                $table->string('response_weightage')->nullable()->after('response_budget');
            }
            if (!Schema::hasColumn('performance_kpi_parents', 'gm_action')) {
                $table->string('gm_action', 30)->nullable()->after('response_weightage');
                // approved / rejected
            }
            if (!Schema::hasColumn('performance_kpi_parents', 'gm_action_at')) {
                $table->timestamp('gm_action_at')->nullable()->after('gm_action');
            }
            if (!Schema::hasColumn('performance_kpi_parents', 'gm_remarks')) {
                $table->text('gm_remarks')->nullable()->after('gm_action_at');
            }
        });

        // Also make score columns nullable (we're removing score from create)
        $scoreCols = ['PropertyGoalscore'];
        foreach ($scoreCols as $col) {
            if (Schema::hasColumn('performance_kpi_parents', $col)) {
                try {
                    \DB::statement("ALTER TABLE performance_kpi_parents MODIFY `{$col}` VARCHAR(255) NULL");
                } catch (\Exception $e) {}
            }
        }

        // Make child score nullable too
        if (Schema::hasColumn('performance_kpi_children', 'score')) {
            try {
                \DB::statement("ALTER TABLE performance_kpi_children MODIFY `score` DOUBLE NULL");
            } catch (\Exception $e) {}
        }
    }

    public function down()
    {
        Schema::table('performance_kpi_parents', function (Blueprint $table) {
            $cols = ['status', 'responded_by', 'responded_at', 'individual_goal', 'response_budget', 'response_weightage', 'gm_action', 'gm_action_at', 'gm_remarks'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('performance_kpi_parents', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
}
