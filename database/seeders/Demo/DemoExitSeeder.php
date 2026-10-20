<?php

namespace Database\Seeders\Demo;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demo ENV: people leaving — resignations at each stage (waiting on the HOD, serving notice, settled and gone,
 * withdrawn), one termination, exit clearance forms and their assignments, Full & Final Settlements
 * (one finalized, one awaiting Finance) and probation outcomes, in the shapes EmployeeResignationController,
 * ExitClearanceController, PayslipController and ProbationController write them.
 *
 * Runs after payroll: the settled leaver and the terminated employee are Maldivian, so their pension
 * contributions in the locked runs fill Pension → "View Former Employees". Everything recorded for them after
 * their last day (attendance, rosters, overtime, leave, later payroll lines) is removed, as if they had left.
 *
 * Kept deliberately: the settled resignation stays 'Approved' (the FFS review page reads the Approved
 * resignation) and nobody gets employees.last_working_day (the daily job would flip them to Inactive).
 */
class DemoExitSeeder
{
    use SeedsDemoRows;

    private array $counts = ['resignations' => 0, 'final settlements' => 0];
    private array $used = [];
    private array $forms = [];
    private object $hr;
    private object $finance;
    private object $gm;
    private float $rate;

    public function __construct(private int $resortId, private int $adminId)
    {
    }

