<?php

namespace App\Console\Commands;

use App\Helpers\StorageHelper;
use App\Models\LetterheadSetting;
use Illuminate\Console\Command;

/**
 * One-off: move letterhead header/footer/signature images saved under
 * public/uploads/letterheads/{resort} to the private StorageHelper disk
 * (letterheads/{resort}/...) and repoint the letterhead_settings columns.
 * Until it runs, LetterheadSetting::imageBytes() still reads the old files.
 */
class MoveLetterheadsToPrivate extends Command
{
    protected $signature = 'letterhead:move-to-private {--dry-run : List what would move, change nothing}';
    protected $description = 'Move letterhead images out of public/ into private storage';

    public function handle()
    {
        $dry = (bool) $this->option('dry-run');
        $moved = $missing = $failed = 0;

        foreach (LetterheadSetting::orderBy('id')->get() as $setting) {
            foreach (['header_image', 'footer_image', 'signature_image'] as $field) {
                $old = $setting->{$field};
                if (!LetterheadSetting::isLegacyPublicPath($old)) {
                    continue;
                }
                $abs = public_path($old);
                if (!is_file($abs)) {
                    $missing++;
                    $this->warn("resort {$setting->resort_id} {$field}: {$old} not found on disk, left as is");
                    continue;
                }
                $new = LetterheadSetting::storageDir($setting->resort_id) . '/' . basename($old);
                $this->line(($dry ? '[dry-run] ' : '') . "resort {$setting->resort_id} {$field}: {$old} -> {$new}");
                if ($dry) {
                    $moved++;
                    continue;
                }
                try {
                    $bytes = file_get_contents($abs);
                    if (!StorageHelper::put($new, $bytes) || StorageHelper::get($new) !== $bytes) {
                        throw new \RuntimeException('write not confirmed');
                    }
                    $setting->{$field} = $new;
                    $setting->save();
                    @unlink($abs);
                    $moved++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("resort {$setting->resort_id} {$field}: failed — {$e->getMessage()}");
                }
            }
        }

        $this->info(($dry ? 'Would move' : 'Moved') . " {$moved}, missing {$missing}, failed {$failed}.");
        return $failed ? 1 : 0;
    }
}
