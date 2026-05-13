<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Events\ResortNotificationEvent;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\VisaConfigReminder;
use App\Models\VisaEmployeeExpiryData;

class CheckVisaExpiryReminders extends Command
{
    protected $signature = 'Daily:CheckVisaExpiryReminders';
    protected $description = 'Send visa document expiry reminders per resort VisaConfigReminder settings (to employee + HR).';

    public function handle()
    {
        $today = Carbon::today();

        $reminders = VisaConfigReminder::all();
        if ($reminders->isEmpty()) {
            $this->info('No VisaConfigReminder rows found.');
            return 0;
        }

        $sentCount = 0;

        foreach ($reminders as $config) {
            $resortId = $config->resort_id;
            $hrRecipientIds = Common::getResortHrEmployeeIds($resortId);

            $checks = [
                ['module' => 'Visa',                'flag' => 'Visa_reminder',            'days' => $config->Visa,            'label' => 'Visa'],
                ['module' => 'Work Permit Fee',     'flag' => 'Work_Permit_Fee_reminder', 'days' => $config->Work_Permit_Fee, 'label' => 'Work Permit Fee'],
                ['module' => 'Slot Fee',            'flag' => 'Slot_Fee_reminder',        'days' => $config->Slot_Fee,        'label' => 'Quota Slot Fee'],
                ['module' => 'Insurance',           'flag' => 'Insurance_reminder',       'days' => $config->Insurance,       'label' => 'Medical International Insurance'],
                ['module' => 'Medical',             'flag' => 'Medical_reminder',         'days' => $config->Medical,         'label' => 'Work Permit Medical Test'],
                ['module' => 'Passport',            'flag' => 'Passport_reminder',        'days' => $config->Passport,        'label' => 'Passport'],
            ];

            foreach ($checks as $c) {
                if (($config->{$c['flag']} ?? null) !== 'Active') {
                    continue;
                }
                $days = (int) $c['days'];
                if ($days <= 0) {
                    continue;
                }
                $targetDate = $today->copy()->addDays($days);

                $matches = $this->findExpiringEmployees($resortId, $c['module'], $targetDate);

                foreach ($matches as $match) {
                    $sentCount += $this->dispatchReminder(
                        $resortId,
                        $match['employee'],
                        $c['label'],
                        $match['expiry_date'],
                        $days,
                        $hrRecipientIds
                    );
                }
            }
        }

        $this->info("Visa expiry reminders processed. Notifications dispatched: {$sentCount}");
        return 0;
    }

    private function findExpiringEmployees(int $resortId, string $module, Carbon $targetDate): array
    {
        $targetDateStr = $targetDate->toDateString();

        switch ($module) {
            case 'Visa':
                return Employee::with('VisaRenewal')
                    ->where('resort_id', $resortId)
                    ->whereHas('VisaRenewal', fn($q) => $q->whereDate('end_date', $targetDateStr))
                    ->get()
                    ->map(fn($e) => ['employee' => $e, 'expiry_date' => $e->VisaRenewal->end_date])
                    ->all();

            case 'Work Permit Fee':
                return Employee::with(['WorkPermit' => fn($q) => $q->where('Status', 'Unpaid')->whereDate('Due_Date', $targetDateStr)])
                    ->where('resort_id', $resortId)
                    ->whereHas('WorkPermit', fn($q) => $q->where('Status', 'Unpaid')->whereDate('Due_Date', $targetDateStr))
                    ->get()
                    ->flatMap(function ($e) {
                        return $e->WorkPermit->map(fn($wp) => ['employee' => $e, 'expiry_date' => $wp->Due_Date]);
                    })
                    ->all();

            case 'Slot Fee':
                return Employee::with(['QuotaSlotRenewal' => fn($q) => $q->where('Status', 'Unpaid')->whereDate('Due_Date', $targetDateStr)])
                    ->where('resort_id', $resortId)
                    ->whereHas('QuotaSlotRenewal', fn($q) => $q->where('Status', 'Unpaid')->whereDate('Due_Date', $targetDateStr))
                    ->get()
                    ->flatMap(function ($e) {
                        return $e->QuotaSlotRenewal->map(fn($qs) => ['employee' => $e, 'expiry_date' => $qs->Due_Date]);
                    })
                    ->all();

            case 'Insurance':
                return Employee::with('EmployeeInsurance')
                    ->where('resort_id', $resortId)
                    ->whereHas('EmployeeInsurance', fn($q) => $q->whereDate('insurance_end_date', $targetDateStr))
                    ->get()
                    ->map(fn($e) => ['employee' => $e, 'expiry_date' => $e->EmployeeInsurance->insurance_end_date])
                    ->all();

            case 'Medical':
                return Employee::with('WorkPermitMedicalRenewal')
                    ->where('resort_id', $resortId)
                    ->whereHas('WorkPermitMedicalRenewal', fn($q) => $q->whereDate('end_date', $targetDateStr))
                    ->get()
                    ->map(fn($e) => ['employee' => $e, 'expiry_date' => $e->WorkPermitMedicalRenewal->end_date])
                    ->all();

            case 'Passport':
                $passportRows = VisaEmployeeExpiryData::where('resort_id', $resortId)
                    ->where('DocumentName', 'Passport_Copy')
                    ->whereRaw("STR_TO_DATE(
                                    JSON_UNQUOTE(JSON_EXTRACT(Ai_extracted_data, '$.extracted_fields.\"Date of Expiry\"')),
                                    '%d%b%Y'
                                ) = ?", [$targetDateStr])
                    ->with('employee')
                    ->get();
                return $passportRows
                    ->filter(fn($row) => $row->employee)
                    ->map(fn($row) => ['employee' => $row->employee, 'expiry_date' => $targetDateStr])
                    ->values()
                    ->all();
        }

        return [];
    }

    private function dispatchReminder($resortId, Employee $employee, string $label, $expiryDate, int $days, array $hrRecipientIds): int
    {
        $employee->loadMissing('resortAdmin');
        $empName = trim(optional($employee->resortAdmin)->first_name . ' ' . optional($employee->resortAdmin)->last_name);
        $expiryFormatted = Carbon::parse($expiryDate)->format('d M Y');

        $title = "{$label} Expiry Reminder";
        $employeeMsg = "Your {$label} expires on {$expiryFormatted} ({$days} days remaining). Please action it.";
        $hrMsg = ($empName !== '' ? $empName : "Employee #{$employee->id}")
            . "'s {$label} expires on {$expiryFormatted} ({$days} days remaining).";

        $count = 0;

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
                \Log::warning("Visa reminder dispatch failed for resort {$resortId}, recipient {$recipientId}: " . $e->getMessage());
            }
        }

        return $count;
    }
}
