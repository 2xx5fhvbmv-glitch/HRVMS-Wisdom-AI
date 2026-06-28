<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Delete an employee's Quota Slot fee rows so HR can re-renew a clean 12-month
 * schedule. Use this for employees whose slot data is corrupted by the old
 * Lumpsum-vs-Installment double-renewal (e.g. a stray 166 installment plus a
 * 2,000 lump-sum = 2,166 for a 2,000/year deposit).
 *
 * Destructive: it removes the rows (including paid history) for the targeted
 * slot schedule. A target is REQUIRED so nothing is wiped by accident — pass
 * --employee=<id> for one person, or --overlapping to hit every employee that
 * has BOTH a lump-sum (Amt >= 1000) and installment (Amt < 1000) row. Always
 * preview with --dry-run first.
 *
 *   php artisan visa:reset-slot-schedule --employee=184 --dry-run
 *   php artisan visa:reset-slot-schedule --employee=184
 *   php artisan visa:reset-slot-schedule --overlapping --resort=26 --dry-run
 */
class ResetSlotSchedule extends Command
{
    protected $signature = 'visa:reset-slot-schedule
                            {--employee= : Reset this employee_id only}
                            {--overlapping : Reset every employee with a lump-sum + installment slot overlap}
                            {--resort= : Limit to a single resort_id}
                            {--dry-run : Show what would be deleted without writing}';

    protected $description = "Delete an employee's Quota Slot rows so HR can re-renew a clean 12-month schedule";

    public function handle()
    {
        $dryRun   = (bool) $this->option('dry-run');
        $employee = $this->option('employee');
        $resort   = $this->option('resort');

        if (!$employee && !$this->option('overlapping')) {
            $this->error('Refusing to run without a target. Pass --employee=<id> or --overlapping.');
            return self::INVALID;
        }

        // Resolve the target employee ids.
        if ($employee) {
            $targets = collect([(int) $employee]);
        } else {
            $targets = DB::table('quota_slot_renewals as q')
                ->when($resort, fn($x) => $x->where('q.resort_id', $resort))
                ->whereRaw('EXISTS (SELECT 1 FROM quota_slot_renewals a WHERE a.employee_id = q.employee_id AND a.resort_id = q.resort_id AND CAST(a.Amt AS DECIMAL(12,2)) >= 1000)')
                ->whereRaw('EXISTS (SELECT 1 FROM quota_slot_renewals b WHERE b.employee_id = q.employee_id AND b.resort_id = q.resort_id AND CAST(b.Amt AS DECIMAL(12,2)) < 1000)')
                ->distinct()
                ->pluck('q.employee_id');
        }

        if ($targets->isEmpty()) {
            $this->info('No matching employees — nothing to reset.');
            return self::SUCCESS;
        }

        $grandDeleted = 0;
        foreach ($targets as $empId) {
            $q = DB::table('quota_slot_renewals')->where('employee_id', $empId);
            if ($resort) {
                $q->where('resort_id', $resort);
            }
            $rows = (clone $q)->get(['id', 'Due_Date', 'Amt', 'Status']);
            if ($rows->isEmpty()) {
                continue;
            }

            $this->line("  employee {$empId}: " . ($dryRun ? 'would delete ' : 'deleting ') . $rows->count()
                . ' slot row(s) (total MVR ' . number_format($rows->sum('Amt'), 2) . ')');
            foreach ($rows as $r) {
                $this->line("     #{$r->id} {$r->Due_Date} " . number_format((float) $r->Amt, 2) . " {$r->Status}");
            }

            if (!$dryRun) {
                (clone $q)->delete();
            }
            $grandDeleted += $rows->count();
        }

        $this->newLine();
        $this->info(($dryRun ? '[dry-run] ' : '') . 'Done. ' . ($dryRun ? 'would delete ' : 'deleted ')
            . "{$grandDeleted} slot row(s) across {$targets->count()} employee(s). Re-renew each from the Renewal page to rebuild a clean 12-month schedule.");

        return self::SUCCESS;
    }
}
