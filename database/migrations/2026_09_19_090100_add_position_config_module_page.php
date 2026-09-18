<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers "Position Configuration — Casuals & Interns"
 * (resort.positionconfig.index) in module_pages, alongside the existing
 * Cost Configuration entry. Without this row Common::checkRouteWisePermission()
 * treats the route as open to anyone logged in once routed — same reasoning
 * as 2026_09_16_223000_add_benefit_grade_level_module_page.
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
            ['internal_route' => 'resort.positionconfig.index'],
            [
                'page_name' => 'Position Configuration — Casuals & Interns',
                'Module_Id' => $moduleId,
                'TypeOfPage' => 'InsideOfMenu',
                'type' => 'normal',
                'place_order' => 6,
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
        DB::table('module_pages')->where('internal_route', 'resort.positionconfig.index')->delete();
    }
};
