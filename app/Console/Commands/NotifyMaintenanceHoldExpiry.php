<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MaintanaceRequest;
use App\Helpers\Common;
use Carbon\Carbon;

/**
 * Daily — for each maintenance request On-Hold whose hold_until date has
 * been reached (or passed), flip it back to 'pending' for HR to review
 * again and notify HR + the requester.
 *
 * Idempotency: hold_expiry_notified_at is set on success, so this command
 * is safe to re-run within a day and across days (it catches up if the
 * scheduler missed a run). Cleared whenever a fresh On-Hold is set
 * (MaintananceContorller::MainRequestOnHold / AccommodationController::
 * handleMaintananceAction) so a request that gets held again later can
 * fire this notice again.
 */
class NotifyMaintenanceHoldExpiry extends Command
{
    protected $signature   = 'accommodation:notify-maintenance-hold-expiry';
    protected $description = 'Move On-Hold maintenance requests back to pending once hold_until is reached, and notify HR + the requester it is ready for review again';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $due = MaintanaceRequest::where('Status', 'On-Hold')
            ->whereDate('hold_until', '<=', $today)
            ->whereNull('hold_expiry_notified_at')
            ->get();

        if ($due->isEmpty()) {
            $this->info("No On-Hold maintenance requests due for review today ({$today}).");
            return self::SUCCESS;
        }

        foreach ($due as $request) {
            try {
                $request->update([
                    'Status'                  => 'pending',
                    'hold_expiry_notified_at' => now(),
                ]);

                $recipients = array_values(array_unique(array_filter(array_merge(
                    [$request->Raised_By],
                    Common::getResortHrEmployeeIds($request->resort_id)
                ))));

                if (!empty($recipients)) {
                    Common::notifyEmployees(
                        $request->resort_id,
                        $recipients,
                        'Maintenance Request Ready for Review',
                        "Maintenance request {$request->Request_id}'s hold period has ended and it is ready for review again.",
                        'Accommodation',
                        $request->id
                    );
                }

                $this->info("Reopened + notified maintenance request #{$request->id} ({$request->Request_id}).");
            } catch (\Throwable $e) {
                \Log::error('accommodation:notify-maintenance-hold-expiry failed for request #' . $request->id . ': ' . $e->getMessage());
                $this->error("Failed request #{$request->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
