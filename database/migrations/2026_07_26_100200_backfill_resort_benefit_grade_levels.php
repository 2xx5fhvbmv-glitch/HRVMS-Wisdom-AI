<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillResortBenefitGradeLevels extends Migration
{
    /**
     * The exact grouping BenifitGridController::store()/update() has
     * hardcoded via match($request->emp_grade) up to this point — reused
     * here so existing grids keep resolving to the same rank set they do
     * today.
     */
    private $legacyRankGroups = [
        '1' => [1, 3, 7, 8],
        '2' => [2],
        '4' => [4],
        '5' => [5],
        '6' => [6],
    ];

    private $legacyGradeNames = [
        '8' => 'GM',
        '1' => 'EXCOM',
        '2' => 'HOD',
        '4' => 'MGR',
        '5' => 'SUP',
        '6' => 'LINE WORKERS',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Snapshot every grid row (id + its CURRENT legacy emp_grade) up
        // front, then always repoint by id. New grade-level ids are plain
        // auto-increments and can numerically collide with the legacy key
        // space (1,2,4,5,6,8) — updating via where('emp_grade', $legacyKey)
        // while other not-yet-processed rows still hold that same legacy
        // key risks a later iteration's new id accidentally re-matching an
        // already-repointed row. Targeting by id sidesteps that entirely.
        $gridRows = DB::table('resort_benifit_grid')->get(['id', 'resort_id', 'emp_grade']);

        foreach ($gridRows->groupBy('resort_id') as $resortId => $rows) {
            DB::transaction(function () use ($resortId, $rows) {
                $gradeLevelIdByKey = [];

                foreach ($rows->pluck('emp_grade')->unique() as $empGrade) {
                    if (!isset($this->legacyGradeNames[$empGrade])) {
                        // Unknown/legacy key not in the current config — skip
                        // rather than guess a name for it.
                        continue;
                    }

                    $gradeLevelId = DB::table('resort_benefit_grade_levels')
                        ->where('resort_id', $resortId)
                        ->where('name', $this->legacyGradeNames[$empGrade])
                        ->value('id');

                    if (!$gradeLevelId) {
                        $gradeLevelId = DB::table('resort_benefit_grade_levels')->insertGetId([
                            'resort_id'  => $resortId,
                            'name'       => $this->legacyGradeNames[$empGrade],
                            'status'     => 'active',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    // BenifitGridController's match() has no case for "8"
                    // (or any key besides 1/2/4/5) — it silently falls to
                    // `default => [6]`. Real data confirmed this: grids exist
                    // with emp_grade="8" today. Reproduce that exact
                    // pre-existing behavior here rather than fixing it, so
                    // backfilled grids resolve to the identical rank set
                    // they already resolve to.
                    foreach (($this->legacyRankGroups[$empGrade] ?? [6]) as $rank) {
                        DB::table('resort_benefit_grade_level_ranks')->updateOrInsert(
                            ['resort_id' => $resortId, 'rank' => $rank],
                            ['grade_level_id' => $gradeLevelId, 'updated_at' => now(), 'created_at' => now()]
                        );
                    }

                    $gradeLevelIdByKey[$empGrade] = $gradeLevelId;
                }

                foreach ($rows as $row) {
                    if (!isset($gradeLevelIdByKey[$row->emp_grade])) {
                        continue;
                    }
                    DB::table('resort_benifit_grid')
                        ->where('id', $row->id)
                        ->update(['emp_grade' => (string) $gradeLevelIdByKey[$row->emp_grade]]);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Same by-id principle as up(): snapshot first, repoint by id, never
        // by where('emp_grade', $newId) against a value space that could
        // still collide mid-loop.
        $gridRows = DB::table('resort_benifit_grid')->get(['id', 'resort_id', 'emp_grade']);
        $levels = DB::table('resort_benefit_grade_levels')->get()->keyBy('id');

        foreach ($gridRows as $row) {
            $level = $levels->get((int) $row->emp_grade);
            if (!$level) {
                continue;
            }
            $legacyKey = array_search($level->name, $this->legacyGradeNames);
            if ($legacyKey === false) {
                continue;
            }
            DB::table('resort_benifit_grid')->where('id', $row->id)->update(['emp_grade' => $legacyKey]);
        }

        // Child (referencing) table first, then parent — TRUNCATE is blocked
        // by MySQL/InnoDB when a live FK references the table, even once
        // empty, so use delete() instead.
        DB::table('resort_benefit_grade_level_ranks')->delete();
        DB::table('resort_benefit_grade_levels')->delete();
    }
}
