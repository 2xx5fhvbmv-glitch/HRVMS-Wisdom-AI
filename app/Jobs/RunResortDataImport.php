<?php

namespace App\Jobs;

use App\Models\ResortDataImport;
use App\Services\ResortDataSetup\ResortDataImporter;
use App\Services\ResortDataSetup\SheetMapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Resort Data Setup work that can outgrow a web request:
 *   analyse  — read uploaded files, detect their layout (saved mapping, else AI), count records
 *   validate — full dry run, rolled back
 *   import   — the same run, committed (all or nothing)
 *
 * Safe against a second pickup: the database queue's retry_after (90s) is
 * shorter than a large import, so a worker can reserve this job again while
 * it is still running. The atomic queued → running claim makes that second
 * run a no-op; unlimited tries keep it from being marked failed instead.
 */
class RunResortDataImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 1800;
    public $tries = 0;

    public function __construct(public int $importId)
    {
    }

    public function handle(): void
    {
        $claimed = ResortDataImport::where('id', $this->importId)->where('job_status', 'queued')
            ->update(['job_status' => 'running', 'job_started_at' => now()]);
        if (!$claimed) {
            return;
        }
        $import = ResortDataImport::findOrFail($this->importId);
        @ini_set('memory_limit', '1024M');

        try {
            $message = match ($import->job_action) {
                'analyse'  => $this->analyse($import),
                'validate' => $this->run($import, false),
                'import'   => $this->run($import, true),
            };
            $import->forceFill(['job_status' => 'done', 'job_message' => $message])->save();
        } catch (\Throwable $e) {
            Log::error('Resort data setup job failed', ['import_id' => $this->importId, 'action' => $import->job_action,
                'error' => $e->getMessage(), 'at' => $e->getFile() . ':' . $e->getLine()]);
            $import->forceFill(['job_status' => 'failed', 'job_message' => 'Failed: ' . $e->getMessage() . ' — nothing was saved.'])->save();
        }
    }

    /** Files flagged pending: apply the admin's requested mapping, else the saved/AI one. */
    private function analyse(ResortDataImport $import): string
    {
        $messages = [];
        $files = $import->files ?? [];
        foreach ($files as $i => $file) {
            if (empty($file['pending'])) {
                continue;
            }
            unset($file['pending']);
            try {
                $rows = SheetMapper::readRows($file['path']);
            } catch (\Throwable $e) {
                $file['note'] = 'Could not be read as a spreadsheet: ' . $e->getMessage();
                $files[$i] = $file;
                $messages[] = "{$file['name']}: could not be read.";
                continue;
            }

            if ($requested = $file['requested'] ?? null) {
                unset($file['requested']);
                [$mapping, $error] = SheetMapper::fromIndexes($rows, $requested['type'], $requested['header_row'], $requested['columns'], $requested['group_column']);
                if ($mapping) {
                    SheetMapper::remember($mapping, 'manual');
                    $file = array_merge($file, ['mapping' => $mapping, 'source' => 'manual', 'note' => null]);
                    $messages[] = "{$file['name']}: mapping saved and remembered for this layout.";
                } else {
                    $file['note'] = $error;
                    $messages[] = "{$file['name']}: mapping not saved — {$error}";
                }
            } else {
                $guess = SheetMapper::guess($rows, $file['name']);
                $file = array_merge($file, ['source' => $guess['source'], 'tokens' => $guess['tokens'], 'note' => $guess['error'], 'mapping' => $guess['mapping']]);
                $messages[] = $guess['mapping']
                    ? "{$file['name']}: recognised as {$guess['mapping']['type']}" . ($guess['source'] === 'cache' ? ' (saved layout, 0 AI tokens).' : " (AI, {$guess['tokens']} tokens).")
                    : "{$file['name']}: {$guess['error']}";
            }

            $files[$i] = SheetMapper::describe($file, $rows);
            unset($rows);
            // Save per file so the page shows progress on a long batch.
            $import->files = $files;
            $import->save();
        }
        return implode("\n", $messages);
    }

    private function run(ResortDataImport $import, bool $commit): string
    {
        $report = (new ResortDataImporter)->run($import, $commit);
        $credentials = $report['credentials'];
        $report['credentials'] = count($credentials); // passwords live only in the encrypted column
        $import->report = $report;

        if (!$commit) {
            return $report['errors'] ? 'Validation found ' . count($report['errors']) . ' error(s) — see below.' : 'Validation passed — nothing was saved.';
        }
        if (!$report['committed']) {
            return 'Nothing was imported — fix the errors below and try again.';
        }
        $import->status = 'imported';
        $import->imported_at = now();
        $import->credentials = $credentials ?: null;
        Log::info('Resort data setup imported', ['import_id' => $import->id, 'resort_id' => $import->resort_id, 'counts' => $report['counts']]);
        return 'Import finished.' . ($credentials ? ' Download the new logins below, then clear them.' : '');
    }
}
