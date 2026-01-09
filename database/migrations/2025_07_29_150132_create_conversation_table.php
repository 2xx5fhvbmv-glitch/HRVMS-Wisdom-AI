<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConversationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversation', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id')->nullable()->comment('ID of the resort');
            $table->enum ('type', ['group', 'individual'])->default('individual')->comment('Type of conversation, group or individual');
            $table->integer('type_id')->comment('ID of the group or individual');
            $table->integer('sender_id')->comment('ID of the sender');
            $table->text('message');
            $table->string('attachment')->nullable()->comment('Attachment');
            $table->integer('created_by')->unsigned()->nullable()->comment('ID of the user who created the conversation');
            $table->integer('modified_by')->unsigned()->nullable()->comment('ID of the user who modified the conversation');
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');

        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversation');
    }
}
