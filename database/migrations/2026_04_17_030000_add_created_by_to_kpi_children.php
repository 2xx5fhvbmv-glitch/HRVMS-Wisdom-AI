<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByToKpiChildren extends Migration
{
    public function up()
    {
        Schema::table('performance_kpi_children', function (Blueprint $table) {
            if (!Schema::hasColumn('performance_kpi_children', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('month');
            }
        });
    }

    public function down()
    {
        Schema::table('performance_kpi_children', function (Blueprint $table) {
            if (Schema::hasColumn('performance_kpi_children', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
}
