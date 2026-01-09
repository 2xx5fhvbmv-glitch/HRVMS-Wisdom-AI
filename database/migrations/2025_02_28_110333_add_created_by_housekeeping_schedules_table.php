<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByHousekeepingSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('housekeeping_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->after('status')->nullable();
            $table->unsignedBigInteger('modified_by')->after('created_by')->nullable();
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
            $table->dropColumn(['created_by', 'modified_by']);
        });
    }
}
