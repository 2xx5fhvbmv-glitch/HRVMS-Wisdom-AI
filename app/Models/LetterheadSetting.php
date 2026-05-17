<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

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

    /**
     * Absolute filesystem path of an image column, for DomPDF embedding.
     * Returns null when the column is empty or the file is missing.
     */
    public function imageAbsolutePath(string $column): ?string
    {
        $relative = $this->{$column} ?? null;
        if (empty($relative)) {
            return null;
        }
        $abs = public_path($relative);
        return is_file($abs) ? $abs : null;
    }

    /** Public URL of an image column (used in the config screen preview). */
    public function imageUrl(string $column): ?string
    {
        $relative = $this->{$column} ?? null;
        return empty($relative) ? null : asset($relative);
    }
}
