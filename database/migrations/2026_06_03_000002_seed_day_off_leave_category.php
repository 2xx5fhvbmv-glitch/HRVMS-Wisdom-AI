<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add a `Day Off` leave_categories row for every resort that already owns
 * leave_categories.
 *
 * Policy (per HR): every employee accrues 1 Day Off per week of service
 * within the current employment-year cycle. Cycle starts at joining_date
 * and resets on each employment anniversary. Unused day-offs carry forward
 * inside a cycle and reset when the cycle rolls over. At settlement, the
 * F&F page surfaces the un-taken balance × daily rate as encashable.
 *
 * This row gives the Leave module a category the employee can apply for
 * when they want to consume a day-off credit. F&F then subtracts those
 * approved rows + any scheduled Status='DayOff' attendance rows from the
 * weekly accrual to land at the net owed.
 *
 * Idempotent: skips any resort that already has a Day Off row.
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
                ->where('leave_type', 'Day Off')
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('leave_categories')->insert([
                'resort_id'          => $resortId,
                'leave_type'         => 'Day Off',
                // number_of_days is informational only here; the real
                // entitlement is computed dynamically from elapsed weeks
                // in the current employment-year cycle (~52 per year).
                'number_of_days'     => 52,
                // Carry-forward inside a cycle is the whole point of the
                // weekly accrual; the cycle-reset happens at settlement
                // boundary, not via this flag.
                'carry_forward'      => 1,
                'carry_max'          => null,
                'earned_leave'       => 0,
                'earned_max'         => null,
                // All ranks earn a weekly day off.
                'eligibility'        => '8,1,2,3,4,5,6,7',
                'frequency'          => 'Weekly',
                'number_of_times'    => 1,
                'color'              => '#f1c40f',
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
        // Only remove the seeded rows; leave HR-authored Day Off
        // categories alone.
        DB::table('leave_categories')
            ->where('leave_type', 'Day Off')
            ->where('eligibility', '8,1,2,3,4,5,6,7')
            ->where('color', '#f1c40f')
            ->delete();
    }
};
