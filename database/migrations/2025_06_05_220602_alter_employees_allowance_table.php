<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterEmployeesAllowanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees_allowance', function (Blueprint $table) {
            $table->string('allowance_type')->nullable()->after('allowance');
            $table->string('frequency')->nullable()->after('amount');// monthly, yearly, one-time
            $table->boolean('is_taxable')->default(false)->after('frequency');
            $table->boolean('is_in_service_charge')->default(false)->after('is_taxable');
            $table->string('source')->nullable()->after('is_in_service_charge');// benefit_grid, manual, budget_costs
            $table->unsignedBigInteger('benefit_grid_id')->nullable()->after('source'); // FK to grid (optional)
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees_allowance', function (Blueprint $table) {
            $table->dropColumn('frequency')->nullable(); // monthly, yearly, one-time
            $table->dropColumn('is_taxable')->default(false);
            $table->dropColumn('is_in_service_charge')->default(false);
            $table->dropColumn('source')->nullable(); // benefit_grid, manual, budget_costs
            $table->dropColumn('benefit_grid_id')->nullable();
        });
    }
}
