<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeItinerariesMeetingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_itineraries_meeting', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_itinerary_id');
            $table->string('meeting_title');
            $table->date('meeting_date');
            $table->time('meeting_time');
            $table->string('meeting_link');
            $table->unsignedInteger('meeting_participant_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_itinerary_id')->references('id')->on('employee_itineraries')->onDelete('cascade');
            $table->foreign('meeting_participant_id')->references('id')->on('employees')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_itineraries_meeting');
    }
}
