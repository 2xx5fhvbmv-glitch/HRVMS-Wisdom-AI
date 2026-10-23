<?php

namespace App\Console\Commands;

use App\Services\Wisdom\PolicyLibrary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Hourly backstop for Wisdom AI's company policy index: catches what file events can't see (a whole folder
 * deleted, a folder renamed to/from "Company Policies"). Also the way to index a folder by hand.
 */
class SyncCompanyPoliciesCommand extends Command
{
    protected $signature = 'wisdom:sync-policies {resort? : Only this resort id}';
    protected $description = "Index each resort's File Management \"Company Policies\" folder for Wisdom AI";

    public function handle(): int
    {
        $resorts = $this->argument('resort') ? [(int) $this->argument('resort')]
            : DB::table('filemangement_systems')->where('Folder_Type', 'uncategorized')
                ->whereIn(DB::raw('LOWER(TRIM(Folder_Name))'), PolicyLibrary::FOLDER_NAMES)->distinct()->pluck('resort_id')->all();
        foreach ($resorts as $resortId) {
            try {
                $r = PolicyLibrary::sync((int) $resortId);
                $this->info("Resort {$resortId}: {$r['indexed']} indexed, {$r['removed']} removed" . ($r['failed'] ? ', problems: ' . implode('; ', $r['failed']) : ''));
            } catch (\Throwable $e) {
                $this->error("Resort {$resortId}: " . $e->getMessage());
            }
        }
        return self::SUCCESS;
    }
}
