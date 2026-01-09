<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CraeteEmployeeTravelPassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_travel_passes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('leave_request_id')->nullable();
            $table->enum('pass_type', ['Boarding','Entry', 'Exit'])->nullable(); // Type of pass
            $table->unsignedBigInteger('transportation')->nullable();
            $table->date('arrival_date')->nullable(); // Arrival date for transportation
            $table->string('arrival_time')->nullable(); 
            $table->date('departure_date')->nullable(); // Departure date for transportation
            $table->string('departure_time')->nullable();
            $table->string('reason')->nullable(); // Reason for travel/pass
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending'); // Pass status
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('employee_id')->references('id')->on('employees'); // FK to resorts table
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
        Schema::dropIfExists('employee_travel_passes');
    }
}
