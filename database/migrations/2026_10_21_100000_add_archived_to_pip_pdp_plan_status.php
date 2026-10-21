<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PipPdpController archives a plan with status 'archived' and lists the archive by it, but the enum never had
 * that value — with strict mode off MySQL stored '' instead, so an archived plan vanished from both the active
 * list and the archive. Add the value, and give back 'archived' to the rows that lost it ('' is only ever
 * written by that archive call: every other write is active/completed/cancelled).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['employee_pip_plans', 'employee_pdp_plans'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            DB::statement("ALTER TABLE `{$table}` MODIFY `status` ENUM('active','completed','cancelled','archived') NOT NULL DEFAULT 'active'");
            DB::table($table)->where('status', '')->update(['status' => 'archived']);
        }
    }

    public function down(): void
    {
        foreach (['employee_pip_plans', 'employee_pdp_plans'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            DB::table($table)->where('status', 'archived')->update(['status' => 'cancelled']);
            DB::statement("ALTER TABLE `{$table}` MODIFY `status` ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active'");
        }
    }
};