    public function seed(): array
    {
        $this->hr = $this->login('hr');
        $this->finance = $this->login('finance');
        $this->gm = $this->login('gm');
        $this->rate = (float) DB::table('resort_site_settings')->where('resort_id', $this->resortId)->value('DollertoMVR') ?: 15.42;
        $today = Carbon::today();
        $setup = now()->subMonths(10);

        foreach (['GM' => 60, 'EXCOM' => 45, 'HOD' => 45, 'MGR' => 30, 'SUP' => 30, 'LINE WORKERS' => 30] as $title => $days) {
            $this->row('employee_notice_period', ['resort_id' => $this->resortId, 'title' => $title, 'period' => (string) $days, 'immediate_release' => 0,
                'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin, 'created_at' => $setup, 'updated_at' => $setup]);
        }
        $this->exitForms($setup);
        $reasons = DB::table('employee_resignation_reasons')->where('resort_id', $this->resortId)->pluck('id', 'reason')->all();

        // R1 — resigned, cleared, settled, gone (left mid-month three months ago).
        $r1 = $this->pick_(fn ($p) => $p->nationality === 'Maldivian');
        $lwd = $today->copy()->startOfMonth()->subMonths(3)->addDays(14);
        $r1Id = $this->resignation($r1, $reasons['Better Opportunity'], $lwd->copy()->subDays(30), $lwd, 'Approved', 'Approved', 'Approved',
            ['certificate_issue' => 'yes', 'full_and_final_settlement' => 'yes', 'hod_meeting_status' => 'Completed', 'hr_meeting_status' => 'Completed']);
        $this->meeting($r1Id, 'Exit discussion with HOD', $lwd->copy()->subDays(27), 'HOD', 'Completed', $this->hodOf($r1));
        $this->meeting($r1Id, 'Exit interview with HR', $lwd->copy()->subDays(5), 'HR', 'Completed', $this->hr);
        $this->clearance($r1Id, $r1, $lwd, ['Completed', 'Completed', 'Completed'], 'Completed');
        $this->settlement($r1, $lwd, 'finalized');
        $this->leave($r1, $lwd, 'Inactive');

        // R2 — resignation approved, serving notice; clearance half done; settlement waiting on Finance.
        $r2 = $this->pick_(fn ($p) => true);
        $r2Lwd = $today->copy()->addDays(25);
        $r2Id = $this->resignation($r2, $reasons['Career growth opportunity'], $today->copy()->subDays(20), $r2Lwd, 'Approved', 'Approved', 'Approved',
            ['hod_meeting_status' => 'Completed', 'hr_meeting_status' => 'Completed']);
        $this->meeting($r2Id, 'Resignation discussion', $today->copy()->subDays(17), 'HOD', 'Completed', $this->hodOf($r2));
        $this->clearance($r2Id, $r2, $r2Lwd, ['Completed', 'Pending', 'Pending'], 'Pending');
        $this->settlement($r2, $r2Lwd, 'review');
        DB::table('employees')->where('id', $r2->id)->update(['status' => 'Offboarding', 'updated_at' => now()]);

        // P1 — just resigned: waiting on the HOD, meeting booked for tomorrow.
        $p1 = $this->pick_(fn ($p) => $this->hodOf($p)->id !== $this->hr->id);
        $p1Id = $this->resignation($p1, $reasons['Relocation'], $today->copy()->subDays(3), $today->copy()->addDays(27), 'Pending', 'Pending', 'Pending',
            ['hod_meeting_status' => 'Scheduled']);
        $this->meeting($p1Id, 'Resignation discussion', $today->copy()->addDay(), 'HOD', 'Pending', $this->hodOf($p1));

        // P2 — HOD approved, now with HR.
        $p2 = $this->pick_(fn ($p) => true);
        $this->resignation($p2, $reasons['Better Opportunity'], $today->copy()->subDays(8), $today->copy()->addDays(22), 'Pending', 'Approved', 'Pending',
            ['hod_meeting_status' => 'Completed', 'hr_meeting_status' => 'Not Scheduled']);

        // W1 — approved, then withdrawn and taken back.
        $w1 = $this->pick_(fn ($p) => true);
        $start = $today->copy()->subDays(40);
        $this->resignation($w1, $reasons['Relocation'], $start, $start->copy()->addDays(30), 'Withdraw', 'Approved', 'Approved', [
            'hod_meeting_status' => 'Completed', 'hr_meeting_status' => 'Completed',
            'withdraw_reason' => 'Family situation resolved — would like to stay.', 'withdraw_requested_at' => $start->copy()->addDays(9)->setTime(10, 0),
            'withdraw_hod_status' => 'Approved', 'withdraw_hod_by' => $this->hodOf($w1)->id, 'withdraw_hod_at' => $start->copy()->addDays(10)->setTime(11, 0),
            'withdraw_hr_status' => 'Approved', 'withdraw_hr_by' => $this->hr->id, 'withdraw_hr_at' => $start->copy()->addDays(11)->setTime(9, 30)]);

        // X1 — resignation rejected (asked to stay; counter-offer accepted).
        $x1 = $this->pick_(fn ($p) => true);
        $this->resignation($x1, $reasons['Career growth opportunity'], $today->copy()->subDays(55), $today->copy()->subDays(25), 'Rejected', 'Rejected', 'Pending',
            ['hod_meeting_status' => 'Completed', 'rejected_reason' => 'Counter-offer accepted after the HOD meeting.']);

        // T1 — terminated two months ago, no resignation (the manual edit on the employee profile).
        $t1 = $this->pick_(fn ($p) => $p->nationality === 'Maldivian');
        $this->leave($t1, $today->copy()->subDays(60), 'Terminated');

        $this->probation($today);
        return $this->counts;
    }

    /** A permanent line worker who is not a demo login, in a department with a HOD, not yet in another story. */
    private function pick_(callable $where): object
    {
        $prefix = strtolower(config('demo.prefix')) . '-';
        $free = $this->people()->filter(fn ($p) => (int) $p->rank === 6 && !isset($this->used[$p->id]) && str_starts_with(strtolower($p->email), $prefix)
            && !in_array($p->employment_type, ['Casual', 'Internship'], true)
            && DB::table('employees')->where('id', $p->id)->value('probation_status') === 'Confirmed'
            // not someone whose leave is waiting on a manager — the leave demo needs every team's pending request
            && !DB::table('employees_leaves')->where('emp_id', $p->id)->where('status', 'Pending')->exists()
            && $this->hodOf($p)->id !== $this->hr->id && $where($p));
        // Spread the stories over departments, so each HOD login has a leaver of their own.
        $p = $free->first(fn ($p) => !in_array($p->Dept_id, $this->used)) ?? $free->first();
        if (!$p) {
            throw new \RuntimeException('No free line worker for the exit stories.');
        }
        $this->used[$p->id] = $p->Dept_id;
        return $p;
    }

    private function hodOf(object $p): object
    {
        return $this->people()->where('Dept_id', $p->Dept_id)->where('rank', '2')->first() ?? $this->hr;
    }

    private function resignation(object $p, int $reason, Carbon $date, Carbon $lwd, string $status, string $hod, string $hr, array $extra): int
    {
        $hodP = $this->hodOf($p);
        $hodAt = $date->copy()->addDays(2)->setTime(11, 0);
        $hrAt = $date->copy()->addDays(4)->setTime(15, 0);
        $id = $this->row('employee_resignation', $extra + [
            'resort_id' => $this->resortId, 'employee_id' => $p->id, 'reason' => $reason, 'resignation_date' => $date->format('Y-m-d'),
            'last_working_day' => $lwd->format('Y-m-d'), 'immediate_release' => 'No', 'certificate_issue' => 'no', 'full_and_final_settlement' => 'no',
            'comments' => 'Thank you for the opportunity. I am serving my notice period as per contract.',
            'departure_arrangements' => json_encode(array_fill_keys(['documentVerifed', 'passport_validity', 'international_flight', 'accommodation_arranged', 'transportation_arranged'], '0')),
            'status' => $status, 'hod_id' => $hodP->id, 'hr_id' => $this->hr->id,
            'hod_status' => $hod, 'hod_meeting_status' => 'Not Scheduled', 'hod_comments' => $hod === 'Pending' ? null : ($hod === 'Approved' ? 'Discussed, accepted.' : 'Asked to stay.'),
            'hod_signature_name' => $hod === 'Approved' ? $this->name($hodP) : null, 'hod_signed_at' => $hod === 'Approved' ? $hodAt : null,
            'hr_status' => $hr, 'hr_meeting_status' => 'Not Scheduled', 'hr_comments' => $hr === 'Approved' ? 'Approved; clearance to start.' : null,
            'hr_signature_name' => $hr === 'Approved' ? $this->name($this->hr) : null, 'hr_signed_at' => $hr === 'Approved' ? $hrAt : null,
            'created_at' => $date->copy()->setTime(9, 0), 'updated_at' => $hr === 'Approved' ? $hrAt : ($hod === 'Pending' ? $date->copy()->setTime(9, 0) : $hodAt),
        ]);
        $this->counts['resignations']++;
        return $id;
    }

    private function meeting(int $resignationId, string $title, Carbon $date, string $with, string $status, object $by): void
    {
        $this->row('resignation_meeting_schedule', ['resignationId' => $resignationId, 'title' => $title, 'meeting_date' => $date->format('Y-m-d'),
            'meeting_time' => '11:00', 'meeting_with' => $with, 'status' => $status, 'created_by' => $by->id, 'created_at' => $date->copy()->subDays(2), 'updated_at' => $date]);
    }

    private function exitForms(Carbon $at): void
    {
        $field = fn (string $type, string $label, string $name, array $extra = []) => $extra + ['type' => $type, 'required' => false, 'label' => $label,
            'className' => 'form-control', 'name' => $name, 'access' => false, 'subtype' => $type];
        $checks = fn (string $name, array $items) => ['type' => 'checkbox-group', 'required' => false, 'label' => 'Items cleared', 'toggle' => false,
            'inline' => false, 'name' => $name, 'access' => false, 'other' => false,
            'values' => array_map(fn ($i, $k) => ['label' => $i, 'value' => 'option-' . ($k + 1), 'selected' => false], $items, array_keys($items))];
        $departments = ['Finance' => ['Staff advance settled', 'Staff shop dues cleared', 'Bank details for final pay'],
            'Human Resources' => ['ID card returned', 'Exit interview done', 'Passport released'],
            'Housekeeping' => ['Uniforms returned', 'Room keys returned', 'Room inspected'],
            'Engineering' => ['Tools returned', 'Locker emptied']];
        foreach ($departments as $dept => $items) {
            $deptId = DB::table('resort_departments')->where('resort_id', $this->resortId)->where('name', $dept)->value('id');
            $slug = strtolower(str_replace(' ', '-', $dept));
            $this->forms['department'][$deptId] = [$this->row('exit_clearance_form', ['resort_id' => $this->resortId, 'form_name' => "{$dept} Department Exit Clearance Form",
                'type' => null, 'department_id' => $deptId, 'form_type' => 'department', 'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin,
                'form_structure' => json_encode([['type' => 'header', 'subtype' => 'h1', 'label' => strtoupper($dept) . ' DEPARTMENT - EMPLOYEE EXIT CLEARANCE FORM', 'access' => false],
                    $field('text', 'Employee Name', "text-{$slug}-name"), $field('date', 'Clearance Date', "date-{$slug}-date"), $checks("checkbox-group-{$slug}", $items),
                    $field('textarea', 'Remarks', "textarea-{$slug}-remarks")]), 'created_at' => $at, 'updated_at' => $at]), $slug, count($items)];
        }
        foreach (['exit_interview' => 'Employee Exit Interview Form', 'handover' => 'Employee Handover & Knowledge Transfer Form'] as $type => $name) {
            $this->forms['employee'][$type] = [$this->row('exit_clearance_form', ['resort_id' => $this->resortId, 'form_name' => $name, 'type' => $type,
                'department_id' => null, 'form_type' => 'employee', 'created_by' => $this->hr->admin, 'modified_by' => $this->hr->admin,
                'form_structure' => json_encode([['type' => 'header', 'subtype' => 'h1', 'label' => strtoupper($name), 'access' => false],
                    $field('text', 'Employee Name', "text-{$type}-name"),
                    $field('textarea', $type === 'exit_interview' ? 'What is the main reason you are leaving?' : 'Open tasks handed over', "textarea-{$type}-main"),
                    $field('textarea', $type === 'exit_interview' ? 'What could we improve?' : 'Handed over to', "textarea-{$type}-more")]),
                'created_at' => $at, 'updated_at' => $at]), $type, 0];
        }
    }

    /** Department forms (Finance, HR, the leaver's own department if it has one) plus the exit interview. */
    private function clearance(int $resignationId, object $p, Carbon $lwd, array $deptStates, string $interviewState): void
    {
        $deptIds = array_keys($this->forms['department']);
        $own = in_array($p->Dept_id, $deptIds) ? [$p->Dept_id] : [];
        $targets = array_slice(array_values(array_unique(array_merge(
            [DB::table('resort_departments')->where('resort_id', $this->resortId)->where('name', 'Finance')->value('id'),
             DB::table('resort_departments')->where('resort_id', $this->resortId)->where('name', 'Human Resources')->value('id')], $own, $deptIds))), 0, 3);
        $assigned = $lwd->copy()->subDays(25)->min(Carbon::today()->subDays(15));
        foreach ($targets as $i => $deptId) {
            [$formId, $slug, $n] = $this->forms['department'][$deptId];
            $owner = $this->people()->where('Dept_id', $deptId)->sortBy('rank')->first(fn ($x) => in_array((int) $x->rank, [1, 2], true)) ?? $this->hr;
            $this->assignment($resignationId, $formId, 'department', $deptId, $owner, $assigned, $lwd->copy()->subDays(5), $deptStates[$i] ?? 'Pending', [
                "text-{$slug}-name" => $this->name($p), "date-{$slug}-date" => $assigned->copy()->addDays(3 + $i)->format('Y-m-d'),
                "checkbox-group-{$slug}" => array_map(fn ($k) => 'option-' . $k, range(1, $n)), "textarea-{$slug}-remarks" => 'All clear.']);
        }
        [$formId, $type] = $this->forms['employee']['exit_interview'];
        $this->assignment($resignationId, $formId, 'employee', null, $p, $assigned, $lwd->copy()->subDays(3), $interviewState, [
            "text-{$type}-name" => $this->name($p), "textarea-{$type}-main" => 'A role closer to my family.', "textarea-{$type}-more" => 'More cross-training between departments.']);
    }

    private function assignment(int $resignationId, int $formId, string $type, ?int $deptId, object $to, Carbon $assigned, Carbon $deadline, string $state, array $answers): void
    {
        $done = $state === 'Completed' ? $assigned->copy()->addDays(mt_rand(2, 8))->min(Carbon::today()) : null;
        $id = $this->row('exit_clearance_form_assignments', ['resort_id' => $this->resortId, 'department_id' => $deptId, 'reminder_frequency' => '2',
            'emp_resignation_id' => $resignationId, 'form_id' => $formId, 'assigned_to_type' => $type, 'assigned_to_id' => $to->id, 'assigned_by' => $this->hr->id,
            'assigned_date' => $assigned->format('Y-m-d'), 'deadline_date' => $deadline->format('Y-m-d'), 'status' => $state,
            'completed_via' => $done ? 'web' : null, 'completed_date' => $done?->format('Y-m-d'), 'created_at' => $assigned, 'updated_at' => $done ?? $assigned]);
        if ($done) {
            $this->row('exit_clearance_form_responses', ['assignment_id' => $id, 'response_data' => json_encode($answers), 'submitted_by' => $to->id,
                'submitted_date' => $done->format('Y-m-d'), 'created_at' => $done, 'updated_at' => $done]);
        }
    }

    /** Full & Final Settlement in the employee's pay currency (store()'s rule), with its HR → Finance → GM chain. */
    private function settlement(object $p, Carbon $lwd, string $state): void
    {
        $e = DB::table('employees')->where('id', $p->id)->first(['Emp_id', 'basic_salary', 'basic_salary_currency', 'nationality', 'payment_mode']);
        $mvr = strtoupper((string) $e->basic_salary_currency) === 'MVR';
        $basic = (float) $e->basic_salary;
        $day = $basic / 30;
        $worked = (int) $lwd->day;
        $earned = round($day * $worked, 2);
        $leaveDays = $state === 'finalized' ? 9.5 : 12.0;
        $encash = round($day * $leaveDays, 2);
        $sc = round($mvr ? 1850 : 120, 2);
        $pension = $e->nationality === 'Maldivian' ? round($earned * 0.07, 2) : 0.0;
        $uniform = round($mvr ? 50 * $this->rate : 50, 2);
        $earnings = round($earned + $sc + $encash, 2);
        $deductions = round($pension + $uniform, 2);
        $doc = $state === 'finalized' ? $lwd->copy()->addDays(6) : Carbon::today()->subDay();
        $id = $this->row('final_settlements', ['employee_id' => $p->id, 'pension' => $pension, 'tax' => 0, 'leave_balance' => $leaveDays,
            'leave_encashment' => $encash, 'leave_breakdown_json' => json_encode([['leave_type' => 'Annual Leave', 'available_days' => $leaveDays]]),
            'loan_payment' => 0, 'basic_salary' => $basic, 'worked_days' => $worked, 'service_charge' => $sc, 'total_earnings' => $earnings,
            'total_deductions' => $deductions, 'net_pay' => round($earnings - $deductions, 2), 'payment_mode' => $e->payment_mode ?: 'Bank Transfer',
            'last_working_date' => $lwd->format('Y-m-d'), 'doc_date' => $doc->format('Y-m-d'),
            'reference_no' => 'FS-' . $doc->format('Ym') . '-' . str_pad((string) $e->Emp_id, 4, '0', STR_PAD_LEFT), 'status' => $state,
            'finalized_at' => $state === 'finalized' ? $doc->copy()->addDays(3)->setTime(16, 0) : null,
            'finalized_by' => $state === 'finalized' ? $this->gm->admin : null, 'created_at' => $doc->copy()->setTime(10, 0), 'updated_at' => $doc->copy()->addDays(3)]);
        $deduction = DB::table('resort_deductions')->where('resort_id', $this->resortId)->value('id');
        if ($deduction) {
            $this->row('final_settlement_deductions', ['final_settlement_id' => $id, 'deduction_id' => $deduction,
                'amount' => $mvr ? $uniform : round($uniform * $this->rate, 2), 'amount_unit' => 'MVR', 'created_at' => $doc, 'updated_at' => $doc]);
        }
        foreach ([[1, 'HR EXCOM', $this->hr], [2, 'Finance EXCOM', $this->finance], [3, 'GM', $this->gm]] as [$step, $role, $approver]) {
            $approved = $state === 'finalized' || $step === 1;
            $at = $doc->copy()->addDays($step)->setTime(10 + $step, 0);
            $this->row('final_settlement_approvals', ['final_settlement_id' => $id, 'resort_id' => $this->resortId, 'step_order' => $step, 'role_title' => $role,
                'approver_id' => $approved ? $approver->id : null, 'approver_name' => $approved ? $this->name($approver) : null,
                'status' => $approved ? 'approved' : 'pending', 'approved_at' => $approved ? $at : null,
                'signature_name' => $approved ? $this->name($approver) : null, 'signed_at' => $approved ? $at : null,
                'created_at' => $doc, 'updated_at' => $approved ? $at : $doc]);
        }
        $this->counts['final settlements']++;
    }

    /** The employee has left: status, and nothing recorded for them after the last day. */
    private function leave(object $p, Carbon $lastDay, string $status): void
    {
        $after = $lastDay->format('Y-m-d');
        DB::table('employees')->where('id', $p->id)->update(['status' => $status, 'updated_at' => now()]
            + ($status === 'Terminated' ? ['termination_date' => $after] : ['resign_effective_date' => $after]));
        $attendance = DB::table('parent_attendaces')->where('Emp_id', $p->id)->where('date', '>', $after)->pluck('id');
        foreach ($attendance->chunk(500) as $ids) {
            DB::table('child_attendaces')->whereIn('Parent_attd_id', $ids)->delete();
            DB::table('break_attendaces')->whereIn('Parent_attd_id', $ids)->delete();
        }
        DB::table('employee_overtimes')->where('Emp_id', $p->id)->where('date', '>', $after)->delete();
        DB::table('parent_attendaces')->whereIn('id', $attendance)->delete();
        DB::table('duty_roster_entries')->where('Emp_id', $p->id)->where('date', '>', $after)->delete();
        $leaves = DB::table('employees_leaves')->where('emp_id', $p->id)->where('from_date', '>', $after)->pluck('id');
        $passes = DB::table('employee_travel_passes')->whereIn('leave_request_id', $leaves)->pluck('id');
        DB::table('employee_travel_pass_status')->whereIn('travel_pass_id', $passes)->delete();
        DB::table('employee_travel_passes')->whereIn('id', $passes)->delete();
        DB::table('employees_leaves_status')->whereIn('leave_request_id', $leaves)->delete();
        DB::table('employees_leaves')->whereIn('id', $leaves)->delete();

        // Payroll: not paid in runs after they left (the settled leaver's final month is in the settlement).
        $from = $status === 'Terminated' ? $after : $lastDay->copy()->startOfMonth()->format('Y-m-d');
        $runs = DB::table('payroll')->where('resort_id', $this->resortId)->where('payroll_category', 'Permanent')->where('start_date', '>=', $from)->get(['id', 'status']);
        foreach ($runs as $run) {
            foreach (['payroll_employees', 'payroll_time_and_attandance', 'payroll_service_charges', 'payroll_deductions'] as $table) {
                DB::table($table)->where('payroll_id', $run->id)->where('employee_id', $p->id)->delete();
            }
            $reviews = DB::table('payroll_reviews')->where('payroll_id', $run->id)->where('employee_id', $p->id)->pluck('id');
            DB::table('payroll_review_allowances')->whereIn('payroll_review_id', $reviews)->delete();
            DB::table('payroll_reviews')->whereIn('id', $reviews)->delete();
            if ($run->status === 'locked') {
                DB::table('payroll')->where('id', $run->id)->update([
                    'total_payroll' => round((float) DB::table('payroll_reviews')->where('payroll_id', $run->id)->sum('net_salary'), 2),
                    'total_employees' => DB::table('payroll_employees')->where('payroll_id', $run->id)->count()]);
            }
        }
    }

    /** Of the staff still on probation: one confirmed early, one extended. */
    private function probation(Carbon $today): void
    {
        $on = DB::table('employees')->where('resort_id', $this->resortId)->where('status', 'Active')->where('probation_status', 'Active')
            ->whereNotIn('employment_type', ['Casual', 'Internship'])->orderBy('joining_date')->get(['id', 'probation_end_date']);
        if ($first = $on->get(0)) {
            DB::table('employees')->where('id', $first->id)->update(['probation_status' => 'Confirmed', 'employment_type' => 'Full-Time',
                'probation_review_date' => $today->format('Y-m-d'), 'probation_confirmed_by' => $this->hr->id, 'confirmation_date' => $today->format('Y-m-d'),
                'probation_remarks' => 'Met all targets; confirmed after the final review.']);
        }
        if ($second = $on->get(1)) {
            $until = Carbon::parse($second->probation_end_date ?: $today)->addDays(30)->format('Y-m-d');
            DB::table('employees')->where('id', $second->id)->update(['probation_status' => 'Extended', 'employment_type' => 'Probationary',
                'probation_end_date' => $until, 'probation_review_date' => $until, 'probation_remarks' => 'Extended 30 days to complete guest-service training.']);
        }
    }
}
