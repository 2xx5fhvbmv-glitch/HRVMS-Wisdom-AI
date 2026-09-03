<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Imports\EmployeeImport;
use App\Models\ImportHistory;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Helpers\Common;

class ImportEmployeesJob implements ShouldQueue
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
        // Queue workers run in a separate process with no HTTP session —
        // every existing Auth-dependent helper on the create path (folder
        // creation, created_by/modified_by model hooks) expects a logged-in
        // resort-admin guard, so authenticate it here rather than refactor
        // each of those helpers.
        Auth::guard('resort-admin')->loginUsingId($this->actingAdminId);

        // Same reasoning for outbound mail: ApplyResortSmtpConfig only runs
        // as HTTP middleware, so the welcome-credentials email sent for
        // each imported row would otherwise fall back to the app-wide
        // mail config instead of this resort's own configured sender
        // (Settings > Email Config) — apply it explicitly here.
        Common::applyResortSmtpConfig($this->resortId);

        $history = ImportHistory::find($this->historyId);
        if (!$history) {
            return;
        }

        $history->update(['status' => 'processing']);

        try {
            $import = new EmployeeImport();
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
