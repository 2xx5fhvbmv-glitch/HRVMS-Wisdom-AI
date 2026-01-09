<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToResortBudgetCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_budget_costs', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
            $table->string('amount_unit')->after('amount');
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
            $table->string('amount')->change();
            $table->dropColumn('amount_unit');
        });
    }
}
