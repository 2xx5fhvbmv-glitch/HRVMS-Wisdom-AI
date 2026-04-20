<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMonthYearToBonusConfigs extends Migration
{
    public function up()
    {
        Schema::table('performance_bonus_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('performance_bonus_configs', 'month')) {
                $table->string('month', 20)->nullable()->after('bonus_percentage');
            }
            if (!Schema::hasColumn('performance_bonus_configs', 'year')) {
                $table->smallInteger('year')->nullable()->after('month');
            }
        });
    }

    public function down()
    {
        Schema::table('performance_bonus_configs', function (Blueprint $table) {
            foreach (['month', 'year'] as $col) {
                if (Schema::hasColumn('performance_bonus_configs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
