<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Services\Wisdom\WisdomTools;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** WAI "who was absent today?": one row per person, rostered day off isn't absent, missing punches aren't absences. */
class WisdomAttendanceRegisterTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    private function register(string $date): array
    {
        $m = new \ReflectionMethod(WisdomTools::class, 'getAttendanceRegister');
        $m->setAccessible(true);
        return $m->invoke(null, $this->resortId, ['date' => $date]);
    }

    public function test_people_come_back_with_id_position_and_department_and_day_off_is_not_absent()
    {
        $this->buildDemoOrganisation();
        $date = now()->toDateString();
        $staff = Employee::where('resort_id', $this->resortId)->where('status', 'Active')->whereDate('joining_date', '<=', $date)->pluck('id')->all();
        $this->assertGreaterThan(10, count($staff));
        DB::table('parent_attendaces')->where('resort_id', $this->resortId)->whereDate('date', $date)->delete();
        DB::table('duty_roster_entries')->where('resort_id', $this->resortId)->whereDate('date', $date)->delete();
        DB::table('employees_leaves')->where('resort_id', $this->resortId)->delete();

        // most staff punched in; one is on a rostered day off; one simply didn't come
        [$dayOff, $missing] = [array_pop($staff), array_pop($staff)];
        foreach ($staff as $id) {
            DB::table('parent_attendaces')->insert(['roster_id' => 0, 'resort_id' => $this->resortId, 'Shift_id' => 0, 'Emp_id' => $id,
                'date' => $date, 'CheckingTime' => '08:00', 'Status' => 'Present', 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('duty_roster_entries')->insert(['roster_id' => 0, 'resort_id' => $this->resortId, 'Shift_id' => 0, 'Emp_id' => $dayOff,
            'date' => $date, 'Status' => 'DayOff', 'created_at' => now(), 'updated_at' => now()]);

        $r = $this->register($date);
        $this->assertSame(count($staff), $r['present_count']);
        $this->assertSame([Employee::find($missing)->Emp_id], array_column($r['absent'], 'emp_id'));
        $this->assertSame([Employee::find($dayOff)->Emp_id], array_column($r['day_off'], 'emp_id'));
        $this->assertSame(['emp_id', 'name', 'position', 'department'], array_keys($r['present'][0]));
        $this->assertNull($r['note']);
    }

    public function test_a_day_with_almost_no_punches_is_reported_as_no_punches_not_absent()
    {
        $this->buildDemoOrganisation();
        $date = now()->addDays(3)->toDateString();
        DB::table('parent_attendaces')->where('resort_id', $this->resortId)->whereDate('date', $date)->delete();

        $r = $this->register($date);
        $this->assertSame(0, $r['absent_count']);
        $this->assertGreaterThan(5, $r['no_punch_recorded_count']);
        $this->assertStringContainsString('no punches recorded yet', $r['note']);
    }
}
