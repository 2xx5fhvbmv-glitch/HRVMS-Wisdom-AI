<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hide the "Permission" submenu under File Management. The route + page
 * controller still exist (for future re-enable), but the menu item is
 * flipped to Inactive so the sidebar render at Common::Menu(...) which
 * filters by status='Active' no longer surfaces it.
 *
 * The seeder entry has also been commented out so a fresh re-seed
 * doesn't reactivate it.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('module_pages')
            ->where('internal_route', 'FileManage.Permission')
            ->update(['status' => 'Inactive', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('module_pages')
            ->where('internal_route', 'FileManage.Permission')
            ->update(['status' => 'Active', 'updated_at' => now()]);
    }
};
