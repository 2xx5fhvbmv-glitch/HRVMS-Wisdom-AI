<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nulls out broken date_discussion rows stored as 1970-01-01 (Unix epoch 0).
 * These were created when the form submitted dd/mm/yyyy but the old code used
 * strtotime() which misinterpreted the slash format and fell back to epoch 0.
 * Save logic is fixed; this cleans up the bad stored data.
 */
class CleanupBadMonthlyCheckinDates extends Migration
{
    public function up()
    {
        if (Schema::hasTable('monthly_checking_models')
            && Schema::hasColumn('monthly_checking_models', 'date_discussion')) {
            DB::table('monthly_checking_models')
                ->where('date_discussion', '1970-01-01')
                ->update(['date_discussion' => null]);
        }
    }

    public function down()
    {
        // Irreversible — the original dates were never valid to begin with.
    }
}
