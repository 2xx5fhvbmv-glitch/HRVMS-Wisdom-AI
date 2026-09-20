<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * WP8 — "Casuals — Payment Model" (people.casualPaymentModel.index) and
 * "Casual Payroll" (resort.casualPayroll.index) were registered as
 * module_pages this session with no grant migration at all — every
 * resort's HR got 403 on both.
 *
 * Originally written to target Rank=3 (HR) positions only, same as
 * 2026_09_19_120100_grant_position_config_to_hr_rank — but live data shows
 * zero active positions with Rank=3 (or Rank=7 for Finance) on this
 * install; resorts here identify HR by department, not by a dedicated
 * rank. Rewritten to match 2026_05_17_100200_grant_transfer_page_to_
 * finance_gm_hr's proven pattern instead: Rank=3 anywhere (covers
 * installs that DO use it) PLUS HOD/EXCOM (Rank 2/1) of the department
 * named "HR"/"Human Resources" (covers this one). Also inserts the
 * resort_pagewise_permissions row Common::CheckResortPermissions() joins
 * against (the bug 2026_09_20_140000 fixes for the Position Config page).
 */
return new class extends Migration
{
    private const ROUTES = [
        'people.casualPaymentModel.index',
        'resort.casualPayroll.index',
    ];

    private const HR_ALIASES = ['hr', 'human resources', 'human resource'];

    public function up()
    {
        $permissionIds = [1, 2, 3, 4]; // view, create, edit, delete
        $now = now();
        $resortIds = DB::table('resorts')->pluck('id');

        foreach (self::ROUTES as $route) {
            $page = DB::table('module_pages')->where('internal_route', $route)->first();
            if (!$page) {
                continue;
            }

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

                $positions = DB::table('resort_positions')
                    ->where('resort_id', $resortId)
                    ->where('status', 'active')
                    ->where(function ($q) use ($hrDeptIds) {
                        $q->where('Rank', 3);
                        if (!empty($hrDeptIds)) {
                            $q->orWhere(function ($q2) use ($hrDeptIds) {
                                $q2->whereIn('Rank', [1, 2])->whereIn('dept_id', $hrDeptIds);
                            });
                        }
                    })
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
    }

    public function down()
    {
        // No-op — see 2026_05_17_100200's rationale: admins may have since
        // relied on this via the Page Permission screen.
    }
};
