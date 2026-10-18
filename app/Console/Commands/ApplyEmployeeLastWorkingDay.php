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
 * When an employee's last working day arrives (their Approved
 * resignation's `last_working_day`, or `employees.last_working_day`):
 *   1. Flip their `employees.status` from Active, Offboarding (accepted
 *      resignation) or Terminated (exit clearance done) to Inactive — the
 *      single end status (founder, 2026-10-05) — so they no longer appear
 *      in lists / approval pools / org chart.
 *   2. Adjust the Workforce Manning numbers for their dept + position
 *      (-1 filled, +1 vacant) so the Manning sub-module reflects the
 *      open seat. Reuses TransferController::adjustManningCount() — the
 *      same helper transfers use to keep filled/vacant in sync.
 *   3. If they still hold a bed / room / villa and FFS is not finalized,
 *      tell HR it is held. Releasing is HR's Yes/Keep decision, prompted
 *      by accommodation:release-follow-up once FFS is finalized
 *      (founder, 2026-10-05) — never automatic.
 *
 * Idempotent — only acts on employees not yet Inactive. Running the
 * command twice in a row is safe.
 */
class ApplyEmployeeLastWorkingDay extends Command
{
    protected $signature   = 'employees:apply-last-working-day';
    protected $description = 'Mark employees inactive, free their manning seat and release their accommodation when last_working_day arrives.';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $due = Employee::whereIn('status', ['Active', 'Offboarding', 'Terminated'])
            ->where(fn ($q) => $this->lastWorkingDayReached($q, $today))
            // A withdrawal still waiting on HOD/HR holds the Inactive change until it is decided
            // (approved → Active again; rejected → this run picks them up). Founder, 2026-10-15.
            ->whereDoesntHave('resignation', fn ($r) => $r->where(fn ($w) => $w
                ->where('withdraw_hod_status', 'Pending')
                ->orWhere(fn ($h) => $h->where('withdraw_hod_status', 'Approved')->where('withdraw_hr_status', 'Pending'))))
            ->get(['id', 'resort_id', 'Dept_id', 'Position_id', 'Emp_id', 'Admin_Parent_id']);

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

                $this->line(" Deactivated {$emp->Emp_id}; manning freed.");
                $processed++;

                // Fires once: they're Inactive on the next run.
                $this->notifyHrIfHeldPendingSettlement($emp);
            } catch (\Throwable $e) {
                \Log::error("ApplyEmployeeLastWorkingDay failed for emp #{$emp->id}: " . $e->getMessage());
                $this->error(" Failed for {$emp->Emp_id}: " . $e->getMessage());
            }
        }

        $this->info("Processed {$processed} of {$due->count()} due employees ({$today}).");

        return 0;
    }

    /**
     * LWD lives on the Approved resignation (employee_resignation) — the
     * app never writes employees.last_working_day, which is still honoured
     * if set. A Withdrawn/Pending resignation never matches.
     */
    private function lastWorkingDayReached($q, string $today): void
    {
        $q->whereDate('last_working_day', '<=', $today)
          ->orWhereHas('resignation', fn ($r) => $r->whereDate('last_working_day', '<=', $today));
    }

    private function notifyHrIfHeldPendingSettlement(Employee $emp): void
    {
        $holdsUnit = AssingAccommodation::where('resort_id', $emp->resort_id)->where('emp_id', $emp->id)->exists();
        $ffsDone = $emp->finalSettlements()->where('status', 'finalized')->exists();
        if ($holdsUnit && !$ffsDone) {
            $this->notifyHr($emp, 'Accommodation Held - FFS Pending',
                'is held until their Full & Final Settlement is finalized; HR will then be asked whether to vacate it.');
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
