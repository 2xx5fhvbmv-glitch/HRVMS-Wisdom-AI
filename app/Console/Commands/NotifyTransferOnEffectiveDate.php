<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmployeeTransfer;
use App\Http\Controllers\Resorts\People\Transfer\TransferController;
use Carbon\Carbon;

/**
 * Fires the "transfer takes effect today" notifications for any Approved
 * EmployeeTransfer whose effective_date is today and that has not yet been
 * notified (effective_day_notified_at IS NULL).
 *
 * Notifies: the employee, current dept HOD/EXCOM, target dept HOD/EXCOM,
 * and the HR dept HOD/EXCOM. Idempotent — the per-transfer timestamp
 * column prevents duplicate notifications if the command runs multiple
 * times in a day.
 */
class NotifyTransferOnEffectiveDate extends Command
{
    protected $signature   = 'transfers:notify-effective';
    protected $description = "Notify the employee + dept leads that a transfer takes effect today";

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        // ── Part A: apply pending transfers + day-of notifications ──────
        // <= today catches anything the scheduler may have missed on a
        // previous day, so the profile move + notifications never get
        // permanently skipped.
        $dueTransfers = EmployeeTransfer::where('status', 'Approved')
            ->whereDate('effective_date', '<=', $today)
            ->whereNull('effective_day_notified_at')
            ->get();

        if ($dueTransfers->isEmpty()) {
            $this->info("No transfers to apply/notify for today ({$today}).");
        }

        foreach ($dueTransfers as $transfer) {
            try {
                TransferController::dispatchEffectiveDateNotifications($transfer);
                $this->info("Applied + notified transfer #{$transfer->id} (employee {$transfer->employee_id}).");
            } catch (\Throwable $e) {
                \Log::error('transfers:notify-effective failed for transfer #' . $transfer->id . ': ' . $e->getMessage());
                $this->error("Failed transfer #{$transfer->id}: {$e->getMessage()}");
            }
        }

        // ── Part B: revert temporary transfers whose window has ended ───
        // A temp transfer reverts the day AFTER temporary_to — so the
        // employee gets the full last day in the temp dept, then is back
        // in their original dept on the following day. The scheduler
        // catches up if it missed a day (temporary_to < today).
        $expiredTemps = EmployeeTransfer::where('status', 'Approved')
            ->where('transfer_status', 'Temporary')
            ->whereNotNull('temporary_to')
            ->whereNotNull('effective_day_notified_at') // was actually applied
            ->whereNull('reverted_at')
            ->whereDate('temporary_to', '<', $today)
            ->get();

        if ($expiredTemps->isEmpty()) {
            $this->info("No temporary transfers to revert today ({$today}).");
        }

        foreach ($expiredTemps as $transfer) {
            try {
                TransferController::revertTemporaryTransfer($transfer);
                $this->info("Reverted temporary transfer #{$transfer->id} (employee {$transfer->employee_id}).");
            } catch (\Throwable $e) {
                \Log::error('transfers:notify-effective revert failed for transfer #' . $transfer->id . ': ' . $e->getMessage());
                $this->error("Revert failed for transfer #{$transfer->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
