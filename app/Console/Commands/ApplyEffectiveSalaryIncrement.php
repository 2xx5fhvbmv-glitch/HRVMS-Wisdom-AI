<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PeopleSalaryIncrement;
use App\Http\Controllers\Resorts\People\SalaryIncrementController;
use Carbon\Carbon;

/**
 * Daily — apply every Approved salary increment whose effective_date has
 * arrived (today or earlier) and that hasn't already been applied
 * (effective_day_applied_at IS NULL).
 *
 * Mirrors NotifyPromotionOnEffectiveDate: the day-of run is what actually
 * mutates the employee row, so updateStatus() can leave future-dated
 * Approved increments alone at approval time.
 *
 * Idempotency comes from `effective_day_applied_at` — the column is
 * stamped on success inside applyApprovedIncrementToEmployee(), so a
 * re-run on the same day (or catch-up after a missed day) won't
 * double-apply.
 */
class ApplyEffectiveSalaryIncrement extends Command
{
    protected $signature   = 'salary-increment:apply-effective';
    protected $description = 'Apply approved salary increments on their effective date';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $due = PeopleSalaryIncrement::where('status', 'Approved')
            ->whereDate('effective_date', '<=', $today)
            ->whereNull('effective_day_applied_at')
            ->get();

        if ($due->isEmpty()) {
            $this->info("No salary increments to apply for today ({$today}).");
            return self::SUCCESS;
        }

        $applied = 0;
        foreach ($due as $increment) {
            try {
                $ok = SalaryIncrementController::applyApprovedIncrementToEmployee($increment);
                if ($ok) {
                    $applied++;
                    $this->info("Applied salary increment #{$increment->id} (employee {$increment->employee_id}).");
                } else {
                    $this->warn("Skipped #{$increment->id} — employee missing or already applied.");
                }
            } catch (\Throwable $e) {
                \Log::error('salary-increment:apply-effective failed for #' . $increment->id . ': ' . $e->getMessage());
                $this->error("Failed increment #{$increment->id}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Applied {$applied} of {$due->count()} due increments.");
        return self::SUCCESS;
    }
}
