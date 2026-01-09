<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeStatusInMaintananceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE maintanace_requests CHANGE COLUMN `Status` `Status` ENUM('Open','pending','On-Hold','In-Progress','Assigned','Closed','Approved','Rejected') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE maintanace_requests CHANGE COLUMN `Status` `Status` ENUM('Open','pending','On-Hold','In-Progress','Assigned','Closed') NOT NULL DEFAULT 'pending'");
    }
}
