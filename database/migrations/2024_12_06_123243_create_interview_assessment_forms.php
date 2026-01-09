<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInterviewAssessmentForms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('interview_assessment_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('form_name');
            $table->json('form_structure'); // To store form field details as JSON
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('interview_assessment_forms');
    }
}
