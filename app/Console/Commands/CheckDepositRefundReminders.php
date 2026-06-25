<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Events\ResortNotificationEvent;
use App\Helpers\Common;
use App\Models\DepositRefound;
use App\Models\EmployeeResignation;

class CheckDepositRefundReminders extends Command
{
    protected $signature = 'Daily:CheckDepositRefundReminders';
    protected $description = 'Send deposit-refund reminders per resort DepositRefound settings (to employee + HR).';

    public function handle()
    {
        $today = Carbon::today();
        $configs = DepositRefound::all();

        if ($configs->isEmpty()) {
            $this->info('No DepositRefound configurations found.');
            return 0;
        }

        $sentCount = 0;

        foreach ($configs as $config) {
            $resortId      = (int) $config->resort_id;
            $initialDays   = (int) $config->initial_reminder;
            $followupDays  = (int) $config->followup_reminder;

            if ($initialDays <= 0) {
                continue;
            }

            $hrRecipientIds = Common::getResortHrEmployeeIds($resortId);

            // Pull every approved resignation at this resort whose deposit
            // hasn't been refunded yet (Deposit_withdraw NULL or != 'Yes').
            $candidates = EmployeeResignation::with('employee.resortAdmin')
                ->where('resort_id', $resortId)
                ->where('hr_status', 'Approved')
                ->where(function ($q) {
                    $q->whereNull('Deposit_withdraw')
                      ->orWhere('Deposit_withdraw', '!=', 'Yes');
                })
                // Skip rows HR snoozed via "No" until the snooze window passes.
                ->where(function ($q) use ($today) {
                    $q->whereNull('deposit_refund_snooze_until')
                      ->orWhereDate('deposit_refund_snooze_until', '<=', $today);
                })
                ->get();

            foreach ($candidates as $resignation) {
                // "Days after departure" = the employee's last working day; fall
                // back to the resignation date when no last working day is set.
                $departureRaw = $resignation->last_working_day ?: $resignation->resignation_date;
                if (empty($departureRaw)) {
                    continue;
                }
                $departureDate = Carbon::parse($departureRaw)->startOfDay();
                $daysSince     = $departureDate->diffInDays($today, false);
                if ($daysSince < 0) {
                    continue; // future-dated departure, skip
                }

                // Initial reminder on day == initialDays; then a follow-up every
                // followupDays after that, for as long as it stays unapplied.
                $isInitial  = ($daysSince === $initialDays);
                $isFollowup = ($followupDays > 0)
                    && ($daysSince > $initialDays)
                    && ((($daysSince - $initialDays) % $followupDays) === 0);

                if (!$isInitial && !$isFollowup) {
                    continue;
                }

                $sentCount += $this->dispatchReminder(
                    $resortId,
                    $resignation,
                    $isInitial ? 'initial' : 'follow-up',
                    $daysSince,
                    $hrRecipientIds
                );
            }
        }

        $this->info("Deposit-refund reminders processed. Notifications dispatched: {$sentCount}");
        return 0;
    }

    private function dispatchReminder($resortId, EmployeeResignation $resignation, string $stage, int $daysSince, array $hrRecipientIds): int
    {
        $employee = $resignation->employee;
        if (!$employee) {
            return 0;
        }

        $employee->loadMissing('resortAdmin');
        $empName = trim(optional($employee->resortAdmin)->first_name . ' ' . optional($employee->resortAdmin)->last_name);
        if ($empName === '') {
            $empName = 'Employee #' . $employee->id;
        }

        $stageLabel  = $stage === 'initial' ? 'Deposit Refund Reminder' : 'Deposit Refund Follow-up Reminder';
        $title       = $stageLabel;
        $employeeMsg = "Your deposit refund is pending ({$daysSince} days since your departure). Please apply for your refund.";
        $hrMsg       = "{$empName}'s deposit refund is still pending ({$daysSince} days since departure). Stage: {$stage}.";

        $recipients = [];
        if (!empty($employee->id)) {
            $recipients[(int) $employee->id] = $employeeMsg;
        }
        foreach ($hrRecipientIds as $hrId) {
            $hrId = (int) $hrId;
            if (!isset($recipients[$hrId])) {
                $recipients[$hrId] = $hrMsg;
            }
        }

        $count = 0;
        foreach ($recipients as $recipientId => $message) {
            try {
                event(new ResortNotificationEvent(Common::nofitication(
                    $resortId,
                    10,
                    $title,
                    $message,
                    0,
                    $recipientId,
                    'Visa'
                )));
                $count++;
            } catch (\Throwable $e) {
                \Log::warning("Deposit refund reminder dispatch failed for resort {$resortId}, recipient {$recipientId}: " . $e->getMessage());
            }
        }

        return $count;
    }
}
