<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortsChildNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resorts_child_notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('response', ['Yes','No','Pending','Approval','Rejected'])->default('No');
            $table->timestamps();
            $table->string('Parent_msg_id');

            $table->foreign('Parent_msg_id')->references('message_id')->on('resorts_parent_notifications');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resorts_child_notifications');
    }
}
