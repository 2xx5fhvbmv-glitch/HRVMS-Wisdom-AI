<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmergencyContactNumbersToResortSiteSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_site_settings', function (Blueprint $table) {
            $table->string('emergency_police_number')->nullable();
            $table->string('emergency_fire_number')->nullable();
            $table->string('emergency_mndf_number')->nullable();
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
            $table->dropColumn(['emergency_police_number', 'emergency_fire_number', 'emergency_mndf_number']);
        });
    }
}
