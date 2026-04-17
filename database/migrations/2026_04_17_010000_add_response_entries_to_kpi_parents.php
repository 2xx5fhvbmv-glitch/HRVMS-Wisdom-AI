<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResponseEntriesToKpiParents extends Migration
{
    public function up()
    {
        Schema::table('performance_kpi_parents', function (Blueprint $table) {
            if (!Schema::hasColumn('performance_kpi_parents', 'response_entries')) {
                $table->json('response_entries')->nullable()->after('response_weightage');
            }
        });
    }

    public function down()
    {
        Schema::table('performance_kpi_parents', function (Blueprint $table) {
            if (Schema::hasColumn('performance_kpi_parents', 'response_entries')) {
                $table->dropColumn('response_entries');
            }
        });
    }
}
