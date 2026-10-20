<?php

namespace Database\Seeders\Demo;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo ENV: Talent Acquisition, as VacancyController / ConfigController / ApplicantController /
 * ApplicantsController / InterviewInvitationController / Offer- and ContractResponseController write it:
 * - vacancies (mostly the seats Workforce Planning left open) at every approval stage — waiting on HR,
 *   Finance, the GM, on hold, approved and advertised, approved but not yet advertised, closed, draft.
 *   Chain = one t_anotification_children row per approver (3 HR, 7 Finance, 8 GM); a fully approved
 *   vacancy has 3/7 'Approved' and 8 'ForwardedToNext' with its application_links row on the GM row.
 * - applicants at every pipeline stage. "Latest status" in the app is the applicant's highest-id
 *   applicant_wise_statuses row, so each applicant's rows are inserted in order: AI shortlist → HR round
 *   → HOD round → selected → offer → contract. Rounds that end update the same row; the next round adds one.
 * - interviews (Pending Review → Invitation Sent → Slot Booked / Invitation Rejected / Cancelled), offer
 *   letters and contracts. Each applicant has exactly one work-experience row (pages inner-join it).
 */
class DemoTalentSeeder
{
    use SeedsDemoRows;

    const NAMES = [['Rahul', 'Menon', 'male', 'India'], ['Aishath', 'Rasheed', 'female', 'Maldives'], ['Kasun', 'Perera', 'male', 'Sri Lanka'],
        ['Maria', 'Santos', 'female', 'Philippines'], ['Bikash', 'Thapa', 'male', 'Nepal'], ['Ahmed', 'Naseem', 'male', 'Maldives'],
        ['Divya', 'Nair', 'female', 'India'], ['Rafiq', 'Hossain', 'male', 'Bangladesh'], ['Nimali', 'Silva', 'female', 'Sri Lanka'],
        ['Joseph', 'Reyes', 'male', 'Philippines'], ['Mariyam', 'Shareef', 'female', 'Maldives'], ['Arjun', 'Pillai', 'male', 'India'],
        ['Sanjeev', 'Gurung', 'male', 'Nepal'], ['Hawwa', 'Didi', 'female', 'Maldives'], ['Kiran', 'Rao', 'male', 'India'],
        ['Liza', 'Cruz', 'female', 'Philippines'], ['Tharindu', 'Fernando', 'male', 'Sri Lanka'], ['Ibrahim', 'Latheef', 'male', 'Maldives'],
        ['Sneha', 'Kumar', 'female', 'India'], ['Anil', 'Shrestha', 'male', 'Nepal'], ['Fathimath', 'Zahir', 'female', 'Maldives'],
        ['Mohammed', 'Islam', 'male', 'Bangladesh']];
    const PHONE = ['India' => '+91', 'Maldives' => '+960', 'Sri Lanka' => '+94', 'Philippines' => '+63', 'Nepal' => '+977', 'Bangladesh' => '+880'];

    private array $counts = ['vacancies' => 0, 'applicants' => 0, 'interviews' => 0];
    private object $hr;
    private object $finance;
    private object $gm;
    private array $sources = [];
    private array $countries = [];
    private int $templateId = 0;
    private int $nameIndex = 0;

    public function __construct(private int $resortId, private int $adminId)
    {
    }

