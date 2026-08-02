<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Was: unique(resort_id, rank) — a rank belonged to exactly one grade
 * level at a time. Now multiple grades can share a rank (e.g. "HOD L1"
 * and "HOD L2" both rank=HOD), with the specific one an employee gets
 * decided per-employee via employees.benefit_grid_level (see
 * Common::resolveEmpGrade()). New unique is (resort_id, rank,
 * grade_level_id) so the same rank can appear against several grades,
 * but can't be duplicated twice against the SAME grade. No data
 * migration needed: every existing row already satisfies both the old
 * and the new constraint.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('resort_benefit_grade_level_ranks', function (Blueprint $table) {
            $table->dropUnique('resort_benefit_grade_level_ranks_resort_id_rank_unique');
            $table->unique(['resort_id', 'rank', 'grade_level_id'], 'resort_benefit_grade_level_ranks_resort_rank_grade_unique');
        });
    }

    public function down()
    {
        Schema::table('resort_benefit_grade_level_ranks', function (Blueprint $table) {
            $table->dropUnique('resort_benefit_grade_level_ranks_resort_rank_grade_unique');
            $table->unique(['resort_id', 'rank'], 'resort_benefit_grade_level_ranks_resort_id_rank_unique');
        });
    }
};
