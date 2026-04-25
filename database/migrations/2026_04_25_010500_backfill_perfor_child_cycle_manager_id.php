<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill performa_child_cycles.Manager_id for rows where it's NULL but we can
 * resolve a manager from the participant's employees.reporting_to value.
 *
 * Why this matters:
 *  - /resort/performance/team-reviews queries `where('Manager_id', $emp->id)`,
 *    so any cycle row with NULL Manager_id was invisible to the rightful manager.
 *  - GM-review rows (is_gm_review=1) intentionally leave Manager_id NULL —
 *    those are skipped here.
 *
 * Idempotent: skips rows that already have a Manager_id set.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('performa_child_cycles') || !Schema::hasColumn('performa_child_cycles', 'Manager_id')) {
            return;
        }

        $children = DB::table('performa_child_cycles')
            ->whereNull('Manager_id')
            ->where(function ($q) {
                $q->where('is_gm_review', 0)->orWhereNull('is_gm_review');
            })
            ->get(['id', 'Emp_main_id']);

        foreach ($children as $child) {
            $empId = $this->resolveEmpMainIdToNumeric($child->Emp_main_id);
            if (!$empId) continue;

            $reportingTo = DB::table('employees')->where('id', $empId)->value('reporting_to');
            if ($reportingTo) {
                DB::table('performa_child_cycles')
                    ->where('id', $child->id)
                    ->update(['Manager_id' => $reportingTo]);
            }
        }
    }

    public function down(): void
    {
        // No-op — backfilled values are real data.
    }

    /**
     * Same resolver used by Common::resolveEmpMainIdToNumeric (numeric / base64 /
     * Emp_id string), inlined here so the migration has no app-helper dependency.
     */
    private function resolveEmpMainIdToNumeric($value)
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (int) $value;

        $decoded = base64_decode($value, true);
        if ($decoded !== false && is_numeric($decoded)) {
            return (int) $decoded;
        }

        $row = DB::table('employees')->where('Emp_id', $value)->first(['id']);
        return $row ? (int) $row->id : null;
    }
};
