<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOccupancyLevelsHitACriticalThresholdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('occupancy_levels_hit_a_critical_thresholds', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('building_id');
            $table->integer('Floor');
            $table->integer('RoomNo');
            $table->float('ThresSoldLevel');
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
        Schema::dropIfExists('occupancy_levels_hit_a_critical_thresholds');
    }
}
