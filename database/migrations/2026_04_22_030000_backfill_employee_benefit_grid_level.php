<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill employees.benefit_grid_level from employees.rank when missing.
 * Required so employee detail pages show the correct Benefit Grid Level
 * (otherwise N/A) for rows imported from the Excel seeder — and for any
 * legacy rows where the level wasn't explicitly set.
 */
class BackfillEmployeeBenefitGridLevel extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('employees')
            || !Schema::hasColumn('employees', 'benefit_grid_level')
            || !Schema::hasColumn('employees', 'rank')) {
            return;
        }

        DB::table('employees')
            ->whereNotNull('rank')
            ->where(function ($q) {
                $q->whereNull('benefit_grid_level')
                  ->orWhere('benefit_grid_level', 0)
                  ->orWhere('benefit_grid_level', '');
            })
            ->update([
                'benefit_grid_level' => DB::raw('`rank`'),
                'updated_at'         => now(),
            ]);
    }

    public function down()
    {
        // Irreversible — original null/0/'' values weren't distinguishable
        // from rows that legitimately had benefit_grid_level = rank.
    }
}
