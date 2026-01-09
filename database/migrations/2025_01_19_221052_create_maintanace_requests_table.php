<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaintanaceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('maintanace_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('FloorNo');
            $table->integer('RoomNo');
            $table->string('Image')->nullable();
            $table->string('Video')->nullable();
            $table->string('Request_id')->unique();
            $table->text('ReasonOnHold')->nullable();
            $table->integer('Raised_By');
            $table->integer('Assigned_To')->nullable();
            $table->text('descriptionIssues');
            $table->date('date');

            $table->enum('priority',['High','Low','Medium'])->default('Medium');
            $table->enum('Status',['Open','pending','On-Hold','In-Progress','Assigned','Closed'])->default('pending');
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('building_id')->references('id')->on('building_models');
            $table->foreign('item_id')->references('id')->on('inventory_modules');
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
        Schema::dropIfExists('maintanace_requests');
    }
}
