<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortVacantBudgetCostTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resort_vacant_budget_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('position_id');
            $table->unsignedInteger('department_id');
            $table->unsignedInteger('resort_id');
            $table->integer('year')->nullable();
            $table->integer('vacant_index')->default(1); // To handle multiple vacancies
            $table->decimal('basic_salary', 15, 2)->nullable();
            $table->decimal('current_salary', 15, 2)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            
            $table->index(['position_id', 'resort_id', 'year', 'vacant_index'], 'rvbc_pos_resort_year_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resort_vacant_budget_costs');
    }
}
