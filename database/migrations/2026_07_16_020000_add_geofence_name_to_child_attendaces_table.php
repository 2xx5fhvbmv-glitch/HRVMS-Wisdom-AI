<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGeofenceNameToChildAttendacesTable extends Migration
{
    /**
     * resolveGeofenceCheck() already looks up the matched ResortGeofence
     * row (for the within-bounds check) but only ever kept the boolean
     * result — the actual geofence name was never persisted, so the
     * attendance register/check-in display had nothing real to show and
     * fell back to a hardcoded "Office" label.
     */
    public function up()
    {
        Schema::table('child_attendaces', function (Blueprint $table) {
            $table->string('InTime_GeofenceName')->nullable()->after('InTime_WithinGeofence');
            $table->string('OutTime_GeofenceName')->nullable()->after('OutTime_WithinGeofence');
        });
    }

    public function down()
    {
        Schema::table('child_attendaces', function (Blueprint $table) {
            $table->dropColumn(['InTime_GeofenceName', 'OutTime_GeofenceName']);
        });
    }
}
