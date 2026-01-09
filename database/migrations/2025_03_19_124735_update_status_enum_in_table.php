<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusEnumInTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maintanace_requests', function (Blueprint $table) {
            DB::statement("ALTER TABLE maintanace_requests MODIFY COLUMN Status ENUM('Open', 'pending', 'On-Hold', 'In-Progress', 'Assigned', 'Closed', 'Approved', 'Rejected', 'ResolvedAwaiting') NOT NULL DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maintanace_requests', function (Blueprint $table) {
            DB::statement("ALTER TABLE maintanace_requests MODIFY COLUMN Status ENUM('Open', 'pending', 'On-Hold', 'In-Progress', 'Assigned', 'Closed', 'Approved', 'Rejected') NOT NULL DEFAULT 'pending'");
        });
    }
}
