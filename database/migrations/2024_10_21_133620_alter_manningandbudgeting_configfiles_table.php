<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterManningandbudgetingConfigfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('manningandbudgeting_configfiles', function (Blueprint $table) {
            $table->integer('xpat')->change();
            // $table->integer('local')->afetr('xapt');
            $table->integer('local')->after('xpat');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('manningandbudgeting_configfiles', function (Blueprint $table) {
            $table->string('xpat')->change();
            $table->dropColumn('local');
        });
    }
}
