<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PositionConfigController::store() never set Rank (resort_positions.Rank
 * is NOT NULL, no default) — every Casual/Intern position created through
 * it so far saved with Rank = 0, which matches no rank label anywhere
 * (config('settings.Position_Rank') is 1-12). Anyone hired into one of
 * those positions inherited rank 0 too. Backfills both to 6 (Line
 * Workers) — the store() fix landed in the same pass, this just corrects
 * the rows created before it.
 */
return new class extends Migration
{
    public function up()
    {
        DB::table('resort_positions')
            ->whereNotNull('employee_category')
            ->where('Rank', 0)
            ->update(['Rank' => 6]);

        $affectedPositionIds = DB::table('resort_positions')
            ->whereNotNull('employee_category')
            ->where('Rank', 6)
            ->pluck('id');

        DB::table('employees')
            ->whereIn('Position_id', $affectedPositionIds)
            ->where('rank', 0)
            ->update(['rank' => 6]);
    }

    public function down()
    {
        // Not reversible — 0 vs "always was 6" can't be told apart after
        // this runs, and rolling back to a rank that matched nothing was
        // never a state worth restoring.
    }
};
