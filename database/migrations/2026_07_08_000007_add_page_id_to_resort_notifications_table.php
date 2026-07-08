<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile notifications are static — tapping one does nothing because there's
 * no way to know which screen it relates to. page_id is a short readable
 * slug (e.g. "leave-approved", "boarding-pass-rejected") the mobile app can
 * map to a route. Nullable — the ~50 existing Common::sendMobileNotification()
 * call sites across the app aren't all updated in this pass, only the ones
 * matching this bug report (Boarding Pass, Leave); the rest keep working
 * exactly as before with page_id = null.
 */
class AddPageIdToResortNotificationsTable extends Migration
{
    public function up()
    {
        Schema::table('resort_notifications', function (Blueprint $table) {
            $table->string('page_id')->nullable()->after('module');
        });
    }

    public function down()
    {
        Schema::table('resort_notifications', function (Blueprint $table) {
            $table->dropColumn('page_id');
        });
    }
}
