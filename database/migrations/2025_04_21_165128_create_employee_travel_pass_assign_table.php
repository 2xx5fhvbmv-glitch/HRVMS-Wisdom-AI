<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeTravelPassAssignTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_travel_pass_assign', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('travel_pass_id');
            $table->unsignedInteger('employee_id');
            $table->timestamps();
            
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('travel_pass_id')->references('id')->on('employee_travel_passes');
            $table->foreign('employee_id')->references('id')->on('employees');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_travel_pass_assign');
    }
}
