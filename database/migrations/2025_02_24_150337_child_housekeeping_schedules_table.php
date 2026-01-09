<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChildHousekeepingSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('child_housekeeping_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('housekeeping_id');
            $table->integer('ApprovedBy')->nullable();
            $table->integer('rank')->nullable();
            $table->date('date')->nullable();
            $table->enum('status',['Pending','In-Progess','Complete'])->default(null);
            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('housekeeping_id')->references('id')->on('housekeeping_schedules'); // FK to housekeeping_schedules
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('child_housekeeping_schedules');
    }
}
