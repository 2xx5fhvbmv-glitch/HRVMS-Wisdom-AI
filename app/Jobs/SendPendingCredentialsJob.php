<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\ImportHistory;
use App\Models\Resort;
use App\Helpers\Common;

/**
 * People > Employees "send login credentials to everyone pending". Same
 * ImportHistory tracking row + resort.import.status polling as the Excel
 * import (ImportEmployeesJob): total_rows = selected, created_count = sent,
 * error_report = failures.
 */
class SendPendingCredentialsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $historyId,
        protected int $resortId,
        protected int $actingAdminId
    ) {
    }

    /**
     * Never-sent (empty password) Permanent employees of one resort. Only
     * ever empty passwords, so it can't reset a live login and a re-run
     * finds nobody left.
     */
    public static function pendingEmployees(int $resortId)
    {
        return Employee::with('resortAdmin')
            ->where('resort_id', $resortId)
            ->whereNotIn('status', ['Inactive', 'Terminated'])
            ->whereHas('resortAdmin', fn($q) => $q->whereNotNull('email')->where('email', '!=', '')
                ->where(fn($q) => $q->whereNull('password')->orWhere('password', '')))
            ->get()
            ->filter(fn($e) => Common::manningCategory($e->employment_type) === 'Permanent')
            ->values();
    }

    public function handle()
    {
        // Same reasons as ImportEmployeesJob: no HTTP session in the worker,
        // and the resort's own SMTP sender must be applied explicitly.
        Auth::guard('resort-admin')->loginUsingId($this->actingAdminId);
        Common::applyResortSmtpConfig($this->resortId);

        $history = ImportHistory::find($this->historyId);
        if (!$history) {
            return;
        }

        $resort = Resort::find($this->resortId);
        $employees = self::pendingEmployees($this->resortId);
        $history->update(['status' => 'processing', 'total_rows' => $employees->count()]);

        $sent = 0;
        $failures = [];
        foreach ($employees as $employee) {
            try {
                $result = Common::issueLoginCredentials($employee, $resort);
            } catch (\Exception $e) {
                $result = ['ok' => false, 'message' => $e->getMessage()];
            }
            if ($result['ok']) {
                $sent++;
            } else {
                $failures[] = ['employee_id' => $employee->id, 'Emp_id' => $employee->Emp_id, 'message' => $result['message']];
            }
            $history->update(['created_count' => $sent, 'error_report' => $failures]);
        }

        $history->update(['status' => 'completed']);
    }
}
