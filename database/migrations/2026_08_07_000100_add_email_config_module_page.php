<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers the Email Config page (resort.emailconfig.index) in
 * module_pages so it can be permission-gated and shows up in the sidebar —
 * same reasoning as the Master Import page (2026_08_04_000100): no row =
 * Common::checkRouteWisePermission() treats it as open to anyone logged in,
 * and the nav is built entirely from this table.
 *
 * Filed under "Settings", id resolved by name rather than trusting either
 * hardcoded copy (the seeder's own "14" has already drifted from live "19").
 */
return new class extends Migration
{
    public function up()
    {
        $settingsModuleId = DB::table('modules')->where('module_name', 'Settings')->value('id');
        if (!$settingsModuleId) {
            return;
        }

        DB::table('module_pages')->updateOrInsert(
            ['internal_route' => 'resort.emailconfig.index'],
            [
                'page_name' => 'Email Config',
                'Module_Id' => $settingsModuleId,
                'TypeOfPage' => 'InsideOfMenu',
                'type' => 'normal',
                'place_order' => 1,
                'status' => 'Active',
                'created_by' => 1,
                'modified_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down()
    {
        DB::table('module_pages')->where('internal_route', 'resort.emailconfig.index')->delete();
    }
};
