<?php

namespace App\Console\Commands;

use App\Helpers\StorageHelper;
use App\Models\disciplinarySubmit;
use App\Models\DisciplinaryInvestigationParent;
use App\Models\Resort;
use Illuminate\Console\Command;

/**
 * One-time backfill for files uploaded before the Disciplinary module's
 * StorageHelper fix. Old uploads were written with ->move(public_path(...)),
 * i.e. straight to the app server's local disk. The view now only ever reads
 * via StorageHelper::temporaryUrl() (Wasabi in prod), so any pre-fix file is
 * unreachable until copied onto the configured disk at the same relative path.
 *
 * Covers 3 fields sharing the same path convention
 * (config('settings.DisciplinaryAttachments')/{resort->resort_id}/{Disciplinary_id}/{filename}):
 *   - disciplinary_submits.Attachements (comma-separated filenames)
 *   - disciplinary_submits.upload_signed_document (single filename)
 *   - disciplinary_investigation_parents.investigation_file (comma-separated filenames)
 *
 * Does NOT delete old local files — that's a deliberate separate follow-up
 * once the summary below has been reviewed and spot-checked against the UI.
 *
 *   php artisan disciplinary:backfill-attachments --dry-run
 *   php artisan disciplinary:backfill-attachments
 *   php artisan disciplinary:backfill-attachments --resort=26
 */
class BackfillDisciplinaryAttachments extends Command
{
    protected $signature = 'disciplinary:backfill-attachments
                            {--resort= : Limit to a single resorts.id}
                            {--dry-run : Show what would migrate without writing}';

    protected $description = 'Copy pre-fix local Disciplinary attachments onto the configured storage disk';

    private array $resortCodeCache = [];
    private int $migrated = 0;
    private int $alreadyOk = 0;
    private array $missing = [];

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $resortFilter = $this->option('resort') ? (int) $this->option('resort') : null;

        $basePath = config('settings.DisciplinaryAttachments');

        $submits = disciplinarySubmit::query()
            ->where(function ($q) {
                $q->whereNotNull('Attachements')->where('Attachements', '!=', '')
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('upload_signed_document')->where('upload_signed_document', '!=', '');
                    });
            })
            ->when($resortFilter, fn($q) => $q->where('resort_id', $resortFilter))
            ->get(['id', 'resort_id', 'Disciplinary_id', 'Attachements', 'upload_signed_document']);

        foreach ($submits as $row) {
            $resortCode = $this->resortCode($row->resort_id);
            if (!$resortCode) {
                continue;
            }
            $prefix = $basePath . '/' . $resortCode . '/' . $row->Disciplinary_id;

            foreach ($this->splitFilenames($row->Attachements) as $filename) {
                $this->migrateOne($prefix, $filename, $row->resort_id, $row->Disciplinary_id, 'Attachements', $dryRun);
            }
            foreach ($this->splitFilenames($row->upload_signed_document) as $filename) {
                $this->migrateOne($prefix, $filename, $row->resort_id, $row->Disciplinary_id, 'upload_signed_document', $dryRun);
            }
        }

        $investigations = DisciplinaryInvestigationParent::query()
            ->whereNotNull('investigation_file')->where('investigation_file', '!=', '')
            ->when($resortFilter, fn($q) => $q->where('resort_id', $resortFilter))
            ->get(['id', 'resort_id', 'Disciplinary_id', 'investigation_file']);

        foreach ($investigations as $row) {
            $resortCode = $this->resortCode($row->resort_id);
            if (!$resortCode) {
                continue;
            }
            $prefix = $basePath . '/' . $resortCode . '/' . $row->Disciplinary_id;

            foreach ($this->splitFilenames($row->investigation_file) as $filename) {
                $this->migrateOne($prefix, $filename, $row->resort_id, $row->Disciplinary_id, 'investigation_file', $dryRun);
            }
        }

        $this->newLine();
        $this->info(($dryRun ? '[dry-run] ' : '') . "Migrated: {$this->migrated}, already OK: {$this->alreadyOk}, missing: " . count($this->missing));

        if (!empty($this->missing)) {
            $this->warn('Unrecoverable (not found on old local disk or configured disk):');
            foreach ($this->missing as $m) {
                $this->line("  resort_id={$m['resort_id']} Disciplinary_id={$m['Disciplinary_id']} field={$m['field']} file={$m['filename']}");
            }
        }

        return self::SUCCESS;
    }

    private function splitFilenames(?string $value): array
    {
        if (empty($value)) {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    private function resortCode(int $resortId): ?string
    {
        if (!array_key_exists($resortId, $this->resortCodeCache)) {
            $this->resortCodeCache[$resortId] = Resort::find($resortId)?->resort_id;
        }
        return $this->resortCodeCache[$resortId];
    }

    private function migrateOne(string $prefix, string $filename, int $resortId, string $disciplinaryId, string $field, bool $dryRun): void
    {
        $relativePath = $prefix . '/' . $filename;

        if (StorageHelper::exists($relativePath)) {
            $this->alreadyOk++;
            return;
        }

        $oldLocalPath = public_path($relativePath);

        if (!is_file($oldLocalPath)) {
            $this->missing[] = compact('resortId', 'disciplinaryId', 'field', 'filename') + [
                'resort_id' => $resortId,
                'Disciplinary_id' => $disciplinaryId,
            ];
            return;
        }

        $this->line(($dryRun ? '[dry-run] would migrate: ' : 'migrating: ') . "{$relativePath}");

        if (!$dryRun) {
            StorageHelper::put($relativePath, file_get_contents($oldLocalPath));
        }

        $this->migrated++;
    }
}
