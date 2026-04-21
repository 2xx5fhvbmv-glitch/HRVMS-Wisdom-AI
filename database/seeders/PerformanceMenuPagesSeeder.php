<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the new Performance module menu pages (Employees, KPI List, Create KPI,
 * Bonus Configuration) into module_pages + grants view/create/edit/delete
 * permissions to every position that already has access to the Performance
 * Dashboard page (41) — so the same users who see Dashboard will see the new tabs.
 *
 * Also grants "My Reviews" (120) and "Team Reviews" (121) view permission to
 * all active positions, since these are self-scoped by controller.
 *
 * Idempotent: safe to re-run.
 *
 *   php artisan db:seed --class=PerformanceMenuPagesSeeder
 */
class PerformanceMenuPagesSeeder extends Seeder
{
    public function run()
    {
        $now = now();
        $moduleId = 7; // Performance

        // Permission IDs from config
        $viewPerm   = config('settings.resort_permissions.view');
        $createPerm = config('settings.resort_permissions.create');
        $editPerm   = config('settings.resort_permissions.edit');
        $deletePerm = config('settings.resort_permissions.delete');

        // ---- 1. Ensure module_pages rows exist ----
        // Place orders are relative to existing Performance menu structure.
        $pagesToEnsure = [
            // [internal_route, page_name, place_order, visible_in_menu]
            // Pre-existing pages that may be missing on live:
            ['Performance.Meeting.scheduled',   'Scheduled Meetings',            4,  true],
            ['Performance.Review.mySelf',       'My Reviews',                    8,  true],
            ['Performance.Review.myTeam',       'Team Reviews',                  9,  true],
            ['Performance.pip.index',           'Performance Improvement Plan',  10, true],
            ['Performance.pdp.index',           'Professional Development Plan', 11, true],
            // Pages added during the session:
            ['Performance.employees',           'Employees',                     2,  true],
            ['Performance.kpi.KpiList',         'KPI List',                      3,  true],
            ['Performance.kpi.create',          'Create KPI',                    0,  false], // hidden from menu, permission only
            ['Performance.bonusConfig',         'Bonus Configuration',           12, true],
            // Name-only fix for existing row (typo correction):
            ['Performance.MonltyCheckIn',       'Monthly Check In',              7,  true],
        ];

        $pageIdMap = []; // internal_route => id
        foreach ($pagesToEnsure as [$route, $name, $order, $inMenu]) {
            $existing = DB::table('module_pages')->where('internal_route', $route)->first();
            if ($existing) {
                $pageIdMap[$route] = $existing->id;
                // Update page_name if it differs (fixes typos like "Monlty" -> "Monthly")
                if ($existing->page_name !== $name) {
                    DB::table('module_pages')->where('id', $existing->id)->update([
                        'page_name'  => $name,
                        'updated_at' => $now,
                    ]);
                    $this->command->info("Updated page name for {$route}: '{$existing->page_name}' -> '{$name}'");
                }
                continue;
            }

            // If new menu-visible entry overlaps an existing place_order, bump later entries down
            if ($inMenu && $order > 0) {
                DB::table('module_pages')
                    ->where('Module_Id', $moduleId)
                    ->where('TypeOfPage', 'InsideOfMenu')
                    ->where('place_order', '>=', $order)
                    ->increment('place_order');
            }

            $id = DB::table('module_pages')->insertGetId([
                'page_name'      => $name,
                'Module_Id'      => $moduleId,
                'internal_route' => $route,
                'TypeOfPage'     => $inMenu ? 'InsideOfMenu' : 'InsideOfMenu',
                'type'           => 'normal',
                'status'         => 'Active',
                'place_order'    => $order,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
            $pageIdMap[$route] = $id;
            $this->command->info("Inserted module_pages row '{$name}' (id={$id})");
        }

        // ---- 2. Clone permissions from Dashboard (page 41) to each new page ----
        $dashboardPageId = DB::table('module_pages')->where('internal_route', 'Performance.Hrdashboard')->value('id');
        if (!$dashboardPageId) {
            $this->command->warn('Performance Dashboard page not found — skipping permission clone.');
            return;
        }

        $sourcePagewise = DB::table('resort_pagewise_permissions')
            ->where('Module_id', $moduleId)
            ->where('page_permission_id', $dashboardPageId)
            ->get();

        $sourceInternal = DB::table('resort_interal_pages_permissions')
            ->where('page_id', $dashboardPageId)
            ->get();

        $cloneCount1 = 0; $cloneCount2 = 0;

        foreach ($pageIdMap as $route => $newPageId) {
            // resort_pagewise_permissions (per resort)
            foreach ($sourcePagewise as $src) {
                $exists = DB::table('resort_pagewise_permissions')
                    ->where('resort_id', $src->resort_id)
                    ->where('Module_id', $moduleId)
                    ->where('page_permission_id', $newPageId)
                    ->exists();
                if (!$exists) {
                    DB::table('resort_pagewise_permissions')->insert([
                        'resort_id'          => $src->resort_id,
                        'Module_id'          => $moduleId,
                        'page_permission_id' => $newPageId,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ]);
                    $cloneCount1++;
                }
            }

            // resort_interal_pages_permissions (per position/permission)
            foreach ($sourceInternal as $src) {
                $exists = DB::table('resort_interal_pages_permissions')
                    ->where('resort_id', $src->resort_id)
                    ->where('page_id', $newPageId)
                    ->where('position_id', $src->position_id)
                    ->where('Permission_id', $src->Permission_id)
                    ->exists();
                if (!$exists) {
                    DB::table('resort_interal_pages_permissions')->insert([
                        'resort_id'     => $src->resort_id,
                        'Dept_id'       => $src->Dept_id,
                        'position_id'   => $src->position_id,
                        'Permission_id' => $src->Permission_id,
                        'page_id'       => $newPageId,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                    $cloneCount2++;
                }
            }
        }
        $this->command->info("Cloned from Dashboard — pagewise={$cloneCount1}, internal={$cloneCount2}");

        // ---- 3. Ensure My Reviews (120) + Team Reviews (121) granted to ALL positions (self-scoped pages) ----
        $reviewPages = [120, 121];
        $positions = DB::table('resort_positions')->where('status', 'active')->get(['id', 'resort_id', 'Dept_id']);
        $reviewInsertCount = 0;

        foreach ($reviewPages as $pageId) {
            $exists = DB::table('module_pages')->where('id', $pageId)->exists();
            if (!$exists) continue;

            // Pagewise per resort
            foreach ($positions->pluck('resort_id')->unique() as $rid) {
                if (!DB::table('resort_pagewise_permissions')->where('resort_id', $rid)->where('Module_id', $moduleId)->where('page_permission_id', $pageId)->exists()) {
                    DB::table('resort_pagewise_permissions')->insert([
                        'resort_id'          => $rid,
                        'Module_id'          => $moduleId,
                        'page_permission_id' => $pageId,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ]);
                }
            }

            foreach ($positions as $pos) {
                if (!DB::table('resort_interal_pages_permissions')->where('resort_id', $pos->resort_id)->where('page_id', $pageId)->where('position_id', $pos->id)->where('Permission_id', $viewPerm)->exists()) {
                    DB::table('resort_interal_pages_permissions')->insert([
                        'resort_id'     => $pos->resort_id,
                        'Dept_id'       => $pos->Dept_id,
                        'position_id'   => $pos->id,
                        'Permission_id' => $viewPerm,
                        'page_id'       => $pageId,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                    $reviewInsertCount++;
                }
            }
        }
        $this->command->info("My Reviews + Team Reviews view permission granted to {$reviewInsertCount} position rows.");

        $this->command->info('PerformanceMenuPagesSeeder complete.');
    }
}
