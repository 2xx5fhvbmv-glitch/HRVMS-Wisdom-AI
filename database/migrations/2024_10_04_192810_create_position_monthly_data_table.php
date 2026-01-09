<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePositionMonthlyDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('position_monthly_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manning_response_id');
            $table->unsignedInteger('position_id');
            $table->integer('month'); // Store month as an integer (1-12)
            $table->integer('headcount'); // Store headcount for the month
            $table->integer('vacantcount'); // Store vacant headcount for the month
            $table->integer('filledcount'); // Store filled headcount for the month
            $table->timestamps();

            $table->foreign('manning_response_id')->references('id')->on('manning_responses');
            $table->foreign('position_id')->references('id')->on('resort_positions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('position_monthly_data');
    }
}
