<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('applicant_wise_statuses', function (Blueprint $table) {
            $table->text('Comments')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('applicant_wise_statuses', function (Blueprint $table) {
            $table->string('Comments', 191)->nullable()->change();
        });
    }
};
