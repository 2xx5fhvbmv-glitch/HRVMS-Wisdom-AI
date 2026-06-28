<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Remove duplicate Work Permit / Quota Slot fee rows created by the renewal
 * flow appending a fresh schedule on every "Renew" (no existence guard). The
 * same month ended up scheduled — and paid — more than once, inflating the
 * payable/paid totals and the lifetime "Total Work Permit / Slot" figures.
 *
 * Dedup key: (resort_id, employee_id, Due_Date) per fee table. For each group
 * of duplicates it KEEPS one row — preferring a settled (Paid) row, then the
 * lowest id — and deletes the rest. Also WARNS when a Quota Slot employee has
 * both a lump-sum row (Amt >= 1000) and installment rows (a slot paid twice),
 * which needs a human decision and is NOT auto-removed.
 *
 *   php artisan visa:dedupe-fee-schedules --dry-run
 *   php artisan visa:dedupe-fee-schedules
 *   php artisan visa:dedupe-fee-schedules --resort=26
 */
class DedupeVisaFeeSchedules extends Command
{
    protected $signature = 'visa:dedupe-fee-schedules
                            {--resort= : Limit to a single resort_id}
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Remove duplicate Work Permit / Quota Slot fee rows (same employee + due date)';

    public function handle()
    {
        $dryRun  = (bool) $this->option('dry-run');
        $resort  = $this->option('resort') ? (int) $this->option('resort') : null;

        $tables = [
            'work_permits'        => 'Work Permit',
            'quota_slot_renewals' => 'Quota Slot',
        ];

        $grandDeleted = 0;

        foreach ($tables as $table => $label) {
            $groups = DB::table($table)
                ->select('resort_id', 'employee_id', 'Due_Date', DB::raw('COUNT(*) as c'))
                ->whereNotNull('Due_Date')
                ->when($resort, fn($q) => $q->where('resort_id', $resort))
                ->groupBy('resort_id', 'employee_id', 'Due_Date')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            $deleted = 0;
            foreach ($groups as $g) {
                $rows = DB::table($table)
                    ->where('resort_id', $g->resort_id)
                    ->where('employee_id', $g->employee_id)
                    ->where('Due_Date', $g->Due_Date)
                    ->orderBy('id')
                    ->get(['id', 'Status', 'Amt']);

                // Keep a Paid row if any (lowest id), else the lowest id overall.
                $keep = $rows->first(fn($r) => strtolower((string) $r->Status) === 'paid') ?? $rows->first();
                $toDelete = $rows->where('id', '!=', $keep->id)->pluck('id');

                $this->line("  {$label} emp {$g->employee_id} due {$g->Due_Date}: {$g->c} rows -> keep #{$keep->id}, delete " . $toDelete->implode(','));
                if (!$dryRun && $toDelete->isNotEmpty()) {
                    DB::table($table)->whereIn('id', $toDelete)->delete();
                }
                $deleted += $toDelete->count();
            }

            $this->info(($dryRun ? '[dry-run] ' : '') . "{$label}: " . ($dryRun ? 'would delete ' : 'deleted ') . "{$deleted} duplicate row(s).");
            $grandDeleted += $deleted;
        }

        // Heuristic warning — slot paid as BOTH lump sum and installments.
        $lumpAndInstallment = DB::table('quota_slot_renewals as q')
            ->select('q.resort_id', 'q.employee_id')
            ->when($resort, fn($x) => $x->where('q.resort_id', $resort))
            ->whereRaw('EXISTS (SELECT 1 FROM quota_slot_renewals a WHERE a.employee_id = q.employee_id AND a.resort_id = q.resort_id AND CAST(a.Amt AS DECIMAL(12,2)) >= 1000)')
            ->whereRaw('EXISTS (SELECT 1 FROM quota_slot_renewals b WHERE b.employee_id = q.employee_id AND b.resort_id = q.resort_id AND CAST(b.Amt AS DECIMAL(12,2)) < 1000)')
            ->distinct()
            ->pluck('q.employee_id');

        if ($lumpAndInstallment->isNotEmpty()) {
            $this->newLine();
            $this->warn('Quota Slot paid as BOTH lump-sum AND installments (needs manual review, NOT auto-removed) for employee_id: ' . $lumpAndInstallment->implode(', '));
        }

        $this->newLine();
        $this->info(($dryRun ? '[dry-run] ' : '') . "Done. " . ($dryRun ? 'would delete ' : 'deleted ') . "{$grandDeleted} duplicate fee row(s) total.");

        return self::SUCCESS;
    }
}
