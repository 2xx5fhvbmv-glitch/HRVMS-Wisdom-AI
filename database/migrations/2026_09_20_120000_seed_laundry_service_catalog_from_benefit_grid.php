<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Same gap as 2026_09_16_223500_seed_housekeeping_service_catalog_from_benefit_grid,
 * but for Laundry: that migration only ever read resort_benifit_grid.housekeeping,
 * so the Housekeeping Request eligibility catalog has never had a single Laundry
 * entry in any resort even though resort_benifit_grid.laundry is its own,
 * independently-configured frequency column HR has always been able to set.
 * One-time bootstrap, same rules as the housekeeping seed (skip blank/"not
 * eligible"/unrecognized values, one catalog row per distinct frequency
 * actually in use per resort, mapped to the grid's grade level).
 */
return new class extends Migration
{
    public function up()
    {
        $frequencyLabels = [
            'once a week'   => 'Laundry - Once a Week',
            'twice a week'  => 'Laundry - Twice a Week',
            'thrice a week' => 'Laundry - Thrice a Week',
            '3 a week'      => 'Laundry - Thrice a Week', // legacy synonym, same service as "thrice a week"
        ];

        $grids = DB::table('resort_benifit_grid')
            ->whereNotNull('laundry')
            ->where('laundry', '!=', '')
            ->where('laundry', '!=', 'not eligible')
            ->get(['resort_id', 'emp_grade', 'laundry']);

        $now = now();

        foreach ($grids as $grid) {
            $normalized = strtolower(trim($grid->laundry));
            $serviceName = $frequencyLabels[$normalized] ?? null;
            if (!$serviceName) {
                continue; // unrecognized free-text value, nothing to safely map
            }

            $gradeLevelId = (int) $grid->emp_grade;
            if ($gradeLevelId <= 0) {
                continue;
            }
            $gradeExists = DB::table('resort_benefit_grade_levels')
                ->where('id', $gradeLevelId)
                ->where('resort_id', $grid->resort_id)
                ->exists();
            if (!$gradeExists) {
                continue;
            }

            $serviceId = DB::table('housekeeping_service_catalog')
                ->where('resort_id', $grid->resort_id)
                ->where('name', $serviceName)
                ->value('id');

            if (!$serviceId) {
                $serviceId = DB::table('housekeeping_service_catalog')->insertGetId([
                    'resort_id'  => $grid->resort_id,
                    'name'       => $serviceName,
                    'status'     => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('benefit_grade_housekeeping_services')->updateOrInsert(
                [
                    'grade_level_id'          => $gradeLevelId,
                    'housekeeping_service_id' => $serviceId,
                ],
                [
                    'resort_id'  => $grid->resort_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down()
    {
        // Data bootstrap only — leaving HR-managed catalog/mapping rows in
        // place on rollback rather than guessing which ones this seeded
        // vs. which HR has since edited through the admin screen.
    }
};
