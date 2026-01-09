<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortsParentNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resorts_parent_notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('resort_id');
            $table->enum('user_type', ['super', 'sub'])->default('sub'); // Fixed enum format
            $table->integer('user_id')->nullable();
            $table->integer('Department_id')->nullable();
            $table->integer('Position_id')->nullable();
            $table->string('message_id',50)->unique();
            $table->text('message_subject')->nullable(); // Corrected spelling
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
        Schema::dropIfExists('resorts_parent_notifications');
    }
}
