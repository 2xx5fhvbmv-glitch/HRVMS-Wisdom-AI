<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cleans up the fallout from 2026_04_22_030000_backfill_employee_benefit_grid_level,
 * which wrote raw employees.rank straight into employees.benefit_grid_level
 * for any employee who had it empty, before resort_benefit_grade_levels
 * existed. Harmless as long as a rank's grade mapping was never customized
 * — but Common::resolveEmpGrade() always trusts an employee's own value
 * first, so once a resort remaps a rank to a different grade (Benefit Grade
 * Levels > Map Ranks), any employee still carrying that raw-rank value
 * keeps resolving to the OLD grade silently.
 *
 * Confirmed at resort 26 across 3 ranks (29 employees, real job titles
 * checked by hand — none coincidental):
 *   - rank 6: Waitress/Accounting Clerk/Commis/HR Coordinator/Carpenter/
 *     Electrician/Security Officer stuck on "GM" instead of the resort's
 *     "LINE WORKERS" remap — this broke OT eligibility (no GM gets OT).
 *   - rank 1: Director Of Finance/Executive Chef/Director Of HR/Chief
 *     Engineer stuck on "LINE WORKERS" instead of "EXCOM".
 *   - rank 2: F&B/HR/Finance/L&D/Security Managers stuck on "SUP" instead
 *     of "HOD Level 1".
 * Scoped to resort 26 only — the same raw-value coincidence also flags 276
 * employees at resort 28, but resort 28's actual grade-level ids don't
 * numerically overlap with its rank numbers at all, so those values are
 * already inert (Common::resolveEmpGrade()'s existing $stillValid check
 * already ignores them; nulling them would be cosmetic only, not a live
 * fix) — left alone here, flagged separately.
 *
 * Common::resolveEmpGrade() has a live guard now (this same commit's
 * companion code change) that stops trusting a value like this going
 * forward, so this migration only needs to clean up already-poisoned rows
 * so the Employee screens stop *displaying* a stale grade too.
 */
class FixPoisonedBenefitGridLevelForRank6Employees extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('employees')
            || !Schema::hasColumn('employees', 'benefit_grid_level')
            || !Schema::hasColumn('employees', 'rank')
            || !Schema::hasTable('resort_benefit_grade_level_ranks')) {
            return;
        }

        // Part 1 (resort 26 only — see class doc comment): clear any
        // employee's benefit_grid_level that still exactly equals their
        // own raw rank number AND that rank now has a DIFFERENT active
        // grade mapping — the precise, narrow signature of the
        // pre-refactor backfill artifact. Never touches a genuinely
        // different, deliberately-chosen override.
        DB::table('employees as e')
            ->join('resort_benefit_grade_level_ranks as r', function ($join) {
                $join->on('r.resort_id', '=', 'e.resort_id')
                     ->on('r.rank', '=', DB::raw('CAST(e.rank AS UNSIGNED)'))
                     ->whereColumn('r.grade_level_id', '!=', 'e.benefit_grid_level');
            })
            ->whereRaw('e.benefit_grid_level = CAST(e.rank AS UNSIGNED)')
            ->where('e.resort_id', 26)
            ->update(['e.benefit_grid_level' => null, 'e.updated_at' => now()]);

        // Part 2 (resort 26, rank 6 specifically, confirmed by hand): rank
        // 6 was seeded pointing at the GM grade level; none of resort 26's
        // rank-6 employees are GM. The resort admin remapped rank 6 to
        // LINE WORKERS today instead, but the stale seed row was never
        // removed (assignRanksToGrade() only ever clears its OWN grade
        // level's rows, not a rank's mapping under a different grade level
        // — intentional, since grades can legitimately share a rank).
        // Removing this specific stale row so the rank-based fallback
        // (Common::getEmpGrade()) is unambiguous for resort 26 again. Ranks
        // 1 and 2 each only ever had a single mapping row (no duplicate),
        // so no equivalent cleanup is needed for them.
        DB::table('resort_benefit_grade_level_ranks')
            ->where('resort_id', 26)
            ->where('rank', 6)
            ->where('grade_level_id', 6)
            ->delete();
    }

    /**
     * Irreversible — original benefit_grid_level values weren't
     * distinguishable from rows that legitimately had it equal their rank,
     * and the deleted mapping row's exact prior state isn't recoverable.
     */
    public function down()
    {
        //
    }
}
