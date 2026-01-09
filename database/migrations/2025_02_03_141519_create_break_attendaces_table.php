<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakAttendacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('break_attendaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Parent_attd_id');
            $table->string('Break_InTime');
            $table->string('Break_OutTime');
            $table->string('Total_Break_Time');
            $table->string('InTime_Location');
            $table->string('OutTime_Location');
            $table->timestamps();

            $table->foreign('Parent_attd_id')->references('id')->on('parent_attendaces');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('break_attendaces');
    }
}
