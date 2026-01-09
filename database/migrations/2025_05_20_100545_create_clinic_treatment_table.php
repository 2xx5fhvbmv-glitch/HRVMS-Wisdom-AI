<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClinicTreatmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clinic_treatment', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedInteger('employee_id');
            $table->unsignedBigInteger('appointment_category_id');
            $table->date('date');
            $table->time('time');
            $table->string('treatment_provided');
            $table->string('additional_notes');
            $table->string('external_consultation')->nullable();
            $table->enum('priority', ['High','Medium', 'Low'])->default('Medium');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by');
            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('appointment_id')->references('id')->on('clinic_appointment');
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('appointment_category_id')->references('id')->on('clinic_appointment_categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clinic_treatment');
    }
}
