<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IncidentsMeeting;
use App\Models\IncidentsMeetingParticipants;
use App\Events\ResortNotificationEvent;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use App\Helpers\Common;

class IncidentMeetingReminder extends Command
{
    protected $signature = 'incident:send-meeting-reminders';
    protected $description = 'Send reminders for upcoming investigation meetings based on resort configuration';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Each resort has its own reminder setting; its meetings belong to it through their incident
        // (incidents_investigation_meetings has no resort_id).
        $configs = DB::table('incident_configuration')->where('setting_key', 'meeting_reminder')->pluck('setting_value', 'resort_id');
        if ($configs->isEmpty()) {
            $this->warn('No meeting_reminder config found.');
            return;
        }
        foreach ($configs as $resortId => $configRaw) {
            $this->remind((int) $resortId, $configRaw);
        }
        $this->info('Incident meeting reminders processed.');
    }

    private function remind(int $resortId, ?string $configRaw): void
    {
        $config = json_decode((string) $configRaw, true);
        preg_match('/(\d+)\s*(Business|Calendar)/i', $config['reminder_days'] ?? '', $matches);

        if (count($matches) < 3) {
            $this->warn("Resort {$resortId}: invalid reminder_days format.");
            return;
        }

        $reminderDays = (int)$matches[1];
        $type = strtolower($matches[2]);

        $today = now()->toDateString();

        $meetings = IncidentsMeeting::whereDate('meeting_date', '>=', $today)
            ->whereIn('incident_id', DB::table('incidents')->where('resort_id', $resortId)->select('id'))
            ->get();

        foreach ($meetings as $meeting) {
            $meetingDate = Carbon::parse($meeting->meeting_date);
            $shouldSendReminder = false;

            if ($type === 'business') {
                $reminderDate = $meetingDate->copy();
                $businessDays = 0;

                while ($businessDays < $reminderDays) {
                    $reminderDate->subDay();
                    if (!$reminderDate->isWeekend()) {
                        $businessDays++;
                    }
                }

                $shouldSendReminder = $today === $reminderDate->toDateString();
            } else {
                $reminderDate = $meetingDate->copy()->subDays($reminderDays);
                $shouldSendReminder = $today === $reminderDate->toDateString();
            }

            if ($shouldSendReminder) {
                $this->info("Sending reminder for meeting ID {$meeting->id} scheduled on {$meetingDate->toDateString()}");

                $participants = IncidentsMeetingParticipants::where('meeting_id', $meeting->id)->pluck('participant_id');
                foreach ($participants as $employeeId) {
                    $employee = Employee::find($employeeId);
                    if ($employee) {
                        $msg = "📝 Meeting: {$meeting->meeting_subject}\n📅 Date: " . Common::formatDate($meeting->meeting_date)
                            . "\n⏰ Time: " . Common::formatDisplayTime($meeting->meeting_time) . "\n📍 Location: {$meeting->location}";

                        // notifyEmployees = in-app row + mobile push (was in-app only). One failed push
                        // must not stop the other participants' / resorts' reminders.
                        try {
                            Common::notifyEmployees(
                                $resortId,
                                [$employeeId],
                                'Upcoming Investigation Meeting Reminder',
                                $msg,
                                'Incident',
                                $meeting->id
                            );
                        } catch (\Throwable $e) {
                            \Log::warning("Incident meeting reminder failed for meeting {$meeting->id}, employee {$employeeId}: " . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
}
