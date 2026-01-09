<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningCalendarSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('learning_calendar_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('learning_program_id'); // Foreign key to the learning program
            $table->date('session_date');
            $table->time('session_time')->nullable();
            $table->string('venue')->nullable();
            $table->enum('frequency', ['one-time', 'recurring', 'quarterly', 'annually'])->default('one-time');

            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('learning_program_id')->references('id')->on('learning_programs')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('learning_calendar_sessions');
    }
}
