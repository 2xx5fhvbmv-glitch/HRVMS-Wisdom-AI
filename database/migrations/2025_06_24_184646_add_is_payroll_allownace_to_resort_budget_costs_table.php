<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPayrollAllownaceToResortBudgetCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_budget_costs', function (Blueprint $table) {
            $table->boolean('is_payroll_allowance')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('resort_budget_costs', function (Blueprint $table) {
            $table->dropColumn('is_payroll_allowance');
        });
    }
}
