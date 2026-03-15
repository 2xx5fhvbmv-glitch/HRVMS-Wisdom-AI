<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortGeofencesTable extends Migration
{
    public function up()
    {
        Schema::create('resort_geofences', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('name');
            $table->string('color', 20)->default('#FF4444');
            $table->enum('shape_type', ['polygon', 'circle'])->default('polygon');
            $table->text('coordinates'); // JSON: polygon vertices or circle center+radius
            $table->integer('grace_period')->default(10); // minutes
            $table->enum('status', ['active', 'paused'])->default('active');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('resort_geofences');
    }
}
