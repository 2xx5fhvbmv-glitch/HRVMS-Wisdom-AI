<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * One-off cleanup of Work Permit fee rows created by an OLD buggy schedule that
 * split the flat fee across months (e.g. 350 / 9 = 38.89 per month). The current
 * sync generates the full configured fee per month, so this won't recur.
 *
 * Rule: a Work Permit fee row must equal the resort's configured "Work Permit
 * Fee" (±5%). Any row outside that band is the bad split/garbage data and is
 * removed. Resorts with no configured fee are left untouched (no reference to
 * judge against). Run the read-only inspection query first (see the chat) to
 * confirm what will be removed.
 *
 * IRREVERSIBLE: deleted rows cannot be restored by down(); re-sync the affected
 * employees to regenerate a correct schedule if needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $totalDeleted = 0;

        $resortIds = DB::table('work_permits')->distinct()->pluck('resort_id');

        foreach ($resortIds as $resortId) {
            $fee = DB::table('resort_budget_costs')
                ->where('resort_id', $resortId)
                ->whereIn('particulars', ['Work Permit Fee', 'WORK PERMIT FEE', 'work permit fee'])
                ->where('status', 'active')
                ->orderBy('updated_at', 'desc')
                ->value('amount');

            if ($fee === null) {
                Log::warning("WP cleanup: resort {$resortId} has no Work Permit Fee config — skipped.");
                continue;
            }

            $fee       = (float) $fee;
            $tolerance = max(5.0, $fee * 0.05);   // 5% or 5 MVR, whichever is larger
            $low       = $fee - $tolerance;
            $high      = $fee + $tolerance;

            $query = DB::table('work_permits')
                ->where('resort_id', $resortId)
                ->where(function ($q) use ($low, $high) {
                    $q->where('Amt', '<', $low)->orWhere('Amt', '>', $high);
                });

            $count = (clone $query)->count();
            if ($count > 0) {
                $query->delete();
                $totalDeleted += $count;
                Log::info("WP cleanup: resort {$resortId} fee={$fee} (band {$low}-{$high}) removed {$count} wrong work_permit rows.");
            }
        }

        Log::info("WP cleanup: total wrong work_permit rows removed = {$totalDeleted}.");
    }

    public function down(): void
    {
        // Irreversible cleanup — nothing to restore.
    }
};
