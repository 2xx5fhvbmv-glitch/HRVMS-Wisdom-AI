<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDutyRostersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('duty_rosters', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('Shift_id');
            $table->unsignedInteger('Emp_id');
            $table->string('ShiftDate')->nullable();
            $table->string('Year')->nullable();
            $table->string('DayOfDate')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('Emp_id')->references('id')->on('employees');
            $table->foreign('resort_id')->references('id')->on('resorts');

            
            $table->foreign('Shift_id')->references('id')->on('shift_settings');

        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('duty_rosters');
    }
}
