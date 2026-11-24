<?php

namespace App\Models;

use App\Helpers\Common;
use App\Helpers\StorageHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TaDocumentTemplate extends Model
{
    use HasFactory;

    protected $table = 'ta_document_templates';

    protected $fillable = [
        'resort_id', 'type', 'name', 'subject', 'content', 'file_path',
        'is_default', 'created_by', 'modified_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Blank offer/contract templates are private, same as the generated letters
     * (ApplicantOfferContract::storeFile): encrypted, resort folder, StorageHelper.
     */
    public static function storeFile(int $resortId, string $type, string $bytes): string
    {
        $folder = Resort::where('id', $resortId)->value('resort_id') ?: $resortId;
        $path = $folder . '/private/talent_acquisition/templates/'
            . $type . '_' . time() . '_' . Str::random(12) . '.docx.enc';
        StorageHelper::put($path, Common::encryptFileBytes($bytes));
        return $path;
    }

    /** Plain bytes, or null. *.enc = private StorageHelper disk; older rows = public disk. */
    public static function readFile(?string $path): ?string
    {
        return ApplicantOfferContract::readFile($path);
    }

    /**
     * Copies the template to a local temp .docx (DOCX filler needs a path). Caller unlinks it.
     * Null when the file is gone.
     */
    public function toTempFile(): ?string
    {
        $bytes = self::readFile($this->file_path);
        if ($bytes === null) {
            return null;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'tatpl_');
        @unlink($tmp);
        $tmp .= '.docx';
        file_put_contents($tmp, $bytes);
        return $tmp;
    }

    public function downloadName(): string
    {
        $name = trim(preg_replace('/[^A-Za-z0-9 _-]+/', '', (string) $this->name)) ?: 'Template';
        return str_replace(' ', '_', $name) . '.docx';
    }

    /** Streams the template as an attachment, or 404 when it is gone. */
    public function downloadResponse()
    {
        try {
            $bytes = self::readFile($this->file_path);
        } catch (\Throwable $e) {
            \Log::error('TA template file read failed for #' . $this->id . ': ' . $e->getMessage());
            $bytes = null;
        }
        abort_if($bytes === null, 404);

        return response($bytes, 200, [
            'Content-Type' => ApplicantOfferContract::mimeFor('docx'),
            'Content-Disposition' => 'attachment; filename="' . $this->downloadName() . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
