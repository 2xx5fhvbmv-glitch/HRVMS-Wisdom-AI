<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAssignedToFieldToHousekeepingSchedules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('housekeeping_schedules', function (Blueprint $table) {
            $table->integer('Assigned_To')->after('RoomNo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('housekeeping_schedules', function (Blueprint $table) {
            $table->dropColumn(['Assigned_To']);
        });
    }
}
