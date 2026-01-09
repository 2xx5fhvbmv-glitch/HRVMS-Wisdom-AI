<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicant_languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_form_id');
            $table->string('language'); // Store language name
            $table->string('level'); 
            $table->timestamps();
            // Add foreign keys if necessary
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
        Schema::dropIfExists('applicant_languages');
    }
}
