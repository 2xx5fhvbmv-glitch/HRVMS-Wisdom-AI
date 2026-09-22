<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddAcknowledgedStatusToDisciplinarySubmits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disciplinary_submits', function (Blueprint $table) {
            DB::statement("ALTER TABLE disciplinary_submits MODIFY status ENUM('pending','In_Review','Acknowledged','resolved','rejected') NOT NULL DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disciplinary_submits', function (Blueprint $table) {
            DB::statement("UPDATE disciplinary_submits SET status = 'In_Review' WHERE status = 'Acknowledged'");
            DB::statement("ALTER TABLE disciplinary_submits MODIFY status ENUM('pending','In_Review','resolved','rejected') NOT NULL DEFAULT 'pending'");
        });
    }
}
