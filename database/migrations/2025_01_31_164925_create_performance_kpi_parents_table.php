<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceKpiParentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('performance_kpi_parents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('property_goal')->nullable();
            $table->string('PropertyGoalbudget')->nullable();
            $table->string('PropertyGoalweightage')->nullable();
            $table->string('PropertyGoalscore')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('performance_kpi_parents');
    }
}
