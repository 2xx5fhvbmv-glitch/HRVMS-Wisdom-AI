<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Centralized storage helper.
 *
 * All file/image upload, retrieval, deletion and presigned URL generation
 * should go through this class instead of calling Storage::disk('s3') directly.
 *
 * The active disk is selected by the STORAGE_DRIVER env var:
 *   - "wasabi" -> Wasabi (S3-compatible)
 *   - "s3"     -> AWS S3
 *   - "local"  -> local filesystem
 *
 * Defaults to "s3" for backward compatibility if the env is not set.
 */
class StorageHelper
{
    /** @var string|null */
    private static $cachedDriver = null;
    /** @var \Illuminate\Contracts\Filesystem\Filesystem|null */
    private static $cachedDisk = null;

    public static function driver(): string
    {
        // Read from config — env() returns null when prod runs
        // `php artisan config:cache`, which made every uploader silently
        // route to the 's3' default with bad / missing AWS_* keys
        // (InvalidAccessKeyId 403 on live). See config/settings.php where
        // 'storage_driver' freezes env('STORAGE_DRIVER') at cache time.
        return self::$cachedDriver ??= (string) config('settings.storage_driver');
    }

    public static function disk()
    {
        return self::$cachedDisk ??= Storage::disk(self::driver());
    }

    public static function isCloud(): bool
    {
        return in_array(self::driver(), ['s3', 'wasabi'], true);
    }

    public static function put(string $path, $contents, array $options = []): bool
    {
        return self::disk()->put($path, $contents, $options);
    }

    public static function get(string $path)
    {
        return self::disk()->get($path);
    }

    public static function exists(string $path): bool
    {
        return self::disk()->exists($path);
    }

    public static function delete($paths): bool
    {
        return self::disk()->delete($paths);
    }

    public static function move(string $from, string $to): bool
    {
        return self::disk()->move($from, $to);
    }

    public static function copy(string $from, string $to): bool
    {
        return self::disk()->copy($from, $to);
    }

    public static function allFiles(?string $directory = null): array
    {
        return self::disk()->allFiles($directory);
    }

    public static function allDirectories(?string $directory = null): array
    {
        return self::disk()->allDirectories($directory);
    }

    public static function mimeType(string $path)
    {
        return self::disk()->mimeType($path);
    }

    public static function size(string $path)
    {
        return self::disk()->size($path);
    }

    /**
     * Build a temporary (presigned) URL for cloud disks.
     * For local storage, falls back to Storage::url().
     */
    public static function temporaryUrl(string $path, int $minutes = 30)
    {
        if (self::isCloud()) {
            return self::disk()->temporaryUrl($path, now()->addMinutes($minutes));
        }
        return self::disk()->url($path);
    }

    public static function url(string $path)
    {
        return self::disk()->url($path);
    }

    /**
     * Upload a file (any type) to $basePath/<original-filename>.
     * Returns ['status' => bool, 'path' => string, 'filename' => string]
     */
    public static function uploadFile(string $basePath, UploadedFile $file): array
    {
        try {
            $newFileName = $file->getClientOriginalName();
            $filePath = rtrim($basePath, '/') . '/' . $newFileName;
            $contents = file_get_contents($file->getRealPath());

            if (self::driver() === 'local') {
                $disk = self::disk();
                $directory = dirname($disk->path($filePath));
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }
                $disk->put($filePath, $contents);
            } else {
                self::disk()->put($filePath, $contents);
            }

            return [
                'status'   => true,
                'path'     => $filePath,
                'filename' => $newFileName,
            ];
        } catch (\Exception $e) {
            \Log::error('StorageHelper::uploadFile failed: ' . $e->getMessage());
            return [
                'status' => false,
                'msg'    => 'Failed to upload file: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Upload an image. Same shape as uploadFile() — kept as a separate
     * entry point so callers can read intent at the call site, and so we
     * can add image-specific behaviour later (mime check, resize, etc.)
     * without touching every caller.
     */
    public static function uploadImage(string $basePath, UploadedFile $file): array
    {
        return self::uploadFile($basePath, $file);
    }
}
