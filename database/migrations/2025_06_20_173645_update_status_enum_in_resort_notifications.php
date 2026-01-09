<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusEnumInResortNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resort_notifications', function (Blueprint $table) {
            DB::statement("ALTER TABLE resort_notifications MODIFY status ENUM('unread','read','deleted') NOT NULL DEFAULT 'unread'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('resort_notifications', function (Blueprint $table) {
            DB::statement("ALTER TABLE resort_notifications MODIFY status ENUM('unread','read') NOT NULL DEFAULT 'unread'");
        });
    }
}
