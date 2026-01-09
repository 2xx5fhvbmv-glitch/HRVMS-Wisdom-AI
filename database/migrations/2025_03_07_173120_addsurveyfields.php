<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Addsurveyfields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('parent_surveys', function (Blueprint $table) {
            $table->string('survey_privacy_type')->nullable();
          
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parent_surveys', function (Blueprint $table) {
            $table->dropColumn('survey_privacy_type')->nullable();
        });
    }
}
