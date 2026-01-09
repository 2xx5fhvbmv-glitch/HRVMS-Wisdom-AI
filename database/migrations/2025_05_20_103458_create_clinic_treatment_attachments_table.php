<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClinicTreatmentAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clinic_treatment_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_treatment_id');
            $table->string('attachment');
            $table->timestamps();
            $table->foreign('clinic_treatment_id')->references('id')->on('clinic_treatment')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clinic_treatment_attachments');
    }
}
