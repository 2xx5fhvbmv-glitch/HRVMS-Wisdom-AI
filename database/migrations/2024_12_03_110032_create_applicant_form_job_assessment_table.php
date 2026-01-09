<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantFormJobAssessmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicant_form_job_assessment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_form_id');
            $table->unsignedBigInteger('question_id')->nullable(); // Links to the questionnaire
            $table->string('question_type')->nullable(); // single, radio, multiple, video
            $table->text('response')->nullable(); // Stores text, selected options, or video URL
            $table->json('multiple_responses')->nullable(); // Stores multiple responses (JSON for multiple choice)
            $table->string('video_language_test')->nullable(); // Language test identifier
            $table->string('video_path')->nullable(); // Path to the uploaded video
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
        Schema::dropIfExists('applicant_form_job_assessment');
    }
}
