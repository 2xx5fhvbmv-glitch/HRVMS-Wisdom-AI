<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Common;
use App\Helpers\StorageHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplicantOfferContract extends Model
{
    use HasFactory;

    protected $table = 'applicant_offer_contracts';

    /** Life of every public candidate link (offer, contract, interview, consent, availability). */
    public const TOKEN_TTL_DAYS = 30;
    public const EXPIRED_MESSAGE = 'This link has expired, please contact HR.';

    protected $fillable = [
        'applicant_id',
        'applicant_status_id',
        'resort_id',
        'type',
        'file_path',
        'status',
        'token',
        'rejection_reason',
        'email_template_id',
        'sent_by',
        'responded_at',
        'ta_document_template_id',
        'generated_html',
        'token_expires_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'token_expires_at' => 'datetime',
    ];

    public static function tokenExpiry()
    {
        return now()->addDays(self::TOKEN_TTL_DAYS);
    }

    /** True when a token's expiry (a datetime or string) has passed; null = never set. */
    public static function expired($expiresAt): bool
    {
        return $expiresAt !== null && $expiresAt !== '' && now()->gt($expiresAt);
    }

    public function isTokenExpired(): bool
    {
        return self::expired($this->token_expires_at);
    }

    /**
     * N18: offer letters / contracts are private. Encrypted (Common::encryptFileBytes, same as
     * File Management) under the resort's folder through StorageHelper; returns the stored path.
     */
    public static function storeFile(int $resortId, string $type, int $applicantId, string $bytes, string $ext): string
    {
        $folder = Resort::where('id', $resortId)->value('resort_id') ?: $resortId;
        $dir = $type === 'contract' ? 'contracts' : 'offer_letters';
        $path = $folder . '/private/talent_acquisition/' . $dir . '/'
            . $type . '_' . time() . '_' . $applicantId . '_' . Str::random(12) . '.' . $ext . '.enc';
        StorageHelper::put($path, Common::encryptFileBytes($bytes));
        return $path;
    }

    /**
     * Plain bytes of a stored file, or null. New files (*.enc) live encrypted on the StorageHelper
     * disk; rows written before N18 still point at the old public disk and are read from there.
     */
    public static function readFile(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (str_ends_with($path, '.enc')) {
            return StorageHelper::exists($path) ? Common::decryptFileBytes(StorageHelper::get($path)) : null;
        }
        return Storage::disk('public')->exists($path) ? Storage::disk('public')->get($path) : null;
    }

    /** Extension of the real document (docx / pdf), ignoring the .enc suffix. */
    public static function fileExtension(?string $path): string
    {
        return strtolower(pathinfo(preg_replace('/\.enc$/', '', (string) $path), PATHINFO_EXTENSION) ?: 'pdf');
    }

    public static function mimeFor(string $ext): string
    {
        return $ext === 'docx'
            ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            : 'application/pdf';
    }

    public function downloadName(): string
    {
        return ($this->type === 'contract' ? 'Employment_Contract' : 'Offer_Letter') . '.' . self::fileExtension($this->file_path);
    }

    /** Streams the file as an attachment, or 404 when it is gone. */
    public function downloadResponse()
    {
        try {
            $bytes = self::readFile($this->file_path);
        } catch (\Throwable $e) {
            \Log::error('Offer/contract file read failed for #' . $this->id . ': ' . $e->getMessage());
            $bytes = null;
        }
        abort_if($bytes === null, 404);

        return response($bytes, 200, [
            'Content-Type' => self::mimeFor(self::fileExtension($this->file_path)),
            'Content-Disposition' => 'attachment; filename="' . $this->downloadName() . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant_form_data::class, 'applicant_id');
    }

    public function applicantStatus()
    {
        return $this->belongsTo(ApplicantWiseStatus::class, 'applicant_status_id');
    }

    public function sentByUser()
    {
        return $this->belongsTo(ResortAdmin::class, 'sent_by');
    }

    public function emailTemplate()
    {
        return $this->belongsTo(TaEmailTemplate::class, 'email_template_id');
    }
}
