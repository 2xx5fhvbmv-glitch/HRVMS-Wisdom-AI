<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * housekeeping_service_catalog and benefit_grade_housekeeping_services
 * (the Card 4 Housekeeping Request eligibility tables) have been empty in
 * every resort since they were created — nothing ever wrote to them. HR
 * has always configured housekeeping eligibility on the OLD free-text
 * resort_benifit_grid.housekeeping field instead ("once a week", "twice a
 * week", "thrice a week"/legacy "3 a week", or "not eligible"/blank = no
 * eligibility), which the mobile Housekeeping Request screen never reads.
 * That's why every grade showed "No housekeeping service eligible" even
 * where the Benefit Grid clearly had one configured.
 *
 * One-time bootstrap: for every resort_benifit_grid row with a real
 * frequency value, ensure a matching catalog service exists for that
 * resort (one per distinct frequency actually in use, not all three
 * unconditionally) and map it to the grid's grade level
 * (resort_benifit_grid.emp_grade, already the resort_benefit_grade_levels
 * id as a string — see BenifitGridController::store()/update()). HR can
 * rename/add/remove services and remap grades afterward via the new
 * Benefit Grade Levels admin screen; this only seeds a starting point so
 * today's already-configured eligibility isn't silently dropped.
 */
return new class extends Migration
{
    public function up()
    {
        $frequencyLabels = [
            'once a week'   => 'Housekeeping - Once a Week',
            'twice a week'  => 'Housekeeping - Twice a Week',
            'thrice a week' => 'Housekeeping - Thrice a Week',
            '3 a week'      => 'Housekeeping - Thrice a Week', // legacy synonym, same service as "thrice a week"
        ];

        $grids = DB::table('resort_benifit_grid')
            ->whereNotNull('housekeeping')
            ->where('housekeeping', '!=', '')
            ->where('housekeeping', '!=', 'not eligible')
            ->get(['resort_id', 'emp_grade', 'housekeeping']);

        $now = now();

        foreach ($grids as $grid) {
            $normalized = strtolower(trim($grid->housekeeping));
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
        // vs. which HR has since edited through the new admin screen.
    }
};
