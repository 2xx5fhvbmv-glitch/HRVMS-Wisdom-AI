<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesLeaveTransportationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees_leave_transportation', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('leave_request_id');
            $table->unsignedBigInteger('transportation');
            $table->date('trans_arrival_date'); // Arrival date for transportation 
            $table->date('trans_departure_date'); // Departure date for transportation
            $table->timestamps();
            $table->foreign('leave_request_id')->references('id')->on('employees_leaves'); // FK to resorts table
            $table->foreign('transportation')->references('id')->on('resort_transportations');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees_leave_transportation');
    }
}
