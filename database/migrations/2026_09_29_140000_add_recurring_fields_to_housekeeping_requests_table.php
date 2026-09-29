<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trello: "Housekeeping – Schedule, Building Name, Recipients & Recurring
 * Dates". HR could only ever raise a request for a single date/time — no
 * way to say "3x/week: Mon, Tue, Wed". recurring_days is a JSON array of
 * ISO weekday ints (1=Mon..7=Sun) in a text column, same convention as
 * DutyRoster::geofence_zone_id. Every generated occurrence row shares the
 * submission's existing batch_id (already the "rows created from one HR
 * submission" grouping key — see HousekeepingRequest table comment) rather
 * than a new grouping column, so each stays its own independently
 * trackable housekeeping_requests row (own status/assignee/photos) while
 * still being found together.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('housekeeping_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('frequency')->nullable()->after('scheduled_time');
            $table->text('recurring_days')->nullable()->after('frequency');
        });
    }

    public function down()
    {
        Schema::table('housekeeping_requests', function (Blueprint $table) {
            $table->dropColumn(['frequency', 'recurring_days']);
        });
    }
};
