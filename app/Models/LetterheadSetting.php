<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Helpers\StorageHelper;

/**
 * Per-resort Letterhead & E-signature configuration.
 *
 * Consumed by document/letter PDFs (Transfer Letter today; Probation and
 * Promotion letters can adopt it via Common::getLetterheadData()).
 */
class LetterheadSetting extends Model
{
    use HasFactory;

    protected $table = 'letterhead_settings';
    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        self::saving(function ($model) {
            if (Auth::guard('resort-admin')->check()) {
                if (!$model->exists) {
                    $model->created_by = Auth::guard('resort-admin')->user()->id;
                }
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }

    public function resort()
    {
        return $this->belongsTo(Resort::class, 'resort_id', 'id');
    }

    /** Folder (on the StorageHelper disk, private) holding a resort's letterhead images. */
    public static function storageDir($resortId): string
    {
        return 'letterheads/' . $resortId;
    }

    /** Paths saved before the move to private storage pointed at public/uploads/letterheads/... */
    public static function isLegacyPublicPath(?string $path): bool
    {
        return !empty($path) && str_starts_with($path, 'uploads/letterheads/');
    }

    /**
     * Raw bytes of an image column: the private StorageHelper file, or the
     * old public/ file for a path not yet moved (letterhead:move-to-private).
     * Null when the column is empty or the file is missing.
     */
    public function imageBytes(string $column): ?string
    {
        $path = $this->{$column} ?? null;
        if (empty($path)) {
            return null;
        }
        try {
            if (self::isLegacyPublicPath($path)) {
                $abs = public_path($path);
                return is_file($abs) ? file_get_contents($abs) : null;
            }
            return StorageHelper::exists($path) ? StorageHelper::get($path) : null;
        } catch (\Throwable $e) {
            \Log::warning('Letterhead image read failed for ' . $path . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * base64 data: URI of an image column, for DomPDF (<img src>) and the
     * settings-page preview, so the file never needs a public URL.
     */
    public function imageDataUri(string $column): ?string
    {
        $bytes = $this->imageBytes($column);
        if ($bytes === null || $bytes === '') {
            return null;
        }
        $info = @getimagesizefromstring($bytes);
        $mime = is_array($info) ? ($info['mime'] ?? null) : null;
        if (!$mime) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: 'application/octet-stream';
        }
        return 'data:' . $mime . ';base64,' . base64_encode($bytes);
    }
}
