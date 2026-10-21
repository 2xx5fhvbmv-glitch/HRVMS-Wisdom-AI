<?php

namespace Database\Seeders\Demo;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demo ENV: the People sub-pages, in the shapes the People controllers write them —
 * qualification / experience tabs, onboarding (templates, events, itineraries with meetings, acknowledgements),
 * announcements, promotions, transfers, info-update requests and salary increments at every approval stage,
 * plus the letter templates those pages send.
 *
 * History is built backwards: an approved promotion / transfer / increment ends where the employee is today
 * (position, department, salary), so the payroll already seeded stays consistent and the daily
 * effective-date jobs find their "already applied" stamp and leave the employee alone.
 * approved_by = employees.id, created_by / modified_by = resort_admins.id, chains ordered by row id.
 */
class DemoPeopleSeeder
{
    use SeedsDemoRows;

    private array $counts = ['promotions' => 0, 'transfers' => 0, 'salary increments' => 0, 'announcements' => 0];
    private array $positions = [];
    private array $used = [];
    private object $hr;
    private object $gm;
    private object $finance;

    public function __construct(private int $resortId, private int $adminId)
    {
    }

    public function seed(): array
    {
        $this->hr = $this->login('hr');
        $this->gm = $this->login('gm');
        $this->finance = $this->login('finance');
        $this->positions = DB::table('resort_positions')->where('resort_id', $this->resortId)->whereNull('employee_category')
            ->get(['id', 'dept_id', 'section_id', 'Rank', 'position_title'])->keyBy('position_title')->all();
        $this->used = [];

        $this->letterTemplates();
        $this->qualifications();
        $this->onboarding();
        $this->announcements();
        $this->promotions();
        $this->transfers();
        $this->infoUpdates();
        $this->salaryIncrements();
        $this->compliances();
        return $this->counts;
    }

    /** A staff member (not a demo login) in that position who hasn't been used by another story yet — null if none is left. */
    private function holder(string $title): ?object
    {
        $prefix = strtolower(config('demo.prefix')) . '-';
        $p = $this->people()->first(fn ($p) => $p->Position_id == $this->positions[$title]->id && !isset($this->used[$p->id])
            && str_starts_with(strtolower($p->email), $prefix) && !in_array($p->employment_type, ['Casual', 'Internship'], true));
        if ($p) {
            $this->used[$p->id] = true;
        }
        return $p;
    }

    private function hodOf(int $deptId): object
    {
        return $this->people()->where('Dept_id', $deptId)->where('rank', '2')->first() ?? $this->hr;
    }

    private function employee(int $id): object
    {
        return DB::table('employees')->where('id', $id)->first();
    }

    private function sign(object $approver, Carbon $at): array
    {
        return ['signature_name' => $this->name($approver), 'signed_at' => $at];
    }

    private function letterTemplates(): void
    {
        $templates = [
            'success' => ['Confirmation of Employment – {{employee_name}}', 'Dear {{employee_name}}, we are pleased to confirm that you have successfully completed your probation as {{position}}.'],
            'failed' => ['Probation Outcome – {{employee_name}}', 'Dear {{employee_name}}, after review your probation as {{position}} has not been confirmed.'],
            'promotion' => ['Promotion Letter – {{employee_name}}', 'Dear {{employee_name}}, congratulations on your promotion to {{new_position}} effective {{effective_date}}.'],
            'experience' => ['Employment Certificate / Experience Letter – {{employee_name}}', 'TO WHOM IT MAY CONCERN. This is to certify that {{employee_name}} ({{employee_code}}) worked with us as {{position}}.'],
            'salary_increment' => ['Salary Increment Letter – {{employee_name}}', 'Dear {{employee_name}}, your salary has been revised to {{new_salary}} effective {{effective_date}}.'],
        ];
        $at = now()->subMonths(8);
        foreach ($templates as $type => [$subject, $content]) {
            $this->row('probation_letter_templates', ['resort_id' => $this->resortId, 'type' => $type, 'subject' => $subject,
                'content' => "<p>{$content}</p><p>Human Resources</p>", 'email_body' => '', 'placeholers' => '', 'created_at' => $at, 'updated_at' => $at]);
        }
    }

