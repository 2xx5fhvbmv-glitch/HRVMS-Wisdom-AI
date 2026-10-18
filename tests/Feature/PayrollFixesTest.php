<?php

namespace Tests\Feature;

use App\Http\Controllers\Resorts\Payroll\PayrollController;
use App\Models\Payroll;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Founder 2026-10-15 payroll fixes: holiday + Friday OT, half day 50%, consented shop only, single interest. */
class PayrollFixesTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    private int $empId;
    private int $shiftId;
    private int $rosterId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        // isRamadanMonth() asks api.aladhan.com; January 2026 is not Ramadan.
        \Illuminate\Support\Facades\Http::swap(new \Illuminate\Http\Client\Factory); // drop the trait's catch-all fake first
        \Illuminate\Support\Facades\Http::fake(['api.aladhan.com/*' => \Illuminate\Support\Facades\Http::response(['data' => ['hijri' => ['month' => ['number' => 7]]]]),
            '*' => \Illuminate\Support\Facades\Http::response(['ok' => true])]);
        $this->empId = $this->emp('supervisor')->id;
        $this->shiftId = DB::table('shift_settings')->insertGetId(['resort_id' => $this->resortId, 'ShiftName' => 'Day', 'StartTime' => '08:00',
            'EndTime' => '16:00', 'TotalHours' => '8:0', 'created_at' => now(), 'updated_at' => now()]);
        $this->rosterId = DB::table('duty_rosters')->insertGetId(['resort_id' => $this->resortId, 'Shift_id' => $this->shiftId, 'Emp_id' => $this->empId,
            'created_at' => now(), 'updated_at' => now()]);
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function day(string $date, string $status, ?string $hours = '08:00', ?string $ot = null): void
    {
        DB::table('parent_attendaces')->insert(['roster_id' => $this->rosterId, 'resort_id' => $this->resortId, 'Shift_id' => $this->shiftId,
            'Emp_id' => $this->empId, 'date' => $date, 'Status' => $status, 'CheckingTime' => '08:00:00', 'CheckingOutTime' => '16:00:00',
            'DayWiseTotalHours' => $hours, 'OverTime' => $ot, 'OTStatus' => $ot ? 'Approved' : null, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function fetch(): array
    {
        $rows = $this->as('hr')->postJson(route('fetch.time.attendance'), ['employees' => [$this->empId], 'startDate' => '2026-01-01',
            'endDate' => '2026-01-31', 'currency' => 'Dollar'])->assertOk()->json('data');
        return collect($rows)->firstWhere('empid', $this->empId);
    }

    public function test_holiday_and_friday_overtime_and_half_day_pay()
    {
        $this->day('2026-01-01', 'Present', '08:00', '02:00'); // public holiday, 2h approved OT
        $this->day('2026-01-02', 'Present');                   // Friday: whole shift is Friday OT
        $this->day('2026-01-06', 'Present');
        $before = $this->fetch();
        $this->assertSame(2.0, (float) $before['holiday_ot'], 'holiday OT matched (was compared as d M Y against dd-mm-YYYY)');
        $this->assertSame(8.0, (float) $before['friday_ot']);

        $this->day('2026-01-05', 'HalfDayLeave', null);
        $after = $this->fetch();
        $perDay = (float) $after['basic_salary'] / 31;
        $this->assertEqualsWithDelta($perDay / 2, $after['earned_salary'] - $before['earned_salary'], 0.02, 'a half day pays 50% (was 0)');
    }

    public function test_friday_ot_pay_is_stored_on_the_review()
    {
        $this->assertContains('fridayOTPay', (new \App\Models\PayrollReview)->getFillable());
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('payroll_reviews', 'fridayOTPay'));
    }

    public function test_lock_settles_only_consented_shop_purchases_by_the_amount_deducted()
    {
        DB::table('resort_site_settings')->where('resort_id', $this->resortId)->update(['currency' => 'Dollar']);
        $buy = fn ($date, $price, $status) => DB::table('payments')->insertGetId(['shopkeeper_id' => 1, 'order_id' => 'T' . $price, 'emp_id' => $this->empId,
            'purchased_date' => $date, 'product_id' => 1, 'quantity' => 1, 'price' => $price, 'cash_paid' => 0, 'payroll_deducted' => 0, 'status' => $status,
            'created_at' => now(), 'updated_at' => now()]);
        $older = $buy('2026-01-03', 30, 'Consented');
        $noConsent = $buy('2026-01-05', 50, 'Pending Consent');
        $newer = $buy('2026-01-10', 100, 'Consented');

        $pid = DB::table('payroll')->insertGetId(['resort_id' => $this->resortId, 'start_date' => '2026-01-01', 'end_date' => '2026-01-31', 'total_payroll' => 0,
            'total_employees' => 1, 'draft_date' => now(), 'payment_date' => now(), 'status' => 'pending_approval', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('payroll_deductions')->insert(['payroll_id' => $pid, 'employee_id' => $this->empId, 'Emp_id' => 'X', 'staff_shop' => 110,
            'created_at' => now(), 'updated_at' => now()]);
        $lock = new \ReflectionMethod(PayrollController::class, 'finalizeAndLockPayroll');
        $this->as('hr');
        $lock->invoke(app(PayrollController::class), Payroll::find($pid), '2026-02-01');

        $p = fn ($id) => DB::table('payments')->where('id', $id)->first(['status', 'payroll_deducted']);
        $this->assertSame(['Paid', '30.00'], [$p($older)->status, $p($older)->payroll_deducted], 'oldest consented purchase settled first');
        $this->assertSame(['Partial Paid', '80.00'], [$p($newer)->status, $p($newer)->payroll_deducted], 'the rest of the 110 deducted');
        $this->assertSame(['Pending Consent', '0.00'], [$p($noConsent)->status, $p($noConsent)->payroll_deducted], 'no consent → never deducted');
    }

    public function test_advance_recovery_counts_interest_once_and_the_report_finds_past_over_recovery()
    {
        $advance = DB::table('payroll_advance')->insertGetId(['employee_id' => $this->empId, 'resort_id' => $this->resortId, 'request_type' => 'Loan',
            'request_date' => '2025-12-01', 'pourpose' => 'x', 'request_amount' => 1000, 'currency' => 'USD', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('payroll_recovery_schedule')->insert(['payroll_advance_id' => $advance, 'employee_id' => $this->empId, 'repayment_date' => '2026-01-05',
            'amount' => 110, 'interest' => 10, 'interest_amount' => 10, 'status' => 'Pending', 'created_at' => now(), 'updated_at' => now()]);

        $recovered = $this->as('hr')->postJson(route('fetch.advance.recovery'), ['employee_ids' => [$this->empId], 'start_date' => '2026-01-01', 'end_date' => '2026-01-31'])
            ->assertOk()->json("data.{$this->empId}");
        $this->assertEquals(110, $recovered, 'the installment already includes its interest (was 120)');

        // A run locked under the old formula deducted 120.
        DB::table('resort_site_settings')->where('resort_id', $this->resortId)->update(['currency' => 'Dollar']);
        $pid = DB::table('payroll')->insertGetId(['resort_id' => $this->resortId, 'start_date' => '2026-01-01', 'end_date' => '2026-01-31', 'total_payroll' => 0,
            'total_employees' => 1, 'draft_date' => now(), 'payment_date' => now(), 'status' => 'locked', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('payroll_deductions')->insert(['payroll_id' => $pid, 'employee_id' => $this->empId, 'Emp_id' => 'DMX-SUP', 'advance_loan' => 120,
            'created_at' => now(), 'updated_at' => now()]);
        $csv = tempnam(sys_get_temp_dir(), 'ovr');
        $this->artisan('payroll:advance-overrecovery-report', ['--resort' => $this->resortId, '--csv' => $csv])->assertSuccessful();
        $lines = array_map('str_getcsv', file($csv, FILE_IGNORE_NEW_LINES));
        $row = array_combine($lines[0], $lines[1]);
        $this->assertSame(['110', '120', '10', '10'], [$row['should_recover'], $row['deducted'], $row['interest_counted_twice'], $row['refund_due']]);
        $this->assertSame(1, DB::table('payroll_deductions')->where('payroll_id', $pid)->where('advance_loan', 120)->count(), 'report changes nothing');
        unlink($csv);
    }
}
