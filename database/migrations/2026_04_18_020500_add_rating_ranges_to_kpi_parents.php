<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRatingRangesToKpiParents extends Migration
{
    public function up()
    {
        Schema::table('performance_kpi_parents', function (Blueprint $table) {
            foreach (['poor_range', 'fair_range', 'good_range', 'superb_range'] as $col) {
                if (!Schema::hasColumn('performance_kpi_parents', $col)) {
                    $table->string($col, 100)->nullable()->after('superb');
                }
            }
        });
    }

    public function down()
    {
        Schema::table('performance_kpi_parents', function (Blueprint $table) {
            foreach (['poor_range', 'fair_range', 'good_range', 'superb_range'] as $col) {
                if (Schema::hasColumn('performance_kpi_parents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
