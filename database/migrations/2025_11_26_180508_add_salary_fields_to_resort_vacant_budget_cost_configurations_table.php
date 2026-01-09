<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSalaryFieldsToResortVacantBudgetCostConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_vacant_budget_cost_configurations', function (Blueprint $table) {
            $table->decimal('basic_salary', 15, 2)->default(0)->after('currency');
            $table->decimal('current_salary', 15, 2)->default(0)->after('basic_salary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('resort_vacant_budget_cost_configurations', function (Blueprint $table) {
            $table->dropColumn(['basic_salary', 'current_salary']);
        });
    }
}
