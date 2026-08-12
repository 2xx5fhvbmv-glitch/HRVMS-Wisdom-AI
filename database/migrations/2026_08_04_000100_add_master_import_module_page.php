<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers the Master Import page (resort.masterimport.index) in
 * module_pages so it can be permission-gated and shows up in the sidebar —
 * previously had no row at all, so Common::checkRouteWisePermission()
 * treated it as ungated (open to everyone with a login) and it never
 * appeared in the nav (menu is built entirely from module_pages, nothing
 * hardcoded).
 *
 * Filed under the "Settings" module. Resolved by name rather than a
 * hardcoded id — the seeder file's own copy of this module's id (14) has
 * already drifted from what's actually live (19), so trusting either
 * hardcoded value here would be a coin flip.
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
            ['internal_route' => 'resort.masterimport.index'],
            [
                'page_name' => 'Master Import',
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
        DB::table('module_pages')->where('internal_route', 'resort.masterimport.index')->delete();
    }
};
