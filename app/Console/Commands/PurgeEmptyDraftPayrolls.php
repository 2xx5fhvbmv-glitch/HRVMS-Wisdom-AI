<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Deletes stale `draft` payrolls that never collected any review rows.
 *
 * Background:
 *   Clicking "Run Payroll" creates a `payroll` row with status=draft
 *   even if HR closes the tab before the per-employee review rows are
 *   computed. The next run creates a fresh row, leaving the abandoned
 *   draft as orphan noise — invisible on the dashboard / liability page
 *   (drafts are filtered out everywhere), but clutters the payroll
 *   list and confuses HR when they scroll back.
 *
 *   This command targets ONLY drafts with **zero** `payroll_reviews`
 *   children. A draft that has employee review rows is treated as
 *   "real work in progress" and skipped — HR has to decide whether to
 *   convert or delete it manually.
 *
 * Usage:
 *   php artisan payroll:purge-empty-drafts --dry-run        # preview only
 *   php artisan payroll:purge-empty-drafts                  # delete drafts > 14d old (default)
 *   php artisan payroll:purge-empty-drafts --days=30        # change the cutoff
 *   php artisan payroll:purge-empty-drafts --resort=26      # scope to one resort
 *   php artisan payroll:purge-empty-drafts --resort=26 --dry-run --days=7
 *
 * Safety:
 *   • status MUST be 'draft' (won't touch approved / locked / processed / etc.)
 *   • COUNT(payroll_reviews) MUST be 0 (drafts with data are skipped)
 *   • Age guard: only payrolls older than `--days` (default 14 days)
 *   • Dry-run mode prints a table without deleting anything
 *
 * Hooked into the scheduler at runtime by adding:
 *   $schedule->command('payroll:purge-empty-drafts')->weekly();
 * to App\Console\Kernel — left off by default so the first run is
 * always manual / reviewed.
 */
class PurgeEmptyDraftPayrolls extends Command
{
    protected $signature = 'payroll:purge-empty-drafts'
        . ' {--days=14 : Minimum age in days before a draft can be purged}'
        . ' {--resort= : Limit to one resort id}'
        . ' {--dry-run : Preview only; do not delete anything}';

    protected $description = 'Delete stale, empty (no review rows) draft payrolls older than N days.';

    public function handle(): int
    {
        $days    = (int) $this->option('days');
        $resort  = $this->option('resort');
        $dryRun  = (bool) $this->option('dry-run');
        $cutoff  = Carbon::now()->subDays($days)->toDateTimeString();

        $this->line('');
        $this->info(sprintf(
            '%s draft payrolls older than %d day(s) (cutoff %s)%s',
            $dryRun ? 'DRY-RUN — would purge' : 'Purging',
            $days,
            $cutoff,
            $resort ? sprintf(' for resort #%d', (int) $resort) : ''
        ));
        $this->line('');

        // Pull every draft that's old enough. Filter by review-row count
        // AFTER the query so an admin scanning the audit log can see
        // both the deleted set AND the "skipped because it has data"
        // set in the same run.
        $query = DB::table('payroll')
            ->where('status', 'draft')
            ->where('created_at', '<=', $cutoff);
        if ($resort) {
            $query->where('resort_id', (int) $resort);
        }
        $drafts = $query->orderBy('id', 'desc')
            ->get(['id', 'resort_id', 'start_date', 'end_date', 'status', 'created_at']);

        if ($drafts->isEmpty()) {
            $this->comment('Nothing matched. No stale empty drafts to clean up.');
            return self::SUCCESS;
        }

        $purgeRows = [];
        $skipRows  = [];

        foreach ($drafts as $p) {
            $reviewCount = (int) DB::table('payroll_reviews')
                ->where('payroll_id', $p->id)
                ->count();
            $row = [
                'id'         => $p->id,
                'resort_id'  => $p->resort_id,
                'period'     => $p->start_date . ' → ' . $p->end_date,
                'created'    => $p->created_at,
                'review_rows'=> $reviewCount,
            ];
            if ($reviewCount === 0) {
                $purgeRows[] = $row;
            } else {
                $skipRows[] = $row;
            }
        }

        // ── Summary tables ────────────────────────────────────────
        if (!empty($purgeRows)) {
            $this->info(sprintf(
                '%d draft payroll(s) %s purged:',
                count($purgeRows),
                $dryRun ? 'WOULD BE' : 'will be'
            ));
            $this->table(
                ['Payroll ID', 'Resort', 'Period', 'Created At', 'Review Rows'],
                $purgeRows
            );
        }
        if (!empty($skipRows)) {
            $this->warn(sprintf(
                '%d draft payroll(s) skipped (have review-row data — manual review needed):',
                count($skipRows)
            ));
            $this->table(
                ['Payroll ID', 'Resort', 'Period', 'Created At', 'Review Rows'],
                $skipRows
            );
        }

        if ($dryRun) {
            $this->comment('Dry-run complete. Re-run without --dry-run to apply.');
            return self::SUCCESS;
        }

        if (empty($purgeRows)) {
            $this->comment('Nothing to delete after filtering.');
            return self::SUCCESS;
        }

        // ── Actual deletion ───────────────────────────────────────
        // Wrap in a transaction so a constraint failure rolls back
        // the whole batch — don't leave orphan child rows.
        DB::beginTransaction();
        try {
            $ids = array_column($purgeRows, 'id');

            // Defensive: also clean up any orphan child rows the
            // schema may have without an ON DELETE CASCADE. Each of
            // these tables references `payroll_id`. Counts logged
            // for the audit trail.
            $childTables = [
                'payroll_deductions',
                'payroll_employees',
            ];
            foreach ($childTables as $table) {
                if (\Schema::hasTable($table) && \Schema::hasColumn($table, 'payroll_id')) {
                    $deleted = DB::table($table)->whereIn('payroll_id', $ids)->delete();
                    if ($deleted > 0) {
                        $this->line(sprintf('  · cleaned %d orphan rows from %s', $deleted, $table));
                    }
                }
            }

            $deletedPayrolls = DB::table('payroll')->whereIn('id', $ids)->delete();
            DB::commit();
            $this->info(sprintf('✓ Deleted %d empty draft payroll(s).', $deletedPayrolls));
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Purge failed — rolled back: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
