<?php

namespace Tests\Feature;

use App\Models\ResortAdmin;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The compliance run on a resort with more than 50 active employees (it used to
 * crash there), with hand-built cases for the probation, break and 48-hour rules.
 */
class ComplianceRunTest extends TestCase
{
    use DatabaseTransactions;

    private int $resortId = 28;
    private array $emp = [];
    private int $shiftId;
    private int $rosterId;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::table('employees')->where('resort_id', $this->resortId)->where('status', 'Active')->count() <= 50) {
            $this->markTestSkipped('Needs a resort with more than 50 active employees.');
        }
        Http::fake(['*' => Http::response(['description' => 'x', 'severity' => 'low', 'remediation' => 'x', 'anomalies' => []])]);
        Event::fake([\App\Events\ResortNotificationEvent::class]);

        $this->emp = DB::table('employees')->where('resort_id', $this->resortId)->where('status', 'Active')->orderBy('id')->limit(8)->pluck('id')->all();
        $window = [Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d'), Carbon::now()->endOfWeek()->format('Y-m-d')];
        DB::table('parent_attendaces')->whereIn('Emp_id', $this->emp)->whereBetween('date', $window)->delete();
        DB::table('compliances')->whereIn('employee_id', $this->emp)->delete();
        $this->shiftId = DB::table('shift_settings')->insertGetId(['resort_id' => $this->resortId, 'ShiftName' => 'Test', 'StartTime' => '07:00', 'EndTime' => '16:00', 'TotalHours' => '9:0']);
        $this->rosterId = DB::table('duty_rosters')->insertGetId(['resort_id' => $this->resortId, 'Shift_id' => $this->shiftId, 'Emp_id' => $this->emp[0]]);
    }

    private function day(int $emp, string $date, string $in, ?string $out, ?string $overtime = null): int
    {
        return DB::table('parent_attendaces')->insertGetId(['roster_id' => $this->rosterId, 'resort_id' => $this->resortId, 'Shift_id' => $this->shiftId,
            'Emp_id' => $emp, 'date' => $date, 'Status' => 'Present', 'CheckingTime' => $in, 'CheckingOutTime' => $out, 'OverTime' => $overtime]);
    }

    private function breach(int $emp, string $name)
    {
        return DB::table('compliances')->where('employee_id', $emp)->where('compliance_breached_name', $name)->first();
    }

    public function test_run_completes_and_each_rule_judges_correctly()
    {
        [$probation, $withBreak, $noBreak, $leftEarly, $long, $twoWeeks, $overnight, $overtime] = $this->emp;
        $today = Carbon::today()->format('Y-m-d');
        $lastMonday = Carbon::now()->subWeek()->startOfWeek();
        $thisMonday = Carbon::now()->startOfWeek();

        // Probation: a 6-month probation breaches the 3-month maximum.
        DB::table('employees')->where('id', $probation)->update(['probation_status' => 'Active', 'joining_date' => '2026-01-10', 'probation_end_date' => '2026-07-10']);
        DB::table('employees')->whereIn('id', array_slice($this->emp, 1))->update(['probation_status' => 'Confirmed']);

        // Breaks (today): 6.5h with a 30-min break → fine; 6h with none → breach; 4h then left → fine.
        $id = $this->day($withBreak, $today, '07:00', '13:30');
        DB::table('break_attendaces')->insert(['Parent_attd_id' => $id, 'Break_InTime' => '10:00', 'Break_OutTime' => '10:30', 'Total_Break_Time' => '00:30',
            'InTime_Location' => '-', 'OutTime_Location' => '-']);
        $this->day($noBreak, $today, '07:00', '13:00');
        $this->day($leftEarly, $today, '08:00', '12:00');

        // Weekly hours (last week, Mon–Sat): 6 × 9h = 54 normal → breach.
        for ($d = 0; $d < 6; $d++) {
            $this->day($long, $lastMonday->copy()->addDays($d)->format('Y-m-d'), '07:00', '16:00');
            // 6 overnight shifts 22:00→06:00 = 48h → fine (used to subtract).
            $this->day($overnight, $lastMonday->copy()->addDays($d)->format('Y-m-d'), '22:00', '06:00');
            // 6 × 9h with 1h overtime each = 48 normal hours → fine (overtime not counted).
            $this->day($overtime, $lastMonday->copy()->addDays($d)->format('Y-m-d'), '07:00', '16:00', '01:00');
        }
        // 5 × 9h last week + 3 × 9h this week: 45h and 27h → fine (used to add up to 72h).
        for ($d = 0; $d < 5; $d++) {
            $this->day($twoWeeks, $lastMonday->copy()->addDays($d)->format('Y-m-d'), '07:00', '16:00');
        }
        for ($d = 0; $d < min(3, Carbon::now()->dayOfWeekIso); $d++) {
            $this->day($twoWeeks, $thisMonday->copy()->addDays($d)->format('Y-m-d'), '07:00', '16:00');
        }

        $this->actingAs(ResortAdmin::where('resort_id', $this->resortId)->where('is_master_admin', 1)->first(), 'resort-admin');
        $response = $this->get(route('people.compliance.run'));
        $this->assertLessThan(500, $response->status(), 'the run no longer crashes with more than 50 active employees');

        $this->assertNotNull($row = $this->breach($probation, 'Extended Probation Period'), 'probation rule runs');
        $this->assertStringContainsString('6 months', $row->description);

        $this->assertNull($this->breach($withBreak, 'Without Mandatory Break'));
        $this->assertNotNull($row = $this->breach($noBreak, 'Without Mandatory Break'));
        $this->assertStringContainsString('6 hours without a break', $row->description);
        $this->assertNull($this->breach($leftEarly, 'Without Mandatory Break'), 'measured to check-out, not to now');

        $this->assertNotNull($row = $this->breach($long, 'Weekly Working Hours Limit'));
        $this->assertStringContainsString('54 normal hours', $row->description);
        $this->assertNull($this->breach($twoWeeks, 'Weekly Working Hours Limit'), 'each week judged on its own');
        $this->assertNull($this->breach($overnight, 'Weekly Working Hours Limit'), 'overnight shifts count forwards');
        $this->assertNull($this->breach($overtime, 'Weekly Working Hours Limit'), 'overtime excluded');
    }

    public function test_vacancy_minimum_wage_overtime_status_and_a_resort_without_hr()
    {
        // No rank-3 employee and no HR department → nobody to notify; the run must still record breaches.
        DB::table('employees')->where('resort_id', $this->resortId)->where('rank', '3')->update(['rank' => '6']);
        DB::table('resort_departments')->where('resort_id', $this->resortId)->whereIn(DB::raw('LOWER(name)'), ['human resources', 'hr'])->update(['name' => 'People Ops']);
        $this->assertNull(\App\Helpers\Common::FindResortHR($this->resortId));

        $position = DB::table('resort_positions')->where('resort_id', $this->resortId)->value('id');
        $title = DB::table('resort_positions')->where('id', $position)->value('position_title');
        DB::table('vacancies')->where('Resort_id', $this->resortId)->update(['status' => 'Cancelled']);
        foreach ([[400, 'USD'], [800, 'USD'], [6500, 'MVR'], [9000, 'MVR'], [0, 'USD']] as [$salary, $unit]) {
            DB::table('vacancies')->insert(['Resort_id' => $this->resortId, 'position' => $position, 'budgeted_salary' => $salary, 'propsed_salary' => 0,
                'amount_unit' => $unit, 'status' => 'Active']);
        }

        $noOvertime = $this->emp[4];
        DB::table('employees')->where('id', $noOvertime)->update(['entitled_overtime' => 'no']);
        DB::table('parent_attendaces')->where('id', $this->day($noOvertime, Carbon::today()->format('Y-m-d'), '07:00', '16:00', '01:00'))->update(['OTStatus' => 'Approved']);

        $this->actingAs(ResortAdmin::where('resort_id', $this->resortId)->where('is_master_admin', 1)->first(), 'resort-admin');
        $this->assertLessThan(500, $this->get(route('people.compliance.run'))->status(), 'no crash without an HR person');

        $flagged = DB::table('compliances')->where('resort_id', $this->resortId)->where('compliance_breached_name', 'Minimum Wage Compliance')
            ->where('description', 'like', "Vacancy {$title} %")->pluck('description')->implode(' | ');
        $this->assertStringContainsString('(400.00)', $flagged);
        $this->assertStringContainsString('(6500.00)', $flagged, 'MVR budget compared with the MVR minimum');
        $this->assertStringNotContainsString('(800.00)', $flagged);
        $this->assertStringNotContainsString('(9000.00)', $flagged);
        $this->assertStringNotContainsString('(0.00)', $flagged, 'unbudgeted vacancy is not a breach');

        $this->assertSame('Breached', $this->breach($noOvertime, 'Over Time Not Eligibile')?->status);
    }

    public function test_a_resort_without_workforce_planning_config_still_runs_the_other_rules()
    {
        DB::table('manningandbudgeting_configfiles')->where('resort_id', $this->resortId)->delete();
        $noBreak = $this->emp[2];
        $this->day($noBreak, Carbon::today()->format('Y-m-d'), '07:00', '13:00');

        $this->actingAs(ResortAdmin::where('resort_id', $this->resortId)->where('is_master_admin', 1)->first(), 'resort-admin');
        $this->assertLessThan(500, $this->get(route('people.compliance.run'))->status());
        $this->assertNotNull($this->breach($noBreak, 'Without Mandatory Break'), 'rules after the ratio check still run');
    }
}
