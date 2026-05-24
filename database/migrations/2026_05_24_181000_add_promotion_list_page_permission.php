<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add a "Promotion List" sub-menu under People (Module_Id=4) and clone its
 * pagewise / per-permission rows from the existing "Promotions" dashboard
 * page so every resort + every position that already sees Promotions
 * automatically sees the Promotion List page too.
 *
 * Specifically requested for HOD / EXCOM / GM who only need the list view —
 * they keep getting view-level access via the mirror, and HR/admin retains
 * the full set.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Locate the existing "Promotions" dashboard page row (Module_Id=4
        // People). Bail if missing — nothing to mirror from.
        $parent = DB::table('module_pages')
            ->where('Module_Id', 4)
            ->where('internal_route', 'people.promotion.dashboard')
            ->first();
        if (!$parent) return;

        // Insert the new Promotion List page if not already present.
        $existing = DB::table('module_pages')
            ->where('Module_Id', 4)
            ->where('internal_route', 'people.promotion.list')
            ->first();

        if ($existing) {
            $newPageId = $existing->id;
        } else {
            $newPageId = DB::table('module_pages')->insertGetId([
                'page_name'      => 'Promotion List',
                'status'         => 'Active',
                'created_by'     => $parent->created_by,
                'modified_by'    => $parent->modified_by,
                'created_at'     => now(),
                'updated_at'     => now(),
                'Module_Id'      => $parent->Module_Id,
                'internal_route' => 'people.promotion.list',
                'TypeOfPage'     => $parent->TypeOfPage ?? 'InsideOfMenu',
                'type'           => $parent->type ?? 'normal',
                'place_order'    => ($parent->place_order ?? 4) + 1,
            ]);
        }

        // Clone per-resort pagewise permissions (resort_pagewise_permissions).
        $pagewise = DB::table('resort_pagewise_permissions')
            ->where('page_permission_id', $parent->id)
            ->get();
        foreach ($pagewise as $row) {
            $exists = DB::table('resort_pagewise_permissions')
                ->where('resort_id', $row->resort_id)
                ->where('Module_id', $row->Module_id)
                ->where('page_permission_id', $newPageId)
                ->exists();
            if (!$exists) {
                DB::table('resort_pagewise_permissions')->insert([
                    'resort_id'          => $row->resort_id,
                    'Module_id'          => $row->Module_id,
                    'page_permission_id' => $newPageId,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }
        }

        // Clone per-position permissions (resort_interal_pages_permissions).
        $internal = DB::table('resort_interal_pages_permissions')
            ->where('page_id', $parent->id)
            ->get();
        foreach ($internal as $row) {
            $exists = DB::table('resort_interal_pages_permissions')
                ->where('resort_id', $row->resort_id)
                ->where('Dept_id', $row->Dept_id)
                ->where('position_id', $row->position_id)
                ->where('page_id', $newPageId)
                ->exists();
            if (!$exists) {
                DB::table('resort_interal_pages_permissions')->insert([
                    'resort_id'     => $row->resort_id,
                    'Dept_id'       => $row->Dept_id,
                    'position_id'   => $row->position_id,
                    'Permission_id' => $row->Permission_id,
                    'page_id'       => $newPageId,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $newPage = DB::table('module_pages')
            ->where('Module_Id', 4)
            ->where('internal_route', 'people.promotion.list')
            ->first();
        if (!$newPage) return;

        DB::table('resort_pagewise_permissions')->where('page_permission_id', $newPage->id)->delete();
        DB::table('resort_interal_pages_permissions')->where('page_id', $newPage->id)->delete();
        DB::table('module_pages')->where('id', $newPage->id)->delete();
    }
};
