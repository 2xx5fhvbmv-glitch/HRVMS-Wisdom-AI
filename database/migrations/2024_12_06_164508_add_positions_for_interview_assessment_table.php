<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPositionsForInterviewAssessmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('interview_assessment_forms', function (Blueprint $table) {
            $table->unsignedInteger('position')->after('resort_id')->default(null);

            $table->foreign('position')->references('id')->on('resort_positions');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('interview_assessment_forms', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
}
