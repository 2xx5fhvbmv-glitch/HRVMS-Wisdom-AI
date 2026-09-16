<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ParentSurvey;
use App\Models\SurveyEmployee;
use App\Helpers\Common;
use Carbon\Carbon;

class SurveyReminderNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'links:survey-reminder-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify employees who have not yet responded to a survey, on the day(s) configured in Reminder_notification (days-before-End_date, comma-separated)';

    public function handle()
    {
        $today = Carbon::today();

        // Reminder_notification is a comma-separated list of "days before
        // End_date" thresholds set in the survey UI (e.g. "3,1") — it was
        // persisted and shown back in the edit form but nothing ever
        // consumed it to actually send a reminder. This is that consumer.
        ParentSurvey::whereIn('Status', ['Publish', 'OnGoing'])
            ->whereNotNull('Reminder_notification')
            ->where('Reminder_notification', '!=', '')
            ->whereNotNull('End_date')
            ->chunk(50, function ($surveys) use ($today) {
                foreach ($surveys as $survey) {
                    $reminderDays = array_filter(array_map('trim', explode(',', $survey->Reminder_notification)), fn($d) => $d !== '' && is_numeric($d));
                    if (empty($reminderDays)) continue;

                    $daysUntilEnd = $today->diffInDays(Carbon::parse($survey->End_date), false);
                    if ($daysUntilEnd < 0 || !in_array((string) $daysUntilEnd, array_map('strval', $reminderDays), true)) {
                        continue;
                    }

                    $pendingEmpIds = SurveyEmployee::where('Parent_survey_id', $survey->id)
                        ->where('emp_status', 'no')
                        ->pluck('Emp_id')
                        ->all();

                    if (empty($pendingEmpIds)) continue;

                    try {
                        Common::notifyEmployees(
                            $survey->resort_id,
                            $pendingEmpIds,
                            'Survey Reminder',
                            "Reminder: please complete the survey '{$survey->Surevey_title}' before it closes on " . Carbon::parse($survey->End_date)->format('d M Y') . ".",
                            'Survey',
                            $survey->id
                        );
                    } catch (\Exception $e) {
                        \Log::warning('Survey reminder notification failed for survey ' . $survey->id . ': ' . $e->getMessage());
                    }
                }
            });
    }
}
