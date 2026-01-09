<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEducationApplicantFormTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('education_applicant_form', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_form_id');
            $table->string('institute_name')->nullable();
            $table->string('educational_level')->nullable();
            $table->string('country_educational')->nullable();
            $table->string('city_educational')->nullable();
            $table->timestamps();

            $table->foreign( 'applicant_form_id')->references('id')->on('applicant_form_data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('education_applicant_form');
    }
}
