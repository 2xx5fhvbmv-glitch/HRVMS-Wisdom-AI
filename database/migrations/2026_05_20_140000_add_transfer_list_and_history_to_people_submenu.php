<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Adds two submenu entries under People for HR to manage in-flight transfer
 * requests + view past ones:
 *   - "Transfer Requests" → people.transfer.list
 *   - "Transfer History"  → people.transfer.history
 *
 * Mirrors the resort-scope + position-scope permission rows from the
 * existing "Transfers" submenu (page id 88 → people.transfer.initiate) so
 * every resort/position that can currently initiate a transfer also
 * automatically gains access to the new list + history pages.
 *
 * Idempotent — skips creation if either page already exists by route.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();

        // The reference page we copy permissions from.
        $sourcePageId = DB::table('module_pages')
            ->where('internal_route', 'people.transfer.initiate')
            ->value('id');

        if (!$sourcePageId) {
            return; // Nothing to mirror against — abort cleanly.
        }

        $moduleId = (int) DB::table('module_pages')->where('id', $sourcePageId)->value('Module_Id');

        $newPages = [
            [
                'page_name'      => 'Transfer Requests',
                'internal_route' => 'people.transfer.list',
                'place_order'    => 6,
            ],
            [
                'page_name'      => 'Transfer History',
                'internal_route' => 'people.transfer.history',
                'place_order'    => 7,
            ],
        ];

        foreach ($newPages as $page) {
            $existingId = DB::table('module_pages')
                ->where('internal_route', $page['internal_route'])
                ->value('id');

            if ($existingId) {
                $newPageId = $existingId; // Re-use; still ensure permissions exist.
            } else {
                $newPageId = DB::table('module_pages')->insertGetId([
                    'page_name'      => $page['page_name'],
                    'Module_Id'      => $moduleId,
                    'internal_route' => $page['internal_route'],
                    'TypeOfPage'     => 'InsideOfMenu',
                    'type'           => 'normal',
                    'status'         => 'Active',
                    'place_order'    => $page['place_order'],
                    'created_by'     => 0,
                    'modified_by'    => 0,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }

            // Mirror resort_pagewise_permissions (resort → page).
            $resortPerms = DB::table('resort_pagewise_permissions')
                ->where('page_permission_id', $sourcePageId)
                ->get(['resort_id', 'Module_id']);
            foreach ($resortPerms as $rp) {
                $exists = DB::table('resort_pagewise_permissions')
                    ->where('resort_id', $rp->resort_id)
                    ->where('page_permission_id', $newPageId)
                    ->exists();
                if (!$exists) {
                    DB::table('resort_pagewise_permissions')->insert([
                        'resort_id'         => $rp->resort_id,
                        'Module_id'         => $rp->Module_id,
                        'page_permission_id'=> $newPageId,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]);
                }
            }

            // Mirror resort_interal_pages_permissions (position → page).
            $positionPerms = DB::table('resort_interal_pages_permissions')
                ->where('page_id', $sourcePageId)
                ->get(['resort_id', 'Dept_id', 'position_id', 'Permission_id']);
            foreach ($positionPerms as $pp) {
                $exists = DB::table('resort_interal_pages_permissions')
                    ->where('resort_id', $pp->resort_id)
                    ->where('position_id', $pp->position_id)
                    ->where('Permission_id', $pp->Permission_id)
                    ->where('page_id', $newPageId)
                    ->exists();
                if (!$exists) {
                    DB::table('resort_interal_pages_permissions')->insert([
                        'resort_id'    => $pp->resort_id,
                        'Dept_id'      => $pp->Dept_id,
                        'position_id'  => $pp->position_id,
                        'Permission_id'=> $pp->Permission_id,
                        'page_id'      => $newPageId,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // No-op — removing menu entries after they have permissions wired
        // would silently revoke access for any role that has interacted
        // with them. Roll back via DB cleanup if absolutely needed.
    }
};
