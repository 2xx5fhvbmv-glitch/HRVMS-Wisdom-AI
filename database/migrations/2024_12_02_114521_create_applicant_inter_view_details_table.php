<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantInterViewDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicant_inter_view_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('Applicant_id')->default(0);
            $table->unsignedBigInteger('ApplicantStatus_id')->default(0);
            $table->date('InterViewDate');
            $table->string('ApplicantInterviewtime')->default(0);
            $table->string('ResortInterviewtime')->default(0);

            $table->integer('Approved_By')->nullable();
            $table->enum('Status',["Active","Slot Booked","Slot Not Booked"])->default('Slot Not Booked');
            $table->string('MeetingLink')->default(0);
            $table->timestamps();
            $table->foreign('Applicant_id')->references('id')->on('applicant_form_data');
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('ApplicantStatus_id')->references('id')->on('applicant_wise_statuses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('applicant_inter_view_details');
    }
}
