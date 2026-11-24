<?php

namespace App\Console\Commands;

use App\Models\ApplicantOfferContract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * N18 backfill: offer letters / contracts written before the fix sit on the public disk
 * (storage/app/public, reachable by URL). Copy each into private encrypted storage,
 * repoint file_path, then remove the public copy. Re-runnable: moved rows end in .enc.
 */
class MoveOfferContractFiles extends Command
{
    protected $signature = 'ta:move-offer-files {--dry-run : List what would move, change nothing}';

    protected $description = 'Move public offer letter / contract files into private storage';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $public = Storage::disk('public');
        $moved = $missing = $failed = 0;

        ApplicantOfferContract::whereNotNull('file_path')->where('file_path', 'not like', '%.enc')
            ->orderBy('id')->each(function (ApplicantOfferContract $row) use ($dry, $public, &$moved, &$missing, &$failed) {
                $old = $row->file_path;
                if (!$public->exists($old)) {
                    $missing++;
                    $this->warn("#{$row->id} missing on public disk: {$old}");
                    return;
                }
                if ($dry) {
                    $moved++;
                    $this->line("#{$row->id} resort {$row->resort_id} {$row->type}: {$old}");
                    return;
                }
                try {
                    $new = ApplicantOfferContract::storeFile((int) $row->resort_id, $row->type, (int) $row->applicant_id,
                        $public->get($old), ApplicantOfferContract::fileExtension($old));
                    $row->file_path = $new;
                    $row->save();
                    $public->delete($old);
                    $moved++;
                    $this->line("#{$row->id} {$old} -> {$new}");
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("#{$row->id} failed: " . $e->getMessage());
                }
            });

        $this->info(($dry ? 'Would move' : 'Moved') . " {$moved}, missing {$missing}, failed {$failed}.");
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
