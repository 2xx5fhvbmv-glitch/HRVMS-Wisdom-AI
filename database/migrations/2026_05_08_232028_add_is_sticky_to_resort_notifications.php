<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsStickyToResortNotifications extends Migration
{
    public function up()
    {
        Schema::table('resort_notifications', function (Blueprint $table) {
            // Pinned-to-top flag for super-admin "Admin Notice" entries.
            // Mirrors notifications.sticky on the parent admin row so the
            // bell-dropdown ORDER BY can put sticky messages on top without
            // needing to join back to notifications on every read.
            $table->boolean('is_sticky')->default(false)->after('request_id');
            $table->index(['user_id', 'status', 'is_sticky']);
        });
    }

    public function down()
    {
        Schema::table('resort_notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status', 'is_sticky']);
            $table->dropColumn('is_sticky');
        });
    }
}
