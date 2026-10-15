<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ExitClearanceController::markAsComplete() sets status = 'Completed', which
 * the enum didn't allow (non-strict MySQL would store ''). Add it.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE employee_resignation MODIFY status ENUM('Pending','Approved','Rejected','On Hold','Withdraw','Completed') NOT NULL DEFAULT 'Pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE employee_resignation MODIFY status ENUM('Pending','Approved','Rejected','On Hold','Withdraw') NOT NULL DEFAULT 'Pending'");
    }
};
