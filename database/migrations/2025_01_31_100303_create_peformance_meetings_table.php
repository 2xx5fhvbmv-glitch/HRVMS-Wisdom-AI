<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeformanceMeetingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('peformance_meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('start_time')->nullable();
            // $table->string('StartTime')->nullable();
            $table->string('end_time')->nullable();
            $table->string('location')->nullable();
            $table->string('conference_links')->nullable();
            $table->longtext('description')->nullable();

            $table->unsignedInteger('resort_id');
            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('peformance_meetings');
    }
}
