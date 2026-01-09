<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMonthToBudgetCostConfigurationsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add month field to resort_employee_budget_cost_configurations table
        Schema::table('resort_employee_budget_cost_configurations', function (Blueprint $table) {
            $table->integer('month')->nullable()->after('year')->comment('Month (1-12)');
            $table->index(['employee_id', 'resort_id', 'year', 'month'], 'rebcc_emp_resort_year_month_idx');
        });

        // Add month field to resort_vacant_budget_cost_configurations table
        Schema::table('resort_vacant_budget_cost_configurations', function (Blueprint $table) {
            $table->integer('month')->nullable()->after('year')->comment('Month (1-12)');
            $table->index(['vacant_budget_cost_id', 'resort_id', 'year', 'month'], 'rvbcc_vacant_resort_year_month_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove month field from resort_employee_budget_cost_configurations table
        Schema::table('resort_employee_budget_cost_configurations', function (Blueprint $table) {
            $table->dropIndex('rebcc_emp_resort_year_month_idx');
            $table->dropColumn('month');
        });

        // Remove month field from resort_vacant_budget_cost_configurations table
        Schema::table('resort_vacant_budget_cost_configurations', function (Blueprint $table) {
            $table->dropIndex('rvbcc_vacant_resort_year_month_idx');
            $table->dropColumn('month');
        });
    }
}
