<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncidentResolutionTimelineTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incident_resolution_timeline', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id'); // If timelines are resort-specific
            $table->string('priority'); // High, Medium, Low
            $table->string('timeline'); // e.g., '2 Business Days'
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
        Schema::dropIfExists('incident_resolution_timeline');
    }
}
