<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Audit run-3 H3: resort_admins.type is a NOT NULL enum('super','sub') with no default, and MySQL runs non-strict, so a
 * create that left `type` out (the Add Employee wizard) stored the enum's first value, 'super' — which passes every page
 * permission check. Give the column a 'sub' default and set back to 'sub' every account that is 'super' without being
 * the resort's master admin (the only legitimate 'super': ResortsController / the Demo seeder set is_master_admin = 1).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE resort_admins MODIFY type ENUM('super','sub') NOT NULL DEFAULT 'sub'");
        DB::table('resort_admins')->where('type', 'super')->where(fn ($q) => $q->where('is_master_admin', 0)->orWhereNull('is_master_admin'))
            ->update(['type' => 'sub']);
    }

    public function down(): void
    {
        // The repaired rows are not turned back into full-access accounts.
        DB::statement("ALTER TABLE resort_admins MODIFY type ENUM('super','sub') NOT NULL");
    }
};
