<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers the unified web-portal Housekeeping Request page
 * (resort.accommodation.HousekeepingRequest) in module_pages. Without this
 * row Common::checkRouteWisePermission() treats the route as open to
 * anyone logged in once routed — same reasoning as
 * 2026_09_19_090100_add_position_config_module_page.
 *
 * Grants are cloned from "Assign Accommodation" (resort.accommodation.
 * AssignAccommation) rather than re-deriving "who is HR" from department
 * name aliases — same audience (HR/accommodation admin staff) already has
 * real, live per-resort grant rows on that sibling page.
 */
return new class extends Migration
{
    private const NEW_ROUTE = 'resort.accommodation.HousekeepingRequest';
    private const SOURCE_ROUTE = 'resort.accommodation.AssignAccommation';

    public function up()
    {
        $sourcePage = DB::table('module_pages')->where('internal_route', self::SOURCE_ROUTE)->first();
        if (!$sourcePage) {
            return;
        }

        DB::table('module_pages')->updateOrInsert(
            ['internal_route' => self::NEW_ROUTE],
            [
                'page_name' => 'Housekeeping Request',
                'Module_Id' => $sourcePage->Module_Id,
                'TypeOfPage' => 'InsideOfMenu',
                'type' => 'normal',
                'place_order' => ($sourcePage->place_order ?? 0) + 1,
                'status' => 'Active',
                'created_by' => 1,
                'modified_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $newPage = DB::table('module_pages')->where('internal_route', self::NEW_ROUTE)->first();
        $now = now();

        foreach (DB::table('resort_interal_pages_permissions')->where('page_id', $sourcePage->id)->get() as $grant) {
            $exists = DB::table('resort_interal_pages_permissions')
                ->where('resort_id', $grant->resort_id)
                ->where('Dept_id', $grant->Dept_id)
                ->where('position_id', $grant->position_id)
                ->where('page_id', $newPage->id)
                ->where('Permission_id', $grant->Permission_id)
                ->exists();

            if (!$exists) {
                DB::table('resort_interal_pages_permissions')->insert([
                    'resort_id' => $grant->resort_id,
                    'Dept_id' => $grant->Dept_id,
                    'position_id' => $grant->position_id,
                    'page_id' => $newPage->id,
                    'Permission_id' => $grant->Permission_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $resortIdsWithSourcePage = DB::table('resort_pagewise_permissions')
            ->where('Module_id', $sourcePage->Module_Id)
            ->where('page_permission_id', $sourcePage->id)
            ->pluck('resort_id');

        foreach ($resortIdsWithSourcePage as $resortId) {
            $exists = DB::table('resort_pagewise_permissions')
                ->where('resort_id', $resortId)
                ->where('Module_id', $newPage->Module_Id)
                ->where('page_permission_id', $newPage->id)
                ->exists();

            if (!$exists) {
                DB::table('resort_pagewise_permissions')->insert([
                    'resort_id' => $resortId,
                    'Module_id' => $newPage->Module_Id,
                    'page_permission_id' => $newPage->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $page = DB::table('module_pages')->where('internal_route', self::NEW_ROUTE)->first();
        if ($page) {
            DB::table('resort_interal_pages_permissions')->where('page_id', $page->id)->delete();
            DB::table('resort_pagewise_permissions')->where('page_permission_id', $page->id)->delete();
        }
        DB::table('module_pages')->where('internal_route', self::NEW_ROUTE)->delete();
    }
};
