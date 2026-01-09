<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClinicAppointmentAttechementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clinic_appointment_attechements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('appointment_id');
            $table->string('attachment');
            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('appointment_id')->references('id')->on('clinic_appointment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clinic_appointment_attechements');
    }
}
