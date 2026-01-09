<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChangeToEmployeeChatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_chat_messages', function (Blueprint $table) {
            $table->string('conversation_id')->change();
            $table->text('message')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_chat_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('conversation_id')->change();
            $table->text('message')->change();
        });
    }
}
