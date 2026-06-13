<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-off cleanup for stale per-month salaries saved in the manning plan
 * (store_manning_response_children.Months).
 *
 * The manning/budget view renders each month's salary from the saved `Months`
 * JSON ([{"month":N,"salary":X}, ...]). Some rows carry a stale placeholder
 * (e.g. a flat $180) that no longer matches the employee's real salary — which
 * is why the manning-plan total ($93,240) diverges from the salary-based
 * compare-budget total ($93,000). This refreshes ONLY those stale rows to the
 * employee's current basic_salary.
 *
 * SAFETY:
 *   - Skips any row with a planned increment (Proposed_Basic_salary > 0) so
 *     intentional per-month plans are never overwritten.
 *   - Skips rows already in sync (idempotent — safe to re-run).
 *   - Uses the query builder (not the Eloquent model) to avoid the model's
 *     auth-dependent saving() hook, which has no user in CLI/migration context.
 *   - Logs every change. IRREVERSIBLE — back up store_manning_response_children
 *     and run on staging first.
 */
class RefreshStaleManningMonthlySalaries extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('store_manning_response_children')) {
            return;
        }

        $updated = 0;
        $scanned = 0;

        DB::table('store_manning_response_children')->orderBy('id')->chunkById(200, function ($rows) use (&$updated, &$scanned) {
            foreach ($rows as $row) {
                $scanned++;

                // No planned increment → every month should equal the current
                // salary; any deviation is a stale placeholder. Rows WITH a
                // proposed increment are intentional plans — leave them alone.
                if ((float) ($row->Proposed_Basic_salary ?? 0) > 0) {
                    continue;
                }

                // The employee's CURRENT basic salary is the source of truth.
                $current = DB::table('employees')->where('id', $row->Emp_id)->value('basic_salary');
                if ($current === null) {
                    continue; // no linked employee — don't guess
                }
                $current = (float) $current;

                $months = json_decode((string) $row->Months, true);
                if (!is_array($months) || empty($months)) {
                    continue;
                }

                // Does any saved month differ from the current salary?
                $needsFix = false;
                foreach ($months as $m) {
                    if (!is_array($m) || !array_key_exists('salary', $m)) {
                        continue;
                    }
                    if ((float) $m['salary'] !== $current) {
                        $needsFix = true;
                        break;
                    }
                }
                if (!$needsFix) {
                    continue; // already in sync
                }

                // Rewrite each month's salary to the current salary, preserving
                // any other keys (month, headcount, etc.).
                foreach ($months as &$m) {
                    if (is_array($m) && array_key_exists('salary', $m)) {
                        $m['salary'] = $current;
                    }
                }
                unset($m);

                DB::table('store_manning_response_children')
                    ->where('id', $row->id)
                    ->update([
                        'Months'              => json_encode($months),
                        'Current_Basic_salary' => $current,
                    ]);

                $updated++;
                \Log::info("RefreshStaleManningMonthlySalaries: child #{$row->id} (Emp_id {$row->Emp_id}) months refreshed to {$current}.");
            }
        });

        \Log::info("RefreshStaleManningMonthlySalaries: scanned {$scanned}, refreshed {$updated} stale manning rows.");
    }

    public function down()
    {
        // Irreversible: stale placeholder salaries are not restored on rollback.
    }
}
