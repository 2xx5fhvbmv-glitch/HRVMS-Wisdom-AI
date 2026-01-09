<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrivanceResoultionTimeLineModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grivance_resoultion_time_line_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->integer('HighPriority')->nullable();
            $table->integer('MediumPriority')->nullable();
            $table->integer('LowPriority')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable(); 
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
        Schema::dropIfExists('grivance_resoultion_time_line_models');
    }
}
