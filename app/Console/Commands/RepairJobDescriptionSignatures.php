<?php

namespace App\Console\Commands;

use App\Helpers\Common;
use App\Helpers\StorageHelper;
use App\Models\Employee;
use App\Models\JobDescriptionEmployeeRecord;
use Illuminate\Console\Command;

/**
 * One-time repair for job descriptions issued before the employer signature got its own file.
 *
 * Until the issue step moved to the 'job_description_employer' context, the employer signature was snapshotted to
 * {resort}/public/approvals/job_description/{id}/signature.png — the same file the employee's mobile sign-off wrote
 * to. So on every record signed since, the employer block shows the employee's signature.
 *
 *  - Signed, both paths equal: that file now holds the EMPLOYEE's signature (keep it as theirs). The employer's
 *    original is gone, so take a fresh snapshot of the signatory director's current profile signature into its own
 *    file, then rebuild the PDF.
 *  - Pending, employer path still the shared file: it still holds the employer's original — copy it to the employer's
 *    own file before anyone signs (the sign-off now writes to 'job_description_employee').
 *
 *   php artisan job-description:repair-signatures --dry-run
 *   php artisan job-description:repair-signatures
 */
class RepairJobDescriptionSignatures extends Command
{
    protected $signature = 'job-description:repair-signatures {--dry-run : List what would change without writing}';

    protected $description = 'Give job descriptions issued before the fix a separate employer signature file and rebuild signed PDFs';

    public function handle()
    {
        $dry = (bool) $this->option('dry-run');
        $fixed = $failed = 0;

        $records = JobDescriptionEmployeeRecord::whereNotNull('employer_signature_path')
            ->where('employer_signature_path', 'like', '%/approvals/job_description/%')
            ->get();

        foreach ($records as $r) {
            $shared = $r->employer_signature_path === $r->employee_signature_path;
            $label = "JD record #{$r->id} ({$r->status})";
            try {
                if ($r->status === 'Signed' || $shared) {
                    // Original employer signature was overwritten: re-snapshot the signatory director's signature.
                    $director = $r->employer_signatory_employee_id ? Employee::find($r->employer_signatory_employee_id) : null;
                    $fallback = !$director || !$director->Admin_Parent_id;
                    if ($fallback) {
                        // Older records have no (or a since-removed) signatory: use the resort's current HR Director,
                        // the same person the issue step picks today, and put their details on the record too so the
                        // PDF never shows one person's name over another's signature.
                        $director = Common::resolveHrDirector($r->resort_id)['employee'] ?? null;
                        if (!$director || !$director->Admin_Parent_id) {
                            throw new \RuntimeException('no signatory on the record and no HR Director configured for the resort');
                        }
                    }
                    $this->line(($dry ? '[dry-run] ' : '') . "{$label}: new employer signature from "
                        . ($fallback ? "the resort's HR Director (employee #{$director->id}), signatory details updated" : 'the signatory director') . ', rebuild PDF');
                    if ($dry) {
                        continue;
                    }
                    $sig = Common::snapshotSignature($director->Admin_Parent_id, 'job_description_employer', $r->id);
                    if (empty($sig['signature_img']) || !Common::signatureImageDataUri($sig['signature_img'])) {
                        throw new \RuntimeException('director has no usable signature');
                    }
                    $r->employer_signature_path = $sig['signature_img'];
                    if ($fallback) {
                        $admin = $director->resortAdmin;
                        $r->employer_signatory_employee_id     = $director->id;
                        $r->employer_signatory_name            = trim(collect([optional($admin)->first_name, optional($admin)->middle_name, optional($admin)->last_name])->filter()->implode(' ')) ?: $director->Emp_id;
                        $r->employer_signatory_designation     = optional($director->position)->position_title;
                        $r->employer_signatory_id_card_number  = $director->nid ?: $director->passport_number;
                    }
                    $r->save();
                    Common::generateAndStoreJobDescriptionPdf($r);
                } else {
                    // Not signed yet: the shared file still holds the employer's own signature — move it out of the way.
                    $target = str_replace('/approvals/job_description/', '/approvals/job_description_employer/', $r->employer_signature_path);
                    $this->line(($dry ? '[dry-run] ' : '') . "{$label}: copy employer signature to its own file");
                    if ($dry) {
                        continue;
                    }
                    StorageHelper::copy($r->employer_signature_path, $target);
                    $r->employer_signature_path = $target;
                    $r->save();
                }
                $fixed++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error("{$label}: {$e->getMessage()}");
            }
        }

        $this->info(($dry ? '[dry-run] ' : '') . "Records found: {$records->count()}, repaired: {$fixed}, failed: {$failed}");
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
