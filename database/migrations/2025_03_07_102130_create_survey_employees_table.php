<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSurveyEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('survey_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Parent_survey_id');
            $table->integer('Emp_id')->default(7);
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
        Schema::dropIfExists('survey_employees');
    }
}
