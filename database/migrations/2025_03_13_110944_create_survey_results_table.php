<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSurveyResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('survey_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Parent_survey_id');
            $table->unsignedBigInteger('Survey_emp_ta_id');
            $table->unsignedBigInteger('Question_id');
            $table->string('Emp_Ans');
            $table->foreign('Parent_survey_id')->references('id')->on('parent_surveys');
            $table->foreign('Question_id')->references('id')->on('survey_questions');
            $table->foreign('Survey_emp_ta_id')->references('id')->on('survey_employees');

         
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
        Schema::dropIfExists('survey_results');
    }
}
