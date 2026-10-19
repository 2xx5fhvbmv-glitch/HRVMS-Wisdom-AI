<?php

namespace Tests\Feature;

use App\Helpers\StorageHelper;
use App\Support\Demo\Demo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Demo ENV slice 4 (payroll) and the payroll day-count fix: the run wizard's
 * own calculation must agree with the seeded run, and the pending run goes
 * Finance → HR → GM and locks itself.
 */
class DemoPayrollTest extends TestCase
{
    use DatabaseTransactions;

    private int $resortId;
    private object $run;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'demo.enabled' => true, 'demo.password' => 'Demo#Pass2026', 'demo.resort_code' => 'DEMOTEST99',
            'demo.resort_email' => 'resort@demo.thewisdom.ai', 'demo.prefix' => 'DMT']);
        Demo::refresh();
        Storage::fake(config('settings.storage_driver'));
        (new \ReflectionClass(StorageHelper::class))->setStaticPropertyValue('cachedDisk', null);
        Mail::fake();
        // Ramadan lookup: never Ramadan in the pending month.
        Http::fake(['api.aladhan.com/*' => Http::response(['data' => ['hijri' => ['month' => ['number' => 3]]]])]);
        $this->artisan('demo:reset', ['--force' => true])->assertSuccessful();
        Demo::refresh();
        $this->resortId = Demo::resortId();
        $this->run = DB::table('payroll')->where('resort_id', $this->resortId)->where('payroll_category', 'Permanent')->where('status', 'pending_approval')->first();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function signIn(string $login): void
    {
        auth('resort-admin')->logout();
        $this->post(route('resort.login'), ['email' => "{$login}@demo.thewisdom.ai", 'password' => 'Demo#Pass2026'])->assertOk();
    }

    public function test_the_wizard_pays_day_offs_and_agrees_with_the_seeded_run()
    {
        $this->signIn('finance');
        $reviews = DB::table('payroll_reviews')->where('payroll_id', $this->run->id)->get()->keyBy('employee_id');
        $times = DB::table('payroll_time_and_attandance')->where('payroll_id', $this->run->id)->pluck('leave_types', 'employee_id');

        $result = collect($this->post(route('fetch.time.attendance'), [
            'employees' => $reviews->keys()->all(), 'startDate' => $this->run->start_date, 'endDate' => $this->run->end_date,
            'payrollId' => $this->run->id, 'currency' => 'Dollar',
        ])->assertOk()->json('data') ?? [])->keyBy('empid');

        $this->assertCount($reviews->count(), $result);
        $this->assertGreaterThan(0, $result->sum('day_off'), 'rostered day-offs are counted');
        $this->assertSame(0.0, (float) $result->sum('absent_deduction'), 'absent days are not deducted twice');
        foreach ($reviews as $id => $review) {
            $this->assertEqualsWithDelta((float) $review->earned_salary, $result[$id]['earned_salary'], 0.011, "earned salary of employee {$id}");
            $this->assertEqualsWithDelta((float) $review->earnings_overtime, $result[$id]['totalOTPay'], 0.011, "OT pay of employee {$id}");
            $this->assertEqualsWithDelta((float) $review->fridayOTPay, $result[$id]['fridayOTPay'], 0.011, "Friday OT pay of employee {$id}");
            $this->assertEqualsWithDelta((float) $review->earnings_basic, $result[$id]['basic_salary'], 0.011, "basic of employee {$id}");
            $this->assertSame($times[$id], strip_tags($result[$id]['leave_types']), "leave days of employee {$id}");
        }
        $this->assertNotSame([], array_diff($times->all(), ['-']), 'some approved leave falls in the pending run');
        // A full month of shifts plus day-offs is (close to) the full basic salary — it was ~78% before.
        $line = $result->first(fn ($r) => (int) $r['absent'] === 0 && (int) $r['unpaid_absent'] === 0 && (int) $r['unaccounted_days'] === 0);
        $this->assertGreaterThan(0.97 * $line['basic_salary'], $line['earned_salary']);

        // The dashboard's live estimate uses the same day counts.
        $this->followingRedirects()->get(route('payroll.dashboard'))->assertOk();
        $this->getJson(route('payroll.dashboard.estimate-breakdown'))->assertOk();
    }

    public function test_the_casual_run_screen_agrees_with_the_seeded_casual_run()
    {
        $this->signIn('finance');
        $run = DB::table('payroll')->where('resort_id', $this->resortId)->where('payroll_category', 'Casual')->where('status', 'pending_approval')->first();
        $reviews = DB::table('payroll_reviews')->where('payroll_id', $run->id)->get()->keyBy('employee_id');
        $this->assertNotEmpty($reviews);

        $result = collect($this->post(route('resort.casualPayroll.fetchTimeAttendance'), [
            'employees' => $reviews->keys()->all(), 'startDate' => $run->start_date, 'endDate' => $run->end_date, 'currency' => 'USD',
        ])->assertOk()->json('data'))->keyBy('employee_id');

        $this->assertCount($reviews->count(), $result);
        foreach ($reviews as $id => $review) {
            $this->assertEqualsWithDelta((float) $review->earned_salary, $result[$id]['earned_salary'], 0.011, "earned salary of casual {$id}");
            $this->assertEqualsWithDelta((float) $review->earnings_overtime, $result[$id]['total_ot_pay'], 0.011, "OT pay of casual {$id}");
            $this->assertEqualsWithDelta((float) $review->service_provider_commission, $result[$id]['service_provider_commission'], 0.011, "agency fee of casual {$id}");
        }
        $this->followingRedirects()->get(route('resort.casualPayroll.index'))->assertOk()->assertSee('Casual Payroll');
    }

    public function test_the_pending_run_is_approved_by_finance_hr_and_gm_and_locks_itself()
    {
        $this->assertSame(12, DB::table('payroll')->where('resort_id', $this->resortId)->where('payroll_category', 'Permanent')->where('status', 'locked')->count());
        $due = DB::table('payroll_recovery_schedule as s')->join('payroll_deductions as d', 'd.employee_id', '=', 's.employee_id')
            ->where('d.payroll_id', $this->run->id)->where('d.advance_loan', '>', 0)
            ->whereBetween('s.repayment_date', [$this->run->start_date, $this->run->end_date])->pluck('s.id');

        foreach (['finance', 'hr', 'gm'] as $n => $login) {
            $this->signIn($login);
            $this->post(route('payroll.approve'), ['payroll_id' => $this->run->id, 'action' => 'approve', 'remarks' => 'ok'])->assertOk();
            $this->assertSame($n + 1, DB::table('payroll_approvals')->where('payroll_id', $this->run->id)->where('status', 'approved')->count());
        }

        $run = DB::table('payroll')->find($this->run->id);
        $this->assertSame('locked', $run->status);
        $this->assertEqualsWithDelta(DB::table('payroll_reviews')->where('payroll_id', $run->id)->sum('net_salary'), (float) $run->total_payroll, 0.01);
        $this->assertSame($due->count(), DB::table('payroll_recovery_schedule')->whereIn('id', $due)->where('status', 'Paid')->count());
    }
}
