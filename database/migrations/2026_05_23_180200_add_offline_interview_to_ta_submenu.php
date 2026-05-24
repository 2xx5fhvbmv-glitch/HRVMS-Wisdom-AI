<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Registers the "Offline Interview" submenu under Talent Acquisition
 * (Module_Id = 3) so HR can reach it from the TA dropdown. Mirrors the
 * permission rows from the existing Vacancies submenu so every resort /
 * position that can currently use Vacancies automatically gets access.
 *
 * Idempotent — skips if the page or permission row already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();

        // We use the Vacancies page as the "source of permissions" — anyone
        // who can manage vacancies should be able to run offline interviews.
        $sourcePage = DB::table('module_pages')
            ->where('internal_route', 'resort.vacancies.FreshApplicant')
            ->first();

        if (!$sourcePage) {
            return; // No reference page — abort cleanly.
        }

        $route = 'offline-interview.index';
        $newPageId = DB::table('module_pages')
            ->where('internal_route', $route)
            ->value('id');

        if (!$newPageId) {
            $newPageId = DB::table('module_pages')->insertGetId([
                'page_name'      => 'Offline Interview',
                'Module_Id'      => $sourcePage->Module_Id,
                'internal_route' => $route,
                'TypeOfPage'     => 'InsideOfMenu',
                'type'           => 'normal',
                'status'         => 'Active',
                'place_order'    => 4,
                'created_by'     => 0,
                'modified_by'    => 0,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        // Mirror resort_pagewise_permissions (resort → page).
        $resortPerms = DB::table('resort_pagewise_permissions')
            ->where('page_permission_id', $sourcePage->id)
            ->get(['resort_id', 'Module_id']);
        foreach ($resortPerms as $rp) {
            $exists = DB::table('resort_pagewise_permissions')
                ->where('resort_id', $rp->resort_id)
                ->where('page_permission_id', $newPageId)
                ->exists();
            if (!$exists) {
                DB::table('resort_pagewise_permissions')->insert([
                    'resort_id'          => $rp->resort_id,
                    'Module_id'          => $rp->Module_id,
                    'page_permission_id' => $newPageId,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            }
        }

        // Mirror resort_interal_pages_permissions (position/dept → page).
        $positionPerms = DB::table('resort_interal_pages_permissions')
            ->where('page_id', $sourcePage->id)
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

    public function down(): void
    {
        // Leave seeded data — revoking would lock users out of an active page.
    }
};
