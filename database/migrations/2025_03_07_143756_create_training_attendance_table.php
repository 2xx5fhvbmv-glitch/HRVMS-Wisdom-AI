<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingAttendanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_attendance', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('training_schedule_id');
            $table->unsignedInteger('employee_id');
            $table->date('attendance_date');
            $table->enum('status', ['Present', 'Absent','Late','Pending'])->default('Pending');
            $table->integer('created_by')->nullable();
            $table->timestamps();

            $table->foreign('training_schedule_id')->references('id')->on('training_schedules')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('training_attendance');
    }
}
