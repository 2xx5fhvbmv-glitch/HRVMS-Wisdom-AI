<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrievanceNonRetaliationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::create('grievance_non_retaliations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('timeframe_submission')->nullable();
            $table->string('reminder_frequency')->nullable();
            $table->string('reminder_default_time')->nullable();
            $table->enum('NonRetaliationFeedback',['yes','no'])->default('no');
            $table->enum('ReminderCompleteFeedback',['yes','no'])->default('no');

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
        Schema::dropIfExists('grievance_non_retaliations');
    }
}
