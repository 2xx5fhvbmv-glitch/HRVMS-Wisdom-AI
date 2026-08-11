<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * resort.report.survey.index (SurveyReportController — response-rate/rating
 * analytics + AI insights, route report/survey) is a real, fully-built page
 * with zero module_pages entry — same class of gap as Master Import/Email
 * Config (2026_08_04_000100 / 2026_08_07_000100): no row means it's
 * unreachable via any sidebar, in any module, for anyone.
 *
 * Registers it under the Survey module (id 11, alongside Dashboard/Surveys)
 * and backfills resort_pagewise_permissions for every resort that already
 * has the Survey module enabled — same two-step pattern as
 * 2026_08_10_120000's Master Import/Email Config backfill.
 *
 * Scoped to Survey only. 19 sibling report pages (Payroll, Visa, Workforce
 * Planning, etc.) have the identical gap but are explicitly out of scope
 * for this migration — a separate, larger registration pass.
 */
return new class extends Migration
{
    public function up()
    {
        $surveyModuleId = DB::table('modules')->where('module_name', 'Survey')->value('id');
        if (!$surveyModuleId) {
            return;
        }

        $pageId = DB::table('module_pages')->where('internal_route', 'resort.report.survey.index')->value('id');

        if (!$pageId) {
            $pageId = DB::table('module_pages')->insertGetId([
                'page_name'    => 'Survey Insights',
                'Module_Id'    => $surveyModuleId,
                'internal_route' => 'resort.report.survey.index',
                'TypeOfPage'   => 'InsideOfMenu',
                'type'         => 'normal',
                'place_order'  => 3,
                'status'       => 'Active',
                'created_by'   => 1,
                'modified_by'  => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $resortIds = DB::table('module_pages')
            ->join('resort_pagewise_permissions', 'resort_pagewise_permissions.page_permission_id', '=', 'module_pages.id')
            ->where('module_pages.Module_Id', $surveyModuleId)
            ->distinct()
            ->pluck('resort_pagewise_permissions.resort_id');

        $alreadyHave = DB::table('resort_pagewise_permissions')
            ->where('Module_id', $surveyModuleId)
            ->where('page_permission_id', $pageId)
            ->pluck('resort_id')
            ->all();

        $missing = $resortIds->diff($alreadyHave);
        if ($missing->isNotEmpty()) {
            $now = now();
            DB::table('resort_pagewise_permissions')->insert(
                $missing->map(fn ($resortId) => [
                    'resort_id'          => $resortId,
                    'Module_id'          => $surveyModuleId,
                    'page_permission_id' => $pageId,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ])->all()
            );
        }
    }

    public function down()
    {
        $pageId = DB::table('module_pages')->where('internal_route', 'resort.report.survey.index')->value('id');
        if ($pageId) {
            DB::table('resort_pagewise_permissions')->where('page_permission_id', $pageId)->delete();
            DB::table('module_pages')->where('id', $pageId)->delete();
        }
    }
};