    /** Qualification and Experience tabs on the employee profile — for most staff and every login. */
    private function qualifications(): void
    {
        $schools = ['Maldives National University', 'IHM Mumbai', 'SLITHM Colombo', 'Villa College', 'Lyceum of the Philippines', 'Tribhuvan University', 'University of Dhaka'];
        $employers = ['Taj Exotica', 'Sun Siyam Iru Fushi', 'Hilton Colombo', 'Shangri-La Villingili', 'Kurumba Maldives', 'Marriott Kathmandu', 'Anantara Veli', 'Conrad Rangali'];
        $education = $experience = [];
        foreach ($this->people() as $p) {
            if (!str_ends_with($p->email, '@' . config('demo.login_domain')) && !$this->chance(0.85)) {
                continue;
            }
            $start = mt_rand(1998, 2016);
            $education[] = ['employee_id' => $p->id, 'education_level' => $p->rank <= 4 && $p->rank > 0 ? "Bachelor's Degree" : $this->pick(['Diploma', 'Certificate', 'Higher Secondary']),
                'institution_name' => $this->pick($schools), 'field_of_study' => $this->pick(['Hospitality Management', 'Culinary Arts', 'Business Administration', 'Accounting', 'Engineering']),
                'degree' => $p->rank <= 4 && $p->rank > 0 ? 'BA' : 'Diploma', 'attendance_period' => $start . '-' . ($start + mt_rand(1, 4)), 'certification' => null,
                'location' => $this->pick(['Maldives', 'India', 'Sri Lanka', 'Philippines', 'Nepal']), 'created_at' => now(), 'updated_at' => now()];
            $from = Carbon::parse($p->joining_date)->subYears(mt_rand(2, 6));
            for ($i = 0, $n = mt_rand(1, 2); $i < $n; $i++) {
                $to = $from->copy()->addMonths(mt_rand(14, 40));
                $experience[] = ['employee_id' => $p->id, 'company_name' => $this->pick($employers), 'job_title' => DB::table('resort_positions')->where('id', $p->Position_id)->value('position_title'),
                    'employment_type' => 'Permanent', 'duration' => $from->format('m/Y') . ' - ' . $to->format('m/Y'), 'location' => $this->pick(['Maldives', 'Dubai', 'Colombo', 'Mumbai']),
                    'reason_for_leaving' => $this->pick(['Career growth', 'Better opportunity', 'Contract ended', 'Relocation']),
                    'reference_name' => $this->pick(['A. Rasheed', 'S. Perera', 'M. Kumar', 'J. Santos']), 'reference_contact' => '+960 7' . mt_rand(100000, 999999),
                    'created_at' => now(), 'updated_at' => now()];
                $from = $to->copy()->addMonth();
            }
        }
        $this->rows('employees_education', $education);
        $this->rows('employees_experiance', $experience);
    }

