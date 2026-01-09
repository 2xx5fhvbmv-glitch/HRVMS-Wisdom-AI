<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortVacantBudgetCostAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resort_vacant_budget_cost_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vacant_budget_cost_id'); // FK to resort_vacant_budget_costs
            $table->unsignedInteger('resort_budget_cost_id');
            $table->decimal('value', 15, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->unsignedInteger('department_id');
            $table->unsignedInteger('position_id');
            $table->unsignedInteger('resort_id');
            $table->integer('year')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('resort_budget_cost_id', 'rvbcc_resort_cost_fk')->references('id')->on('resort_budget_costs')->onDelete('cascade');
            $table->foreign('vacant_budget_cost_id', 'rvbcc_vacant_cost_fk')->references('id')->on('resort_vacant_budget_costs')->onDelete('cascade');
            $table->index(['vacant_budget_cost_id', 'resort_id', 'year'], 'rvbcc_vacant_resort_year_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resort_vacant_budget_cost_configurations');
    }
}
