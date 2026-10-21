<?php

namespace App\Support\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * `demo:verify` — checks the rebuilt demo actually works. Each slice adds its
 * flow checks (spec §12). A FAIL means the demo should not be shown.
 */
class DemoVerifier
{
    private array $checks = [];

    public function run(): array
    {
        $this->check('Demo resort exists and is safe to reset', function () {
            Demo::assertSafeToReset();
            return true;
        });
        $resortId = Demo::resortId();

        $this->check('Master admin signs in with the demo password', function () use ($resortId) {
            $hash = DB::table('resort_admins')->where('resort_id', $resortId)->where('is_master_admin', 1)->value('password');
            return $hash && Hash::check(config('demo.password'), $hash);
        });
        $this->check('Every module page is on the menu', function () use ($resortId) {
            $pages = DB::table('module_pages')->where('status', 'Active')->whereNull('deleted_at')->count();
            $granted = DB::table('resort_pagewise_permissions')->where('resort_id', $resortId)->count();
            return $granted === $pages ?: "{$granted} of {$pages} pages granted";
        });

        // Slice 2: organisation and people.
        $employees = fn () => DB::table('employees')->where('resort_id', $resortId)->whereNull('deleted_at');
        $this->check('About 100 active employees in 3 divisions', function () use ($resortId, $employees) {
            $n = $employees()->where('status', 'Active')->count();
            $divisions = DB::table('resort_divisions')->where('resort_id', $resortId)->count();
            return ($n >= 90 && $divisions === 3) ?: "{$n} employees, {$divisions} divisions";
        });
        $this->check('Every demo login signs in with the demo password, no forced change', function () use ($resortId) {
            $logins = ['gm', 'excom', 'hr', 'hr.manager', 'finance', 'finance.manager', 'hod.frontoffice', 'hod.housekeeping', 'hod.fnb', 'hod.kitchen',
                'hod.engineering', 'manager', 'supervisor', 'employee'];
            $bad = [];
            foreach ($logins as $login) {
                $a = DB::table('resort_admins')->where('resort_id', $resortId)->where('email', $login . '@' . config('demo.login_domain'))->first();
                if (!$a || $a->must_change_password || !Hash::check(config('demo.password'), $a->password)) {
                    $bad[] = $login;
                }
            }
            return !$bad ?: 'not ready: ' . implode(', ', $bad);
        });
        $this->check('The HR login is recognised as HR, the GM login as GM', function () use ($resortId) {
            $emp = fn ($login) => \App\Models\Employee::whereHas('resortAdmin', fn ($q) => $q->where('email', $login . '@' . config('demo.login_domain')))
                ->where('resort_id', $resortId)->first();
            $hr = $emp('hr');
            $gm = $emp('gm');
            return ($hr && \App\Helpers\Common::isHR($hr) && $gm && (int) $gm->rank === 8) ?: 'hr/gm login has the wrong rank or department';
        });
        $this->check('Everyone but the GM has a reporting manager', function () use ($employees) {
            $missing = $employees()->whereNull('reporting_to')->where('rank', '!=', 8)->count();
            return $missing === 0 ?: "{$missing} without a manager";
        });
        $this->check('No blank status values (a wrong enum is stored as \'\')', function () use ($employees) {
            $blank = $employees()->where(fn ($q) => $q->where('status', '')->orWhere('probation_status', '')->orWhere('title', ''))->count();
            return $blank === 0 ?: "{$blank} employees with a blank status/title";
        });

        // Slice 3: rosters and attendance.
        $this->check('Everyone is rostered this week and next', function () use ($resortId, $employees) {
            $nextWeek = now()->addWeek()->endOfWeek()->format('Y-m-d');
            $rostered = DB::table('duty_roster_entries')->where('resort_id', $resortId)->where('date', $nextWeek)->distinct()->count('Emp_id');
            $n = $employees()->whereIn('status', ['Active', 'Offboarding'])->count(); // someone serving notice still works
            return $rostered === $n ?: "{$rostered} of {$n} rostered through {$nextWeek}";
        });
        $this->check('12 months of attendance, payroll-ready before the last 10 days', function () use ($resortId) {
            $att = fn () => DB::table('parent_attendaces')->where('resort_id', $resortId);
            $oldest = $att()->min('date');
            if (!$oldest || $oldest > now()->subMonths(11)->format('Y-m-d')) {
                return 'oldest attendance is ' . ($oldest ?? 'missing');
            }
            // What PayrollController blocks a run on: no status, a Present day without both punches, undecided OT.
            $bad = $att()->where('date', '<', now()->subDays(10)->format('Y-m-d'))->where(fn ($q) => $q->whereNull('Status')->orWhere('Status', '')
                ->orWhereNull('CheckingOutTime')->orWhere(fn ($q) => $q->whereNotNull('OverTime')->whereNull('OTStatus')))->count();
            return $bad === 0 ?: "{$bad} older attendance days would block payroll";
        });
        $this->check('Overtime: decided in the past, some pending now', function () use ($resortId) {
            $pending = DB::table('employee_overtimes')->where('resort_id', $resortId)->where('status', 'pending')->count();
            $approved = DB::table('employee_overtimes')->where('resort_id', $resortId)->where('status', 'approved')->count();
            return ($pending > 0 && $approved > 0) ?: "{$approved} approved, {$pending} pending";
        });

        // Slice 3b: leave and island passes.
        $leaves = fn () => DB::table('employees_leaves')->where('resort_id', $resortId);
        $this->check('Leave in every state: taken, on leave today, waiting, rejected', function () use ($leaves) {
            $today = now()->format('Y-m-d');
            $n = ['taken' => $leaves()->where('status', 'Approved')->where('to_date', '<', $today)->count(),
                'on leave today' => $leaves()->where('status', 'Approved')->where('from_date', '<=', $today)->where('to_date', '>=', $today)->count(),
                'waiting' => $leaves()->where('status', 'Pending')->count(), 'rejected' => $leaves()->where('status', 'Rejected')->count()];
            return min($n) > 0 ?: json_encode($n);
        });
        $this->check('Every leave\'s approval chain matches its status', function () use ($leaves) {
            $steps = DB::table('employees_leaves_status')->whereIn('leave_request_id', $leaves()->pluck('id'))->get()->groupBy('leave_request_id');
            $bad = $leaves()->get(['id', 'status'])->filter(function ($l) use ($steps) {
                $s = ($steps[$l->id] ?? collect())->pluck('status');
                return $s->isEmpty() || match ($l->status) {
                    'Approved' => $s->contains(fn ($x) => $x !== 'Approved'),
                    'Pending' => !$s->contains('Pending') || $s->contains('Rejected'),
                    default => !$s->contains('Rejected'),
                };
            })->count();
            return $bad === 0 ?: "{$bad} leaves out of step with their approvals";
        });
        $this->check('Every login with a team has a leave request waiting on them', function () use ($resortId) {
            $missing = [];
            foreach (DB::table('employees as e')->join('resort_admins as a', 'a.id', '=', 'e.Admin_Parent_id')->where('e.resort_id', $resortId)
                ->where('e.status', 'Active')->whereExists(fn ($q) => $q->from('employees as r')->whereColumn('r.reporting_to', 'e.id'))
                ->get(['e.id', 'a.email'])->filter(fn ($a) => Demo::isLogin($a->email)) as $a) {
                $waiting = DB::table('employees_leaves_status as s')->join('employees_leaves as l', 'l.id', '=', 's.leave_request_id')
                    ->where('l.resort_id', $resortId)->where('l.status', 'Pending')->where('s.status', 'Pending')->where('s.approver_id', $a->id)
                    ->whereRaw('s.id = (SELECT MIN(p.id) FROM employees_leaves_status p WHERE p.leave_request_id = l.id AND p.status = ?)', ['Pending'])->exists();
                if (!$waiting) {
                    $missing[] = strstr($a->email, '@', true);
                }
            }
            return !$missing ?: 'nothing waiting for ' . implode(', ', $missing);
        });
        $this->check('Leave balances: every permanent employee has one, nobody is overdrawn', function () use ($resortId, $employees) {
            // Casual/Intern staff sit outside the benefit grid.
            foreach ($employees()->where('status', 'Active')->whereNotIn('employment_type', ['Casual', 'Internship'])->pluck('id') as $id) {
                $balance = \App\Helpers\Common::getEmployeeLeaveBalance($id, $resortId);
                if (!$balance || collect($balance)->min('balance') < 0) {
                    return "employee {$id}: " . ($balance ? 'overdrawn' : 'no balance');
                }
            }
            return true;
        });
        $this->check('Approved leave days are leave in the attendance', function () use ($resortId) {
            $worked = DB::table('parent_attendaces as p')->join('employees_leaves as l', fn ($j) => $j->on('l.emp_id', '=', 'p.Emp_id')
                ->where('l.status', 'Approved')->whereColumn('p.date', '>=', 'l.from_date')->whereColumn('p.date', '<=', 'l.to_date'))
                ->where('p.resort_id', $resortId)->where('p.Status', '!=', 'FullDayLeave')->count();
            return $worked === 0 ?: "{$worked} attendance days inside approved leave";
        });
        $this->check('Island passes: travelled, waiting, rejected, cancelled', function () use ($resortId) {
            $passes = fn () => DB::table('employee_travel_passes')->where('resort_id', $resortId);
            $n = ['travelled' => $passes()->where('status', 'Approved')->where('employee_arrival_status', 'arrived')->count(),
                'waiting' => $passes()->where('status', 'Pending')->count(), 'rejected' => $passes()->where('status', 'Rejected')->count(),
                'cancelled' => $passes()->where('status', 'Cancel')->count()];
            return min($n) > 0 ?: json_encode($n);
        });

        // Slice 5a: workforce planning.
        $this->check('Workforce planning: this year approved with open seats, next year out to the HODs', function () use ($resortId) {
            $y = (int) date('Y');
            $responses = fn ($year) => DB::table('manning_responses')->where('resort_id', $resortId)->where('year', $year);
            $open = DB::table('position_monthly_data as p')->join('manning_responses as m', 'm.id', '=', 'p.manning_response_id')
                ->where('m.resort_id', $resortId)->where('m.year', $y)->where('p.month', 1)->sum('p.vacantcount');
            $depts = DB::table('resort_departments')->where('resort_id', $resortId)->where('status', 'active')->count();
            $approved = $responses($y)->where('employment_type', 'Permanent')->where('budget_process_status', 'Approved')->count();
            $pending = DB::table('resorts_child_notifications')->where('response', 'No')
                ->whereIn('Parent_msg_id', DB::table('resorts_parent_notifications')->where('resort_id', $resortId)->select('message_id'))->count();
            $priced = DB::table('resort_vacant_budget_costs')->where('resort_id', $resortId)->where('year', $y)->where('basic_salary', '>', 0)->count();
            return ($approved === $depts && $open > 0 && $priced > 0 && $responses($y + 1)->exists() && $pending > 0)
                ?: "{$approved}/{$depts} departments approved, {$open} open seats, {$priced} priced, {$pending} pending";
        });
        $this->check('Casual and intern staff on their own positions, each position with a pay rate', function () use ($resortId, $employees) {
            $casual = $employees()->where('employment_type', 'Casual')->count();
            $interns = $employees()->where('employment_type', 'Internship')->count();
            $unpaid = DB::table('resort_positions')->where('resort_id', $resortId)->whereNotNull('employee_category')
                ->whereNotIn('id', DB::table('casual_position_pay_configs')->where('resort_id', $resortId)->select('position_id'))->count();
            return ($casual > 0 && $interns > 0 && $unpaid === 0) ?: "{$casual} casual, {$interns} interns, {$unpaid} positions without a pay rate";
        });
        $this->check('Cost configuration: permanent and casual/intern lines', function () use ($resortId) {
            $perm = DB::table('resort_budget_costs')->where('resort_id', $resortId)->where('status', 'active')->count();
            $non = DB::table('resort_nonpermanent_budget_costs')->where('resort_id', $resortId)->where('status', 'active')->count();
            return ($perm > 0 && $non > 0) ?: "{$perm} permanent, {$non} casual/intern lines";
        });
        $this->check('Occupancy: 12 months of history and today ± 5 days', function () use ($resortId) {
            $occ = fn () => DB::table('occuplanies')->where('resort_id', $resortId);
            $months = $occ()->where('occupancydate', '<', now()->startOfMonth()->format('Y-m-d'))->selectRaw("DISTINCT DATE_FORMAT(occupancydate, '%Y-%m') m")->get()->count();
            $around = $occ()->whereBetween('occupancydate', [now()->subDays(5)->format('Y-m-d'), now()->addDays(5)->format('Y-m-d')])->count();
            return ($months >= 12 && $around === 11) ?: "{$months} past months, {$around} of 11 days around today";
        });

        // Slice 4: payroll.
        $this->check('12 locked payroll runs, last month waiting on Finance → HR → GM', function () use ($resortId) {
            $locked = DB::table('payroll')->where('resort_id', $resortId)->where('payroll_category', 'Permanent')->where('status', 'locked')->count();
            $pending = DB::table('payroll')->where('resort_id', $resortId)->where('payroll_category', 'Permanent')->where('status', 'pending_approval')->pluck('id');
            $steps = DB::table('payroll_approvals')->whereIn('payroll_id', $pending)->where('status', 'pending')->orderBy('step_order')->pluck('role_title')->all();
            return ($locked === 12 && $pending->count() === 1 && $steps === ['Finance EXCOM', 'HR EXCOM', 'GM']) ?: "{$locked} locked, {$pending->count()} pending";
        });
        $this->check('Casual payroll: 12 locked runs and last month waiting, casual staff only', function () use ($resortId) {
            $runs = DB::table('payroll')->where('resort_id', $resortId)->where('payroll_category', 'Casual');
            $locked = (clone $runs)->where('status', 'locked')->count();
            $pending = (clone $runs)->where('status', 'pending_approval')->count();
            $others = DB::table('payroll_reviews')->whereIn('payroll_id', (clone $runs)->select('id'))
                ->whereNotIn('employee_id', DB::table('employees')->where('resort_id', $resortId)->where('employment_type', 'Casual')->select('id'))->count();
            $model = DB::table('resort_site_settings')->where('resort_id', $resortId)->value('casual_payment_model');
            return ($locked === 12 && $pending === 1 && $others === 0 && $model === 'direct_pay') ?: "{$locked} locked, {$pending} pending, {$others} non-casual payslips, model {$model}";
        });
        $this->check('Every locked run totals its payslips', function () use ($resortId) {
            $off = DB::table('payroll as p')->where('p.resort_id', $resortId)->where('p.status', 'locked')
                ->whereRaw('ABS(p.total_payroll - (SELECT COALESCE(SUM(r.net_salary), 0) FROM payroll_reviews r WHERE r.payroll_id = p.id)) > 0.01')->count();
            return $off === 0 ?: "{$off} runs don't add up";
        });

        // Module seeders: every list the client opens has rows in each state it can show.
        $has = function (string $table, string $column, array $want) use ($resortId) {
            $missing = array_values(array_diff($want, DB::table($table)->where('resort_id', $resortId)->distinct()->pluck($column)->all()));
            return $missing ? "{$table}: no " . implode(', ', $missing) : true;
        };
        $all = fn (...$results) => collect($results)->first(fn ($r) => $r !== true) ?? true;
        $this->check('Leavers: resignations in every state, settlements, former staff in Pension', function () use ($resortId, $has, $all) {
            $settlements = DB::table('final_settlements as f')->join('employees as e', 'e.id', '=', 'f.employee_id')->where('e.resort_id', $resortId)->distinct()->pluck('f.status')->all();
            $former = DB::table('employees as e')->where('e.resort_id', $resortId)->where('e.nationality', 'Maldivian')->whereIn('e.status', ['Inactive', 'Terminated'])
                ->whereExists(fn ($q) => $q->from('payroll_deductions as d')->join('payroll as p', 'p.id', '=', 'd.payroll_id')
                    ->whereColumn('d.employee_id', 'e.id')->where('p.status', 'locked')->where('d.pension', '>', 0))->count();
            return $all($has('employee_resignation', 'status', ['Pending', 'Approved', 'Withdraw', 'Rejected']),
                array_diff(['finalized', 'review'], $settlements) ? 'settlements: ' . implode(', ', $settlements) : true,
                $former >= 2 ?: "{$former} former Maldivian staff with pension");
        });
        $this->check('Talent Acquisition: vacancies at every approval stage, applicants at every stage', function () use ($resortId, $has, $all) {
            $chain = DB::table('t_anotification_children as c')->join('t_anotification_parents as p', 'p.id', '=', 'c.Parent_ta_id')
                ->where('p.Resort_id', $resortId)->distinct()->pluck('c.status')->all();
            $latest = DB::table('applicant_wise_statuses as s')->join('applicant_form_data as a', 'a.id', '=', 's.Applicant_id')->where('a.resort_id', $resortId)
                ->whereRaw('s.id = (SELECT MAX(id) FROM applicant_wise_statuses WHERE Applicant_id = s.Applicant_id)')->distinct()->pluck('s.status')->all();
            $booked = DB::table('applicant_inter_view_details')->where('resort_id', $resortId)->where('Status', 'Slot Booked')->where('InterViewDate', '>=', now()->format('Y-m-d'))->count();
            $missing = array_merge(array_diff(['Active', 'Approved', 'Hold', 'ForwardedToNext'], $chain), array_diff(['Sortlisted By Wisdom AI', 'Selected', 'Offer Letter Sent', 'Contract Accepted', 'Rejected'], $latest));
            return $all($has('vacancies', 'status', ['Active', 'Closed', 'Draft']), $missing ? 'missing ' . implode(', ', $missing) : true, $booked > 0 ?: 'no upcoming interview');
        });
        $this->check('People: promotions, transfers, increments, info updates, announcements, onboarding', function () use ($resortId, $has, $all) {
            $logins = DB::table('employees as e')->join('resort_admins as a', 'a.id', '=', 'e.Admin_Parent_id')->where('e.resort_id', $resortId)
                ->where('a.email', 'not like', strtolower(config('demo.prefix')) . '-%')->whereNotExists(fn ($q) => $q->from('employees_education as x')->whereColumn('x.employee_id', 'e.id'))->count();
            return $all($has('employee_promotions', 'status', ['Approved', 'Pending', 'On Hold', 'Rejected']),
                $has('employee_transfers', 'status', ['Approved', 'Pending', 'On Hold', 'Rejected']),
                $has('people_salary_increment', 'status', ['Approved', 'Pending', 'Hold', 'Change-Request', 'Rejected']),
                $has('employee_info_update_request', 'status', ['Pending', 'Approved', 'Rejected']),
                $has('announcement', 'status', ['Published', 'Scheduled', 'Draft']),
                DB::table('employee_itineraries')->where('resort_id', $resortId)->exists() ?: 'no onboarding itineraries',
                DB::table('compliances')->where('resort_id', $resortId)->where('Dismissal_status', 'Pending')->exists() ?: 'no compliance breaches',
                $logins === 0 ?: "{$logins} logins without a qualification");
        });
        $this->check('Performance: a closed and a running cycle, KPIs, check-ins, PIP and PDP', fn () => $all(
            $has('performance_cycles', 'status', ['Close', 'OnGoing']),
            DB::table('performance_kpi_parents')->where('resort_id', $resortId)->exists() ?: 'no KPIs',
            DB::table('monthly_checking_models')->where('resort_id', $resortId)->exists() ?: 'no check-ins',
            DB::table('employee_pip_plans')->where('resort_id', $resortId)->exists() ?: 'no PIP',
            DB::table('employee_pdp_plans')->where('resort_id', $resortId)->exists() ?: 'no PDP'));
        $this->check('Accommodation: staff housed, maintenance requests open and closed, housekeeping', function () use ($resortId, $has, $all) {
            $housed = DB::table('assing_accommodations')->where('resort_id', $resortId)->where('emp_id', '>', 0)->count();
            return $all($housed >= 80 ?: "{$housed} housed", $has('maintanace_requests', 'Status', ['pending', 'Open', 'In-Progress', 'Closed']),
                DB::table('housekeeping_requests')->where('resort_id', $resortId)->exists() ?: 'no housekeeping requests');
        });
        $this->check('Incidents in every state, with investigation meetings', fn () => $all(
            $has('incidents', 'status', ['Reported', 'Assigned To', 'Investigation In Progress', 'Approval Pending', 'Approved', 'Rejected', 'Resolved']),
            DB::table('incidents_investigation_meetings as m')->join('incidents as i', 'i.id', '=', 'm.incident_id')->where('i.resort_id', $resortId)->exists() ?: 'no meetings'));
        $this->check('Surveys: finished with answers, running, draft', fn () => $all(
            $has('parent_surveys', 'Status', ['Complete', 'OnGoing', 'SaveAsDraft']),
            DB::table('survey_results as r')->join('parent_surveys as s', 's.id', '=', 'r.Parent_survey_id')->where('s.resort_id', $resortId)->exists() ?: 'no answers'));

        return ['passed' => !collect($this->checks)->contains('ok', false), 'checks' => $this->checks];
    }

    /** $test returns true (pass), or false / a string explaining the failure. */
    private function check(string $name, callable $test): void
    {
        try {
            $result = $test();
        } catch (\Throwable $e) {
            $result = $e->getMessage();
        }
        $this->checks[] = ['name' => $name, 'ok' => $result === true, 'detail' => $result === true ? null : ($result ?: 'failed')];
    }
}
