<?php

namespace Database\Seeders\Demo;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Demo ENV: Incident module. Configuration (categories, committees, statuses, severities, timelines,
 * follow-ups, actions, outcomes) and ~20 incidents over six months, each at its stage of the flow as
 * IncidentController / API\IncidentController write it: Reported → assigned to a committee → investigation
 * (witness statements, meetings) → forwarded to the GM → Approved / Rejected → Resolved.
 *
 * Shapes: statuses and severities are comma strings in incident_configuration (the dashboard hard-codes
 * Minor/Moderate/Severe); assigned_to is JSON of committee ids as strings (whereJsonContains matches
 * strings); incidents.created_by is the reporter's login id; meeting created_by is an employees.id.
 * The app never writes resolved_at / resolved_by but the reports read them, so Resolved rows carry both.
 */
class DemoIncidentSeeder
{
    use SeedsDemoRows;

    const STATUSES = ['Reported', 'Assigned To', 'Acknowledged', 'Investigation In Progress', 'Under Review', 'Findings Submitted',
        'Resolution Suggested', 'Approval Pending', 'Approved', 'Rejected', 'Resolved'];
    const CATEGORIES = [
        'Workplace Injuries' => [['Slips, trips and falls', 'Medium'], ['Cuts from kitchen equipment', 'Medium'], ['Lifting / back injury', 'Low']],
        'Guest Safety' => [['Guest injury at the pool', 'High'], ['Snorkelling / water sports incident', 'High']],
        'Security & Theft' => [['Theft from guest villa', 'High'], ['Missing inventory', 'Medium']],
        'Conduct & Harassment' => [['Verbal abuse', 'Medium'], ['Harassment complaint', 'High']],
        'Property Damage' => [['Damage to resort equipment', 'Low'], ['Water leak / flooding', 'Medium']],
    ];
    // [category, subcategory index, title, location, severity]
    const STORIES = [
        ['Workplace Injuries', 0, 'Waiter slipped on wet floor near the main restaurant', 'Main Restaurant', 'Minor'],
        ['Workplace Injuries', 1, 'Commis chef cut finger on slicer', 'Hot Kitchen', 'Moderate'],
        ['Workplace Injuries', 2, 'Room attendant strained back moving a mattress', 'Villa 112', 'Minor'],
        ['Guest Safety', 0, 'Guest fell on pool steps', 'Infinity Pool', 'Moderate'],
        ['Guest Safety', 1, 'Guest stung by jellyfish while snorkelling', 'House Reef', 'Moderate'],
        ['Security & Theft', 0, 'Guest reported a missing watch from villa safe', 'Villa 204', 'Severe'],
        ['Security & Theft', 1, 'Wine stock count short by 6 bottles', 'Bar Store', 'Moderate'],
        ['Conduct & Harassment', 0, 'Argument between two kitchen staff during service', 'Main Kitchen', 'Minor'],
        ['Conduct & Harassment', 1, 'Staff complaint about a supervisor’s behaviour', 'Staff Village', 'Severe'],
        ['Property Damage', 0, 'Buggy damaged after hitting a palm tree', 'Jetty Road', 'Minor'],
        ['Property Damage', 1, 'AC leak flooded staff room 3B', 'Staff Block B', 'Minor'],
        ['Workplace Injuries', 0, 'Gardener tripped over irrigation pipe', 'Garden Path', 'Minor'],
        ['Guest Safety', 0, 'Child guest slipped at the kids club pool', 'Kids Club', 'Severe'],
        ['Security & Theft', 1, 'Housekeeping linen shortage on inventory', 'Laundry', 'Minor'],
        ['Property Damage', 0, 'Kayak hull cracked during guest use', 'Water Sports Centre', 'Moderate'],
        ['Workplace Injuries', 1, 'Steward burned hand on dishwasher steam', 'Kitchen Stewarding', 'Moderate'],
        ['Conduct & Harassment', 0, 'Guest verbally abused a front desk agent', 'Reception', 'Moderate'],
        ['Property Damage', 1, 'Pipe burst in the laundry', 'Laundry', 'Severe'],
        ['Workplace Injuries', 2, 'Electrician fell from a step ladder', 'Engineering Workshop', 'Minor'],
        ['Guest Safety', 1, 'Guest needed help after a strong current at the reef', 'House Reef', 'Minor'],
    ];
    // Where each story sits in the flow (same order as STORIES): 4 reported, 3 assigned, 4 investigating,
    // 2 waiting for the GM, 2 approved, 1 rejected, 4 resolved.
    const FLOW = ['Resolved', 'Resolved', 'Resolved', 'Approved', 'Investigation In Progress', 'Approval Pending', 'Under Review',
        'Assigned To', 'Investigation In Progress', 'Resolved', 'Rejected', 'Approved', 'Approval Pending', 'Acknowledged',
        'Under Review', 'Reported', 'Assigned To', 'Reported', 'Reported', 'Reported'];

