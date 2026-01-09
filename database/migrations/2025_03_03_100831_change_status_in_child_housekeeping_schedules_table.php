<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeStatusInChildHousekeepingSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE child_housekeeping_schedules CHANGE COLUMN `status` `status` ENUM('Pending','Open','On-Hold','Assigned','In-Progress', 'Complete') NOT NULL DEFAULT 'Pending'");
   
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE child_housekeeping_schedules CHANGE COLUMN `status` `status` ENUM('Pending', 'In-Progess', 'Complete') NOT NULL DEFAULT 'Pending'");
    }
}