    public function seed(): array
    {
        $now = Carbon::now();
        $this->hr = $this->login('hr');
        $this->finance = $this->login('finance');
        $this->gm = $this->login('gm');
        $setup = $now->copy()->subMonths(6);

        // Set-up rows the TA pages read.
        $this->row('job_advertisements', ['Resort_id' => $this->resortId, 'Jobadvimg' => '', 'vacancy_id' => null, 'FinalApproval' => null,
            'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin, 'created_at' => $setup, 'updated_at' => $setup]);
        foreach (['Direct' => '#6c757d', 'LinkedIn' => '#0A66C2', 'Facebook' => '#1877F2', 'Indeed' => '#003A9B'] as $name => $colour) {
            $this->sources[] = $this->row('hiring_sources', ['resort_id' => $this->resortId, 'source_name' => $name, 'colour' => $colour,
                'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin, 'created_at' => $setup, 'updated_at' => $setup]);
        }
        foreach (['Interview Slot Selection Request' => 'Please choose an interview slot', 'Confirmed Interview Meeting Link' => 'Your interview is confirmed',
            'Selection Email Template' => 'Congratulations — you have been selected', 'Rejection Email Template' => 'Your application',
            'HOD Round Interview Invitation' => 'Next round interview'] as $name => $subject) {
            $id = $this->row('ta_email_templates', ['Resort_id' => $this->resortId, 'TempleteName' => $name, 'MailSubject' => $subject,
                'MailTemplete' => '<p>Dear {{candidate_name}},</p><p>' . $subject . ' for the {{position_title}} role at {{resort_name}}.</p><p>Kind regards,<br>HR Team</p>',
                'Placeholders' => json_encode(['candidate_name', 'position_title', 'resort_name']), 'created_at' => $setup, 'updated_at' => $setup]);
            $this->templateId = $this->templateId ?: $id;
        }
        foreach (['offer_letter' => 'Standard Offer Letter', 'contract' => 'Employment Contract'] as $type => $name) {
            $this->row('ta_document_templates', ['resort_id' => $this->resortId, 'type' => $type, 'name' => $name, 'is_default' => 1,
                'content' => "<p>Dear {{candidate_name}},</p><p>{$name} for the position of {{position_title}}, starting {{start_date}}.</p>",
                'created_at' => $setup, 'updated_at' => $setup]);
        }
        $this->countries = DB::table('countries')->whereIn('name', array_keys(self::PHONE))->pluck('id', 'name')->map(fn ($id) => (string) $id)->all();
        foreach (['Island Staffing Agency', 'Asia Hospitality Recruiters'] as $name) {
            $this->row('service_providers', ['name' => $name, 'resort_id' => $this->resortId, 'created_at' => $setup, 'updated_at' => $setup]);
        }
        foreach (['Air Maldives Travel' => 'tickets@airmaldivestravel.example', 'Island Hopper Tours' => 'bookings@islandhopper.example'] as $name => $email) {
            $this->row('ticket_agents', ['Resort_id' => $this->resortId, 'name' => $name, 'agents_email' => $email,
                'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin, 'created_at' => $setup, 'updated_at' => $setup]);
        }
        // Job descriptions (the list filters by compliance), a screening questionnaire and interview assessment forms.
        $position = fn (string $title) => DB::table('resort_positions as p')->join('resort_departments as d', 'd.id', '=', 'p.dept_id')
            ->where('p.resort_id', $this->resortId)->where('p.position_title', $title)->whereNull('p.employee_category')
            ->first(['p.id', 'p.dept_id', 'p.section_id', 'd.division_id']);
        foreach ([['Guest Relations Officer', 'Approved', null], ['Plumber', 'Approved', null], ['Waiter', 'Approved', null], ['Bartender', 'HR Approved', null],
            ['Pastry Chef', 'Rejected', 'Missing required content: Working hours.']] as [$title, $compliance, $reason]) {
            $p = $position($title);
            $this->row('job_descriptions', ['Resort_id' => $this->resortId, 'Division_id' => $p->division_id, 'Department_id' => $p->dept_id,
                'Position_id' => $p->id, 'Section_id' => $p->section_id, 'slug' => 'jd-' . strtolower(Str::random(8)), 'compliance' => $compliance, 'reason' => $reason,
                'jobdescription' => "<h4>{$title}</h4><p><strong>Reports to:</strong> Head of Department</p><p><strong>Duties:</strong> deliver the resort's service standards, "
                    . 'keep the work area safe and clean, support the team during peak occupancy.</p>' . ($reason ? '' : '<p><strong>Working hours:</strong> 48 hours a week, 6 days.</p>'),
                'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin, 'created_at' => $setup, 'updated_at' => $setup]);
        }
        $p = $position('Guest Relations Officer');
        $questionnaire = $this->row('questionnaires', ['Resort_id' => $this->resortId, 'Division_id' => $p->division_id, 'Department_id' => $p->dept_id,
            'Position_id' => $p->id, 'video' => 'No', 'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin, 'created_at' => $setup, 'updated_at' => $setup]);
        foreach ([['Why do you want to work in the Maldives?', 'single', null, null], ['Which languages can you speak with guests?', 'multiple', ['English', 'Russian', 'Chinese', 'German'], '0'],
            ['Are you comfortable with split shifts?', 'Radio', null, null]] as [$question, $type, $options, $answer]) {
            $this->row('questionnaire_children', ['Q_Parent_id' => $questionnaire, 'Question' => $question, 'questionType' => $type,
                'options' => $options ? json_encode($options) : null, 'ans' => $answer, 'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin,
                'created_at' => $setup, 'updated_at' => $setup]);
        }
        $rating = fn (string $label, string $name) => ['type' => 'select', 'required' => true, 'label' => $label, 'className' => 'form-control', 'name' => $name,
            'values' => array_map(fn ($v) => ['label' => (string) $v, 'value' => (string) $v, 'selected' => false], range(1, 5))];
        foreach (['Guest Relations Officer', 'Plumber', 'Waiter'] as $title) {
            $this->row('interview_assessment_forms', ['resort_id' => $this->resortId, 'position' => $position($title)->id, 'form_name' => "{$title} Interview Assessment",
                // stored double-encoded, the way the form builder posts it
                'form_structure' => json_encode(json_encode([
                    ['type' => 'header', 'subtype' => 'h2', 'label' => "{$title} – Interview Assessment"],
                    $rating('Communication skills', 'select-communication'), $rating('Role knowledge', 'select-knowledge'),
                    $rating('Attitude and presentation', 'select-attitude'),
                    ['type' => 'textarea', 'required' => false, 'label' => 'Interviewer comments', 'className' => 'form-control', 'name' => 'textarea-comments', 'subtype' => 'textarea'],
                ])), 'created_at' => $setup, 'updated_at' => $setup]);
        }

        // [position title, chain (HR / Finance / GM), vacancy status, advertised?]
        $vacancies = [
            'pendingHr' => ['Pastry Chef', ['Active', 'Active', 'Active'], 'Active', false],
            'pendingFinance' => ['Sommelier', ['Approved', 'Active', 'Active'], 'Active', false],
            'pendingGm' => ['Commis Chef', ['Approved', 'Approved', 'Active'], 'Active', false],
            'onHold' => ['Technician', ['Approved', 'Hold', 'Active'], 'Active', false],
            'guestRelations' => ['Guest Relations Officer', ['Approved', 'Approved', 'ForwardedToNext'], 'Active', true],
            'plumber' => ['Plumber', ['Approved', 'Approved', 'ForwardedToNext'], 'Active', true],
            'waiter' => ['Waiter', ['Approved', 'Approved', 'ForwardedToNext'], 'Active', true],
            'bartender' => ['Bartender', ['Approved', 'Approved', 'ForwardedToNext'], 'Active', null], // approved, no job ad yet
            'closed' => ['Room Attendant', ['Approved', 'Approved', 'ForwardedToNext'], 'Closed', true],
            'draft' => ['Laundry Attendant', [], 'Draft', false],
        ];
        $ids = [];
        $i = 0;
        foreach ($vacancies as $key => [$title, $chain, $status, $advertised]) {
            $ids[$key] = $this->vacancy($title, $chain, $status, $advertised, $now->copy()->subDays(70 - $i * 6));
            $i++;
        }

        // Applicants. [vacancy, steps]; a step is [round rank, status, comments?, interview?]. The HR round is
        // rank 3, the HOD round rank 2 (line positions have two rounds). Interview: [status, days from today, link?, interviewer].
        $plans = [
            ['guestRelations', [[0, 'Sortlisted By Wisdom AI']]],
            ['guestRelations', [[0, 'Sortlisted By Wisdom AI'], [3, 'Sortlisted', null, ['Pending Review', 3, false, 'hr']]]],
            ['guestRelations', [[0, 'Sortlisted By Wisdom AI'], [3, 'Sortlisted', null, ['Invitation Sent', 4, false, 'hr']]]],
            ['guestRelations', [[0, 'Sortlisted By Wisdom AI'], [3, 'Sortlisted', null, ['Slot Booked', 1, true, 'hr']]]],
            ['guestRelations', [[0, 'Sortlisted By Wisdom AI'], [3, 'Round', null, ['Slot Booked', 2, true, 'hr']]]],
            ['guestRelations', [[0, 'Sortlisted By Wisdom AI'], [3, 'Sortlisted', null, ['Invitation Rejected', 2, false, 'hr']]]],
            ['guestRelations', [[0, 'Sortlisted By Wisdom AI'], [3, 'Complete', 'Good English, warm with guests.', ['Slot Booked', -6, true, 'hr']], [2, 'Sortlisted', null, ['Slot Booked', 4, true, 'hod']]]],
            ['guestRelations', [[0, 'Sortlisted By Wisdom AI'], [3, 'Complete', 'Strong candidate, pass to the HOD.', ['Slot Booked', -3, true, 'hr']]]],
            ['plumber', [[0, 'Sortlisted By Wisdom AI'], [3, 'Complete', 'Hands-on experience with resort plumbing.', ['Slot Booked', -12, true, 'hr']], [2, 'Complete', 'Technically solid.', ['Slot Booked', -7, true, 'hod']]]],
            ['plumber', [[0, 'Sortlisted By Wisdom AI'], [3, 'Complete', null, ['Slot Booked', -15, true, 'hr']], [2, 'Selected', 'Best of the shortlist.', ['Slot Booked', -10, true, 'hod']]]],
            ['plumber', [[0, 'Sortlisted By Wisdom AI'], [3, 'Complete', null, ['Slot Booked', -20, true, 'hr']], [2, 'Offer Letter Sent', null, ['Slot Booked', -14, true, 'hod']]], 'offer:Sent'],
            ['waiter', [[0, 'Sortlisted By Wisdom AI'], [3, 'Complete', null, ['Slot Booked', -24, true, 'hr']], [2, 'Offer Letter Accepted', null, ['Slot Booked', -18, true, 'hod']]], 'offer:Accepted'],
            ['waiter', [[0, 'Sortlisted By Wisdom AI'], [3, 'Complete', null, ['Slot Booked', -25, true, 'hr']], [2, 'Offer Letter Rejected', null, ['Slot Booked', -19, true, 'hod']]], 'offer:Rejected'],
            ['waiter', [[0, 'Sortlisted By Wisdom AI'], [3, 'Complete', null, ['Slot Booked', -28, true, 'hr']], [2, 'Contract Sent', null, ['Slot Booked', -22, true, 'hod']]], 'contract:Sent'],
            ['closed', [[0, 'Sortlisted By Wisdom AI'], [3, 'Complete', null, ['Slot Booked', -40, true, 'hr']], [2, 'Contract Accepted', null, ['Slot Booked', -35, true, 'hod']]], 'contract:Accepted'],
            ['waiter', [[0, 'Sortlisted By Wisdom AI'], [3, 'Complete', null, ['Slot Booked', -30, true, 'hr']], [2, 'Contract Rejected', null, ['Slot Booked', -26, true, 'hod']]], 'contract:Rejected'],
            ['guestRelations', [[0, 'Sortlisted By Wisdom AI'], [3, 'Rejected', 'Insufficient guest-facing experience.']]],
            ['plumber', [[0, 'Sortlisted By Wisdom AI'], [3, 'Rejected', 'Removed from shortlist by HR', ['Cancelled', 2, false, 'hr']]]],
            ['waiter', [[0, 'Sortlisted By Wisdom AI'], [3, 'Complete', null, ['Slot Booked', -9, true, 'hr']], [2, 'Rejected', 'Not ready for fine-dining service yet.', ['Slot Booked', -5, true, 'hod']]]],
            ['waiter', [[0, 'Rejected By Wisdom AI', 'CV does not match the role requirements.']]],
            ['closed', [[0, 'Sortlisted By Wisdom AI'], [3, 'Rejected', 'Position filled.']]],
            ['plumber', [[0, 'Sortlisted By Wisdom AI']]],
        ];
        foreach ($plans as $plan) {
            $this->applicant($ids[$plan[0]], $plan[1], $plan[2] ?? null);
        }

        Cache::forget("ta_insights:{$this->resortId}");
        return $this->counts;
    }

    /** One vacancy with its approval chain (and job link once the GM forwarded it). Returns [vacancy id, position, HOD]. */
    private function vacancy(string $title, array $chain, string $status, ?bool $advertised, Carbon $raised): array
    {
        $position = DB::table('resort_positions')->where('resort_id', $this->resortId)->where('position_title', $title)
            ->whereNull('employee_category')->first(['id', 'dept_id', 'section_id', 'Rank']);
        $dept = DB::table('resort_departments')->where('id', $position->dept_id)->first(['id', 'division_id', 'name']);
        $hod = $this->people()->where('Dept_id', $dept->id)->whereIn('rank', [2, 1])->sortBy('rank')->last() ?? $this->hr;
        $salary = (float) ($this->people()->where('Position_id', $position->id)->avg(fn ($p) => DB::table('employees')->where('id', $p->id)->value('basic_salary')) ?: 750);
        $vacancyId = $this->row('vacancies', [
            'Resort_id' => $this->resortId, 'budgeted' => 'Budgeted', 'department' => $dept->id, 'division' => $dept->division_id,
            'section' => $position->section_id, 'position' => $position->id, 'rank' => (int) $position->Rank, 'reporting_to' => $hod->id,
            'required_starting_date' => $raised->copy()->addDays(60)->format('Y-m-d'), 'employee_type' => 'Permanant', 'Total_position_required' => '1',
            'amount_unit' => 'USD', 'salary' => round($salary), 'budgeted_salary' => round($salary), 'propsed_salary' => round($salary),
            'accomodation' => '100.00', 'allowance' => 50, 'medical' => 150, 'insurance' => 150, 'pension' => 0, 'food' => 'YES',
            'service_charge' => 'YES', 'uniform' => 'YES', 'recruitment' => 'Online job posting', 'is_required_local' => 'No',
            'justification' => "Budgeted seat for {$title} in {$dept->name} is open.", 'status' => $status,
            'created_by' => $hod->admin, 'modified_by' => $hod->admin, 'created_at' => $raised, 'updated_at' => $raised,
        ]);
        $this->counts['vacancies']++;
        if (!$chain) {
            return [$vacancyId, $position, $hod];
        }
        $parentId = $this->row('t_anotification_parents', ['Resort_id' => $this->resortId, 'V_id' => $vacancyId,
            'created_by' => $hod->admin, 'modified_by' => $hod->admin, 'created_at' => $raised, 'updated_at' => $raised]);
        $gmChild = null;
        foreach ([3 => $this->hr, 7 => $this->finance, 8 => $this->gm] as $rank => $approver) {
            $state = array_shift($chain);
            $acted = $state !== 'Active';
            $at = $raised->copy()->addDays(['3' => 1, '7' => 3, '8' => 5][$rank]);
            $childId = $this->row('t_anotification_children', ['Parent_ta_id' => $parentId, 'Approved_By' => (string) $rank, 'status' => $state,
                'reason' => $state === 'Hold' ? 'Waiting for the Q4 budget review.' : null,
                'holding_date' => $state === 'Hold' ? Carbon::today()->addDays(14)->format('Y-m-d') : null,
                'signature_name' => $acted && $state !== 'Hold' ? $this->name($approver) : null, 'signed_at' => $acted && $state !== 'Hold' ? $at : null,
                'created_by' => $hod->admin, 'modified_by' => $acted ? $approver->admin : $hod->admin, 'created_at' => $raised, 'updated_at' => $acted ? $at : $raised]);
            if ($rank === 8 && $state === 'ForwardedToNext') {
                $gmChild = $childId;
            }
        }
        if ($gmChild) {
            $closed = $status === 'Closed';
            $expiry = $closed ? Carbon::yesterday() : Carbon::today()->addDays(30);
            $this->row('application_links', ['Resort_id' => $this->resortId, 'ta_child_id' => $gmChild,
                'link' => $advertised === null ? null : route('resort.applicantForm', base64_encode("{$this->resortId}/{$gmChild}/{$vacancyId}")),
                'link_Expiry_date' => $expiry->format('Y-m-d'), 'Old_ExpiryDate' => $raised->format('Y-m-d'),
                'expiry_before_close' => $closed ? Carbon::today()->addDays(10)->format('Y-m-d') : null,
                'created_at' => $raised->copy()->addDays(5), 'updated_at' => $raised->copy()->addDays(5)]);
        }
        return [$vacancyId, $position, $hod];
    }

    private function applicant(array $vacancy, array $steps, ?string $document): void
    {
        [$vacancyId, $position, $hod] = $vacancy;
        [$first, $last, $gender, $country] = self::NAMES[$this->nameIndex++ % count(self::NAMES)];
        $applied = Carbon::today()->subDays(mt_rand(30, 45))->setTime(mt_rand(8, 22), mt_rand(0, 59));
        $applied = $applied->year === Carbon::today()->year ? $applied : Carbon::today()->startOfYear()->addDays(mt_rand(1, 20)); // hiring-source chart is per year
        $applicantId = $this->row('applicant_form_data', [
            'resort_id' => $this->resortId, 'Parent_v_id' => $vacancyId, 'first_name' => $first, 'last_name' => $last,
            'email' => strtolower("{$first}.{$last}") . mt_rand(10, 99) . '@example.com', 'gender' => $gender,
            'mobile_number' => mt_rand(7000000, 9999999), 'country_phone_code' => self::PHONE[$country], 'country' => $this->countries[$country] ?? null,
            'city' => ['India' => 'Kochi', 'Maldives' => 'Malé', 'Sri Lanka' => 'Colombo', 'Philippines' => 'Manila', 'Nepal' => 'Kathmandu', 'Bangladesh' => 'Dhaka'][$country],
            'dob' => Carbon::today()->subYears(mt_rand(22, 38))->subDays(mt_rand(0, 360))->format('d/m/Y'),
            'passport_no' => strtoupper(Str::random(2)) . mt_rand(1000000, 9999999), 'passport_expiry_date' => Carbon::today()->addYears(mt_rand(2, 8))->format('d/m/Y'),
            'marital_status' => $this->chance(0.4) ? 'married' : 'unmarried', 'terms_conditions' => 'yes', 'NotiesPeriod' => $this->pick([15, 30, 30, 60]),
            'Joining_availability' => $this->pick(['Immediately', 'Within 1 month', 'Within 2 months']), 'SalaryExpectation' => mt_rand(55, 90) * 10,
            'employment_status' => $this->chance(0.6) ? 'Working' : 'Available', 'Applicant_Source' => (string) $this->pick($this->sources),
            'Total_Experiance' => mt_rand(1, 9) . ' years', 'TimeZone' => 'Asia/Kolkata', 'data_retention_month' => '6',
            'consent_status' => 'pending', 'availability_status' => 'pending', 'Application_date' => $applied->format('Y-m-d'),
            'passport_photo' => null, 'created_at' => $applied, 'updated_at' => $applied,
        ]);
        $years = mt_rand(1, 8);
        $this->row('work_experience_applicant_form', ['applicant_form_id' => $applicantId, 'job_title' => DB::table('resort_positions')->where('id', $position->id)->value('position_title'),
            'employer_name' => $this->pick(['Taj Exotica', 'Sun Siyam', 'Hilton Colombo', 'Shangri-La', 'Kurumba Maldives', 'Marriott Kathmandu']),
            'work_country_name' => $this->countries[$country] ?? null, 'work_city' => 'Head Office', 'total_work_exp' => "{$years} years",
            'work_start_date' => Carbon::today()->subYears($years)->format('Y-m-d'), 'currently_working' => '1', 'created_at' => $applied, 'updated_at' => $applied]);
        $this->row('education_applicant_form', ['applicant_form_id' => $applicantId, 'institute_name' => $this->pick(['IHM Kochi', 'Maldives National University', 'SLITHM Colombo', 'Lyceum of the Philippines']),
            'educational_level' => $this->pick(['Diploma', 'Certificate', 'Bachelor']), 'pass_out_year' => (string) (Carbon::today()->year - $years - mt_rand(1, 3)),
            'created_at' => $applied, 'updated_at' => $applied]);
        foreach ([['English', $this->pick(['Fluent', 'Intermediate'])], [['India' => 'Hindi', 'Maldives' => 'Dhivehi', 'Sri Lanka' => 'Sinhala', 'Philippines' => 'Tagalog', 'Nepal' => 'Nepali', 'Bangladesh' => 'Bengali'][$country], 'Native']] as [$language, $level]) {
            $this->row('applicant_languages', ['applicant_form_id' => $applicantId, 'language' => $language, 'level' => $level, 'created_at' => $applied, 'updated_at' => $applied]);
        }

        $statusId = 0;
        $at = $applied->copy();
        foreach ($steps as $step) {
            [$rank, $status, $comments, $interview] = $step + [2 => null, 3 => null];
            $at = $at->copy()->addDays(mt_rand(1, 3))->min(now());
            $statusId = $this->row('applicant_wise_statuses', ['Applicant_id' => $applicantId, 'As_ApprovedBy' => (string) $rank, 'status' => $status,
                'Comments' => $comments, 'created_at' => $at, 'updated_at' => $at]);
            if ($interview) {
                [$state, $days, $link, $who] = $interview;
                $interviewer = $who === 'hod' ? $hod : $this->hr;
                $date = Carbon::today()->addDays($days);
                $this->row('applicant_inter_view_details', ['resort_id' => $this->resortId, 'Applicant_id' => $applicantId, 'ApplicantStatus_id' => $statusId,
                    'Approved_By' => (string) $rank, 'InterViewDate' => $date->format('Y-m-d'), 'ApplicantInterviewtime' => '11:45 AM', 'ResortInterviewtime' => '11:00 AM',
                    'Status' => $state, 'MeetingLink' => $link ? 'https://meet.google.com/' . strtolower(Str::random(3) . '-' . Str::random(4) . '-' . Str::random(3)) : '',
                    'EmailTemplateId' => (string) $this->templateId, 'invitation_token' => (string) Str::uuid(), 'interviewer_id' => $interviewer->admin,
                    'rejection_reason' => match ($state) { 'Invitation Rejected' => 'Not available on the proposed dates.', 'Cancelled' => 'Removed from shortlist by HR', default => null },
                    'created_at' => $at, 'updated_at' => $at]);
                $this->counts['interviews']++;
            }
        }
        // Offer letter / contract on the applicant's last status row. A contract follows an accepted offer.
        if ($document) {
            [$type, $state] = explode(':', $document);
            $sentAt = $at->copy()->addDay()->min(now());
            $docs = $type === 'contract' ? [['offer_letter', 'Accepted'], ['contract', $state]] : [['offer_letter', $state]];
            foreach ($docs as [$docType, $docState]) {
                $this->row('applicant_offer_contracts', ['applicant_id' => $applicantId, 'applicant_status_id' => $statusId, 'resort_id' => $this->resortId,
                    'type' => $docType, 'status' => $docState, 'token' => (string) Str::uuid(), 'sent_by' => $this->hr->admin,
                    'rejection_reason' => $docState === 'Rejected' ? 'Accepted another offer closer to home.' : null,
                    'responded_at' => $docState === 'Sent' ? null : $sentAt->copy()->addDays(2)->min(now()), 'created_at' => $sentAt, 'updated_at' => $sentAt]);
            }
        }
        $this->counts['applicants']++;
    }
}
