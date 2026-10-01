<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\StorageHelper;
use Carbon\Carbon;

/**
 * FM-01 — FileManageController's "view" and "share link" actions decrypt an
 * employee's document (passports, contracts, medical certs, ...) to a
 * plaintext copy under temp/decrypted_* or temp/share_* so a signed URL can
 * be handed back, but nothing ever deleted those copies: every open/share
 * left another decrypted personal document sitting in storage permanently.
 * Sweeps anything older than an hour — the signed URLs themselves expire at
 * 30 minutes, so nothing legitimate still needs the file by then.
 */
class CleanupOldFileManagementTempFiles extends Command
{
    protected $signature = 'filemanagement:cleanup-temp-files';

    protected $description = 'Delete decrypted temp/decrypted_* and temp/share_* copies older than 1 hour.';

    public function handle()
    {
        $cutoff = Carbon::now()->subHour();
        $deleted = 0;

        foreach (StorageHelper::allFiles('temp') as $path) {
            $basename = basename($path);
            if (strpos($basename, 'decrypted_') !== 0 && strpos($basename, 'share_') !== 0) {
                continue;
            }
            $modified = StorageHelper::disk()->lastModified($path);
            if ($modified !== false && Carbon::createFromTimestamp($modified)->lt($cutoff)) {
                StorageHelper::delete($path);
                $deleted++;
            }
        }

        $this->info("Deleted {$deleted} decrypted temp file(s) older than 1 hour.");
    }
}
