<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Follow-up to 2026_06_13_000002. That migration refreshed stale manning
 * monthly salaries to the employee's current basic_salary, but it SKIPPED any
 * employee whose basic_salary is NULL (treating "no salary on record" as
 * "unknown — don't touch"). The result: people with no salary (e.g. Svetlana,
 * Sarah) kept a bogus $180 placeholder in the plan, inflating the manning total
 * vs the salary-based compare-budget total.
 *
 * Per the decision: if an employee has no salary, the plan should not show one.
 * So for manning rows whose linked employee EXISTS but has a NULL basic_salary,
 * zero out the saved monthly salaries (and Current_Basic_salary).
 *
 * SAFETY (same as 000002):
 *   - Skips rows with a planned increment (Proposed_Basic_salary > 0).
 *   - Only touches rows where the employee row exists AND basic_salary IS NULL
 *     (the exact set 000002 left behind) — does not re-touch real-salary rows.
 *   - Idempotent; logs every change. IRREVERSIBLE — back up the table and run
 *     on staging first.
 */
class ZeroOutNullsalaryManningMonths extends Migration
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

                // Leave intentional planned increments alone.
                if ((float) ($row->Proposed_Basic_salary ?? 0) > 0) {
                    continue;
                }

                // Distinguish "no employee row" (can't decide → skip) from
                // "employee exists but salary is NULL" (the case to fix).
                $emp = DB::table('employees')->where('id', $row->Emp_id)->first(['basic_salary']);
                if (!$emp) {
                    continue; // no linked employee — don't guess
                }
                if ($emp->basic_salary !== null) {
                    continue; // real salary → already handled by 000002
                }

                // Employee has no salary → the plan should show $0, not $180.
                $months = json_decode((string) $row->Months, true);
                if (!is_array($months) || empty($months)) {
                    continue;
                }

                $needsFix = false;
                foreach ($months as $m) {
                    if (is_array($m) && array_key_exists('salary', $m) && (float) $m['salary'] !== 0.0) {
                        $needsFix = true;
                        break;
                    }
                }
                if (!$needsFix) {
                    continue; // already zeroed
                }

                foreach ($months as &$m) {
                    if (is_array($m) && array_key_exists('salary', $m)) {
                        $m['salary'] = 0;
                    }
                }
                unset($m);

                DB::table('store_manning_response_children')
                    ->where('id', $row->id)
                    ->update([
                        'Months'               => json_encode($months),
                        'Current_Basic_salary' => 0,
                    ]);

                $updated++;
                \Log::info("ZeroOutNullsalaryManningMonths: child #{$row->id} (Emp_id {$row->Emp_id}) zeroed — employee has no salary.");
            }
        });

        \Log::info("ZeroOutNullsalaryManningMonths: scanned {$scanned}, zeroed {$updated} no-salary manning rows.");
    }

    public function down()
    {
        // Irreversible: placeholder salaries are not restored on rollback.
    }
}
