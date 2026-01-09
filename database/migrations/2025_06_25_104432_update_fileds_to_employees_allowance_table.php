<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFiledsToEmployeesAllowanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees_allowance', function (Blueprint $table) {
            $table->dropColumn('allowance_type');
            $table->dropColumn('allowance');
            $table->dropColumn('frequency');
            $table->dropColumn('is_taxable');
            $table->dropColumn('is_in_service_charge');
            $table->dropColumn('source');
            $table->dropColumn('benefit_grid_id');
            $table->unsignedInteger('allowance_id')->after('employee_id');
            $table->enum('amount_unit',['USD','MVR'])->default('USD')->after('amount');

            $table->foreign('allowance_id')
                ->references('id')
                ->on('resort_budget_costs')
                ->onDelete('cascade')
                ->onUpdate('cascade');
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
            $table->dropForeign(['allowance_id']);
            $table->dropColumn('allowance_id');
            $table->dropColumn('amount_unit');

            $table->string('allowance')->after('employee_id');
            $table->string('allowance_type')->nullable()->after('allowance');
            $table->string('frequency')->nullable()->after('amount');// monthly, yearly, one-time
            $table->boolean('is_taxable')->default(false)->after('frequency');
            $table->boolean('is_in_service_charge')->default(false)->after('is_taxable');
            $table->string('source')->nullable()->after('is_in_service_charge');// benefit_grid, manual, budget_costs
            $table->unsignedBigInteger('benefit_grid_id')->nullable()->after('source');
        });
    }
}
