<?php

namespace Database\Seeders\Demo;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Demo ENV: Survey module — two finished surveys with results, two running, one draft, in the shapes
 * SurveyController / API\SurveyController write them (one result row per participant × question, choice
 * answers exactly one of the options, Complete_time set when the participant has answered).
 *
 * Finished surveys go through the same transition the nightly links:survey-change-status job makes —
 * OnGoing with an end date in the past becomes Complete — applied to this resort only (the command itself
 * is resort-wide).
 */
class DemoSurveySeeder
{
    use SeedsDemoRows;

    const RATING = ['1.0', '2.0', '3.0', '4.0', '5.0'];
    // [title, start (days from today), end, privacy, share of invited who answered, status before the job]
    const SURVEYS = [
        ['Employee Engagement Survey 2026', -75, -45, 'Confidential', 0.72, 'OnGoing'],
        ['Accommodation & Staff Canteen Feedback', -150, -120, 'Anonymous', 0.55, 'OnGoing'],
        ['Quarterly Pulse Check', -10, 12, 'Neutral', 0.41, 'OnGoing'],
        ['Training Needs 2027', -20, 2, 'Neutral', 0.6, 'OnGoing'],
        ['Wellbeing & Work-Life Balance', 15, 45, 'Confidential', 0.0, 'SaveAsDraft'],
    ];
    const QUESTIONS = [
        ['Rating', 'How satisfied are you working here overall?', null],
        ['Single-Choice', 'Do you have the support you need from your manager?', ['Yes, fully', 'Mostly', 'Somewhat', 'Not really']],
        ['Multi-Choice', 'Which of these would most improve your work life?', ['Better staff meals', 'More training', 'Flexible day-offs', 'Faster internet in staff housing']],
        ['Rating', 'How likely are you to recommend this resort as a workplace?', null],
        ['Text', 'Anything else you would like HR to know?', null],
    ];
    const COMMENTS = ['More cross-training between departments would help.', 'Staff canteen food has improved a lot.',
        'Wi-Fi in Block A is slow in the evenings.', 'Happy with my team and my supervisor.', 'Would like more Maldivian language classes.',
        'Day-off planning could be shared earlier.', 'Thank you for the recognition programme.'];

    private array $counts = ['surveys' => 0, 'survey answers' => 0];

    public function __construct(private int $resortId, private int $adminId)
    {
    }

    public function seed(): array
    {
        $hr = $this->login('hr');
        // Everyone on the permanent payroll is invited; casual and intern staff are not.
        $invited = $this->people()->filter(fn ($p) => !in_array($p->employment_type, ['Casual', 'Internship'], true))->values();
        $today = Carbon::today();

        foreach (self::SURVEYS as [$title, $startIn, $endIn, $privacy, $answeredShare, $status]) {
            $start = $today->copy()->addDays($startIn);
            $end = $today->copy()->addDays($endIn);
            $created = $start->copy()->subDays(3)->setTime(10, 0);
            $surveyId = $this->row('parent_surveys', ['resort_id' => $this->resortId, 'Surevey_title' => $title,
                'Start_date' => $start->format('Y-m-d'), 'End_date' => $end->format('Y-m-d'), 'Recurring_survey' => 'Quarterly',
                'Reminder_notification' => 2, 'Min_response' => 0, 'Allow_edit' => 'yes', 'Status' => $status,
                'survey_privacy_type' => $privacy, 'created_by' => $hr->admin, 'modified_by' => $hr->admin,
                'created_at' => $created, 'updated_at' => $created]);
            $questions = [];
            foreach (self::QUESTIONS as [$type, $text, $options]) {
                $questions[] = [$this->row('survey_questions', ['Parent_survey_id' => $surveyId, 'Question_Type' => $type, 'type' => $type,
                    'Question_Text' => $text, 'Total_Option_Json' => $options ? json_encode($options) : null,
                    'Question_Complusory' => $type === 'Text' ? 'no' : 'yes', 'created_at' => $created, 'updated_at' => $created]), $type, $options];
            }

            $answeredUntil = $end->copy()->min($today);
            $results = [];
            foreach ($invited as $p) {
                $answered = $status !== 'SaveAsDraft' && $this->chance($answeredShare);
                $at = $answered ? $start->copy()->addDays(mt_rand(0, max(0, (int) $start->diffInDays($answeredUntil, true))))->setTime(mt_rand(8, 21), mt_rand(0, 59)) : $created;
                $participantId = $this->row('survey_employees', ['Parent_survey_id' => $surveyId, 'Emp_id' => $p->id,
                    'emp_status' => $answered ? 'yes' : 'no', 'Complete_time' => $answered ? sprintf('00:%02d:%02d', mt_rand(1, 8), mt_rand(0, 59)) : null,
                    'created_at' => $created, 'updated_at' => $at]);
                if (!$answered) {
                    continue;
                }
                $mood = mt_rand(2, 5); // each person answers consistently
                foreach ($questions as [$questionId, $type, $options]) {
                    if ($type === 'Text' && $this->chance(0.5)) {
                        continue; // optional
                    }
                    $answer = match ($type) {
                        'Rating' => self::RATING[max(0, min(4, $mood - 1 + mt_rand(-1, 1)))],
                        'Single-Choice' => $options[max(0, min(3, 5 - $mood + mt_rand(-1, 0)))],
                        'Multi-Choice' => $this->pick($options),
                        default => $this->pick(self::COMMENTS),
                    };
                    $results[] = ['Parent_survey_id' => $surveyId, 'Survey_emp_ta_id' => $participantId, 'Question_id' => $questionId,
                        'Emp_Ans' => $answer, 'created_at' => $at, 'updated_at' => $at];
                }
            }
            $this->rows('survey_results', $results);
            $this->counts['surveys']++;
            $this->counts['survey answers'] += count($results);
        }

        // What links:survey-change-status does each night, for this resort only.
        $todayYmd = $today->format('Y-m-d');
        DB::table('parent_surveys')->where('resort_id', $this->resortId)->where('Status', 'Publish')->where('Start_date', '<=', $todayYmd)
            ->update(['Status' => 'OnGoing', 'updated_at' => now()]);
        DB::table('parent_surveys')->where('resort_id', $this->resortId)->whereIn('Status', ['Publish', 'OnGoing'])->where('End_date', '<=', $todayYmd)
            ->update(['Status' => 'Complete', 'updated_at' => now()]);

        Cache::forget("survey_insights:{$this->resortId}");
        return $this->counts;
    }
}
