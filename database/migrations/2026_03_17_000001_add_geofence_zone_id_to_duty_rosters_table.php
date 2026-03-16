<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGeofenceZoneIdToDutyRostersTable extends Migration
{
    public function up()
    {
        Schema::table('duty_rosters', function (Blueprint $table) {
            $table->unsignedBigInteger('geofence_zone_id')->nullable()->after('DayOfDate');
        });
    }

    public function down()
    {
        Schema::table('duty_rosters', function (Blueprint $table) {
            $table->dropColumn('geofence_zone_id');
        });
    }
}
