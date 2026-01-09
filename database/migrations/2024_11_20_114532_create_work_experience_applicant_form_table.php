<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkExperienceApplicantFormTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_experience_applicant_form', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_form_id');
            $table->string('job_title')->nullable();
            $table->string('employer_name')->nullable();
            $table->string('work_country_name')->nullable();
            $table->string('work_city')->nullable();
            $table->string('total_work_exp')->nullable();
            $table->date('work_start_date')->nullable();
            $table->date('work_end_date')->nullable();
            $table->text('job_description_work')->nullable();
            $table->string('currently_working',100)->nullable();
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
        Schema::dropIfExists('work_experience_applicant_form');
    }
}
