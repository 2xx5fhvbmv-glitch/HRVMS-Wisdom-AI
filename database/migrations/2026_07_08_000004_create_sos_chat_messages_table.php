<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated SOS chat log — child_sos_history_status.sos_status already
 * reserves a 'chat_updates' timeline value that nothing writes to; the
 * shared `conversation` table's type enum is hard-limited to
 * 'group'/'individual' at the DB level, so widening it for SOS would touch
 * shared chat infra other modules depend on. A small dedicated table keeps
 * this isolated.
 */
class CreateSosChatMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('sos_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('sos_history_id');
            $table->unsignedInteger('sender_id'); // resort_admins.id, same convention as sos_team_member_activity.emp_id
            $table->text('message');
            $table->timestamps();

            $table->foreign('sos_history_id')->references('id')->on('sos_history')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sos_chat_messages');
    }
}
