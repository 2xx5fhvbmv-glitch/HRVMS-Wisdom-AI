<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmployeePromotion;
use App\Http\Controllers\Resorts\People\Promotion\PromotionController;
use Carbon\Carbon;

/**
 * Daily — for each Approved EmployeePromotion whose effective_date is today
 * (or earlier and not yet applied), apply the position/rank/salary change to
 * the employee and fan out the "promotion takes effect today" notifications.
 *
 * Idempotency: effective_day_notified_at is set on success, so this command
 * is safe to re-run within a day and across days (it catches up if the
 * scheduler missed a run).
 */
class NotifyPromotionOnEffectiveDate extends Command
{
    protected $signature   = 'promotions:notify-effective';
    protected $description = "Apply approved promotions on their effective date + notify employee/HOD/HR/GM";

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $due = EmployeePromotion::where('status', 'Approved')
            ->whereDate('effective_date', '<=', $today)
            ->whereNull('effective_day_notified_at')
            ->get();

        if ($due->isEmpty()) {
            $this->info("No promotions to apply/notify for today ({$today}).");
            return self::SUCCESS;
        }

        foreach ($due as $promotion) {
            try {
                PromotionController::dispatchEffectiveDateNotifications($promotion);
                $this->info("Applied + notified promotion #{$promotion->id} (employee {$promotion->employee_id}).");
            } catch (\Throwable $e) {
                \Log::error('promotions:notify-effective failed for promotion #' . $promotion->id . ': ' . $e->getMessage());
                $this->error("Failed promotion #{$promotion->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
