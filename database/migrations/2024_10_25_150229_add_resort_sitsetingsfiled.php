<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResortSitsetingsfiled extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {


        Schema::table('resort_site_settings', function (Blueprint $table) {
            $table->string('FinalApproval')->after('Footer');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('resort_site_settings', function (Blueprint $table) {

            $table->dropColumn('FinalApproval');
        });
    }
}
