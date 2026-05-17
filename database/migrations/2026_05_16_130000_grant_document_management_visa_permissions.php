<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Grants the "Document Management" Visa page to every resort/position that
 * already has access to any other Visa page.
 *
 * Runs after 2026_05_16_120000_add_document_management_to_visa_menu, which
 * inserts the module_pages row. The Visa submenu (MasterDashboardController::
 * getMenuData) only renders pages the user's position has been granted via:
 *   - resort_pagewise_permissions      (per resort)
 *   - resort_interal_pages_permissions (per position, per permission type)
 * A page with no grant rows is silently omitted from the menu.
 *
 * This migration mirrors the existing Visa grants onto the new page so that
 * "whoever can see Renewals / Liabilities / etc. can also see Document
 * Management" — no manual ticking in the Page Permission screen per resort.
 *
 * All ids are resolved by route name / module — never hardcoded — because
 * module_pages auto-increment ids differ between environments.
 */
class GrantDocumentManagementVisaPermissions extends Migration
{
    private const DOC_ROUTE = 'resort.visa.DocumentManage';

    public function up()
    {
        $docPage = DB::table('module_pages')
            ->where('internal_route', self::DOC_ROUTE)
            ->first();

        // Menu migration hasn't run / page missing — nothing to grant.
        if (!$docPage) {
            return;
        }

        $newPageId    = $docPage->id;
        $visaModuleId = $docPage->Module_Id;

        // Every other Visa page (resolved by module, not hardcoded ids).
        $visaPageIds = DB::table('module_pages')
            ->where('Module_Id', $visaModuleId)
            ->where('id', '!=', $newPageId)
            ->pluck('id')
            ->all();

        if (empty($visaPageIds)) {
            return;
        }

        $now = Carbon::now();

        // ── 1. resort_pagewise_permissions — one row per resort that already
        //       has any Visa page. ──────────────────────────────────────────
        $resortIds = DB::table('resort_pagewise_permissions')
            ->where('Module_id', $visaModuleId)
            ->whereIn('page_permission_id', $visaPageIds)
            ->distinct()
            ->pluck('resort_id');

        foreach ($resortIds as $resortId) {
            $exists = DB::table('resort_pagewise_permissions')
                ->where('resort_id', $resortId)
                ->where('Module_id', $visaModuleId)
                ->where('page_permission_id', $newPageId)
                ->exists();

            if (!$exists) {
                DB::table('resort_pagewise_permissions')->insert([
                    'resort_id'          => $resortId,
                    'Module_id'          => $visaModuleId,
                    'page_permission_id' => $newPageId,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            }
        }

        // ── 2. resort_interal_pages_permissions — mirror every distinct
        //       (resort, dept, position, permission) grant on a Visa page
        //       onto the Document Management page. ────────────────────────
        $grants = DB::table('resort_interal_pages_permissions')
            ->whereIn('page_id', $visaPageIds)
            ->select('resort_id', 'Dept_id', 'position_id', 'Permission_id')
            ->distinct()
            ->get();

        foreach ($grants as $g) {
            $exists = DB::table('resort_interal_pages_permissions')
                ->where('resort_id', $g->resort_id)
                ->where('Dept_id', $g->Dept_id)
                ->where('position_id', $g->position_id)
                ->where('Permission_id', $g->Permission_id)
                ->where('page_id', $newPageId)
                ->exists();

            if (!$exists) {
                DB::table('resort_interal_pages_permissions')->insert([
                    'resort_id'     => $g->resort_id,
                    'Dept_id'       => $g->Dept_id,
                    'position_id'   => $g->position_id,
                    'Permission_id' => $g->Permission_id,
                    'page_id'       => $newPageId,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $docPage = DB::table('module_pages')
            ->where('internal_route', self::DOC_ROUTE)
            ->first();

        if (!$docPage) {
            return;
        }

        // Remove only the grants this migration could have created.
        DB::table('resort_interal_pages_permissions')
            ->where('page_id', $docPage->id)
            ->delete();

        DB::table('resort_pagewise_permissions')
            ->where('page_permission_id', $docPage->id)
            ->delete();
    }
}
