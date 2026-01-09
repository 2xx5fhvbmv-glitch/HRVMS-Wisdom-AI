<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsGrievanceInformally extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grivance_submission_models', function (Blueprint $table) {
            $table->enum('grievance_informally',['Yes','No','NotApplicable'])->after('Grivance_Submission_Type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('grivance_submission_models', function (Blueprint $table) {
            $table->dropColumn('grievance_informally');
        });
    }
}
