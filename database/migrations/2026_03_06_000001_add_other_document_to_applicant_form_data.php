<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('applicant_form_data', function (Blueprint $table) {
            $table->string('other_document')->nullable()->after('full_length_photo');
        });
    }

    public function down()
    {
        Schema::table('applicant_form_data', function (Blueprint $table) {
            $table->dropColumn('other_document');
        });
    }
};
