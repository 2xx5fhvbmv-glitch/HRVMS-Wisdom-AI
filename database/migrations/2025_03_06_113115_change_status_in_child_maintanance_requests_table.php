<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeStatusInChildMaintananceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE child_maintanance_requests CHANGE COLUMN `Status` `Status` ENUM('pending','On-Hold','Open','Assinged','In-Progress','Resolvedawaiting','Closed','Approved','Rejected') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE child_maintanance_requests CHANGE COLUMN `Status` `Status` ENUM('pending','On-Hold','Open','Assinged','In-Progress','Resolvedawaiting','Closed') NOT NULL DEFAULT 'pending'");
    }
}
