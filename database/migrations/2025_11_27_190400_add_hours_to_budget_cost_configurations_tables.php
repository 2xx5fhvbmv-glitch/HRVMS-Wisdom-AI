<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHoursToBudgetCostConfigurationsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_employee_budget_cost_configurations', function (Blueprint $table) {
            $table->decimal('hours', 8, 2)->default(0)->after('current_salary')->comment('Hours for percentage-based calculations like overtime');
        });

        Schema::table('resort_vacant_budget_cost_configurations', function (Blueprint $table) {
            $table->decimal('hours', 8, 2)->default(0)->after('current_salary')->comment('Hours for percentage-based calculations like overtime');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('resort_employee_budget_cost_configurations', function (Blueprint $table) {
            $table->dropColumn('hours');
        });

        Schema::table('resort_vacant_budget_cost_configurations', function (Blueprint $table) {
            $table->dropColumn('hours');
        });
    }
}
