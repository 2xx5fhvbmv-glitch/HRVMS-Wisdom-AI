<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * One-off cleanup for duplicate compliance breaches created before the
 * firstOrCreate dedup fix in ComplianceController::checkCompliance().
 *
 * The old code put `reported_on => Carbon::now()` inside the firstOrCreate
 * match array, so the dedup check never matched and every run (and, for the
 * overtime rule, every approved-OT attendance row within a single run) created
 * a fresh duplicate. This collapses each set of identical breaches down to one
 * row, keeping an AI-enriched copy where available, otherwise the earliest.
 *
 * Duplicates are soft-deleted (deleted_at) rather than removed, so the change
 * is recoverable if needed.
 */
class DedupeComplianceBreaches extends Migration
{
    public function up()
    {
        $hasAiColumn = DB::getSchemaBuilder()->hasColumn('compliances', 'ai_generated_at');

        // Rows that are duplicated on their stable identity. `description` is
        // included so genuinely distinct breaches (e.g. per-vacancy rows that
        // only differ by description) are never merged.
        $groups = DB::table('compliances')
            ->whereNull('deleted_at')
            ->select(
                'resort_id',
                'employee_id',
                'module_name',
                'compliance_breached_name',
                'description',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('resort_id', 'employee_id', 'module_name', 'compliance_breached_name', 'description')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $now = Carbon::now();
        $deleted = 0;

        foreach ($groups as $group) {
            $query = DB::table('compliances')
                ->whereNull('deleted_at')
                ->where('resort_id', $group->resort_id)
                ->where('module_name', $group->module_name)
                ->where('compliance_breached_name', $group->compliance_breached_name);

            // Match NULLs explicitly (= NULL never matches in SQL).
            $group->employee_id === null
                ? $query->whereNull('employee_id')
                : $query->where('employee_id', $group->employee_id);

            $group->description === null
                ? $query->whereNull('description')
                : $query->where('description', $group->description);

            // Keep an AI-enriched copy first, then the earliest row.
            if ($hasAiColumn) {
                $query->orderByRaw('ai_generated_at IS NULL ASC');
            }
            $ids = $query->orderBy('id', 'asc')->pluck('id');

            $idsToDelete = $ids->slice(1)->values(); // keep the first, drop the rest

            if ($idsToDelete->isNotEmpty()) {
                $deleted += DB::table('compliances')
                    ->whereIn('id', $idsToDelete)
                    ->update(['deleted_at' => $now]);
            }
        }

        info("DedupeComplianceBreaches: soft-deleted {$deleted} duplicate compliance rows.");
    }

    public function down()
    {
        // Irreversible: the duplicates removed here were redundant copies and
        // are not restored on rollback.
    }
}
