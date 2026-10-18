<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Saving a roster with overtime for someone not entitled to it: one breach row, readable description — not one per save. */
class RosterOvertimeComplianceTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    public function test_resaving_a_roster_does_not_duplicate_the_breach()
    {
        $this->buildDemoOrganisation();
        $hod = $this->emp('hod.housekeeping');
        $this->assertSame('no', DB::table('employees')->where('id', $hod->id)->value('entitled_overtime'));
        // The grid allows overtime (else the save is refused outright); the employee's own entitlement doesn't.
        DB::table('resort_benifit_grid')->where('resort_id', $this->resortId)->update(['overtime' => 'yes']);
        $long = DB::table('shift_settings')->insertGetId(['resort_id' => $this->resortId, 'ShiftName' => 'Long', 'StartTime' => '07:00', 'EndTime' => '19:00',
            'TotalHours' => '12:0', 'created_at' => now(), 'updated_at' => now()]);

        foreach ([3, 5] as $weeksAhead) {
            $start = now()->addWeeks($weeksAhead)->startOfWeek();
            $this->as('hr')->withoutMiddleware(VerifyCsrfToken::class)->post(route('resort.timeandattendance.StoreDutyRoster'), [
                'Emp_id' => [$hod->id], 'Shift' => $long, 'DefaultShiftTime' => 'All', 'TotalHours' => '72:00',
                'hiddenInput' => $start->format('m/d/Y') . ' - ' . $start->copy()->addDays(6)->format('m/d/Y'),
                'DayOffDates' => $start->copy()->addDays(4)->format('Y-m-d'),
            ])->assertJson(['success' => true]);
        }

        $rows = DB::table('compliances')->where('employee_id', $hod->id)->where('compliance_breached_name', 'Over Time Not Eligibile')->get();
        $this->assertCount(1, $rows, 'one breach per employee, not one per save');
        $this->assertSame($hod->resortAdmin->full_name . " ({$hod->Emp_id} - Executive Housekeeper) is not eligible for overtime.", $rows[0]->description);
    }
}
