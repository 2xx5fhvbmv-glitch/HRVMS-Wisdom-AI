<?php

namespace Tests\Feature;

use App\Helpers\StorageHelper;
use App\Models\ResortAdmin;
use App\Support\Demo\Demo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Demo ENV slice 3 (rosters + attendance) and the T&A fixes found while building it,
 * driven through the real routes against the seeded demo resort.
 */
class DemoTimeAndAttendanceTest extends TestCase
{
    use DatabaseTransactions;

    private int $resortId;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'demo.enabled' => true, 'demo.password' => 'Demo#Pass2026', 'demo.resort_code' => 'DEMOTEST99',
            'demo.resort_email' => 'resort@demo.thewisdom.ai', 'demo.prefix' => 'DMT']);
        Demo::refresh();
        Storage::fake(config('settings.storage_driver'));
        (new \ReflectionClass(StorageHelper::class))->setStaticPropertyValue('cachedDisk', null);
        Mail::fake();
        $this->artisan('demo:reset', ['--force' => true])->assertSuccessful();
        Demo::refresh();
        $this->resortId = Demo::resortId();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function minutes(string $hm): int
    {
        [$h, $m] = array_pad(explode(':', $hm), 2, 0);
        return (int) $h * 60 + (int) $m;
    }

    private function signInHr(): ResortAdmin
    {
        $this->post(route('resort.login'), ['email' => 'hr@demo.thewisdom.ai', 'password' => 'Demo#Pass2026'])->assertOk();
        return ResortAdmin::where('email', 'hr@demo.thewisdom.ai')->first();
    }

    public function test_seeded_attendance_has_the_shapes_the_screens_read()
    {
        $pa = fn () => DB::table('parent_attendaces')->where('resort_id', $this->resortId);
        $this->assertGreaterThan(10000, $pa()->count());
        // Register inner-joins the shift and the dashboard joins the roster: neither may be missing.
        $this->assertSame(0, $pa()->whereNotIn('Shift_id', DB::table('shift_settings')->where('resort_id', $this->resortId)->select('id'))->count());
        $this->assertSame(0, $pa()->whereNotIn('roster_id', DB::table('duty_rosters')->where('resort_id', $this->resortId)->select('id'))->count());
        // Nobody works a day longer than 24h; every punch has a parseable location (leave days are marked, not punched).
        $this->assertLessThan(24 * 60, $pa()->pluck('DayWiseTotalHours')->map(fn ($h) => $this->minutes($h))->max());
        $this->assertSame(0, DB::table('child_attendaces as c')->join('parent_attendaces as p', 'p.id', '=', 'c.Parent_attd_id')
            ->where('p.resort_id', $this->resortId)->where('p.Status', '!=', 'FullDayLeave')->where('c.InTime_Location', 'not like', '%center=%')->count());
        // Open breaks look like the API's: blank end, blank total.
        $this->assertSame(0, DB::table('break_attendaces as b')->join('parent_attendaces as p', 'p.id', '=', 'b.Parent_attd_id')
            ->where('p.resort_id', $this->resortId)->where('b.Break_InTime', '')->where('b.Total_Break_Time', '!=', '')->count());
    }

    public function test_hr_fills_a_missing_checkout_on_a_mobile_punch()
    {
        $hr = $this->signInHr();
        $day = DB::table('parent_attendaces as pa')->join('shift_settings as s', 's.id', '=', 'pa.Shift_id')
            ->where('pa.resort_id', $this->resortId)->where('s.ShiftName', 'Morning Shift')->where('pa.Status', 'Present')
            ->where('pa.date', '<', now()->subDays(20)->toDateString())->whereNotNull('pa.CheckingOutTime')->first(['pa.*']);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $day->CheckingTime, 'mobile punches are H:i:s');
        DB::table('employee_overtimes')->where('parent_attendance_id', $day->id)->delete();
        DB::table('parent_attendaces')->where('id', $day->id)
            ->update(['CheckingOutTime' => null, 'DayWiseTotalHours' => '00:00', 'OverTime' => null, 'OTStatus' => null, 'OTApproved_By' => null]);
        DB::table('child_attendaces')->where('Parent_attd_id', $day->id)->update(['OutTime_out' => '00:00']);

        $this->post(route('resort.timeandattendance.CheckoutTimeMissing'), ['AttdanceId' => $day->id, 'CheckoutTime' => '13:30', 'action' => 'approve'])
            ->assertJson(['success' => true]);

        $breaks = DB::table('break_attendaces')->where('Parent_attd_id', $day->id)->pluck('Total_Break_Time')->sum(fn ($t) => $t ? $this->minutes($t) : 0);
        $worked = (int) Carbon::parse("{$day->date} {$day->CheckingTime}")->diffInMinutes(Carbon::parse("{$day->date} 13:30")) - $breaks;
        $row = DB::table('parent_attendaces')->find($day->id);
        $this->assertSame('13:30', $row->CheckingOutTime);
        $this->assertSame(sprintf('%02d:%02d', intdiv($worked, 60), $worked % 60), $row->DayWiseTotalHours);
        // Overtime is what's worked beyond the 8h shift — not the whole day.
        $this->assertSame(sprintf('%02d:%02d', intdiv($worked - 480, 60), ($worked - 480) % 60), $row->OverTime);
        $this->assertSame('Approved', $row->OTStatus);
        $this->assertSame($hr->id, (int) $row->OTApproved_By);
        $this->assertSame('13:30', DB::table('child_attendaces')->where('Parent_attd_id', $day->id)->value('OutTime_out'));
    }

    public function test_overtime_approved_on_the_overtime_screen_reaches_payroll()
    {
        $hr = $this->signInHr();
        $ot = DB::table('employee_overtimes')->where('resort_id', $this->resortId)->where('status', 'pending')->whereNotNull('parent_attendance_id')->first();
        $this->assertNull(DB::table('parent_attendaces')->where('id', $ot->parent_attendance_id)->value('OverTime'));

        $this->post(route('resort.timeandattendance.UpdateOvertimeStatus'), ['entries' => [['id' => $ot->id, 'status' => 'approved']]])
            ->assertJson(['success' => true]);

        $row = DB::table('parent_attendaces')->find($ot->parent_attendance_id);
        $this->assertSame($ot->total_time, $row->OverTime);
        $this->assertSame('Approved', $row->OTStatus); // what PayrollController pays on
        $this->assertSame($hr->id, (int) $row->OTApproved_By);

        $this->post(route('resort.timeandattendance.UpdateOvertimeStatus'), ['entries' => [['id' => $ot->id, 'status' => 'rejected']]])
            ->assertJson(['success' => true]);
        $this->assertSame('Rejected', DB::table('parent_attendaces')->where('id', $ot->parent_attendance_id)->value('OTStatus'));
    }

    public function test_a_new_roster_stores_one_days_hours_not_the_forms_total()
    {
        $this->signInHr();
        $emp = DB::table('employees as e')->join('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->where('e.resort_id', $this->resortId)->where('p.position_title', 'Room Attendant')->value('e.id');
        $morning = DB::table('shift_settings')->where('resort_id', $this->resortId)->where('ShiftName', 'Morning Shift')->first();
        $start = now()->addWeeks(3)->startOfWeek();
        $end = $start->copy()->addDays(6);

        $this->post(route('resort.timeandattendance.StoreDutyRoster'), [
            'Emp_id' => [$emp], 'Shift' => $morning->id, 'DefaultShiftTime' => 'All',
            'TotalHours' => '48:00', // the form's summary: 8h × 6 working days
            'hiddenInput' => $start->format('m/d/Y') . ' - ' . $end->format('m/d/Y'),
            'DayOffDates' => $start->copy()->addDays(2)->format('Y-m-d'),
        ])->assertJson(['success' => true]);

        $days = DB::table('duty_roster_entries')->where('Emp_id', $emp)->whereBetween('date', [$start->toDateString(), $end->toDateString()])->get();
        $this->assertCount(7, $days);
        foreach ($days->where('Status', 'Present') as $d) {
            $this->assertSame('08:00', $d->DayWiseTotalHours);
            $this->assertSame('12:00', $d->CheckingOutTime);
            $this->assertSame('00:00', $d->OverTime, 'an 8h shift on a 48h/6-day grid has no overtime');
        }
    }

    public function test_mobile_check_in_keeps_the_geofence_result_of_todays_roster()
    {
        $admin = ResortAdmin::where('email', 'employee@demo.thewisdom.ai')->first();
        $empId = DB::table('employees')->where('Admin_Parent_id', $admin->id)->value('id');
        $today = now()->toDateString();
        $todayEntry = DB::table('duty_roster_entries')->where('Emp_id', $empId)->where('date', $today)->first();
        DB::table('duty_roster_entries')->where('id', $todayEntry->id)->update(['Status' => 'Present']);
        // Only today's roster has the island zone; the oldest one points at nothing.
        DB::table('duty_rosters')->where('Emp_id', $empId)->where('id', '!=', $todayEntry->roster_id)->update(['geofence_zone_id' => '["999999"]']);
        $paIds = DB::table('parent_attendaces')->where('Emp_id', $empId)->where('date', $today)->pluck('id');
        DB::table('child_attendaces')->whereIn('Parent_attd_id', $paIds)->delete();
        DB::table('break_attendaces')->whereIn('Parent_attd_id', $paIds)->delete();
        DB::table('employee_overtimes')->whereIn('parent_attendance_id', $paIds)->delete();
        DB::table('parent_attendaces')->whereIn('id', $paIds)->delete();

        Passport::actingAs($admin, [], 'api');
        $this->postJson('/api/timeandattendance/manual-check-in', [
            'current_time' => now()->format('H:i'), 'current_date' => $today, 'flag' => 'Geofencing',
            'in_time_location' => 'https://www.google.com/maps/embed/v1/view?center=4.1745,73.5076&zoom=12',
        ])->assertOk();

        $child = DB::table('child_attendaces as c')->join('parent_attendaces as p', 'p.id', '=', 'c.Parent_attd_id')
            ->where('p.Emp_id', $empId)->where('p.date', $today)->first(['c.*']);
        $this->assertEquals(4.1745, (float) $child->InTime_Latitude);
        $this->assertEquals(73.5076, (float) $child->InTime_Longitude);
        $this->assertSame(1, (int) $child->InTime_WithinGeofence);
        $this->assertSame('Resort Island', $child->InTime_GeofenceName);
    }
}
