<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers the Benefit Grade Levels page (resort.benefitgradelevel.index)
 * in module_pages — the controller and its blade view already existed but
 * had no route AND no module_pages row, so the page was both unreachable
 * and, once routed, would have been wide open (no row =
 * Common::checkRouteWisePermission() treats it as open to anyone logged
 * in). Same reasoning as 2026_08_07_000100_add_email_config_module_page.
 *
 * Filed under Workforce Planning, next to the existing "Benefit Grid" page
 * — resolved by module name rather than trusting a hardcoded id.
 */
return new class extends Migration
{
    public function up()
    {
        $moduleId = DB::table('modules')->where('module_name', 'Workforce Planning')->value('id');
        if (!$moduleId) {
            return;
        }

        DB::table('module_pages')->updateOrInsert(
            ['internal_route' => 'resort.benefitgradelevel.index'],
            [
                'page_name' => 'Benefit Grade Levels',
                'Module_Id' => $moduleId,
                'TypeOfPage' => 'InsideOfPage',
                'type' => 'normal',
                'place_order' => 0,
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
        DB::table('module_pages')->where('internal_route', 'resort.benefitgradelevel.index')->delete();
    }
};
