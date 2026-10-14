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
            $n = $employees()->where('status', 'Active')->count();
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
