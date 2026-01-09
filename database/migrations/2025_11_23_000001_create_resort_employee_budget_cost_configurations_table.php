<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortEmployeeBudgetCostConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resort_employee_budget_cost_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('resort_budget_cost_id');
            $table->decimal('value', 15, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->unsignedInteger('department_id');
            $table->unsignedInteger('position_id');
            $table->unsignedInteger('resort_id');
            $table->integer('year')->nullable();
            $table->decimal('basic_salary', 15, 2)->nullable();
            $table->decimal('current_salary', 15, 2)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('resort_budget_cost_id', 'rebcc_resort_cost_fk')->references('id')->on('resort_budget_costs')->onDelete('cascade');
            $table->index(['employee_id', 'resort_id', 'year'], 'rebcc_emp_resort_year_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resort_employee_budget_cost_configurations');
    }
}

