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
        $configRaw = DB::table('incident_configuration')
            ->where('key', 'meeting_reminder')
            ->value('setting_value');

        if (!$configRaw) {
            $this->warn('No meeting_reminder config found.');
            return;
        }

        $config = json_decode($configRaw, true);
        preg_match('/(\d+)\s*(Business|Calendar)/i', $config['reminder_days'] ?? '', $matches);

        if (count($matches) < 3) {
            $this->warn('Invalid reminder_days format.');
            return;
        }

        $reminderDays = (int)$matches[1];
        $type = strtolower($matches[2]);

        $today = now()->toDateString();

        $meetings = IncidentsMeeting::whereDate('meeting_date', '>=', $today)->get();

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
                        $msg = "📝 Meeting: {$meeting->meeting_subject}\n📅 Date: {$meeting->meeting_date}\n⏰ Time: {$meeting->meeting_time}\n📍 Location: {$meeting->location}";

                        event(new ResortNotificationEvent(Common::nofitication(
                            $meeting->resort_id, // Make sure `resort_id` exists on the `meetings` table
                            10,
                            'Upcoming Investigation Meeting Reminder',
                            $msg,
                            0,
                            $employeeId,
                            'Incident'
                        )));
                    }
                }
            }
        }

        $this->info('Incident meeting reminders processed.');
    }
}
