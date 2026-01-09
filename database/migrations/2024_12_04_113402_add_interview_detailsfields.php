<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInterviewDetailsfields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applicant_inter_view_details', function (Blueprint $table)
        {
            $table->string('EmailTemplateId')->nullable();


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('applicant_inter_view_details', function (Blueprint $table)
        {
            $table->dropColumn('EmailTemplateId')->nullable();

        });
    }
}
