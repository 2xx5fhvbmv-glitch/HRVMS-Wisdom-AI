<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRatingThresholdsToKpiParents extends Migration
{
    public function up()
    {
        Schema::table('performance_kpi_parents', function (Blueprint $table) {
            foreach (['poor', 'fair', 'good', 'superb'] as $col) {
                if (!Schema::hasColumn('performance_kpi_parents', $col)) {
                    $table->decimal($col, 5, 2)->nullable()->after('gm_remarks');
                }
            }
        });
    }

    public function down()
    {
        Schema::table('performance_kpi_parents', function (Blueprint $table) {
            foreach (['poor', 'fair', 'good', 'superb'] as $col) {
                if (Schema::hasColumn('performance_kpi_parents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
