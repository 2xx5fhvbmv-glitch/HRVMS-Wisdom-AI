<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnnouncementNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('announcement_notification', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('announcement_id');
            $table->unsignedInteger('employee_id');
            $table->enum('status', ['unread','read','deleted'])->default('unread');
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('announcement_id')->references('id')->on('announcement')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
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
        Schema::dropIfExists('announcement_notification');
    }
}
