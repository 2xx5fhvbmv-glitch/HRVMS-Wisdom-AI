<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * WP1 (D1) — withdraws the Rank=6 decision from
 * 2026_09_19_120000_backfill_casual_intern_position_rank. Casual/Intern
 * must never carry a Permanent rank at all: Rank 6 = "Line Workers", a
 * real Permanent rank that resolveEmpGrade()/getBenefitGrid() can (and,
 * confirmed live, did) match to a genuine benefit grid — giving Casual/
 * Intern staff Permanent entitlements (service charge, meals,
 * accommodation, leave, OT rules). 0 is the sentinel value now: it matches
 * no config('settings.Position_Rank') key and resolveEmpGrade() short-
 * circuits on it, so Casual/Intern reliably never reach a grid.
 *
 * Deliberately a new migration rather than editing 2026_09_19_120000's
 * body — that file may already be recorded as run elsewhere; this one
 * re-asserts the correct end state regardless of whether the old one ran
 * with 6 or (on a fresh install using this repo's current code) never
 * wrote 6 in the first place.
 */
return new class extends Migration
{
    public function up()
    {
        DB::table('resort_positions')
            ->whereNotNull('employee_category')
            ->where('Rank', '!=', 0)
            ->update(['Rank' => 0]);

        DB::table('employees')
            ->whereIn('employment_type', ['Casual', 'Internship'])
            ->where(function ($q) {
                $q->where('rank', '!=', 0)->orWhereNull('rank');
            })
            ->update(['rank' => 0]);

        DB::table('employees')
            ->whereIn('employment_type', ['Casual', 'Internship'])
            ->where(function ($q) {
                $q->where('main_rank', '!=', 0)->orWhereNull('main_rank');
            })
            ->update(['main_rank' => 0]);
    }

    public function down()
    {
        // Not reversible — matches 2026_09_19_120000's own reasoning:
        // whatever rank a row had before either backfill ran is gone
        // either way, and "restore to 6" is exactly the state this
        // migration exists to undo.
    }
};
