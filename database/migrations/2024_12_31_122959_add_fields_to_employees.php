<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToEmployees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('probation_end_date')->nullable()->after('joining_date');
            $table->string('work_location')->nullable()->after('emg_cont_permanent_address');
            $table->string('skill')->nullable()->after('work_location');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('probation_end_date');
            $table->dropColumn('work_location');
            $table->dropColumn('skill');
        });
    }
}
