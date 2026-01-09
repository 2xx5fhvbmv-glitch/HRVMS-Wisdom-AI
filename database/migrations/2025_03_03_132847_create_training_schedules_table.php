<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_schedules', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('training_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('venue')->nullable();
            $table->text('description')->nullable();
            $table->integer('created_by');
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('training_id')->references('id')->on('learning_programs')->onDelete('cascade');
        });

        Schema::create('training_participants', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('training_schedule_id');
            $table->unsignedInteger('employee_id');
            $table->enum('status', ['Pending', 'Present', 'Absent','Late'])->default('Pending');
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('training_schedule_id')->references('id')->on('training_schedules')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('training_participants'); // Drop child table first
        Schema::dropIfExists('training_schedules');   // Then drop parent table
    }

}
