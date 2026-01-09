<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupportChatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('support_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('support_id');
            $table->unsignedBigInteger('sender_id');
            $table->enum('sender_type', ['admin', 'employee']);
            $table->unsignedBigInteger('receiver_id');
            $table->enum('receiver_type', ['admin', 'employee']);
            $table->text('message');
            $table->string('attachment')->nullable();
            $table->timestamps();

            $table->foreign('support_id')->references('id')->on('support')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('support_chat_messages');
    }
}
