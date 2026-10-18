<?php

namespace App\Console\Commands;

use App\Http\Controllers\Resorts\Payroll\PayrollController;
use App\Models\ResortAdmin;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * READ-ONLY: what an open (unlocked) payroll run would pay under the current
 * rules — roster-based day-offs, prorated days, half day 50%, holiday/Friday OT,
 * advance recovery without double interest — against what its saved review
 * holds now. Founder 2026-10-15: recalculate the open run, but only after HR has
 * seen this diff; locked runs are never touched. Nothing is written.
 *
 *   php artisan payroll:open-run-diff            (newest unlocked run per resort)
 *   php artisan payroll:open-run-diff 24         (one run)
 */
class PayrollOpenRunDiff extends Command
{
    protected $signature = 'payroll:open-run-diff {payroll_id? : A specific unlocked run} {--resort= : Only this resort id}';
    protected $description = 'Per-employee old vs recalculated pay for open payroll runs (read-only).';

    public function handle(): int
    {
        $open = DB::table('payroll')->whereNotIn('status', ['locked', 'approved'])
            ->when($this->argument('payroll_id'), fn ($q, $id) => $q->where('id', $id))
            ->when($this->option('resort'), fn ($q, $r) => $q->where('resort_id', $r))
            ->orderByDesc('start_date')->get(['id', 'resort_id', 'status', 'start_date', 'end_date']);
        if (!$this->argument('payroll_id')) {
            $open = $open->unique('resort_id')->values(); // newest open run per resort
        }
        if ($open->isEmpty()) {
            $this->info('No open payroll run.');
            return self::SUCCESS;
        }

        foreach ($open as $run) {
            $admin = ResortAdmin::where('resort_id', $run->resort_id)->where('is_master_admin', 1)->first();
            $employees = DB::table('payroll_employees')->where('payroll_id', $run->id)->pluck('employee_id')->all();
            if (!$admin || !$employees) {
                $this->warn("Run #{$run->id} (resort {$run->resort_id}): " . (!$admin ? 'no master admin' : 'no employees') . ' — skipped.');
                continue;
            }
            $currency = DB::table('resort_site_settings')->where('resort_id', $run->resort_id)->value('currency') ?: 'Dollar';

            // The run's own calculation, exactly as the wizard calls it (HR overrides included via payrollId).
            Auth::guard('resort-admin')->setUser($admin);
            $request = Request::create('/payroll/fetch-time-attendance', 'POST', ['employees' => $employees, 'startDate' => $run->start_date,
                'endDate' => $run->end_date, 'payrollId' => $run->id, 'currency' => $currency]);
            $fresh = collect((app(PayrollController::class)->fetchTimeAttendance($request)->getData(true)['data'] ?? []))->keyBy('empid');
            $saved = DB::table('payroll_reviews')->where('payroll_id', $run->id)->get()->keyBy('employee_id');

            $rows = [];
            foreach ($employees as $empId) {
                $new = $fresh->get($empId); // null: no attendance in the period
                $old = $saved->get($empId);
                $newEarned = (float) ($new['earned_salary'] ?? 0);
                $newOt = (float) ($new['totalOTPay'] ?? 0);
                $oldEarned = (float) ($old->earned_salary ?? 0);
                $oldOt = (float) ($old->earnings_overtime ?? 0);
                if (abs($newEarned - $oldEarned) < 0.01 && abs($newOt - $oldOt) < 0.01) {
                    continue;
                }
                $rows[] = [$new['employee_id'] ?? $empId, $oldEarned, $newEarned, round($newEarned - $oldEarned, 2), $oldOt, $newOt, round($newOt - $oldOt, 2)];
            }

            $this->info("Run #{$run->id} — resort {$run->resort_id}, {$run->start_date} to {$run->end_date} ({$run->status}, {$currency}): "
                . count($rows) . ' of ' . count($employees) . ' employee(s) change.' . ($saved->isEmpty() ? ' (no saved review yet — "old" is 0)' : ''));
            if ($rows) {
                $this->table(['employee', 'earned (saved)', 'earned (new)', 'Δ earned', 'OT (saved)', 'OT (new)', 'Δ OT'], $rows);
            }
        }
        $this->comment('Read-only: nothing was recalculated or saved. Re-run the payroll wizard for the run once HR agrees.');
        return self::SUCCESS;
    }
}
