<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSurveyQuestionFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
          if (!Schema::hasColumn('survey_questions', 'type')) {
            Schema::table('survey_questions', function (Blueprint $table) {
                $table->string('type')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
                if (Schema::hasColumn('survey_questions', 'type')) {

                    Schema::table('survey_questions', function (Blueprint $table) {
                        $table->dropColumn('type');
                    });
                }
    }
}
