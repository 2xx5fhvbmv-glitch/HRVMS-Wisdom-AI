<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatGroupMemberTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_group_member', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_group_id')->comment('chat_group_id')->nullable();
            $table->unsignedInteger('user_id')->comment('resort_admin_id')->nullable();
            $table->string('role')->nullable();
            $table->timestamp('joined_at');
            
            $table->foreign('chat_group_id')->references('id')->on('chat_group')->onDelete('cascade');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_group_member');
    }
}
