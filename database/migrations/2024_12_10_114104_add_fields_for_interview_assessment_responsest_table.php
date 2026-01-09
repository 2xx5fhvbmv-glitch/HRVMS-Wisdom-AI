<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsForInterviewAssessmentResponsestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('interview_assessment_responses', function (Blueprint $table) {
            $table->unsignedInteger('interviewer_id')->after('form_id');
            $table->unsignedBigInteger('interviewee_id')->after('interviewer_id');
            $table->string('interviewer_signature')->after('interviewee_id');

            $table->foreign('interviewee_id')->references('id')->on('applicant_form_data');
            $table->foreign('interviewer_id')->references('id')->on('resort_admins');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('interview_assessment_responses', function (Blueprint $table) {
            $table->dropColumn('applicant_id');
            $table->unsignedBigInteger('interviewee_id');
        });
    }
}
