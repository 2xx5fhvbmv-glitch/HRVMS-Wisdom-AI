<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChildAttendacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('child_attendaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Parent_attd_id');
            $table->string('InTime_out');
            $table->string('OutTime_out');
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
        Schema::dropIfExists('child_attendaces');
    }
}
