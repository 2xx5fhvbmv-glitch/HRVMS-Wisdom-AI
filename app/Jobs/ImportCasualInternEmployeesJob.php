<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Imports\CasualInternEmployeeImport;
use App\Models\ImportHistory;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

/**
 * Mirrors ImportEmployeesJob exactly (queue worker has no HTTP session,
 * so the same Auth::guard('resort-admin')->loginUsingId() is needed for
 * every Auth-dependent helper on the create path) — the only difference
 * is which Import class runs. Unlike the Permanent path, no
 * ApplyResortSmtpConfig()/credential email is relevant here at all:
 * CasualInternEmployeeImport never sends one.
 */
class ImportCasualInternEmployeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $historyId,
        protected string $filePath,
        protected int $resortId,
        protected int $actingAdminId
    ) {
    }

    public function handle()
    {
        Auth::guard('resort-admin')->loginUsingId($this->actingAdminId);

        $history = ImportHistory::find($this->historyId);
        if (!$history) {
            return;
        }

        $history->update(['status' => 'processing']);

        try {
            $import = new CasualInternEmployeeImport();
            Excel::import($import, $this->filePath);

            $history->update([
                'status' => 'completed',
                'total_rows' => $import->rowNumber,
                'created_count' => $import->created,
                'updated_count' => $import->updated,
                'error_report' => $import->errors,
            ]);
        } catch (\Exception $e) {
            $history->update([
                'status' => 'failed',
                'failure_message' => $e->getMessage(),
                'error_report' => $import->errors ?? [],
            ]);
        } finally {
            if (File::exists($this->filePath)) {
                File::delete($this->filePath);
            }
        }
    }
}
