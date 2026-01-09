<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSurveyQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Parent_survey_id');
            $table->string('Question_Type')->nullable();
            $table->json('Total_Option_Json')->nullable();
            $table->text('Question_Text')->nullable();
            $table->string('type')->nullable();
            $table->enum('Question_Complusory',['yes','no'])->default('yes');
            $table->timestamps();
            $table->foreign('Parent_survey_id')->references('id')->on('parent_surveys');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('survey_questions');
    }
}
