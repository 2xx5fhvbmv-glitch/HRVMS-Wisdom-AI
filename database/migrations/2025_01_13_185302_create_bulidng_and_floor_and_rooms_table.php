<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBulidngAndFloorAndRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bulidng_and_floor_and_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('building_id');
            $table->integer('Floor');
            $table->integer('Room');
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('building_id')->references('id')->on('building_models');

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
        Schema::dropIfExists('bulidng_and_floor_and_rooms');
    }
}
