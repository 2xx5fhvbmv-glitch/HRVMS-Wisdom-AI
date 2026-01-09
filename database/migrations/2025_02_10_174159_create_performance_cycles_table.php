<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceCyclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('performance_cycles', function (Blueprint $table) 
        {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('Cycle_Name');
            $table->date('Start_Date');
            $table->date('End_Date');
            $table->text('CycleSummary');
            $table->integer('Self_Review_Templete')->nullable();
            // $table->integer('Self_Review')->nullable();
            // $table->integer('Manager_Review')->nullable();
            $table->integer('Manager_Review_Templete')->nullable();
            $table->date('Self_Activity_Start_Date')->nullable();
            $table->date('Self_Activity_End_Date')->nullable();
            $table->date('Manager_Activity_Start_Date')->nullable();
            $table->date('Manager_Activity_End_Date')->nullable();
            $table->enum('CycleReminders',['ON',"OFF"])->default('OFF');
            $table->enum('status', ['Pending','OnGoing','Close'])->default('Pending');
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
        Schema::dropIfExists('performance_cycles');
    }
}
