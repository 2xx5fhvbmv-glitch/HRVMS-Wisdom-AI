<?php

namespace Database\Seeders\Demo;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo ENV: Performance module, in the shapes CycleController / ReviewController / KpiController /
 * MonthlyCheckingController / PerformanceMeetingController / PipPdpController write them:
 * - review templates (formBuilder JSON; the employee page reads the 'feedback' and 'training' fields),
 * - a closed mid-year cycle with every review done and an open annual cycle part-way through
 *   (child rows: Emp_main_id = employees.id as text, Manager_id = reporting_to, the GM's own review
 *   'not_applicable'; no controller ever sets OnGoing / Close, so those are set here),
 * - property KPIs at every stage (pending → responded by a HOD → approved / rejected by the GM) with
 *   monthly actuals, performance meetings with RSVPs, monthly check-ins through to finalised review
 *   rounds (Checkin_id continues the app's global M-C-I-NNNN sequence), and active PIP / PDP plans.
 */
class DemoPerformanceSeeder
{
    use SeedsDemoRows;

    const CRITERIA = ['job_knowledge' => 'Job Knowledge', 'quality_of_work' => 'Quality of Work', 'teamwork' => 'Teamwork',
        'guest_service' => 'Guest Service', 'reliability' => 'Reliability'];
    const FEEDBACK = ['Consistently delivers and supports the team during busy periods.', 'Good guest feedback; needs to plan ahead more.',
        'Reliable and punctual; ready for more responsibility.', 'Solid performer, should work on communication with other departments.',
        'Needs closer supervision on standards; agreed an improvement plan.'];

    private array $counts = ['appraisals' => 0, 'check-ins' => 0, 'KPIs' => 0];

    public function __construct(private int $resortId, private int $adminId)
    {
    }

    public function seed(): array
    {
        $now = Carbon::now();
        $year = (int) $now->format('Y');
        $hr = $this->login('hr');
        $gm = $this->login('gm');
        $stamp = ['resort_id' => $this->resortId, 'created_at' => $now->copy()->subMonths(8), 'updated_at' => $now->copy()->subMonths(8)];

        // Configuration.
        foreach (['Self-Review' => 30, 'Manager Review' => 50, 'Peer Review' => 10, 'Upward Review' => 10] as $title => $weight) {
            $this->row('performance_review_types', $stamp + ['category_title' => $title, 'category_weightage' => $weight]);
        }
        $fields = [['type' => 'header', 'subtype' => 'h3', 'label' => 'Annual Performance Review']];
        foreach (self::CRITERIA as $name => $label) {
            $fields[] = ['type' => 'starRating', 'name' => $name, 'label' => $label, 'required' => true, 'maxRating' => 5];
        }
        $fields[] = ['type' => 'textarea', 'name' => 'feedback', 'label' => 'Overall feedback', 'required' => true];
        $fields[] = ['type' => 'select', 'name' => 'training', 'label' => 'Training recommended?',
            'values' => [['label' => 'Yes', 'value' => 'Yes', 'selected' => false], ['label' => 'No', 'value' => 'No', 'selected' => true]]];
        $templateId = $this->row('performance_template_forms', $stamp + ['FormName' => 'Annual Performance Review', 'form_structure' => json_encode($fields)]);
        $this->row('ninty_day_peformance_forms', $stamp + ['FormName' => '90-Day Probation Evaluation', 'form_structure' => json_encode(array_slice($fields, 0, 4))]);
        $forms = [];
        foreach (['ProfessionalForm' => 'Professional Conduct Review', 'annualAppraisal' => 'Annual Appraisal (Short)',
            'pipForm' => 'Performance Improvement Plan', 'pdpForm' => 'Personal Development Plan'] as $type => $name) {
            $structure = [['type' => 'textarea', 'name' => 'goals', 'label' => 'Goals for the period', 'required' => true],
                ['type' => 'textarea', 'name' => 'support', 'label' => 'Support and resources', 'required' => false],
                ['type' => 'date', 'name' => 'review_date', 'label' => 'Next review date', 'required' => true]];
            $forms[$type] = $this->row('professionalforms', $stamp + ['FormName' => $name, 'form_type' => $type,
                'form_structure' => json_encode($structure), 'created_by' => $hr->admin]);
        }
        $this->row('performance_meeting_contents', $stamp + ['content' => '<p>Dear {Employee_name},</p><p>You are invited to <b>{Title}</b> on '
            . '{Meeting_Date} at {Meeting_Time}, {Meeting_Location}.</p><p>{Description}</p><p>Regards,<br>{Your_Name}, {Your_Designation}<br>{Resort_Name}</p>']);

        // Who is appraised: permanent staff (casual and interns are not on the review cycle).
        $staff = $this->people()->filter(fn ($p) => !in_array($p->employment_type, ['Casual', 'Internship'], true))->values();
        $isGm = fn ($p) => (int) $p->rank === 8;

        // Mid-year cycle, closed: about 30 people, every review done; the lowest scores become the PIP cases.
        $midYear = $this->cycle("Mid-Year Review {$year}", Carbon::create($year, 4, 1), Carbon::create($year, 6, 30), 'Close', $templateId,
            [Carbon::create($year, 4, 1), Carbon::create($year, 4, 20)], [Carbon::create($year, 4, 21), Carbon::create($year, 5, 15)]);
        $scores = [];
        foreach ($staff->reject($isGm)->shuffle()->take(30) as $p) {
            $scores[$p->id] = $this->appraisal($midYear, $templateId, $p, 'completed', 'completed', Carbon::create($year, 4, 10), Carbon::create($year, 5, 5));
        }
        asort($scores);
        $struggling = array_slice(array_keys($scores), 0, 2);

        // Annual cycle, open now: everyone, about a third finished, a third waiting on the manager, the rest not started.
        $annual = $this->cycle("Annual Appraisal {$year}", Carbon::create($year, 10, 1), Carbon::create($year, 12, 31), 'OnGoing', $templateId,
            [Carbon::create($year, 10, 1), Carbon::create($year, 11, 15)], [Carbon::create($year, 10, 10), Carbon::create($year, 11, 30)]);
        $employee = $this->login('employee');
        $supervisor = $this->login('supervisor');
        $supervisorTeam = $staff->where('reporting_to', $supervisor->id)->pluck('id')->all();
        foreach ($staff as $p) {
            if ($isGm($p)) {
                $this->appraisal($annual, $templateId, $p, 'pending', 'not_applicable', null, null);
                continue;
            }
            $roll = mt_rand(1, 100);
            if ($p->id === $employee->id) {
                $roll = 100; // the employee login still has their self review to do
            } elseif (in_array($p->id, $supervisorTeam, true) && $roll > 35 && $roll <= 65) {
                $roll = 50; // someone in the supervisor's team is waiting on the supervisor
            }
            [$self, $manager] = $roll <= 35 ? ['completed', 'completed'] : ($roll <= 65 ? ['completed', 'pending'] : ['pending', 'pending']);
            $selfAt = Carbon::create($year, 10, 1)->addDays(mt_rand(1, max(1, min(25, (int) Carbon::create($year, 10, 1)->diffInDays($now, true)))));
            $this->appraisal($annual, $templateId, $p, $self, $manager, $selfAt->min($now), $selfAt->copy()->addDays(3)->min($now));
        }

        $this->kpis($year, $gm);
        $this->meetings($hr, $staff);
        $this->checkIns($hr, $staff, $employee);
        foreach ([['employee_pip_plans', 'pipForm', $struggling, ['60 Days', '90 Days']], ['employee_pdp_plans', 'pdpForm', null, ['6 Months', '12 Months']]] as [$table, $form, $who, $durations]) {
            $people = $who ? $staff->whereIn('id', $who)->values() : $staff->whereIn('rank', [4, 5])->shuffle()->take(2)->values();
            foreach ($people as $i => $p) {
                $this->row($table, ['resort_id' => $this->resortId, 'employee_id' => $p->id, 'position_id' => $p->Position_id,
                    'template_id' => $forms[$form], 'duration' => $durations[$i % 2],
                    'factors' => $form === 'pipForm' ? "Service standards below target\nLate to two briefings this month\nGuest complaint follow-up"
                        : "Ready for a supervisor role\nWants to lead the new hires' induction",
                    'status' => 'active', 'created_by' => $hr->admin, 'created_at' => $now->copy()->subDays(20 + 10 * $i), 'updated_at' => $now->copy()->subDays(5)]);
            }
        }

        Cache::forget("performance_insights:{$this->resortId}:all");
        return $this->counts;
    }

    private function cycle(string $name, Carbon $start, Carbon $end, string $status, int $templateId, array $self, array $manager): int
    {
        return $this->row('performance_cycles', ['resort_id' => $this->resortId, 'Cycle_Name' => $name,
            'Start_Date' => $start->format('Y-m-d'), 'End_Date' => $end->format('Y-m-d'),
            'CycleSummary' => "{$name}: self review, then the line manager's review and sign-off.", 'CycleReminders' => 'ON', 'status' => $status,
            'Self_Review_Templete' => $templateId, 'Manager_Review_Templete' => $templateId,
            'Self_Activity_Start_Date' => $self[0]->format('Y-m-d'), 'Self_Activity_End_Date' => $self[1]->format('Y-m-d'),
            'Manager_Activity_Start_Date' => $manager[0]->format('Y-m-d'), 'Manager_Activity_End_Date' => $manager[1]->format('Y-m-d'),
            'created_at' => $start->copy()->subDays(7), 'updated_at' => $start->copy()->subDays(7)]);
    }

    /** One person's review in a cycle; returns their average manager score (0 when not reviewed). */
    private function appraisal(int $cycleId, int $templateId, object $p, string $self, string $manager, ?Carbon $selfAt, ?Carbon $managerAt): float
    {
        $level = mt_rand(20, 46) / 10; // where this person sits: 2.0 – 4.6
        $ratings = fn () => array_map(fn () => (string) max(1, min(5, (int) round($level + mt_rand(-5, 5) / 10))), self::CRITERIA);
        $managerPerson = $this->people()->get($p->reporting_to);
        $managerData = $manager === 'completed' ? $ratings() + ['feedback' => self::FEEDBACK[max(0, min(4, (int) round(4.6 - $level) * 2 - 1))],
            'training' => $level < 3.2 ? 'Yes' : 'No'] : null;
        $this->row('performa_child_cycles', [
            'Parent_cycle_id' => $cycleId, 'Emp_main_id' => (string) $p->id, 'Manager_id' => $manager === 'not_applicable' ? null : $p->reporting_to,
            'is_gm_review' => $manager === 'not_applicable' ? 1 : 0, 'template_id' => (string) $templateId,
            'self_review_status' => $self, 'manager_review_status' => $manager,
            'self_review_data' => $self === 'completed' ? json_encode($ratings() + ['feedback' => 'I worked hard on guest scores this period.', 'training' => 'Yes']) : null,
            'Self_review_date' => $self === 'completed' ? $selfAt?->format('Y-m-d') : null,
            'self_signature_name' => $self === 'completed' ? $this->name($p) : null, 'self_signed_at' => $self === 'completed' ? $selfAt : null,
            'manager_review_data' => $managerData ? json_encode($managerData) : null,
            'Manager_review_date' => $managerData ? $managerAt?->format('Y-m-d') : null,
            'manager_signature_name' => $managerData && $managerPerson ? $this->name($managerPerson) : null,
            'manager_signed_at' => $managerData ? $managerAt : null,
            'created_at' => $selfAt ?? now(), 'updated_at' => $managerAt ?? $selfAt ?? now(),
        ]);
        $this->counts['appraisals']++;
        return $managerData ? array_sum(array_map('intval', array_intersect_key($managerData, self::CRITERIA))) / count(self::CRITERIA) : 0.0;
    }

    /** Property KPIs set by the GM, answered by HODs, approved or sent back. */
    private function kpis(int $year, object $gm): void
    {
        // [goal, budget, weightage, status, responding HOD login]
        $kpis = [['Total Revenue', '18500000', '25', 'approved', 'hod.fnb'], ['Gross Operating Profit', '6200000', '20', 'approved', 'hod.frontoffice'],
            ['Guest Satisfaction Score', '92', '15', 'responded', 'hod.housekeeping'], ['Occupancy', '82', '15', 'rejected', 'hod.frontoffice'],
            ['Staff Retention', '90', '10', 'pending', null], ['Cost Efficiency', '3', '15', 'pending', null]];
        $created = Carbon::create($year, 1, 15, 10);
        foreach ($kpis as $i => [$goal, $budget, $weight, $status, $hod]) {
            $responder = $hod ? $this->login($hod) : null;
            $entries = $responder ? [['individual_goal' => "{$goal} — " . $responder->dept, 'budget' => $budget, 'weightage' => $weight]] : null;
            $respondedAt = $created->copy()->addDays(10 + $i);
            $parentId = $this->row('performance_kpi_parents', [
                'resort_id' => $this->resortId, 'property_goal' => $goal, 'PropertyGoalbudget' => $budget, 'PropertyGoalweightage' => $weight,
                'budget_currency' => 'USD', 'poor' => 2, 'fair' => 4, 'good' => 6, 'superb' => 8,
                'poor_range' => '80', 'fair_range' => '81 - 90', 'good_range' => '91 - 100', 'superb_range' => '101',
                'status' => $status, 'individual_goal' => $entries ? $entries[0]['individual_goal'] : null,
                'response_budget' => $responder ? $budget : null, 'response_budget_currency' => $responder ? 'USD' : null,
                'response_weightage' => $responder ? $weight : null, 'response_entries' => $entries ? json_encode($entries) : null,
                'responded_by' => $responder?->id, 'responded_at' => $responder ? $respondedAt : null,
                'gm_action' => in_array($status, ['approved', 'rejected'], true) ? $status : null,
                'gm_action_at' => in_array($status, ['approved', 'rejected'], true) ? $respondedAt->copy()->addDays(3) : null,
                'gm_remarks' => $status === 'rejected' ? 'Target too low for the season — please revise to at least 85%.' : null,
                'created_by' => $gm->admin, 'modified_by' => $gm->admin, 'created_at' => $created, 'updated_at' => $respondedAt,
            ]);
            if ($status === 'approved') {
                $actuals = [];
                foreach (['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September'] as $m => $month) {
                    $actuals[] = ['kpi_parents_id' => $parentId, 'individual_goal' => $entries[0]['individual_goal'], 'month' => $month,
                        'budget' => (string) round((float) $budget / 12 * mt_rand(85, 115) / 100), 'budget_currency' => 'USD', 'weightage' => $weight,
                        'remarks' => $this->pick(['On track', 'Above target', 'Slightly below — low season', 'Strong month']),
                        'created_by' => $responder->admin, 'created_at' => Carbon::create($year, $m + 1, 28), 'updated_at' => Carbon::create($year, $m + 1, 28)];
                }
                $this->rows('performance_kpi_children', $actuals);
            }
            $this->counts['KPIs']++;
        }
    }

    /** Performance meetings with RSVPs (the calendar only shows meetings someone accepted). */
    private function meetings(object $hr, $staff): void
    {
        $month = Carbon::today()->startOfMonth();
        $plans = [[$month->copy()->subMonth()->addDays(8), 'Mid-year review feedback — F&B'], [$month->copy()->subMonth()->addDays(20), 'Appraisal kick-off briefing'],
            [Carbon::today()->addDays(3), 'Annual appraisal calibration'], [Carbon::today()->addDays(9), 'KPI quarterly review']];
        foreach ($plans as $i => [$date, $title]) {
            $meetingId = $this->row('peformance_meetings', ['resort_id' => $this->resortId, 'title' => $title, 'date' => $date->format('Y-m-d'),
                'start_time' => '10:00', 'end_time' => '11:00', 'location' => 'Conference Room',
                'description' => 'Bring your team’s review summaries.', 'conference_links' => null, 'created_by' => $hr->admin,
                'created_at' => $date->copy()->subDays(7), 'updated_at' => $date->copy()->subDays(7)]);
            $rsvps = $date->isPast() ? ['accepted', 'accepted', 'accepted', 'declined'] : ['accepted', 'pending', 'pending', 'declined', 'accepted'];
            foreach ($staff->whereIn('rank', [1, 2, 4])->shuffle()->take(count($rsvps))->values() as $j => $p) {
                $this->row('performance_meeting_participants', ['meeting_id' => $meetingId, 'employee_id' => $p->id, 'resort_id' => $this->resortId,
                    'status' => $rsvps[$j], 'token' => Str::random(48), 'reason' => $rsvps[$j] === 'declined' ? 'On leave that day' : null,
                    'responded_at' => $rsvps[$j] === 'pending' ? null : $date->copy()->subDays(5), 'created_at' => $date->copy()->subDays(7),
                    'updated_at' => $date->copy()->subDays(5)]);
            }
        }
    }

    /** Monthly check-ins: scheduled, approved, rejected, and finalised with review rounds. */
    private function checkIns(object $hr, $staff, object $employee): void
    {
        $last = (string) DB::table('monthly_checking_models')->orderByDesc('id')->value('Checkin_id');
        $parts = explode('-', $last);
        $next = (count($parts) === 4 ? (int) $parts[3] : 0) + 1;
        $today = Carbon::today();
        // [state, days from today]
        $plan = [['pending', 4], ['pending', 6], ['pending', 9], ['rejected', -12], ['approved', -5], ['approved', -3],
            ['finalised', -40], ['finalised', -33], ['finalised', -26], ['finalised', -19]];
        $people = $staff->whereIn('rank', [5, 6])->where('id', '!=', $employee->id)->shuffle()->values();
        foreach ($plan as $i => [$state, $days]) {
            $p = $i === 0 ? $employee : $people[$i];
            $manager = $this->people()->get($p->reporting_to) ?? $hr;
            $date = $today->copy()->addDays($days);
            $finalised = $state === 'finalised';
            $checkinId = $this->row('monthly_checking_models', [
                'Checkin_id' => 'M-C-I-' . str_pad((string) $next++, 4, '0', STR_PAD_LEFT), 'resort_id' => $this->resortId, 'emp_id' => $p->id,
                'date_discussion' => $date->format('Y-m-d'), 'start_time' => '15:00', 'end_time' => '15:30', 'Meeting_Place' => 'HOD Office',
                'approval_status' => match ($state) { 'pending' => 'pending', 'rejected' => 'rejected', default => 'approved' },
                'approved_at' => in_array($state, ['approved', 'finalised'], true) ? $date->copy()->subDays(2) : null,
                'rejected_at' => $state === 'rejected' ? $date->copy()->subDays(1) : null,
                'employee_rejection_reason' => $state === 'rejected' ? 'I am on a day off that afternoon — can we move it?' : null,
                'Area_of_Discussion' => $finalised ? 'Guest feedback and service speed' : null,
                'Area_of_Improvement' => $finalised ? 'Upselling during dinner service' : null,
                'Time_Line' => $finalised ? '30 Days' : null, 'comment' => $finalised ? 'Agreed actions for next month.' : null,
                'finalized_at' => $finalised ? $date->copy()->addDay() : null, 'created_by' => $manager->admin,
                'created_at' => $date->copy()->subDays(7), 'updated_at' => $finalised ? $date->copy()->addDay() : $date->copy()->subDays(2),
            ]);
            if ($finalised) {
                $response = ['Acknowledged', 'Acknowledged', 'Pending', 'Declined'][$i - 6];
                $this->row('monthly_checkin_reviews', ['resort_id' => $this->resortId, 'monthly_checkin_id' => $checkinId, 'round' => 1,
                    'area_of_improvement' => 'Upselling during dinner service', 'initiated_by' => $manager->admin, 'initiated_at' => $date->copy()->addDay(),
                    'employee_response' => $response, 'employee_comment' => $response === 'Acknowledged' ? 'Thank you, I will work on it.' : null,
                    'decline_reason' => $response === 'Declined' ? 'I was covering two sections that week.' : null,
                    'responded_at' => $response === 'Pending' ? null : $date->copy()->addDays(2), 'created_at' => $date->copy()->addDay(), 'updated_at' => $date->copy()->addDays(2)]);
                if ($response === 'Declined') {
                    $this->row('monthly_checkin_reviews', ['resort_id' => $this->resortId, 'monthly_checkin_id' => $checkinId, 'round' => 2,
                        'area_of_improvement' => 'Upselling during dinner service — with a buddy on busy nights', 'initiated_by' => $manager->admin,
                        'initiated_at' => $date->copy()->addDays(4), 'employee_response' => 'Pending', 'created_at' => $date->copy()->addDays(4), 'updated_at' => $date->copy()->addDays(4)]);
                }
            }
            $this->counts['check-ins']++;
        }
    }
}
