<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClinicAppointmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clinic_appointment', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('doctor_id');
            $table->unsignedBigInteger('appointment_category_id');
            $table->date('date');
            $table->time('time');
            $table->string('description')->nullable();
            $table->enum('status', ['Pending','Approved', 'Rejected','Reschedule','Cancel'])->default('Pending');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by');
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('doctor_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('appointment_category_id')->references('id')->on('clinic_appointment_categories');
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
        Schema::dropIfExists('clinic_appointment');
    }
}
