<?php

namespace App\Console\Commands;

use App\Helpers\Common;
use App\Helpers\StorageHelper;
use App\Models\Resort;
use App\Models\ResortAdmin;
use Illuminate\Console\Command;

/**
 * One-time backfill for the e-signature encryption-at-rest fix. Every
 * signature written before this fix (resort_admins.signature_img and every
 * approvals/{context}/{recordId}/signature.* snapshot produced by
 * Common::snapshotSignature()) was saved as plaintext bytes — the read
 * sites now decrypt on every read, so an un-migrated signature would fail
 * to decrypt (or worse, "decrypt" to garbage) once this ships.
 *
 * Safely re-runnable: Common::looksLikeEncryptedImage() detects a file
 * that's already been through encryptFileBytes() and skips it, so running
 * this twice (or against a signature saved after the fix already shipped)
 * is a no-op for that file.
 *
 * Approval snapshots aren't backfilled by walking every table that calls
 * snapshotSignature() (20+ call sites across Budget, Promotion, Transfer,
 * Grievance, Disciplinary, Exit Clearance, Salary Increment/Advance,
 * Incident, Job Description, Interview Assessment, Vacancy, PIP/PDP,
 * Performance Review, Payroll Settlement...) — the storage path itself is
 * fully deterministic (resort_id/public/approvals/{context}/{recordId}/
 * signature.{ext}), so this scans storage directly per resort instead.
 *
 *   php artisan signatures:encrypt-existing --dry-run
 *   php artisan signatures:encrypt-existing
 *   php artisan signatures:encrypt-existing --resort=26
 */
class EncryptExistingSignatures extends Command
{
    protected $signature = 'signatures:encrypt-existing
                            {--resort= : Limit to a single resorts.resort_id (the short code, not the numeric id)}
                            {--dry-run : Show what would be encrypted without writing}';

    protected $description = 'Encrypt at rest every pre-existing plaintext e-signature (profile signatures + approval snapshots)';

    private int $encrypted = 0;
    private int $alreadyOk = 0;
    private int $failed = 0;

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $resortFilter = $this->option('resort');

        $this->encryptProfileSignatures($dryRun, $resortFilter);
        $this->encryptApprovalSnapshots($dryRun, $resortFilter);

        $this->newLine();
        $this->info(($dryRun ? '[dry-run] ' : '') . "Encrypted: {$this->encrypted}, already OK: {$this->alreadyOk}, failed: {$this->failed}");

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function encryptProfileSignatures(bool $dryRun, ?string $resortFilter): void
    {
        $admins = ResortAdmin::query()
            ->whereNotNull('signature_img')
            ->where('signature_img', '!=', '')
            ->when($resortFilter, function ($q) use ($resortFilter) {
                $q->whereHas('resort', fn ($r) => $r->where('resort_id', $resortFilter));
            })
            ->get(['id', 'signature_img']);

        foreach ($admins as $admin) {
            $this->encryptPathInPlace($admin->signature_img, "resort_admins.id={$admin->id}", $dryRun);
        }
    }

    private function encryptApprovalSnapshots(bool $dryRun, ?string $resortFilter): void
    {
        $resorts = Resort::query()
            ->when($resortFilter, fn ($q) => $q->where('resort_id', $resortFilter))
            ->pluck('resort_id')
            ->filter()
            ->unique();

        foreach ($resorts as $resortCode) {
            $approvalsDir = $resortCode . '/public/approvals';
            if (!StorageHelper::exists($approvalsDir)) {
                continue;
            }

            foreach (StorageHelper::allFiles($approvalsDir) as $filePath) {
                if (!str_starts_with(basename($filePath), 'signature.')) {
                    continue;
                }
                $this->encryptPathInPlace($filePath, "snapshot", $dryRun);
            }
        }
    }

    private function encryptPathInPlace(string $path, string $label, bool $dryRun): void
    {
        if (!StorageHelper::exists($path)) {
            return;
        }

        try {
            $bytes = StorageHelper::get($path);
        } catch (\Throwable $e) {
            $this->warn("  failed to read {$path} ({$label}): {$e->getMessage()}");
            $this->failed++;
            return;
        }

        if (Common::looksLikeEncryptedImage($bytes)) {
            $this->alreadyOk++;
            return;
        }

        if (!Common::looksLikeImageBytes($bytes)) {
            // Neither a recognizable plaintext image nor something that
            // decrypts to one — don't guess, flag it for manual review
            // instead of encrypting bytes that were never a valid image.
            $this->warn("  skipping {$path} ({$label}): not a recognizable image, left untouched");
            $this->failed++;
            return;
        }

        $this->line(($dryRun ? '[dry-run] would encrypt: ' : 'encrypting: ') . "{$path} ({$label})");

        if (!$dryRun) {
            try {
                StorageHelper::put($path, Common::encryptFileBytes($bytes));
            } catch (\Throwable $e) {
                $this->warn("  failed to encrypt {$path} ({$label}): {$e->getMessage()}");
                $this->failed++;
                return;
            }
        }

        $this->encrypted++;
    }
}
