<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\StorageHelper;
use Carbon\Carbon;

/**
 * P-03 — mobile payslip PDFs (API\PayrollController::downloadPayslip) are
 * written to storage so a URL can be handed back to the app, but nothing
 * ever deleted them: every download left another salary document sitting
 * in the bucket/disk permanently. Sweeps anything older than an hour —
 * downloads are one-shot, the app never needs to re-fetch the same link.
 */
class CleanupOldPayslipPdfs extends Command
{
    protected $signature = 'payroll:cleanup-payslip-pdfs';

    protected $description = 'Delete generated payslip PDFs (uploads/payslip/**) older than 1 hour.';

    public function handle()
    {
        $baseDir = trim(config('settings.PayslipPdf'), '/');
        $cutoff = Carbon::now()->subHour();
        $deleted = 0;

        foreach (StorageHelper::allFiles($baseDir) as $path) {
            $modified = StorageHelper::disk()->lastModified($path);
            if ($modified !== false && Carbon::createFromTimestamp($modified)->lt($cutoff)) {
                StorageHelper::delete($path);
                $deleted++;
            }
        }

        $this->info("Deleted {$deleted} payslip PDF(s) older than 1 hour.");
    }
}
