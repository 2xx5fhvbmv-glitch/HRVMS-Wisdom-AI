<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers the Clinic > Temporary Doctors page (resort.clinic.temporary-doctors.index)
 * in module_pages so it shows in the sidebar and is permission-gated — same pattern as
 * Master Import (2026_08_04_000100) and Email Config (2026_08_07_000100): no row =
 * MasterDashboardController::getMenuData() never renders it, and Common::resortHasPermissions()
 * has nothing to check against.
 *
 * Filed under Settings (id resolved by name, not trusted as a hardcoded constant).
 *
 * Also backfills resort_pagewise_permissions for every resort that already has Settings
 * enabled (has a page_permission_id=8 row) — the earlier two pages needed a separate
 * follow-up migration (2026_08_10_120000) after this step was missed the first time, so
 * it's folded in here directly instead of repeating that gap.
 *
 * Per-position (view/create/edit/delete) grants in resort_interal_pages_permissions are
 * left to the resort's own Page Permission settings screen, same as Master Import/Email
 * Config — this migration only makes the page exist and be assignable, not auto-grants it
 * to every position.
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
            ['internal_route' => 'resort.clinic.temporary-doctors.index'],
            [
                'page_name' => 'Temporary Doctors',
                'Module_Id' => $settingsModuleId,
                'TypeOfPage' => 'InsideOfMenu',
                'type' => 'normal',
                'place_order' => 2,
                'status' => 'Active',
                'created_by' => 1,
                'modified_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $pageId = DB::table('module_pages')->where('internal_route', 'resort.clinic.temporary-doctors.index')->value('id');

        $resortIds = DB::table('resort_pagewise_permissions')
            ->where('Module_id', $settingsModuleId)
            ->where('page_permission_id', 8)
            ->distinct()
            ->pluck('resort_id');

        $alreadyHave = DB::table('resort_pagewise_permissions')
            ->where('Module_id', $settingsModuleId)
            ->where('page_permission_id', $pageId)
            ->pluck('resort_id')
            ->all();

        $missing = $resortIds->diff($alreadyHave);
        if ($missing->isNotEmpty()) {
            $now = now();
            DB::table('resort_pagewise_permissions')->insert(
                $missing->map(fn ($resortId) => [
                    'resort_id'          => $resortId,
                    'Module_id'          => $settingsModuleId,
                    'page_permission_id' => $pageId,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ])->all()
            );
        }
    }

    public function down()
    {
        $settingsModuleId = DB::table('modules')->where('module_name', 'Settings')->value('id');
        $pageId = DB::table('module_pages')->where('internal_route', 'resort.clinic.temporary-doctors.index')->value('id');

        if ($pageId) {
            DB::table('resort_pagewise_permissions')
                ->where('Module_id', $settingsModuleId)
                ->where('page_permission_id', $pageId)
                ->delete();
        }

        DB::table('module_pages')->where('internal_route', 'resort.clinic.temporary-doctors.index')->delete();
    }
};
