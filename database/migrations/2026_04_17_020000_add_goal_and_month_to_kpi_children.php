<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGoalAndMonthToKpiChildren extends Migration
{
    public function up()
    {
        Schema::table('performance_kpi_children', function (Blueprint $table) {
            if (!Schema::hasColumn('performance_kpi_children', 'individual_goal')) {
                $table->string('individual_goal', 500)->nullable()->after('kpi_parents_id');
            }
            if (!Schema::hasColumn('performance_kpi_children', 'month')) {
                $table->string('month', 20)->nullable()->after('individual_goal');
            }
        });
    }

    public function down()
    {
        Schema::table('performance_kpi_children', function (Blueprint $table) {
            foreach (['individual_goal', 'month'] as $c) {
                if (Schema::hasColumn('performance_kpi_children', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
}
