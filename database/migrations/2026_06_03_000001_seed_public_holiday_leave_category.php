<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add a `Public Holiday` leave_categories row for every resort that already
 * owns leave_categories. The F&F page auto-populates the "Public Holiday"
 * row in the leave breakdown from the count of (Status='Present' on a PH
 * date) — but had no way to subtract credits the employee later used as
 * a comp day. With this row in place, an employee uses their PH comp by
 * applying for a leave under category "Public Holiday" via the existing
 * Leave module flow; F&F then subtracts the approved total from the
 * gross PH worked count.
 *
 * Idempotent: skips any resort that already has a Public Holiday row so
 * re-running won't create duplicates.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('leave_categories')) {
            return;
        }

        $resortIds = DB::table('leave_categories')
            ->select('resort_id')
            ->distinct()
            ->pluck('resort_id')
            ->all();

        $now = now();
        foreach ($resortIds as $resortId) {
            $exists = DB::table('leave_categories')
                ->where('resort_id', $resortId)
                ->where('leave_type', 'Public Holiday')
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('leave_categories')->insert([
                'resort_id'          => $resortId,
                'leave_type'         => 'Public Holiday',
                'number_of_days'     => 0,
                'carry_forward'      => 0,
                'carry_max'          => null,
                'earned_leave'       => 0,
                'earned_max'         => null,
                // All ranks may claim a Public Holiday comp day. PH credits
                // are 1-to-1 with worked PH days, not a grade-based grant.
                'eligibility'        => '8,1,2,3,4,5,6,7',
                'frequency'          => 'Yearly',
                'number_of_times'    => 1,
                'color'              => '#1abc9c',
                // NOT NULL in the schema; existing rows use either an empty
                // string or a CSV of sibling category ids. PH-comp doesn't
                // combine with another category, so empty string is right.
                'leave_category'     => '',
                'combine_with_other' => 0,
                'is_paid'            => 'paid',
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('leave_categories')) {
            return;
        }
        // Only remove rows we seeded — leave any custom 'Public Holiday'
        // category an HR team may have added by hand.
        DB::table('leave_categories')
            ->where('leave_type', 'Public Holiday')
            ->where('eligibility', '8,1,2,3,4,5,6,7')
            ->where('color', '#1abc9c')
            ->delete();
    }
};
