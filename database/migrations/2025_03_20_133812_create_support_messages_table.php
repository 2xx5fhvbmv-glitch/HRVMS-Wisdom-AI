<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupportMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->enum('sender', ['admin', 'employee']); // 'admin' or 'resort'
            $table->unsignedBigInteger('sender_id');
            $table->text('message');
            $table->json('attachments')->nullable(); // Store file paths as JSON
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('support')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('support_messages');
    }
}
