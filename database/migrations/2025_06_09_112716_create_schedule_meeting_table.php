<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduleMeetingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resignation_meeting_schedule', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resignationId')->nullable();
            $table->text('title')->nullable();
            $table->date('meeting_date')->nullable();
            $table->time('meeting_time')->nullable();
            $table->enum('meeting_with', ['HOD', 'HR'])->default('HOD');
            $table->enum('status', ['Pending','Completed'])->default('Pending');
            $table->unsignedInteger('created_by')->nullable();

            $table->foreign('resignationId')->references('id')->on('employee_resignation')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resignation_meeting_schedule');
    }
}
