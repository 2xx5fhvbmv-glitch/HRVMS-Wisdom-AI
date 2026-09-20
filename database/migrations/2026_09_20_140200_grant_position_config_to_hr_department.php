<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * WP8 — 2026_09_19_120100 granted Position Configuration to every Rank=3
 * (HR) position, but live data on this install has zero active positions
 * with Rank=3 — HR here is identified by department, not a dedicated rank
 * (same discovery made for the two Casual pages — see
 * 2026_09_20_140100_grant_casual_payment_and_payroll_pages_to_hr, whose
 * HOD/EXCOM-of-HR-department fallback this mirrors). Does not re-touch
 * 2026_09_19_120100 (already run/committed) — only adds the missing
 * HOD/EXCOM (Rank 2/1) of the department named "HR"/"Human Resources"
 * grant, plus the resort_pagewise_permissions row.
 */
return new class extends Migration
{
    private const ROUTE = 'resort.positionconfig.index';
    private const HR_ALIASES = ['hr', 'human resources', 'human resource'];

    public function up()
    {
        $page = DB::table('module_pages')->where('internal_route', self::ROUTE)->first();
        if (!$page) {
            return;
        }

        $permissionIds = [1, 2, 3, 4]; // view, create, edit, delete
        $now = now();
        $resortIds = DB::table('resorts')->pluck('id');

        foreach ($resortIds as $resortId) {
            $hrDeptIds = DB::table('resort_departments')
                ->where('resort_id', $resortId)
                ->where(function ($q) {
                    foreach (['name', 'short_name', 'code'] as $col) {
                        $q->orWhereIn(DB::raw('LOWER(TRIM(' . $col . '))'), self::HR_ALIASES);
                    }
                })
                ->pluck('id')
                ->all();

            if (empty($hrDeptIds)) {
                continue;
            }

            $positions = DB::table('resort_positions')
                ->where('resort_id', $resortId)
                ->where('status', 'active')
                ->whereIn('Rank', [1, 2])
                ->whereIn('dept_id', $hrDeptIds)
                ->get(['id', 'dept_id']);

            if ($positions->isEmpty()) {
                continue;
            }

            foreach ($positions as $position) {
                foreach ($permissionIds as $permissionId) {
                    $exists = DB::table('resort_interal_pages_permissions')
                        ->where('resort_id', $resortId)
                        ->where('Dept_id', $position->dept_id)
                        ->where('position_id', $position->id)
                        ->where('page_id', $page->id)
                        ->where('Permission_id', $permissionId)
                        ->exists();

                    if (!$exists) {
                        DB::table('resort_interal_pages_permissions')->insert([
                            'resort_id' => $resortId,
                            'Dept_id' => $position->dept_id,
                            'position_id' => $position->id,
                            'page_id' => $page->id,
                            'Permission_id' => $permissionId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }

            $pwExists = DB::table('resort_pagewise_permissions')
                ->where('resort_id', $resortId)
                ->where('Module_id', $page->Module_Id)
                ->where('page_permission_id', $page->id)
                ->exists();

            if (!$pwExists) {
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
        // No-op — see 2026_05_17_100200's rationale.
    }
};
