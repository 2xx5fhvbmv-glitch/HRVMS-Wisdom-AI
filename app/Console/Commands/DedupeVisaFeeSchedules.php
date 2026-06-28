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

        // A slot lump-sum (Amt >= this) is bucketed separately so it is never
        // collapsed into a monthly installment of the same calendar month.
        $LUMPSUM_MIN = 1000;

        foreach ($tables as $table => $label) {
            // Group by CALENDAR MONTH, not exact Due_Date — a re-renewal on a
            // different day produced e.g. 2026-07-17 AND 2026-07-26 (same month,
            // different day), which an exact-date match could never dedupe.
            $rows = DB::table($table)
                ->whereNotNull('Due_Date')
                ->when($resort, fn($q) => $q->where('resort_id', $resort))
                ->orderBy('id')
                ->get(['id', 'resort_id', 'employee_id', 'Due_Date', 'Status', 'Amt']);

            $groups = $rows->groupBy(function ($r) use ($LUMPSUM_MIN) {
                $bucket = ((float) $r->Amt >= $LUMPSUM_MIN) ? 'L' : 'I'; // lump-sum vs installment
                return $r->resort_id . '|' . $r->employee_id . '|' . substr((string) $r->Due_Date, 0, 7) . '|' . $bucket;
            });

            $deleted = 0;
            foreach ($groups as $key => $g) {
                if ($g->count() < 2) {
                    continue;
                }
                // Keep a Paid row if any (lowest id), else the lowest id overall.
                $keep = $g->first(fn($r) => strtolower((string) $r->Status) === 'paid') ?? $g->first();
                $toDelete = $g->where('id', '!=', $keep->id)->pluck('id');

                [$rid, $emp, $ym, $bucket] = explode('|', $key);
                $this->line("  {$label} emp {$emp} {$ym} (" . ($bucket === 'L' ? 'lump-sum' : 'installment') . "): {$g->count()} rows -> keep #{$keep->id}, delete " . $toDelete->implode(','));
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
