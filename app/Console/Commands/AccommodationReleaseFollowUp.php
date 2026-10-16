<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AccommodationReleaseDecision as Decision;
use App\Models\AssingAccommodation;
use App\Models\Employee;
use App\Helpers\Common;

/**
 * Daily: asks HR whether to vacate the bed / room / villa of every Inactive
 * employee who still holds one and whose Full & Final Settlement is
 * finalized (founder, 2026-10-05). HR answers Yes (vacate now) or Keep on
 * the Release Decisions page; Keep — and a prompt nobody answered — is asked
 * again every 3 days (founder, 2026-10-15). Open
 * decisions close once the unit is no longer held or the employee is back
 * to Active. Nothing is released here — only HR's Yes releases.
 */
class AccommodationReleaseFollowUp extends Command
{
    protected $signature   = 'accommodation:release-follow-up';
    protected $description = 'Prompt HR to vacate or keep accommodation held by Inactive employees.';

    public function handle(): int
    {
        $today = now()->toDateString();
        $holders = AssingAccommodation::where('emp_id', '!=', 0)->distinct()->pluck('emp_id')->all();

        // Follow up / close open decisions.
        foreach (Decision::whereIn('decision', Decision::OPEN)->with('employee')->get() as $d) {
            $emp = $d->employee;
            if (!$emp || !in_array($d->employee_id, $holders)) {
                $d->update(['decision' => 'closed']);
                continue;
            }
            if ($emp->status !== 'Inactive') {
                $d->update(['decision' => 'closed']);
                self::notifyHr($emp, $d, 'Accommodation Decision Closed',
                    'is no longer Inactive (status: ' . $emp->status . '). Their accommodation was not changed; reassign it by hand if needed.');
                continue;
            }
            // Re-ask every 3 days until HR answers: after Keep, and after an ignored prompt
            // (pending rows from before next_check_on was set count from when they were created).
            $checkOn = $d->next_check_on ?? ($d->decision === 'pending' ? $d->created_at?->copy()->addDays(3) : null);
            if (in_array($d->decision, ['keep', 'pending'], true) && $checkOn && $checkOn->toDateString() <= $today) {
                $d->update(['next_check_on' => now()->addDays(3)->toDateString()]);
                self::prompt($emp, $d);
            }
        }

        // New prompts.
        $new = Employee::whereIn('id', $holders)
            ->where('status', 'Inactive')
            ->whereHas('finalSettlements', fn ($q) => $q->where('status', 'finalized'))
            ->whereNotIn('id', Decision::whereIn('decision', Decision::OPEN)->select('employee_id'))
            ->get();
        foreach ($new as $emp) {
            self::prompt($emp, Decision::create(['resort_id' => $emp->resort_id, 'employee_id' => $emp->id, 'decision' => 'pending',
                'next_check_on' => now()->addDays(3)->toDateString()]));
        }

        $this->info("Prompted HR for {$new->count()} new employee(s).");
        return 0;
    }

    public static function prompt(Employee $emp, Decision $d): void
    {
        self::notifyHr($emp, $d, 'Vacate Accommodation?',
            'is now Inactive. Do you want us to vacate their bed / room / villa? Choose Yes or Keep on the Release Decisions page.');
    }

    private static function notifyHr(Employee $emp, Decision $d, string $title, string $tail): void
    {
        try {
            $name = $emp->resortAdmin->full_name ?? ('Employee #' . $emp->Emp_id);
            Common::notifyEmployees($emp->resort_id, Common::getResortHrEmployeeIds($emp->resort_id),
                $title, "{$name} {$tail}", 'Accommodation', $d->id, 'accommodation-release');
        } catch (\Throwable $e) {
            \Log::warning("Accommodation release prompt failed for emp #{$emp->id}: " . $e->getMessage());
        }
    }
}
