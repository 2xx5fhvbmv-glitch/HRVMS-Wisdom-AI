<?php

namespace App\Services\Wisdom;

use App\Helpers\Common;
use App\Helpers\StorageHelper;
use App\Jobs\SyncCompanyPolicies;
use App\Models\ChildFileManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Company policy documents for Wisdom AI. HR uploads them into a "Company Policies" folder in File Management;
 * the AI service (HRVMS-Wisdom-AI-Code, /policies) indexes each resort's files separately and searches them
 * together with the shared employment-law library. This class keeps that index in line with the folder and
 * runs the searches.
 */
class PolicyLibrary
{
    /** Folder names HR may give the policy folder (compared trimmed and lower-cased). */
    const FOLDER_NAMES = ['company policies', 'company policy', 'policies'];

    /** The resort's policy folder(s) and their direct sub-folders. */
    public static function folderIds(int $resortId): array
    {
        $roots = DB::table('filemangement_systems')->where('resort_id', $resortId)->where('Folder_Type', 'uncategorized')
            ->whereIn(DB::raw('LOWER(TRIM(Folder_Name))'), self::FOLDER_NAMES)->pluck('id')->all();
        if (!$roots) {
            return [];
        }
        return array_merge($roots, DB::table('filemangement_systems')->where('resort_id', $resortId)->whereIn('UnderON', $roots)->pluck('id')->all());
    }

    /** Called on every file create / move / delete: re-sync that resort when a policy folder is involved. */
    public static function fileChanged(ChildFileManagement $file): void
    {
        try {
            $folders = array_filter([(int) $file->Parent_File_ID, (int) $file->getOriginal('Parent_File_ID')]);
            if (array_intersect($folders, self::folderIds((int) $file->resort_id))) {
                SyncCompanyPolicies::dispatch((int) $file->resort_id)->afterCommit();
            }
        } catch (\Throwable $e) {
            // An upload or delete must never fail because of the assistant's index; the hourly sync catches up.
            Log::warning('Company policy sync not queued: ' . $e->getMessage());
        }
    }

    /** Bring the AI index in line with the folder: index files it doesn't have, drop files that are gone. */
    public static function sync(int $resortId): array
    {
        // A re-upload with the same name keeps the old row as a version; only the newest is the policy.
        $files = ChildFileManagement::where('resort_id', $resortId)->whereIn('Parent_File_ID', self::folderIds($resortId) ?: [0])
            ->orderByDesc('id')->get(['id', 'Parent_File_ID', 'File_Name', 'File_Path', 'is_secure'])
            ->unique(fn ($f) => $f->Parent_File_ID . '|' . mb_strtolower(trim($f->File_Name)));
        $indexed = collect(self::http()->get("/policies/{$resortId}")->throw()->json('documents') ?? [])->keyBy('file_id');

        $result = ['indexed' => 0, 'removed' => 0, 'failed' => []];
        foreach ($files as $f) {
            if ($indexed->has($f->id)) {
                continue;
            }
            try {
                $bytes = StorageHelper::disk()->get($f->File_Path);
                if ($f->is_secure) {
                    $bytes = Common::decryptFileBytes($bytes);
                }
                $chunks = self::http()->attach('file', $bytes, $f->File_Name)
                    ->post('/policies/index', ['resort_id' => $resortId, 'file_id' => $f->id, 'file_name' => $f->File_Name])
                    ->throw()->json('chunks');
                $result['indexed']++;
                if (!$chunks) {
                    $result['failed'][] = "{$f->File_Name}: no readable text (a scanned PDF needs a text version)";
                }
            } catch (\Throwable $e) {
                $result['failed'][] = "{$f->File_Name}: " . $e->getMessage();
                Log::warning("Company policy not indexed (resort {$resortId}, file {$f->id}): " . $e->getMessage());
            }
        }
        foreach ($indexed->keys()->diff($files->pluck('id')) as $fileId) {
            self::http()->delete("/policies/{$resortId}/{$fileId}")->throw();
            $result['removed']++;
        }
        return $result;
    }

    /** Passages from the resort's own policies and from the employment-law library, with document and page. */
    public static function search(int $resortId, string $question, int $k = 4): array
    {
        return self::http()->timeout(30)->post('/policies/search', ['resort_id' => $resortId, 'query' => $question, 'k' => $k])->throw()->json();
    }

    private static function http()
    {
        return Http::baseUrl(rtrim((string) config('services.ai_service.url'), '/'))
            ->withHeaders(['X-Internal-Key' => (string) config('services.wisdom_ai.internal_key')])
            ->timeout(180);
    }
}
