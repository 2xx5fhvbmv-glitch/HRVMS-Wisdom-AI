<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldsToSupportChatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('support_chat_messages', function (Blueprint $table) {
            $table->string('message_id')->nullable()->unique();
            $table->string('in_reply_to')->nullable();
            $table->string('subject')->nullable();
            $table->boolean('is_email')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('support_chat_messages', function (Blueprint $table) {
            $table->dropColumn(['message_id', 'in_reply_to', 'subject', 'is_email']);
        });
    }
}