    private function onboarding(): void
    {
        $at = now()->subMonths(6);
        $checklist = fn (string $week, array $items) => [
            ['type' => 'header', 'subtype' => 'h1', 'label' => $week, 'access' => false],
            ['type' => 'checkbox-group', 'required' => false, 'label' => "{$week} checklist", 'toggle' => false, 'inline' => false, 'access' => false, 'other' => false,
                'name' => 'checkbox-group-' . strtolower(str_replace(' ', '-', $week)),
                'values' => array_map(fn ($i) => ['label' => $i, 'value' => $i, 'selected' => false], $items)],
        ];
        $templates = [];
        foreach (['supervisor_line' => ['Line & Supervisor Onboarding', ['Airport pick-up', 'Work permit medical', 'Accommodation hand-over', 'Uniform issue'], ['Department orientation', 'Fire & safety training', 'Buddy shadowing']],
            'manager_above' => ['Manager Onboarding', ['Meet the GM', 'Department hand-over', 'Systems access'], ['Meet the HODs', 'Budget review', '30-day plan']]] as $type => [$name, $week1, $week2]) {
            // stored double-encoded, as the template builder saves it
            $templates[$type] = $this->row('itinerary_templates', ['resort_id' => $this->resortId, 'name' => $name, 'template_type' => $type,
                'description' => "{$name} programme for the first two weeks.",
                'fields' => json_encode(json_encode(array_merge($checklist('First Week', $week1), $checklist('Second Week', $week2)))),
                'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin, 'created_at' => $at, 'updated_at' => $at]);
        }
        foreach (['Employee Arrival' => '2 Days Before', 'Employee Picked Up' => 'Immidiate', 'Employee Dropped at Accommodation' => 'Immidiate',
            'Work Permit Medical' => '1 Day Before', 'Welcome Meeting' => '2 Hours Before'] as $event => $timing) {
            $this->row('onboarding_events', ['resort_id' => $this->resortId, 'event_name' => $event, 'notification_time' => $timing, 'created_at' => $at, 'updated_at' => $at]);
        }
        $this->row('cultural_insights', ['resort_id' => $this->resortId, 'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin, 'created_at' => $at, 'updated_at' => $at,
            'cultural_insights' => '<h4>Welcome to the Maldives</h4><p>The Maldives is a Muslim country: dress modestly off the guest islands, and alcohol is only served on resort islands.</p>'
                . '<p>Friday is the weekly holy day; during Ramadan staff working hours change. Dhivehi greetings: <em>Assalaamu alaikum</em>, thank you = <em>shukuriyyaa</em>.</p>']);

        // Itineraries for the newest line staff: past arrivals completed, the latest still pending.
        $transport = DB::table('resort_transportations')->where('resort_id', $this->resortId)->where('transportation_option', '!=', 'International Flight')->pluck('id', 'transportation_option')->all();
        $newest = $this->people()->where('rank', '6')->sortByDesc('joining_date')->take(5)->values();
        $hodPool = $this->people()->whereIn('rank', ['2', '4']);
        foreach ($newest as $i => $p) {
            $arrival = Carbon::parse($p->joining_date)->subDay();
            $done = $arrival->lt(Carbon::today()->subDays(2));
            $via = array_keys($transport)[$i % count($transport)];
            $hod = $this->hodOf($p->Dept_id);
            $pickup = $hodPool->where('Dept_id', $p->Dept_id)->first() ?? $hod;
            $itinerary = $this->row('employee_itineraries', [
                'resort_id' => $this->resortId, 'employee_id' => $p->id, 'template_id' => $templates['supervisor_line'],
                'greeting_message' => "Dear {$this->name($p)}, welcome aboard! We are excited to have you join the {$p->dept} team.",
                'arrival_date' => $arrival->format('Y-m-d'), 'arrival_time' => '14:00:00', 'entry_pass_file' => '', 'flight_ticket_file' => '',
                'pickup_employee_id' => $pickup->id, 'pickup_status' => $done ? 'Completed' : 'Pending',
                'accompany_medical_employee_id' => $this->hr->id, 'medical_escort_status' => $done ? 'Completed' : 'Pending',
                'resort_transportation_id' => $transport[$via],
                'speedboat_name' => $via === 'Speedboat' ? 'Ocean Runner' : null, 'speedboat_date' => $via === 'Speedboat' ? $arrival->format('Y-m-d') : null,
                'speedboat_departure_time' => $via === 'Speedboat' ? '15:30:00' : null, 'speedboat_arrival_time' => $via === 'Speedboat' ? '16:15:00' : null,
                'captain_number' => $via === 'Speedboat' ? '+960 7781234' : null,
                'seaplane_name' => $via === 'Seaplane' ? 'Trans Maldivian TMA' : null, 'seaplane_date' => $via === 'Seaplane' ? $arrival->format('Y-m-d') : null,
                'seaplane_departure_time' => $via === 'Seaplane' ? '15:00:00' : null, 'seaplane_arrival_time' => $via === 'Seaplane' ? '15:45:00' : null,
                'domestic_flight_date' => $via === 'Domestic Flight' ? $arrival->format('Y-m-d') : null,
                'domestic_departure_time' => $via === 'Domestic Flight' ? '16:00:00' : null, 'domestic_arrival_time' => $via === 'Domestic Flight' ? '16:50:00' : null,
                'hotel_name' => 'Hulhulé Island Hotel', 'hotel_contact_no' => '+960 3330888', 'booking_reference' => 'HIH-' . mt_rand(10000, 99999),
                'hotel_address' => 'Hulhulé Island, Malé', 'medical_center_name' => 'ADK Hospital', 'medical_center_contact_no' => '+960 3313553',
                'medical_type' => 'Work Permit Medical Test', 'medical_date' => $arrival->format('Y-m-d'), 'medical_time' => '10:00:00', 'approx_time' => '02:00',
                'created_at' => $arrival->copy()->subDays(10), 'updated_at' => $done ? $arrival : $arrival->copy()->subDays(10),
            ]);
            foreach ([['Welcome meeting with HR', 0, '10:00:00', [$this->hr->id, $p->id]], ["Department introduction – {$p->dept}", 1, '11:00:00', [$hod->id, $p->id]],
                ['Meet the General Manager', 3, '16:00:00', [$this->gm->id, $this->hr->id, $p->id]]] as [$title, $days, $time, $who]) {
                $this->row('employee_itineraries_meeting', ['employee_itinerary_id' => $itinerary, 'meeting_title' => $title,
                    'meeting_date' => Carbon::parse($p->joining_date)->addDays($days)->format('Y-m-d'), 'meeting_time' => $time,
                    'meeting_link' => 'https://meet.google.com/demo-onb-' . $itinerary . $days, 'meeting_participant_ids' => implode(',', $who),
                    'created_at' => $arrival->copy()->subDays(10), 'updated_at' => $arrival->copy()->subDays(10)]);
            }
            if ($done) {
                foreach (['Contract Signed', 'Benefit Grid Received', 'Employee Handbook Received and Acknowledged', 'Disciplinary Process'] as $type) {
                    $signed = Carbon::parse($p->joining_date)->addDays(mt_rand(0, 2))->setTime(mt_rand(9, 17), mt_rand(0, 59));
                    $this->row('employee_onboarding_acknowledgements', ['resort_id' => $this->resortId, 'employee_id' => $p->id, 'acknowledgement_type' => $type,
                        'status' => 'Yes', 'acknowledged_date' => $signed->format('Y-m-d'), 'signature_name' => $this->name($p), 'signed_at' => $signed,
                        'created_at' => $signed, 'updated_at' => $signed]);
                }
            }
        }
    }

    private function announcements(): void
    {
        $at = now()->subMonths(7);
        $categories = [];
        foreach (['Employee of the Month', 'Manager of the Month', 'Work Anniversary', 'Welcome Aboard'] as $name) {
            $categories[$name] = $this->row('announcement_category', ['resort_id' => $this->resortId, 'name' => $name,
                'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin, 'created_at' => $at, 'updated_at' => $at]);
        }
        $line = $this->people()->where('rank', '6')->shuffle()->values(); // shuffled: winners from every department
        $managers = $this->people()->whereIn('rank', ['2', '4'])->shuffle()->values();
        // [category, person pool, status, days from today, archived]
        $plan = [['Employee of the Month', $line, 'Published', -170, 1], ['Employee of the Month', $line, 'Published', -140, 1],
            ['Employee of the Month', $line, 'Published', -110, 0], ['Manager of the Month', $managers, 'Published', -95, 0],
            ['Employee of the Month', $line, 'Published', -80, 0], ['Work Anniversary', $line, 'Published', -50, 0],
            ['Employee of the Month', $line, 'Published', -20, 0], ['Manager of the Month', $managers, 'Published', -18, 0],
            ['Welcome Aboard', $line->sortByDesc('joining_date')->values(), 'Published', -6, 0],
            ['Employee of the Month', $line, 'Scheduled', 8, 0], ['Work Anniversary', $line, 'Scheduled', 13, 0], ['Manager of the Month', $managers, 'Draft', 0, 0]];
        $messages = [
            'Employee of the Month' => 'Congratulations %s on being named Employee of the Month! Thank you for the outstanding guest feedback and teamwork.',
            'Manager of the Month' => 'Congratulations %s, Manager of the Month — for leading the team through a record-occupancy month.',
            'Work Anniversary' => 'Happy work anniversary %s! Thank you for your dedication to the resort.',
            'Welcome Aboard' => 'Please join us in welcoming %s to the team!',
        ];
        $everyone = $this->people()->keys()->all();
        foreach ($plan as $i => [$category, $pool, $status, $days, $archived]) {
            $who = $pool[$i % max(1, count($pool))];
            $when = Carbon::today()->addDays($days)->setTime(9, 30);
            $created = $status === 'Published' ? $when : now()->subDays(2);
            $id = $this->row('announcement', ['resort_id' => $this->resortId, 'title' => $categories[$category], 'employee_id' => $who->id,
                'message' => sprintf($messages[$category], $this->name($who)), 'published_date' => $status === 'Draft' ? null : $when,
                'status' => $status, 'archived' => $archived, 'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin,
                'created_at' => $created, 'updated_at' => $created]);
            if ($status === 'Published') {
                $rows = [];
                foreach (array_rand(array_flip($everyone), mt_rand(3, 8)) as $from) {
                    if ($from != $who->id) {
                        $rows[] = ['resort_id' => $this->resortId, 'announcement_id' => $id, 'employee_id' => $from, 'status' => $this->pick(['unread', 'read', 'read']),
                            'created_at' => $when, 'updated_at' => $when];
                    }
                }
                $this->rows('announcement_notification', $rows);
            }
            $this->counts['announcements']++;
        }
    }

    private function promotions(): void
    {
        // [from position, to position, stage states HOD / Finance / GM, days from today the request was raised]
        $plan = [
            ['Guest Service Agent', 'Guest Service Supervisor', ['Approved', 'Approved', 'Approved'], -300],
            ['Commis Chef', 'Chef De Partie', ['Approved', 'Approved', 'Approved'], -220],
            ['Waiter', 'Captain', ['Approved', 'Approved', 'Approved'], -150],
            ['Technician', 'Engineering Supervisor', ['Approved', 'Approved', 'Approved'], -75],
            ['Waiter', 'Captain', ['Pending', 'Pending', 'Pending'], -2],
            ['Room Attendant', 'Housekeeping Supervisor', ['Approved', 'Pending', 'Pending'], -6],
            ['Butler', 'Guest Service Supervisor', ['Approved', 'Pending', 'Pending'], -4],
            ['Commis Chef', 'Chef De Partie', ['Approved', 'Approved', 'Pending'], -9],
            ['Electrician', 'Engineering Supervisor', ['Approved', 'On Hold', 'Pending'], -15],
            ['Kitchen Steward', 'Chef De Partie', ['Approved', 'Rejected', 'Pending'], -25],
        ];
        $remarks = ['Approved' => 'Strong performer, ready for the role.', 'On Hold' => 'Hold until the Q4 budget review.', 'Rejected' => 'Not budgeted this year — revisit next cycle.'];
        $letters = 0;
        foreach ($plan as [$from, $to, $states, $days]) {
            $done = $states === ['Approved', 'Approved', 'Approved'];
            // A finished promotion: the employee holds the new position today. An open one: still in the old.
            if (!$p = $this->holder($done ? $to : $from)) {
                continue; // that seat's holder left or is in another story this reset
            }
            $e = $this->employee($p->id);
            $raised = Carbon::today()->addDays($days)->setTime(10, 0);
            $effective = $done ? $raised->copy()->addDays(30) : Carbon::today()->addDays(30 + mt_rand(0, 20));
            $pct = $this->pick([8.0, 10.0, 12.5]);
            $newSalary = $done ? (float) $e->basic_salary : round($e->basic_salary * (1 + $pct / 100), 2);
            $oldSalary = $done ? round($newSalary / (1 + $pct / 100), 2) : (float) $e->basic_salary;
            $status = in_array('Rejected', $states, true) ? 'Rejected' : (in_array('On Hold', $states, true) ? 'On Hold' : ($done ? 'Approved' : 'Pending'));
            $hod = $this->hodOf($this->positions[$to]->dept_id);
            // The benefit-grid level of the new position's rank (the initiate form fills level + grid from it; the detail page links to it).
            $level = DB::table('resort_benefit_grade_level_ranks')->where('resort_id', $this->resortId)->where('rank', $this->positions[$to]->Rank)->value('grade_level_id');
            $id = $this->row('employee_promotions', [
                'resort_id' => $this->resortId, 'employee_id' => $p->id, 'current_position_id' => $this->positions[$from]->id, 'new_position_id' => $this->positions[$to]->id,
                'effective_date' => $effective->format('Y-m-d'), 'new_level' => (string) $level, 'updated_benefit_grid' => $level,
                'current_salary' => $oldSalary, 'salary_increment_percent' => $pct,
                'salary_increment_amount' => round($newSalary - $oldSalary, 2), 'new_salary' => $newSalary, 'budgeted_salary' => round($newSalary, -1),
                'comments' => "Recommended by {$this->name($hod)} for the {$to} role.", 'status' => $status,
                'follow_up_date' => $status === 'On Hold' ? Carbon::today()->addDays(21)->format('Y-m-d') : null,
                'letter_dispatched' => $done && $letters++ < 2 ? 'Yes' : 'No',
                'applied_at' => $done ? $effective : null, 'effective_day_notified_at' => $done ? $effective->copy()->setTime(6, 45) : null,
                'pre_promotion_snapshot' => $done ? json_encode(['Position_id' => $this->positions[$from]->id, 'Dept_id' => $e->Dept_id,
                    'rank' => (string) $this->positions[$from]->Rank, 'basic_salary' => number_format($oldSalary, 2, '.', '')]) : null,
                'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin, 'created_at' => $raised, 'updated_at' => $raised,
            ]);
            foreach (['HOD' => $hod, 'Finance' => $this->finance, 'GM' => $this->gm] as $rank => $approver) {
                $state = array_shift($states);
                $acted = $state !== 'Pending';
                $at = $raised->copy()->addDays(['HOD' => 1, 'Finance' => 3, 'GM' => 5][$rank]);
                $this->row('employee_promotions_approval', ['promotion_id' => $id, 'status' => $state, 'approval_rank' => $rank, 'approved_by' => $approver->id,
                    'remarks' => $acted ? $remarks[$state] : null, 'approved_at' => $acted ? $at : null,
                    'signature_name' => $state === 'Approved' ? $this->name($approver) : null, 'signed_at' => $state === 'Approved' ? $at : null,
                    'created_at' => $raised, 'updated_at' => $acted ? $at : $raised]);
            }
            $this->counts['promotions']++;
        }
    }

    private function transfers(): void
    {
        // [from position, to position, Permanent/Temporary, Finance / GM states, days from today raised]
        $plan = [
            ['Waiter', 'Guest Service Agent', 'Permanent', ['Approved', 'Approved'], -260],
            ['Room Attendant', 'Butler', 'Permanent', ['Approved', 'Approved'], -190],
            ['Gardener', 'Kitchen Steward', 'Permanent', ['Approved', 'Approved'], -120],
            ['Kitchen Steward', 'Storekeeper', 'Permanent', ['Approved', 'Approved'], -60],
            ['Butler', 'Waiter', 'Temporary', ['Approved', 'Approved'], -40],
            ['Waiter', 'Butler', 'Permanent', ['Pending', 'Pending'], -3],
            ['Room Attendant', 'Guest Service Agent', 'Permanent', ['Approved', 'Pending'], -7],
            ['Commis Chef', 'Waiter', 'Permanent', ['On Hold', 'Pending'], -12],
            ['Gardener', 'Technician', 'Permanent', ['Rejected', 'Pending'], -20],
        ];
        $remarks = ['Approved' => 'Cost neutral, approved.', 'On Hold' => 'Hold until the replacement is hired.', 'Rejected' => 'Department cannot release the employee now.'];
        $letters = 0;
        foreach ($plan as [$from, $to, $kind, $states, $days]) {
            $done = $states === ['Approved', 'Approved'];
            if (!$p = $this->holder($done ? $to : $from)) {
                continue;
            }
            $e = $this->employee($p->id);
            $raised = Carbon::today()->addDays($days)->setTime(11, 0);
            $effective = $done ? $raised->copy()->addDays(14) : Carbon::today()->addDays(14 + mt_rand(0, 14));
            [$fromPos, $toPos] = [$this->positions[$from], $this->positions[$to]];
            $status = in_array('Rejected', $states, true) ? 'Rejected' : (in_array('On Hold', $states, true) ? 'On Hold' : ($done ? 'Approved' : 'Pending'));
            $temporary = $kind === 'Temporary';
            $id = $this->row('employee_transfers', [
                'resort_id' => $this->resortId, 'employee_id' => $p->id,
                'current_department_id' => $fromPos->dept_id, 'current_section_id' => $fromPos->section_id, 'current_position_id' => $fromPos->id,
                'target_department_id' => $toPos->dept_id, 'target_section_id' => $toPos->section_id, 'target_position_id' => $toPos->id,
                'reason_for_transfer' => $temporary ? 'Cover during peak season.' : "Moving to {$to} to fill an open seat and widen experience.",
                'effective_date' => $effective->format('Y-m-d'), 'transfer_status' => $kind, 'reporting_manager' => $this->hodOf($toPos->dept_id)->id,
                'budgeted_salary' => round((float) $e->basic_salary, -1), 'proposed_salary' => $temporary ? null : (float) $e->basic_salary,
                'temporary_from' => $temporary ? $effective->format('Y-m-d') : null, 'temporary_to' => $temporary ? Carbon::today()->addMonths(2)->format('Y-m-d') : null,
                'status' => $status, 'letter_dispatched' => $done && $letters++ < 3 ? 'Yes' : 'No',
                'effective_day_notified_at' => $done ? $effective->copy()->setTime(6, 30) : null,
                'pre_transfer_snapshot' => $done ? json_encode(['Dept_id' => $fromPos->dept_id, 'Section_id' => $fromPos->section_id, 'Position_id' => $fromPos->id,
                    'division_id' => DB::table('resort_departments')->where('id', $fromPos->dept_id)->value('division_id'),
                    'reporting_to' => $this->hodOf($fromPos->dept_id)->id, 'rank' => (string) $fromPos->Rank, 'basic_salary' => (string) $e->basic_salary]) : null,
                'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin, 'created_at' => $raised, 'updated_at' => $raised,
            ]);
            foreach (['Finance' => $this->finance, 'GM' => $this->gm] as $rank => $approver) {
                $state = array_shift($states);
                $acted = $state !== 'Pending';
                $at = $raised->copy()->addDays($rank === 'Finance' ? 2 : 4);
                $this->row('employee_transfers_approval', ['transfer_id' => $id, 'status' => $state, 'approval_rank' => $rank, 'approved_by' => $approver->id,
                    'remarks' => $acted ? $remarks[$state] : null, 'approved_at' => $acted ? $at : null,
                    'signature_name' => $state === 'Approved' ? $this->name($approver) : null, 'signed_at' => $state === 'Approved' ? $at : null,
                    'created_at' => $raised, 'updated_at' => $acted ? $at : $raised]);
            }
            $this->counts['transfers']++;
        }
    }

    /** Requests raised from the mobile profile; payload keys are exactly the ones the review modal knows. */
    private function infoUpdates(): void
    {
        $plan = [['Pending', 'personal_phone'], ['Pending', 'address_line_1'], ['Pending', 'last_name'], ['Pending', 'personal_phone'],
            ['Approved', null], ['Approved', null], ['Rejected', 'last_name'], ['Rejected', 'dob']];
        $streets = ['Majeedhee Magu', 'Chandhanee Magu', 'Orchid Magu', 'Ameenee Magu'];
        $pool = $this->people()->filter(fn ($p) => $p->rank == 6 && !isset($this->used[$p->id]))->values();
        foreach ($plan as $i => [$status, $change]) {
            $p = $pool[$i * 3 % count($pool)];
            $this->used[$p->id] = true;
            $admin = DB::table('resort_admins')->where('id', $p->admin)->first();
            $payload = ['first_name' => $admin->first_name, 'middle_name' => $admin->middle_name, 'last_name' => $admin->last_name,
                'personal_phone' => $admin->personal_phone, 'dob' => DB::table('employees')->where('id', $p->id)->value('dob'),
                'address_line_1' => $admin->address_line_1, 'address_line_2' => $admin->address_line_2];
            $payload[$change ?? 'personal_phone'] = match ($change) {
                'personal_phone' => '+960 9' . mt_rand(100000, 999999),
                'address_line_1' => 'H. ' . $this->pick(['Blue Lagoon', 'Coral View', 'Sea Breeze']) . ', ' . $this->pick($streets),
                'last_name' => $admin->last_name . ($p->gender === 'female' ? ' Rasheed' : ' Hassan'),
                'dob' => Carbon::parse($payload['dob'] ?: '1990-01-01')->subYear()->format('Y-m-d'),
                default => $admin->personal_phone, // approved: already applied, nothing differs any more
            };
            $at = now()->subDays($status === 'Pending' ? mt_rand(0, 6) : mt_rand(15, 60));
            $this->row('employee_info_update_request', ['resort_id' => $this->resortId, 'employee_id' => $p->id, 'title' => 'Personal Information',
                'info_payload' => json_encode($payload), 'status' => $status,
                'reject_reason' => $status === 'Rejected' ? 'Please attach a supporting document (passport / marriage certificate).' : null,
                'created_by' => $p->admin, 'modified_by' => $status === 'Pending' ? $p->admin : $this->hr->admin, 'created_at' => $at, 'updated_at' => $at]);
        }
    }

    private function salaryIncrements(): void
    {
        $types = DB::table('increment_types')->where('resort_id', $this->resortId)->where('status', 'Active')->pluck('name')->all() ?: ['Annual Performance Increment'];
        // [Finance state, GM state, days from today raised]
        $plan = [['Approved', 'Approved', -330], ['Approved', 'Approved', -280], ['Approved', 'Approved', -200], ['Approved', 'Approved', -120], ['Approved', 'Approved', -45],
            ['Pending', 'Pending', -2], ['Pending', 'Pending', -3], ['Pending', 'Pending', -5], ['Approved', 'Pending', -6], ['Approved', 'Pending', -8],
            ['Hold', 'Pending', -10], ['Change-Request', 'Pending', -9], ['Rejected', 'Pending', -30], ['Rejected', 'Pending', -40]];
        $pool = $this->people()->filter(fn ($p) => in_array((int) $p->rank, [4, 5, 6], true) && !isset($this->used[$p->id])
            && !in_array($p->employment_type, ['Casual', 'Internship'], true))->values();
        foreach ($plan as $i => [$financeState, $gmState, $days]) {
            $p = $pool[($i * 5 + 1) % count($pool)];
            $this->used[$p->id] = true;
            $e = $this->employee($p->id);
            $done = $gmState === 'Approved';
            $percentage = $i % 3 !== 2;
            $value = $percentage ? $this->pick([3.0, 5.0, 7.5]) : $this->pick([50.0, 75.0, 100.0]); // value is decimal(5,2)
            $raised = Carbon::today()->addDays($days)->setTime(9, 0);
            $effective = $done ? $raised->copy()->addDays(20) : Carbon::today()->addDays(25);
            // Approved: today's salary is the new one. Open: today's salary is the old one.
            if ($done) {
                $new = (float) $e->basic_salary;
                $old = $percentage ? round($new / (1 + $value / 100), 2) : $new - $value;
            } else {
                $old = (float) $e->basic_salary;
                $new = $percentage ? round($old * (1 + $value / 100), 2) : $old + $value;
            }
            $parent = match (true) { $done => 'Approved', $financeState === 'Rejected' => 'Rejected', $financeState === 'Hold' => 'Hold',
                $financeState === 'Change-Request' => 'Change-Request', default => 'Pending' };
            $id = $this->row('people_salary_increment', [
                'employee_id' => $p->id, 'resort_id' => $this->resortId, 'increment_type' => $types[$i % count($types)], 'previous_salary' => $old,
                'new_salary' => $new, 'increment_amount' => round($new - $old, 2), 'pay_increase_type' => $percentage ? 'Percentage' : 'Fixed', 'value' => $value,
                'effective_date' => $effective->format('Y-m-d'), 'status' => $parent, 'letter_dispatched' => $done && $i < 3 ? 'Yes' : 'No',
                'effective_day_applied_at' => $done ? $effective->copy()->setTime(6, 15) : null,
                'remarks' => $financeState === 'Change-Request' ? 'Please align with the benefit grid for this rank.' : null,
                'due_date' => $parent === 'Hold' ? Carbon::today()->addDays(14)->format('Y-m-d') : null,
                'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin, 'created_at' => $raised, 'updated_at' => $raised,
            ]);
            foreach (['Finance' => [$financeState, $this->finance, 2], 'GM' => [$gmState, $this->gm, 4]] as $rank => [$state, $approver, $after]) {
                $acted = $state !== 'Pending';
                $at = $raised->copy()->addDays($after);
                $this->row('people_salary_increment_status', ['people_salary_increment_id' => $id, 'approval_rank' => $rank, 'status' => $state,
                    'approved_by' => $acted ? $approver->id : null, 'action_date' => $acted ? $at->format('Y-m-d') : null,
                    'remarks' => match ($state) { 'Hold' => "Let's hold until the budget review.", 'Change-Request' => 'Please align with the benefit grid for this rank.', 'Approved' => 'Approved.', default => null },
                    'reject_reason' => $state === 'Rejected' ? 'Increment already given this year.' : null,
                    'signature_name' => $state === 'Approved' ? $this->name($approver) : null, 'signed_at' => $state === 'Approved' ? $at : null,
                    'created_by' => $this->hr->admin, 'modified_by' => $acted ? $approver->admin : $this->hr->admin, 'created_at' => $raised, 'updated_at' => $acted ? $at : $raised]);
            }
            if ($done) {
                DB::table('employees')->where('id', $p->id)->update(['incremented_date' => $effective->format('Y-m-d'),
                    'last_increment_salary_amount' => round($new - $old, 2), 'last_salary_increment_type' => $types[$i % count($types)]]);
            }
            $this->counts['salary increments']++;
        }
    }
    /**
     * Compliance breaches as the rules engine (ComplianceController::checkCompliance) records them — same module
     * and breach names and description wording — some already explained by the AI (ai_status 'ready'), a few
     * resolved. HR can still press Run to add the live ones.
     */
    private function compliances(): void
    {
        $title = fn ($p) => DB::table('resort_positions')->where('id', $p->Position_id)->value('position_title');
        // Prefer someone not already in another story; a breach may still land on someone who is (some seats have one holder).
        $pick = fn (callable $where) => $this->people()->first(fn ($p) => !isset($this->used[$p->id]) && $where($p)) ?? $this->people()->first($where);
        $line = fn (string $dept) => $pick(fn ($p) => (int) $p->rank === 6 && $p->dept === $dept && !in_array($p->employment_type, ['Casual', 'Internship'], true));
        $week = Carbon::today()->subWeeks(2)->startOfWeek();
        $rows = [];
        // $text builds the rules engine's wording for that employee; nothing is written when no one fits.
        $add = function (?object $p, string $module, string $breach, callable $text, int $daysAgo, ?array $ai = null, bool $resolved = false) use (&$rows) {
            if (!$p) {
                return;
            }
            $this->used[$p->id] = true;
            $at = now()->subDays($daysAgo)->setTime(6, 30);
            $rows[] = ['resort_id' => $this->resortId, 'employee_id' => $p->id, 'module_name' => $module, 'compliance_breached_name' => $breach,
                'description' => $text($p), 'severity_ai' => $ai[0] ?? null, 'description_ai' => $ai[1] ?? null, 'remediation_ai' => $ai[2] ?? null,
                'ai_status' => $ai ? 'ready' : null, 'ai_generated_at' => $ai ? $at : null, 'reported_on' => $at,
                'status' => $resolved ? 'Resolved' : 'Breached', 'Dismissal_status' => $resolved ? 'Resolved' : 'Pending',
                'created_at' => $at, 'updated_at' => $resolved ? $at->copy()->addDays(3) : $at];
        };
        $weekly = fn (string $hours, Carbon $of) => fn ($p) => "Employee {$this->name($p)} ({$title($p)}) worked {$hours} normal hours (breaks and approved overtime excluded) in the week of "
            . $of->format('d M Y') . '. Adjust the schedule to comply with the 48-hour weekly limit.';

        foreach (['F&B Service' => '52.5', 'Kitchen' => '50'] as $dept => $hours) {
            $add($line($dept), 'Time & Attendance', 'Weekly Working Hours Limit', $weekly($hours, $week), 9,
                ['High', 'Rostered above the 48-hour weekly limit in the Employment Act during a high-occupancy week.', 'Rebalance next week\'s roster across the section and record the extra hours as overtime.']);
        }
        $add($line('Housekeeping'), 'Time & Attendance', 'Without Mandatory Break',
            fn ($p) => "Employee {$this->name($p)} ({$title($p)}) worked 9 hours without a break. Review schedule to ensure adherence to labor laws.", 5,
            ['Medium', 'A shift over 6 hours had no recorded break.', 'Ask the supervisor to confirm the break and correct the punch, or schedule a fixed break slot.']);
        $add($pick(fn ($p) => (int) $p->rank === 4), 'Time and Attendance', 'Over Time Not Eligibile',
            fn ($p) => "{$this->name($p)} ({$p->Emp_id} - {$title($p)}) is not eligible for overtime.", 12);
        $rate = (float) DB::table('resort_site_settings')->where('resort_id', $this->resortId)->value('DollertoMVR') ?: 15.42;
        foreach ($this->people()->where('rank', '2')->filter(fn ($p) => $p->nationality !== 'Maldivian')->take(2) as $p) {
            $add($p, 'People Management', 'TIN Requirement', function ($p) use ($title, $rate) {
                $mvr = number_format(round((float) DB::table('employees')->where('id', $p->id)->value('basic_salary') * $rate / 100) * 100); // expat pay is USD
                return "{$this->name($p)} ({$p->Emp_id} - {$title($p)}) (RSWT: MVR {$mvr}/month) not registered. Submit MIRA 118 form.";
            }, 20, ['High', 'Earnings are above the MIRA withholding threshold but no TIN is on file.', 'Collect the TIN or file MIRA 118 for this employee before the next payroll run.']);
        }
        $add($pick(fn ($p) => $p->nationality === 'Maldivian' && (int) $p->rank === 6), 'Pension Compliance', 'Pension Deduction Below 7%', function ($p) {
            $basic = number_format((float) DB::table('employees')->where('id', $p->id)->value('basic_salary'), 2, '.', '');
            return "Employee {$this->name($p)} is eligible for pension (age " . mt_rand(24, 45) . " years). Pension deduction is below the required 7% of basic salary ({$basic}).";
        }, 30, ['Critical', 'Last month\'s pension deduction was taken on earned salary, below 7% of the full basic.', 'Top up the difference in the next run and report the correction to the Maldives Pension Administration Office.']);
        if ($promotion = DB::table('employee_promotions')->where('resort_id', $this->resortId)->where('status', 'Approved')->orderByDesc('effective_date')->first()) {
            $new = DB::table('resort_positions')->where('id', $promotion->new_position_id)->value('position_title');
            $add($this->people()[$promotion->employee_id] ?? null, 'Employee Promotion', 'Job Description Not Received',
                fn ($p) => "Employee {$this->name($p)} ({$title($p)}) was promoted to {$new} on " . Carbon::parse($promotion->effective_date)->format('d M Y')
                    . ' but has not received the job description.', 14);
        }
        if ($extended = DB::table('employees')->where('resort_id', $this->resortId)->where('probation_status', 'Extended')->first(['id', 'joining_date', 'probation_end_date'])) {
            $months = (int) round(Carbon::parse($extended->joining_date)->diffInMonths(Carbon::parse($extended->probation_end_date), true));
            $add($this->people()[$extended->id] ?? null, 'Probation', 'Extended Probation Period',
                fn ($p) => "Probation period for {$this->name($p)}({$title($p)}) is set to {$months} months. Reduce to comply with the 3-month maximum", 1);
        }
        $add($line('Front Office'), 'Time & Attendance', 'Weekly Working Hours Limit', $weekly('49', $week->copy()->subWeeks(6)), 50, null, true);
        $add($line('Engineering'), 'Payroll Compliance', 'Service Charge Compliance',
            fn ($p) => "Employee {$this->name($p)} is eligible for service charge but receiving less than the required amount. Expected: 1.00, Received: 0.80", 70, null, true);
        $this->rows('compliances', $rows);
        $this->counts['compliance breaches'] = count($rows);
    }
}
