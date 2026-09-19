<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Position Configuration page (resort.positionconfig.index, registered
 * by 2026_09_19_090100) was granted to zero roles by default — confirmed
 * live: an HR user got 403 until someone manually ticked it on in Roles &
 * Permissions. There's no existing "auto-grant a new page to every resort"
 * mechanism anywhere in this codebase (every module_pages row added by a
 * past migration has the same gap) — resort_interal_pages_permissions
 * rows are keyed by (resort_id, Dept_id, position_id), one row per
 * (page, permission), and are otherwise only ever created by hand through
 * that screen (app/Http/Controllers/Resorts/ResortInternalPermission.php).
 *
 * This grants View/Create/Edit/Delete on the new page to every active
 * position with Rank = 3 (HR — config('settings.Position_Rank')) across
 * every resort, matching who this page is actually for. Idempotent:
 * skips a (resort, dept, position, page, permission) combo that's
 * already there (e.g. already granted by hand).
 */
return new class extends Migration
{
    public function up()
    {
        $pageId = DB::table('module_pages')->where('internal_route', 'resort.positionconfig.index')->value('id');
        if (!$pageId) {
            return;
        }

        $hrPositions = DB::table('resort_positions')
            ->where('status', 'active')
            ->where('Rank', 3)
            ->get(['id', 'resort_id', 'dept_id']);

        $permissionIds = [1, 2, 3, 4]; // view, create, edit, delete — config('settings.resort_permissions')
        $now = now();

        foreach ($hrPositions as $position) {
            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('resort_interal_pages_permissions')
                    ->where('resort_id', $position->resort_id)
                    ->where('Dept_id', $position->dept_id)
                    ->where('position_id', $position->id)
                    ->where('page_id', $pageId)
                    ->where('Permission_id', $permissionId)
                    ->exists();

                if (!$exists) {
                    DB::table('resort_interal_pages_permissions')->insert([
                        'resort_id' => $position->resort_id,
                        'Dept_id' => $position->dept_id,
                        'position_id' => $position->id,
                        'page_id' => $pageId,
                        'Permission_id' => $permissionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down()
    {
        $pageId = DB::table('module_pages')->where('internal_route', 'resort.positionconfig.index')->value('id');
        if (!$pageId) {
            return;
        }

        DB::table('resort_interal_pages_permissions')->where('page_id', $pageId)->delete();
    }
};
