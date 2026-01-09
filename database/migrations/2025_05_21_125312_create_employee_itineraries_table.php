<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeItinerariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_itineraries', function (Blueprint $table) {
            $table->id(); // defaults to unsignedBigInteger

            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('employee_id');
            $table->unsignedBigInteger('template_id'); // Assuming itinerary_templates.id is bigInteger

            $table->string('greeting_message');
            $table->date('arrival_date');
            $table->time('arrival_time');

            $table->string('entry_pass_file');
            $table->string('flight_ticket_file');

            $table->unsignedInteger('pickup_employee_id')->nullable();
            $table->unsignedInteger('accompany_medical_employee_id')->nullable();

            $table->date('domestic_flight_date');
            $table->time('domestic_departure_time');
            $table->time('domestic_arrival_time');

            $table->string('resort_transportation');
            $table->string('speedboat_name');
            $table->string('captain_number');
            $table->string('location');

            $table->string('hotel_id');
            $table->string('hotel_name');
            $table->string('hotel_contact_no');
            $table->string('booking_reference');
            $table->string('hotel_address');

            $table->string('medical_center_name');
            $table->string('medical_center_contact_no');
            $table->string('medical_type');
            $table->string('approx_time');

            $table->timestamps();

            // Foreign keys
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('itinerary_templates')->onDelete('cascade');
            $table->foreign('pickup_employee_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('accompany_medical_employee_id')->references('id')->on('employees')->onDelete('set null');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_itineraries');
    }
}
