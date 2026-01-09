<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClinicMedicalCertificateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clinic_medical_certificate', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('clinic_treatment_id')->nullable();
            $table->unsignedInteger('leave_request_id')->nullable();
            $table->unsignedInteger('employee_id');
            $table->unsignedBigInteger('appointment_category_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('description')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by');
            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('appointment_id')->references('id')->on('clinic_appointment')->onDelete('cascade');
            $table->foreign('clinic_treatment_id')->references('id')->on('clinic_treatment')->onDelete('cascade');
            $table->foreign('leave_request_id')->references('id')->on('employees_leaves'); // FK to employees_leaves table
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('appointment_category_id')->references('id')->on('clinic_appointment_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clinic_medical_certificate');
    }
}
