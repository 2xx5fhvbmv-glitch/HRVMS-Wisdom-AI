<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Item 4 — Listing screens for Finance, GM and HR.
 *
 * The Transfer module exposes a single menu page (`people.transfer.initiate`,
 * Module_Id 4). The Transfer List / approval screen (`people.transfer.list`)
 * is reached through that page and gated by its view permission
 * (Common::checkRouteWisePermission('people.transfer.initiate', ...)).
 *
 * Finance / GM / HR approvers could therefore only see the transfer listing
 * and act on their approval queue if their position had been ticked on the
 * Page Permission screen — which was not guaranteed. This migration grants
 * the Transfer page's VIEW (1) and EDIT (3) permissions to every Finance,
 * GM and HR position in each resort that already has the Transfer page
 * registered, so those roles can always reach their approval queue.
 *
 * - Finance positions  : Rank = 7
 * - GM positions       : Rank = 8
 * - HR positions       : Rank = 3, plus HOD/EXCOM (Rank 2/1) of the HR dept
 *
 * Idempotent — re-runnable; only inserts grant rows that don't exist.
 */
class GrantTransferPageToFinanceGmHr extends Migration
{
    private const TRANSFER_ROUTE = 'people.transfer.initiate';

    public function up()
    {
        $page = DB::table('module_pages')
            ->where('internal_route', self::TRANSFER_ROUTE)
            ->first();

        if (!$page) {
            return; // Transfer page not registered — nothing to grant.
        }

        $pageId   = $page->id;
        $moduleId = $page->Module_Id;
        $now      = Carbon::now();

        // VIEW + EDIT permissions (see config/settings.php resort_permissions).
        $permissionIds = [1, 3];

        // Resorts that already have the Transfer page wired in pagewise perms.
        $resortIds = DB::table('resort_pagewise_permissions')
            ->where('Module_id', $moduleId)
            ->where('page_permission_id', $pageId)
            ->distinct()
            ->pluck('resort_id');

        // Fallback: if no pagewise rows exist, cover every resort that has
        // any internal grant on the Transfer page.
        if ($resortIds->isEmpty()) {
            $resortIds = DB::table('resort_interal_pages_permissions')
                ->where('page_id', $pageId)
                ->distinct()
                ->pluck('resort_id');
        }

        $hrAliases = ['hr', 'human resources', 'human resource'];

        foreach ($resortIds as $resortId) {
            // Ensure a pagewise row exists for the resort.
            $pwExists = DB::table('resort_pagewise_permissions')
                ->where('resort_id', $resortId)
                ->where('Module_id', $moduleId)
                ->where('page_permission_id', $pageId)
                ->exists();
            if (!$pwExists) {
                DB::table('resort_pagewise_permissions')->insert([
                    'resort_id'          => $resortId,
                    'Module_id'          => $moduleId,
                    'page_permission_id' => $pageId,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            }

            // HR department ids for this resort (name/short/code alias match).
            $hrDeptIds = DB::table('resort_departments')
                ->where('resort_id', $resortId)
                ->where(function ($q) use ($hrAliases) {
                    foreach (['name', 'short_name', 'code'] as $col) {
                        $q->orWhereIn(DB::raw('LOWER(TRIM(' . $col . '))'), $hrAliases);
                    }
                })
                ->pluck('id')
                ->all();

            // Finance / GM / HR positions for this resort.
            //  - rank 7 (Finance), 8 (GM), 3 (HR) anywhere
            //  - rank 2 (HOD) / 1 (EXCOM) only within the HR department
            $positions = DB::table('resort_positions')
                ->where('resort_id', $resortId)
                ->where(function ($q) use ($hrDeptIds) {
                    $q->whereIn('Rank', [7, 8, 3]);
                    if (!empty($hrDeptIds)) {
                        $q->orWhere(function ($q2) use ($hrDeptIds) {
                            $q2->whereIn('Rank', [1, 2])
                               ->whereIn('dept_id', $hrDeptIds);
                        });
                    }
                })
                ->get(['id', 'dept_id']);

            foreach ($positions as $position) {
                foreach ($permissionIds as $permissionId) {
                    $exists = DB::table('resort_interal_pages_permissions')
                        ->where('resort_id', $resortId)
                        ->where('position_id', $position->id)
                        ->where('page_id', $pageId)
                        ->where('Permission_id', $permissionId)
                        ->exists();

                    if (!$exists) {
                        DB::table('resort_interal_pages_permissions')->insert([
                            'resort_id'     => $resortId,
                            'Dept_id'       => $position->dept_id,
                            'position_id'   => $position->id,
                            'Permission_id' => $permissionId,
                            'page_id'       => $pageId,
                            'created_at'    => $now,
                            'updated_at'    => $now,
                        ]);
                    }
                }
            }
        }
    }

    public function down()
    {
        // No-op: grants may have been further adjusted by admins via the
        // Page Permission screen after this migration. Reversing risks
        // removing legitimately-added permissions. Left intentionally empty.
    }
}
