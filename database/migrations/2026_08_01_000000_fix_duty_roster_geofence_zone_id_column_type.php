<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assign Geo-Fence Zone on create-duty-roster is a multi-select (checkbox
 * list, field name geofence_zone_ids[]) and StoreDutyRoster always wrote
 * json_encode($geofenceZoneIds) into geofence_zone_id — but the column was
 * schema'd as a single unsignedBigInteger. With strict mode off, MySQL
 * silently truncated every JSON array string (e.g. "[3]") to 0 on insert,
 * so every roster with a zone assigned actually stored 0 instead — geofence
 * was effectively never configured for any roster, and the mobile app
 * always saw it as null/absent.
 *
 * Existing rows already saved as 0 have lost their original zone selection
 * (0 destroyed the data on write) and cannot be recovered — they'll read as
 * "no zone assigned" going forward and need to be re-assigned.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('duty_rosters', function (Blueprint $table) {
            $table->text('geofence_zone_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('duty_rosters', function (Blueprint $table) {
            $table->unsignedBigInteger('geofence_zone_id')->nullable()->change();
        });
    }
};
