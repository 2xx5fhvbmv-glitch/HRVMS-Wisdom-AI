<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Http\Controllers\Resorts\People\Transfer\TransferController;
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
 *
 * Idempotent — only acts on employees still marked Active. Running the
 * command twice in a row is safe.
 */
class ApplyEmployeeLastWorkingDay extends Command
{
    protected $signature   = 'employees:apply-last-working-day';
    protected $description = 'Mark employees inactive + free their manning seat when last_working_day arrives.';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $due = Employee::where('status', 'Active')
            ->whereNotNull('last_working_day')
            ->whereDate('last_working_day', '<=', $today)
            ->get(['id', 'resort_id', 'Dept_id', 'Position_id', 'Emp_id', 'last_working_day']);

        if ($due->isEmpty()) {
            $this->info("No employees due to be deactivated today ({$today}).");
            return 0;
        }

        $processed = 0;
        foreach ($due as $emp) {
            try {
                $emp->status = 'Inactive';
                $emp->save();

                TransferController::adjustManningCount(
                    (int) $emp->resort_id,
                    (int) $emp->Dept_id,
                    (int) $emp->Position_id,
                    -1
                );

                $this->line(" Deactivated {$emp->Emp_id} (LWD {$emp->last_working_day}); manning freed.");
                $processed++;
            } catch (\Throwable $e) {
                \Log::error("ApplyEmployeeLastWorkingDay failed for emp #{$emp->id}: " . $e->getMessage());
                $this->error(" Failed for {$emp->Emp_id}: " . $e->getMessage());
            }
        }

        $this->info("Processed {$processed} of {$due->count()} due employees.");
        return 0;
    }
}
