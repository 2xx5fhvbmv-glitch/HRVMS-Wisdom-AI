<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Master Import (module_pages id 134, added 2026_08_04_000100) and Email
 * Config (id 135, added 2026_08_07_000100) were registered in module_pages
 * but never backfilled into resort_pagewise_permissions — the parent
 * "is this page enabled for this resort at all" table that
 * getMenuData()'s whereHas('resort_internal_pages', ...) join requires
 * before it even looks at a position's granular permission rows in
 * resort_interal_pages_permissions.
 *
 * Confirmed against real data: a resort admin (Olivia Davis, resort 26)
 * had full view/create/edit/delete granted on both pages in
 * resort_interal_pages_permissions, but neither page ever appeared in her
 * rendered menu — because resort_pagewise_permissions had no row for
 * page_id 134/135 on any resort, only for the older "Setting" page (id 8)
 * under the same Settings module (id 19).
 *
 * Backfills one row per resort that already has the Settings module
 * enabled (i.e. already has a page_id=8 row) but is missing 134 and/or
 * 135 — mirrors the seeding pattern used when a resort's permission
 * matrix was first set up, just applied retroactively for these two pages.
 */
return new class extends Migration
{
    public function up()
    {
        $resortIds = DB::table('resort_pagewise_permissions')
            ->where('Module_id', 19)
            ->where('page_permission_id', 8)
            ->distinct()
            ->pluck('resort_id');

        foreach ([134, 135] as $pageId) {
            $alreadyHave = DB::table('resort_pagewise_permissions')
                ->where('Module_id', 19)
                ->where('page_permission_id', $pageId)
                ->pluck('resort_id')
                ->all();

            $missing = $resortIds->diff($alreadyHave);
            if ($missing->isEmpty()) {
                continue;
            }

            $now = now();
            DB::table('resort_pagewise_permissions')->insert(
                $missing->map(fn ($resortId) => [
                    'resort_id'          => $resortId,
                    'Module_id'          => 19,
                    'page_permission_id' => $pageId,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ])->all()
            );
        }
    }

    public function down()
    {
        DB::table('resort_pagewise_permissions')
            ->where('Module_id', 19)
            ->whereIn('page_permission_id', [134, 135])
            ->delete();
    }
};
