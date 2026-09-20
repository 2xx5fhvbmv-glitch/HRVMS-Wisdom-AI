<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * WP8 — 2026_09_19_120100 granted Position Configuration's view/create/
 * edit/delete to every Rank=3 (HR) position via
 * resort_interal_pages_permissions, but Common::CheckResortPermissions()
 * inner-joins that table against resort_pagewise_permissions, and that
 * migration never inserted the matching resort_pagewise_permissions row.
 * Result: the grant was a no-op on every resort (same missing-row bug that
 * 2026_05_17_100200 already fixes for the Transfer page). This migration
 * only adds the missing pagewise row — it doesn't re-touch or re-scope the
 * Rank=3 grant itself, which is already correct and already ran.
 */
return new class extends Migration
{
    public function up()
    {
        $page = DB::table('module_pages')->where('internal_route', 'resort.positionconfig.index')->first();
        if (!$page) {
            return;
        }

        $resortIds = DB::table('resort_interal_pages_permissions')
            ->where('page_id', $page->id)
            ->distinct()
            ->pluck('resort_id');

        $now = now();
        foreach ($resortIds as $resortId) {
            $exists = DB::table('resort_pagewise_permissions')
                ->where('resort_id', $resortId)
                ->where('Module_id', $page->Module_Id)
                ->where('page_permission_id', $page->id)
                ->exists();

            if (!$exists) {
                DB::table('resort_pagewise_permissions')->insert([
                    'resort_id' => $resortId,
                    'Module_id' => $page->Module_Id,
                    'page_permission_id' => $page->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        // No-op — see 2026_05_17_100200's rationale: admins may have since
        // relied on this via the Page Permission screen.
    }
};
