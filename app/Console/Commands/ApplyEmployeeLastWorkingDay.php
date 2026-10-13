<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\AssingAccommodation;
use App\Http\Controllers\Resorts\People\Transfer\TransferController;
use App\Helpers\Common;
use Carbon\Carbon;

/**
 * Daily housekeeping for resignations.
 *
 * When an employee's `last_working_day` arrives:
 *   1. Flip their `employees.status` from Active to Inactive so they no
 *      longer appear in lists / approval pools / org chart.
 *   2. Adjust the Workforce Manning numbers for their dept + position
 *      (-1 filled, +1 vacant) so the Manning sub-module reflects the
 *      open seat. Reuses TransferController::adjustManningCount() — the
 *      same helper transfers use to keep filled/vacant in sync.
 *   3. Release their bed / room / villa once last_working_day has passed
 *      AND their Full & Final Settlement is finalized
 *      (Common::releaseAccommodationForEmployee, HR notified). If FFS is
 *      still open on the day they're deactivated, the unit is held and HR
 *      is told why; the next run after FFS is finalized releases it.
 *
 * Idempotent — step 1-2 only act on employees still marked Active, step 3
 * only on units still held. Running the command twice in a row is safe.
 */
class ApplyEmployeeLastWorkingDay extends Command
{
    protected $signature   = 'employees:apply-last-working-day';
    protected $description = 'Mark employees inactive, free their manning seat and release their accommodation when last_working_day arrives.';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $due = Employee::where('status', 'Active')
            ->whereNotNull('last_working_day')
            ->whereDate('last_working_day', '<=', $today)
            ->get(['id', 'resort_id', 'Dept_id', 'Position_id', 'Emp_id', 'last_working_day', 'Admin_Parent_id']);

        $processed = 0;
        foreach ($due as $emp) {
            try {
                $emp->status = 'Inactive';
                $emp->save();

                // Cut off mobile/web API access immediately (S4-01).
                try {
                    Common::revokeAllApiTokens($emp->resortAdmin);
                } catch (\Throwable $tokenErr) {
                    \Log::warning("revokeAllApiTokens failed for emp #{$emp->id}: " . $tokenErr->getMessage());
                }

                TransferController::adjustManningCount(
                    (int) $emp->resort_id,
                    (int) $emp->Dept_id,
                    (int) $emp->Position_id,
                    -1
                );

                $this->line(" Deactivated {$emp->Emp_id} (LWD {$emp->last_working_day}); manning freed.");
                $processed++;

                // Fires once: they're no longer Active on the next run.
                $this->notifyHrIfHeldPendingSettlement($emp);
            } catch (\Throwable $e) {
                \Log::error("ApplyEmployeeLastWorkingDay failed for emp #{$emp->id}: " . $e->getMessage());
                $this->error(" Failed for {$emp->Emp_id}: " . $e->getMessage());
            }
        }

        $this->info("Processed {$processed} of {$due->count()} due employees ({$today}).");

        $this->releaseAccommodation($today);
        return 0;
    }

    /**
     * Holders whose LWD has passed, who are no longer Active/Probationary
     * (guards a withdrawn resignation whose LWD wasn't cleared), and whose
     * FFS is finalized.
     */
    private function releaseAccommodation(string $today): void
    {
        $leavers = Employee::whereIn('id', AssingAccommodation::where('emp_id', '!=', 0)->select('emp_id'))
            ->whereNotIn('status', ['Active', 'Probationary'])
            ->whereNotNull('last_working_day')
            ->whereDate('last_working_day', '<=', $today)
            ->whereHas('finalSettlements', fn ($q) => $q->where('status', 'finalized'))
            ->get();

        foreach ($leavers as $emp) {
            try {
                $units = Common::releaseAccommodationForEmployee($emp);
                $this->line(" Released accommodation of {$emp->Emp_id}: " . implode(', ', $units));
            } catch (\Throwable $e) {
                // Status change above is already saved; the unit stays held
                // (transaction rolled back) and is retried on the next run.
                // ponytail: HR is re-notified each daily run until it succeeds.
                \Log::error("Accommodation release failed for emp #{$emp->id}: " . $e->getMessage());
                $this->error(" Accommodation release failed for {$emp->Emp_id}: " . $e->getMessage());
                $this->notifyHr($emp, 'Accommodation Not Released',
                    'could not be released automatically. Please unassign it manually.');
            }
        }
    }

    private function notifyHrIfHeldPendingSettlement(Employee $emp): void
    {
        $holdsUnit = AssingAccommodation::where('resort_id', $emp->resort_id)->where('emp_id', $emp->id)->exists();
        $ffsDone = $emp->finalSettlements()->where('status', 'finalized')->exists();
        if ($holdsUnit && !$ffsDone) {
            $this->notifyHr($emp, 'Accommodation Held - FFS Pending',
                'is held until their Full & Final Settlement is finalized; it will be released automatically after that.');
        }
    }

    private function notifyHr(Employee $emp, string $title, string $tail): void
    {
        try {
            $name = $emp->resortAdmin->full_name ?? ('Employee #' . $emp->Emp_id);
            Common::notifyEmployees(
                $emp->resort_id,
                Common::getResortHrEmployeeIds($emp->resort_id),
                $title,
                "{$name} has passed their last working day. Their accommodation {$tail}",
                'Accommodation',
                $emp->id,
                'accommodation-release-held'
            );
        } catch (\Throwable $e) {
            \Log::warning("ApplyEmployeeLastWorkingDay HR notice failed for emp #{$emp->id}: " . $e->getMessage());
        }
    }
}