    private array $counts = ['incidents' => 0, 'incident meetings' => 0];

    public function __construct(private int $resortId, private int $adminId)
    {
    }

    public function seed(): array
    {
        $now = Carbon::now();
        $hr = $this->login('hr');
        $gm = $this->login('gm');
        $stamp = fn ($by = null) => ['resort_id' => $this->resortId, 'created_by' => $by ?? $this->adminId, 'modified_by' => $by ?? $this->adminId,
            'created_at' => $now->copy()->subMonths(7), 'updated_at' => $now->copy()->subMonths(7)];

        // Configuration.
        $subIds = [];
        foreach (self::CATEGORIES as $category => $subs) {
            $catId = $this->row('incident_categories', $stamp() + ['category_name' => $category]);
            foreach ($subs as $i => [$sub, $priority]) {
                $subIds[$category][$i] = [$catId, $this->row('incident_subcategories', $stamp() + ['category_id' => $catId,
                    'subcategory_name' => $sub, 'priority' => $priority])];
            }
        }
        $committees = [];
        foreach (['Safety Committee' => ['hr', 'hod.engineering', 'hod.housekeeping'], 'Conduct Committee' => ['hr', 'gm', 'excom']] as $name => $logins) {
            $id = $this->row('incident_committee', $stamp() + ['commitee_name' => $name, 'date' => $now->copy()->subMonths(7)->format('Y-m-d')]);
            $members = [];
            foreach ($logins as $login) {
                $members[$login] = $this->row('incident_committee_members', ['commitee_id' => $id, 'member_id' => $this->login($login)->id,
                    'created_at' => $now, 'updated_at' => $now]);
            }
            $committees[$name] = $id;
        }
        foreach (['status' => implode(',', self::STATUSES), 'severity_levels' => 'Minor,Moderate,Severe',
            'meeting_reminder' => json_encode(['reminder_days' => '1 Business Days'])] as $key => $value) {
            $this->row('incident_configuration', $stamp() + ['setting_key' => $key, 'setting_value' => $value]);
        }
        foreach (['High' => '2 Business Days', 'Medium' => '4 Business Days', 'Low' => '6 Business Days'] as $priority => $timeline) {
            $this->row('incident_resolution_timeline', ['resort_id' => $this->resortId, 'priority' => $priority, 'timeline' => $timeline,
                'created_at' => $now, 'updated_at' => $now]);
        }
        $followUps = [];
        foreach (['Employee Statement' => 1, 'Refresher Training' => 0, 'Equipment Inspection' => 0] as $action => $statement) {
            $followUps[] = $this->row('incident_followup_actions', $stamp() + ['followup_action' => $action, 'requires_employee_statement' => $statement]);
        }
        foreach (['Consultation', 'First Aid Given', 'Written Warning', 'Repair Ordered'] as $action) {
            $this->row('incident_actions_taken', $stamp() + ['action_taken' => $action]);
        }
        foreach (['Advised', 'Warning Issued', 'No Further Action', 'Policy Updated'] as $outcome) {
            $this->row('incident_outcome_types', $stamp() + ['outcome_type' => $outcome]);
        }

        // Incidents: reporters from across the departments (never the GM), the victim usually someone nearby.
        $staff = $this->people()->filter(fn ($p) => (int) $p->rank !== 8)->values();
        $employee = $this->login('employee');
        $codes = [];
        foreach (self::STORIES as $n => [$category, $subIndex, $title, $location, $severity]) {
            $status = self::FLOW[$n];
            $stage = array_search($status, self::STATUSES, true);
            // Older the further along: resolved ones 2–6 months ago, new reports in the last 10 days.
            $daysAgo = match (true) {
                $status === 'Reported' => mt_rand(1, 10),
                $stage <= 2 => mt_rand(8, 20),
                $stage <= 6 => mt_rand(15, 45),
                $stage <= 9 => mt_rand(30, 75),
                default => mt_rand(60, 180),
            };
            $date = Carbon::today()->subDays($daysAgo)->setTime(mt_rand(6, 22), mt_rand(0, 59));
            $reporter = in_array($n, [15, 17], true) ? $employee : $this->pick($staff->all());
            $sameDept = $staff->where('Dept_id', $reporter->Dept_id)->where('id', '!=', $reporter->id)->values();
            $victim = $category === 'Guest Safety' || $sameDept->isEmpty() ? $reporter : $this->pick($sameDept->all());
            $involved = collect([$victim->id])->merge($sameDept->pluck('id')->shuffle()->take(mt_rand(0, 1)))->unique()->values();
            $witness = $sameDept->where('id', '!=', $victim->id)->shuffle()->first();
            do {
                $code = chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . mt_rand(1000, 9999);
            } while (isset($codes[$code]));
            $codes[$code] = true;
            [$catId, $subId] = $subIds[$category][$subIndex];
            $priority = ['Minor' => 'Low', 'Moderate' => 'Medium', 'Severe' => 'High'][$severity];
            $committee = $category === 'Conduct & Harassment' ? $committees['Conduct Committee'] : $committees['Safety Committee'];
            $assigned = $stage >= 1;
            $investigated = $stage >= 3 && $status !== 'Rejected' || in_array($status, ['Rejected', 'Approved', 'Resolved'], true);
            $decidedAt = $date->copy()->addDays(mt_rand(6, 14));
            $resolvedAt = $date->copy()->addDays(mt_rand(10, 25));
            $outcome = $investigated && $stage >= 7 ? ['outcome_type' => $this->pick(['Advised', 'Warning Issued', 'No Further Action', 'Policy Updated']),
                'action_taken' => $this->pick(['Consultation', 'First Aid Given', 'Written Warning', 'Repair Ordered']),
                'preventive_measures' => 'Briefed the team at the next shift meeting and added the hazard to the weekly safety walk.'] : [];

            $incidentId = $this->row('incidents', [
                'resort_id' => $this->resortId, 'incident_id' => $code, 'incident_name' => $title, 'reporter_id' => $reporter->id,
                'description' => $title . '. Reported by ' . $this->name($reporter) . ' through the staff app.',
                'victims' => (string) $victim->id, 'involved_employees' => $involved->implode(','), 'incident_date' => $date->format('Y-m-d'),
                'incident_time' => $date->format('H:i:s'), 'location' => $location, 'category' => $catId, 'subcategory' => $subId,
                'isWitness' => $witness ? 'Yes' : 'No', 'attachements' => '[]',
                'priority' => $assigned ? $priority : 'Low', 'severity' => $stage >= 3 ? $severity : null,
                'assigned_to' => $assigned ? json_encode([(string) $committee]) : null,
                'comments' => $assigned ? 'Please investigate and share findings with HR.' : null,
                'approval' => $stage >= 7 ? 1 : 0, 'status' => $status,
                'approved_by' => in_array($status, ['Approved', 'Rejected', 'Resolved'], true) ? $gm->id : null,
                'approved_at' => in_array($status, ['Approved', 'Rejected', 'Resolved'], true) ? $decidedAt : null,
                'approval_remarks' => match ($status) { 'Approved', 'Resolved' => 'Findings accepted.', 'Rejected' => 'Not enough evidence — please re-interview the witnesses.', default => null },
                'gm_signature_name' => in_array($status, ['Approved', 'Resolved'], true) ? $this->name($gm) : null,
                'gm_signed_at' => in_array($status, ['Approved', 'Resolved'], true) ? $decidedAt : null,
                'resolved_at' => $status === 'Resolved' ? $resolvedAt : null, 'resolved_by' => $status === 'Resolved' ? $hr->id : null,
                'created_by' => $reporter->admin, 'modified_by' => $assigned ? $hr->admin : $reporter->admin,
                'created_at' => $date, 'updated_at' => $status === 'Resolved' ? $resolvedAt : ($assigned ? $date->copy()->addDays(2) : $date),
            ] + $outcome);
            $this->counts['incidents']++;

            if ($witness) {
                $this->row('incidents_witness', ['incident_id' => $incidentId, 'witness_id' => $witness->id,
                    'witness_statements' => $stage >= 3 ? 'I saw it happen and called my supervisor straight away.' : null,
                    'witness_statement_file' => $stage >= 3 ? '[]' : null, 'witness_status' => $stage >= 3 ? 'Acknowledged' : null,
                    'witness_signature_name' => $stage >= 3 ? $this->name($witness) : null,
                    'witness_signed_at' => $stage >= 3 ? $date->copy()->addDays(2) : null, 'created_at' => $date, 'updated_at' => $date]);
            }
            if (!$investigated) {
                continue;
            }

            // Investigation: one in progress past its expected date, one due within 3 days, the rest on time.
            $start = $date->copy()->addDays(2);
            $expected = match (true) {
                $n === 4 => Carbon::today()->subDays(2),
                $n === 8 => Carbon::today()->addDays(2),
                default => $start->copy()->addDays(mt_rand(5, 12)),
            };
            $notify = fn ($on) => $on ? 'yes' : 'no';
            $severe = $severity === 'Severe';
            $this->row('incidents_investigation', [
                'incident_id' => $incidentId, 'committee_id' => $committee, 'start_date' => $start->format('Y-m-d'),
                'expected_resolution_date' => $expected->format('Y-m-d'),
                'investigation_findings' => 'Statements taken from the people involved; the area was inspected the same day.',
                'folloup_action' => $followUps[0], 'resolution_notes' => $stage >= 7 ? 'Corrective actions agreed with the department head.' : null,
                'police_notified' => $notify($category === 'Security & Theft' && $severe), 'police_date' => '0000-00-00', 'police_time' => '00:00:00',
                'mdf_notified' => 'no', 'mndf_date' => '0000-00-00', 'mndf_time' => '00:00:00',
                'fire_rescue_notified' => 'no', 'fire_rescue_date' => '0000-00-00', 'fire_rescue_time' => '00:00:00',
                // Severe ones were reported to the Ministry, so the hourly compliance check has nothing to flag.
                'Ministry_notified' => $notify($severe), 'Ministry_notified_date' => $severe ? $start->format('Y-m-d') : null,
                'added_by_member_id' => null, 'created_by' => $hr->admin, 'created_at' => $start, 'updated_at' => $start,
            ]);

            // Meetings with the people involved; open investigations also have one coming up.
            $investigator = $category === 'Conduct & Harassment' ? $this->login('excom') : $this->login('hod.housekeeping');
            $meetingDates = [$start->copy()->addDay()->setTime(10, 0)];
            if ($stage >= 3 && $stage <= 7) {
                $meetingDates[] = Carbon::today()->addDays(mt_rand(1, 6))->setTime(mt_rand(9, 15), 0);
            }
            foreach ($meetingDates as $i => $when) {
                $meetingId = $this->row('incidents_investigation_meetings', ['incident_id' => $incidentId,
                    'meeting_subject' => $i === 0 ? 'Fact-finding interview' : 'Follow-up review', 'meeting_date' => $when->format('Y-m-d'),
                    'meeting_time' => $when->format('H:i:s'), 'location' => 'HR Meeting Room', 'meeting_type' => 'Physical',
                    'meeting_agenda' => 'Go through the statements and agree the next steps.', 'attachments' => null,
                    'created_by' => $hr->id, 'modified_by' => $hr->id, 'created_at' => $start, 'updated_at' => $start]);
                foreach (array_filter([[$victim->id, 'Victim'], $witness ? [$witness->id, 'Witness'] : null, [$investigator->id, 'Investigator']]) as [$pid, $role]) {
                    $this->row('incidents_investigation_meetings_participants', ['meeting_id' => $meetingId, 'participant_id' => $pid,
                        'participant_role' => $role, 'created_at' => $start, 'updated_at' => $start]);
                }
                $this->counts['incident meetings']++;
            }
            if ($stage >= 3) {
                $this->row('incident_employee_statements', ['incident_id' => $incidentId, 'employee_id' => $victim->id,
                    'statement' => 'I was following the usual procedure when it happened.', 'status' => 'submitted', 'document_path' => '[]',
                    'signature_name' => $this->name($victim), 'signed_at' => $start->copy()->addDay(), 'created_at' => $start, 'updated_at' => $start]);
            }
        }

        // The HR dashboard caches its AI summary for 30 days; a rebuilt demo should get a fresh one.
        Cache::forget("incident_insights:{$this->resortId}");
        return $this->counts;
    }
}
