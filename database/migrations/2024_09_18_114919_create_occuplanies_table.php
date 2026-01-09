<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOccuplaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('occuplanies', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger(  'resort_id');
            $table->date('occupancydate')->format('d/m/y');
            $table->float('occupancyinPer');
            $table->integer('occupancytotalRooms')->nullable();
            $table->integer('occupancyOccupiedRooms')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('occuplanies');
    }
}
