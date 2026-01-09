<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ApplicantformFeilds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applicant_form_data', function (Blueprint $table)
        {
            $table->string('AIRanking')->nullable();
            $table->string('Scoring')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('applicant_form_data', function (Blueprint $table): void
        {
            $table->dropColumn('AIRanking')->nullable();
            $table->dropColumn('Scoring')->nullable();
        });
    }
}
