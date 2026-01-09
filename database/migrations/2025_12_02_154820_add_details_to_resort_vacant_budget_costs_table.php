<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailsToResortVacantBudgetCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_vacant_budget_costs', function (Blueprint $table) {
            $table->string('details', 50)->nullable()->after('vacant_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('resort_vacant_budget_costs', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
}
