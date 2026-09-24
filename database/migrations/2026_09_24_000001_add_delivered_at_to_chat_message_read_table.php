<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDeliveredAtToChatMessageReadTable extends Migration
{
    /**
     * One chat_message_read row = one (message, recipient). delivered_at gives
     * the sent -> delivered -> read tick progression; the index backs the
     * per-thread receipt lookups.
     */
    public function up()
    {
        Schema::table('chat_message_read', function (Blueprint $table) {
            $table->datetime('delivered_at')->nullable()->after('status');
            $table->index(['conversation_id', 'user_id'], 'cmr_conversation_user_idx');
            $table->index(['user_id', 'status'], 'cmr_user_status_idx');
        });

        // Group sends used to store the GROUP id as user_id — those rows
        // belong to no real recipient (and show as phantom unread to whichever
        // admin happens to share that numeric id). Per-recipient rows are
        // written from now on.
        DB::statement("DELETE r FROM chat_message_read r
            JOIN conversation c ON c.id = r.conversation_id
            WHERE c.type = 'group' AND r.user_id = c.type_id");
    }

    public function down()
    {
        Schema::table('chat_message_read', function (Blueprint $table) {
            $table->dropIndex('cmr_conversation_user_idx');
            $table->dropIndex('cmr_user_status_idx');
            $table->dropColumn('delivered_at');
        });
    }
}
