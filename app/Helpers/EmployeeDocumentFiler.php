<?php

namespace App\Helpers;

use App\Models\ApplicantOfferContract;
use App\Models\FilemangementSystem;
use App\Models\ChildFileManagement;
use App\Models\Resort;
use Illuminate\Support\Facades\Storage;

/**
 * Files an applicant's accepted offer letter and contract into the new
 * employee's File Management folder, mirroring the proven transfer-letter
 * flow (AES-256 encryption + a ChildFileManagement row under the Emp_id
 * categorized folder).
 *
 * Shared because an applicant can become an employee through several paths
 * (contract acceptance, the Onboarding screen, offline-interview conversion),
 * and each should file the same documents. Every method is best-effort and
 * idempotent — it never throws, and never files a second copy — so it is safe
 * to call after employee creation in any of those flows.
 */
class EmployeeDocumentFiler
{
    /**
     * File the offer letter + contract for $applicantId into $employee's folder.
     */
    public static function fileApplicantOnboardingDocs($employee, $applicantId): void
    {
        if (!$employee || empty($applicantId)) {
            return;
        }

        // type (in applicant_offer_contracts) => display name in file manager
        $docs = [
            'contract'     => 'Contract',
            'offer_letter' => 'Offer Letter',
        ];

        foreach ($docs as $type => $displayName) {
            $record = ApplicantOfferContract::where('applicant_id', $applicantId)
                ->where('type', $type)
                ->whereNotNull('file_path')
                ->latest('id')
                ->first();

            if ($record && !empty($record->file_path)) {
                self::fileDocToEmployeeFolder($employee, $record->file_path, $displayName);
            }
        }
    }

    /**
     * Encrypt a stored document (on the 'public' disk) and attach it to the
     * employee's categorized File Management folder. Never throws.
     */
    public static function fileDocToEmployeeFolder($employee, string $sourceRelativePath, string $displayName): void
    {
        try {
            // The employee's categorized root folder (created by the Employee
            // model `created` hook — named after Emp_id, UnderON 0).
            $folder = FilemangementSystem::where('resort_id', $employee->resort_id)
                ->where('Folder_Name', $employee->Emp_id)
                ->where('Folder_Type', 'categorized')
                ->where('UnderON', 0)
                ->first();

            if (!$folder) {
                \Log::warning("Onboarding doc: no File Management folder for employee {$employee->Emp_id}");
                return;
            }

            // Idempotency — don't file a second copy if it's already there.
            $already = ChildFileManagement::where('Parent_File_ID', $folder->id)
                ->where('File_Name', $displayName)
                ->exists();
            if ($already) {
                return;
            }

            if (!Storage::disk('public')->exists($sourceRelativePath)) {
                \Log::warning("Onboarding doc: source file missing ({$sourceRelativePath}) for employee {$employee->Emp_id}");
                return;
            }
            $binary = Storage::disk('public')->get($sourceRelativePath);
            $ext    = strtolower(pathinfo($sourceRelativePath, PATHINFO_EXTENSION) ?: 'pdf');

            $resortFolder = optional(Resort::find($employee->resort_id))->resort_id;

            $uniqueString = substr(md5(uniqid('onboarding-doc', true)), 0, 10);
            $newFileName  = $uniqueString . '.' . $ext . '.enc';
            $path = $resortFolder . '/public/categorized/' . $folder->Folder_unique_id . '/' . $newFileName;

            // AES-256-CBC — identical scheme to FileManageController / the
            // transfer-letter flow, so the file manager can decrypt it.
            $key = hash('sha256', env('ENCRYPTION_KEY'), true);
            $iv  = random_bytes(16);
            $encrypted = $iv . openssl_encrypt($binary, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

            if ($encrypted === false) {
                throw new \Exception('Encryption failed: ' . openssl_error_string());
            }

            StorageHelper::disk()->put($path, $encrypted, [
                'ContentType'        => 'application/octet-stream',
                'ContentDisposition' => 'attachment; filename="' . $displayName . '.' . $ext . '"',
            ]);

            ChildFileManagement::create([
                'resort_id'      => $employee->resort_id,
                'unique_id'      => $uniqueString,
                'Parent_File_ID' => $folder->id,
                'File_Name'      => $displayName,
                'NewFileName'    => $displayName,
                'File_Type'      => $ext,
                'File_Size'      => round(strlen($binary) / 1024, 2),
                'File_Path'      => $path,
                'File_Extension' => $ext,
            ]);
        } catch (\Throwable $e) {
            \Log::error("Onboarding doc filing failed for employee {$employee->Emp_id} ({$displayName}): " . $e->getMessage());
        }
    }
}
