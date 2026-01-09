<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProbationaryLearningProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('probationary_learning_programs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('program_id');
            $table->integer('completion_days');
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('learning_programs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('probationary_learning_programs');
    }
}
