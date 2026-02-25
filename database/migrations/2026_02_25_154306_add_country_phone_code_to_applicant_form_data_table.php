<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountryPhoneCodeToApplicantFormDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applicant_form_data', function (Blueprint $table) {
            $table->string('country_phone_code', 10)->nullable()->after('mobile_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('applicant_form_data', function (Blueprint $table) {
            $table->dropColumn('country_phone_code');
        });
    }
}
