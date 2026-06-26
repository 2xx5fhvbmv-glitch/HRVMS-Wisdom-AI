<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\Common;

/**
 * One-off cleanup — snap Work Permit fee rows to the exact configured fee.
 *
 * Older rows were stored after a currency-conversion round-trip (MVR -> USD ->
 * MVR), which left fractional amounts like 349.88 / 350.03 instead of a clean
 * 350.00. This walks each resort's configured "Work Permit Fee" and rewrites
 * any work_permits row whose amount is within --tolerance of that fee to the
 * exact configured value. Amounts that differ by more than the tolerance are
 * left alone (they aren't rounding noise).
 *
 * Safe to re-run (idempotent — already-exact rows are skipped). Use --dry-run
 * to preview, --resort= to limit to one resort.
 *
 *   php artisan visa:normalize-work-permit-fees --dry-run
 *   php artisan visa:normalize-work-permit-fees
 *   php artisan visa:normalize-work-permit-fees --resort=26 --tolerance=5
 */
class NormalizeWorkPermitFees extends Command
{
    protected $signature = 'visa:normalize-work-permit-fees
                            {--resort= : Limit to a single resort_id}
                            {--tolerance=10 : Max MVR difference from the configured fee to snap}
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Snap Work Permit fee rows to the exact configured fee (fix 349.88/350.03 rounding)';

    public function handle()
    {
        $tolerance = (float) $this->option('tolerance');
        $dryRun    = (bool) $this->option('dry-run');

        $resortIds = $this->option('resort')
            ? [(int) $this->option('resort')]
            : DB::table('work_permits')->distinct()->pluck('resort_id')->filter()->values()->all();

        if (empty($resortIds)) {
            $this->warn('No work_permits rows found.');
            return self::SUCCESS;
        }

        $grandTotal = 0;

        foreach ($resortIds as $resortId) {
            $config = Common::VisaRenewalCost($resortId);
            $fee    = isset($config['WORK PERMIT FEE']['amount']) ? (float) $config['WORK PERMIT FEE']['amount'] : 0.0;

            if ($fee <= 0) {
                $this->warn("Resort {$resortId}: no configured Work Permit fee — skipped.");
                continue;
            }

            // Rows close to the configured fee but not exactly equal to it.
            $rows = DB::table('work_permits')
                ->where('resort_id', $resortId)
                ->whereRaw('ABS(CAST(Amt AS DECIMAL(12,4)) - ?) <= ?', [$fee, $tolerance])
                ->whereRaw('CAST(Amt AS DECIMAL(12,4)) <> ?', [$fee])
                ->get(['id', 'Amt']);

            if ($rows->isEmpty()) {
                $this->line("Resort {$resortId}: fee={$fee} — nothing to fix.");
                continue;
            }

            $this->info("Resort {$resortId}: fee={$fee} — " . ($dryRun ? 'would update ' : 'updating ') . $rows->count() . ' row(s):');
            foreach ($rows as $row) {
                $this->line("   #{$row->id}: {$row->Amt} -> " . number_format($fee, 2));
            }

            if (!$dryRun) {
                DB::table('work_permits')
                    ->whereIn('id', $rows->pluck('id'))
                    ->update(['Amt' => $fee]);
            }

            $grandTotal += $rows->count();
        }

        $this->newLine();
        $this->info(($dryRun ? '[dry-run] ' : '') . "Done. {$grandTotal} row(s) " . ($dryRun ? 'would be ' : '') . 'normalized.');

        return self::SUCCESS;
    }
}
