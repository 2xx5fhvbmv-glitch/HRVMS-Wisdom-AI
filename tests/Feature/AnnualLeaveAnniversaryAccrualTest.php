<?php

namespace Tests\Feature;

use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/**
 * Founder 2026-10-15: Annual Leave accrues 2.5 days a month on the employee's own
 * anniversary cycle; every other leave type stays calendar-year.
 */
class AnnualLeaveAnniversaryAccrualTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_annual_leave_follows_the_anniversary_and_sick_leave_the_calendar_year()
    {
        $this->buildDemoOrganisation();
        $cat = fn ($type) => DB::table('leave_categories')->where('resort_id', $this->resortId)->where('leave_type', $type)->value('id');
        [$annual, $sick] = [$cat('Annual Leave'), $cat('Sick Leave')];

        Carbon::setTestNow('2026-12-10');
        $this->assertSame(12.5, (float) Common::prorateLeaveByJoiningDate(30, '2026-06-15', $annual), '5 full months × 2.5, no 31 Dec reset');
        $this->assertSame(15.0, (float) Common::prorateLeaveByJoiningDate(30, '2026-06-10', $annual), '6 months → 15 days');
        $this->assertSame('2026-06-15', Common::annualLeaveCycle('2026-06-15')['start']->toDateString());

        Carbon::setTestNow('2027-06-20');
        $cycle = Common::annualLeaveCycle('2026-06-15');
        $this->assertSame(['2027-06-15', '2028-06-14'], [$cycle['start']->toDateString(), $cycle['end']->toDateString()], 'second cycle from the first anniversary');
        $this->assertSame(0.0, (float) Common::prorateLeaveByJoiningDate(30, '2026-06-15', $annual), 'a new cycle starts earning again');
        $this->assertSame(12.5, (float) Common::prorateLeaveByJoiningDate(30, '2024-01-01', $annual), 'older joiner: 5 months since the 1 Jan 2027 anniversary');

        // Sick leave for the same employee: calendar year, joined last year → full allocation.
        $this->assertSame(30, Common::prorateLeaveByJoiningDate(30, '2026-06-15', $sick));
        Carbon::setTestNow('2026-12-10');
        $this->assertSame(17.5, (float) Common::prorateLeaveByJoiningDate(30, '2026-06-15', $sick), 'calendar-year proration (June–Dec = 7 months) unchanged');
        $this->assertSame(17.5, (float) Common::prorateLeaveByJoiningDate(30, '2026-06-15'), 'no category → old rule');
    }
}
