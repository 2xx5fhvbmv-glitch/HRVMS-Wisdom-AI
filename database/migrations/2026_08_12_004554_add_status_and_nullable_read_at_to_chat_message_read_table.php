<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusAndNullableReadAtToChatMessageReadTable extends Migration
{
    /**
     * ChatBoat\ConversationController::sendMessage() creates a row with
     * 'status' => 'Unread' (no read_at), markAsRead() later sets status to
     * 'Read' + read_at, and ChatController::index() filters unread counts on
     * status = 'Unread' — but the original create migration never added a
     * `status` column and made `read_at` NOT NULL, so every one of those
     * three call sites has always thrown a "Column not found" /
     * "doesn't have a default value" SQL error.
     */
    public function up()
    {
        Schema::table('chat_message_read', function (Blueprint $table) {
            $table->string('status')->default('Unread')->after('user_id');
            $table->datetime('read_at')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('chat_message_read', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->datetime('read_at')->nullable(false)->change();
        });
    }
}
