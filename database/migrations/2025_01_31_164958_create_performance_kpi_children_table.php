<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceKpiChildrenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('performance_kpi_children', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kpi_parents_id');
            $table->float('budget')->nullable();
            $table->float('weightage')->nullable();
            $table->float('score')->nullable();
            $table->foreign('kpi_parents_id')->references('id')->on('performance_kpi_parents');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('performance_kpi_children');
    }
}
