<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParentAttendacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parent_attendaces', function (Blueprint $table) {
            $table->id();


            $table->unsignedInteger('roster_id');
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('Shift_id');
            $table->unsignedInteger('Emp_id');
            $table->string('OverTime')->nullable();
            $table->string('CheckingTime')->nullable();
            $table->string('DayWiseTotalHours')->nullable();
            $table->string('CheckingOutTime')->nullable();
            $table->date('date')->nullable();
            $table->enum('Status',["On-Time","Late","Absent","Present","DayOff","ShortLeave","HalfDayLeave","FullDayLeave"])->default(null);
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            $table->foreign('Emp_id')->references('id')->on('employees');
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('Shift_id')->references('id')->on('shift_settings');
            $table->foreign('roster_id')->references('id')->on('duty_rosters');

        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parent_attendaces');
    }
}
