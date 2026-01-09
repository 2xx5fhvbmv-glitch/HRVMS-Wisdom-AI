<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HousekeepingSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('housekeeping_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('available_a_id');
            $table->string('BuildingName')->nullable();
            $table->integer('Floor')->nullable();
            $table->string('RoomNo')->nullable();
            $table->date('date')->nullable();
            $table->string('time')->nullable();
            $table->string('special_instructions')->nullable();
            $table->enum('clean_type',['Deep Cleaning','Standard'])->default(null);
            $table->enum('status',['Pending','In-Progess','Complete'])->default(null);
            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('available_a_id')->references('id')->on('available_accommodation_models'); // FK to available_accommodation_models
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('housekeeping_schedules');
    }
}
