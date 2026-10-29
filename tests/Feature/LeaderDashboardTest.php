<?php

namespace Tests\Feature;

use App\Services\LeaderDashboard;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Backend of the leader master dashboard: scope, $leader, roster, inbox decisions, WAI label/source. */
class LeaderDashboardTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    private int $shiftId;
    private int $rosterId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        DB::table('employees')->update(['device_token' => null]); // push is raw curl — no device gets one from a test
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->shiftId = DB::table('shift_settings')->insertGetId(['resort_id' => $this->resortId, 'ShiftName' => 'Morning', 'StartTime' => '00:00',
            'EndTime' => '08:00', 'TotalHours' => '8:0', 'created_at' => now(), 'updated_at' => now()]);
        $this->rosterId = DB::table('duty_rosters')->insertGetId(['resort_id' => $this->resortId, 'Shift_id' => $this->shiftId,
            'Emp_id' => $this->emp('hod.housekeeping')->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function deptOf(string $login): int
    {
        return (int) $this->emp($login)->Dept_id;
    }

    private function housekeepers(int $n): array
    {
        return DB::table('employees')->where('resort_id', $this->resortId)->where('Dept_id', $this->deptOf('hod.housekeeping'))
            ->where('rank', 6)->limit($n)->pluck('id')->all();
    }

    private function forUser(string $login): LeaderDashboard
    {
        $this->as($login);
        return LeaderDashboard::forCurrentUser();
    }

    public function test_hod_is_scoped_to_their_department_and_gm_to_the_resort()
    {
        $hod = $this->forUser('hod.housekeeping');
        $deptStaff = DB::table('employees')->where('resort_id', $this->resortId)->where('Dept_id', $this->deptOf('hod.housekeeping'))->where('status', 'Active')->count();
        $this->assertCount($deptStaff, $hod->employeeIds());
        $this->assertCount(DB::table('employees')->where('resort_id', $this->resortId)->where('status', 'Active')->count(), $this->forUser('gm')->employeeIds());

        $leader = $this->forUser('excom')->leader();
        $this->assertSame(['absent_delta', 'health', 'requests', 'events'], array_keys($leader));
        $this->assertSame(['appraisals_pending', 'in_pip', 'in_pdp', 'overtime_hours', 'overtime_vs_last', 'open_grievances', 'disciplinary_cases',
            'turnover_annual', 'turnover_quarter'], array_keys($leader['health']));
    }

    public function test_gm_gets_the_cards_gm_dashboard_does_not_compute_and_the_composer_fills_only_missing_keys()
    {
        $dir = sys_get_temp_dir() . '/ldview' . uniqid();
        mkdir("$dir/resorts/master-dashboard", 0777, true);
        file_put_contents("$dir/resorts/master-dashboard/leaderdashboard.blade.php",
            '{{ json_encode(["leader" => array_keys($leader), "resign" => $pendingResignationsForHod instanceof \Illuminate\Support\Collection, "ot" => $totalOverallWorkingHours, "checkins" => is_iterable($monthlyCheckinPerformance), "kept" => $total_employees]) }}');
        app('view')->getFinder()->prependLocation($dir); app('view')->getFinder()->flush(); // before the real view, which also exists

        $this->as('gm');
        view()->share('errors', new \Illuminate\Support\ViewErrorBag); // a web request always has it (ShareErrorsFromSession)
        $html = view('resorts.master-dashboard.leaderdashboard', ['total_employees' => 77, 'totalOverallWorkingHours' => 5])->render();
        $data = json_decode(html_entity_decode($html), true);
        $this->assertSame(['absent_delta', 'health', 'requests', 'events'], $data['leader']);
        $this->assertTrue($data['resign'] && $data['checkins']);
        $this->assertSame(5, $data['ot'], "the controller's own value wins");
        $this->assertSame(77, $data['kept']);
        array_map('unlink', glob("$dir/resorts/master-dashboard/*")); rmdir("$dir/resorts/master-dashboard"); rmdir("$dir/resorts"); rmdir($dir);
    }

    public function test_inbox_lists_leave_and_overtime_for_this_approver_and_decisions_go_through_the_modules()
    {
        [$a, $b] = $this->housekeepers(2);
        $hodId = $this->emp('hod.housekeeping')->id;
        $annual = DB::table('leave_categories')->where('resort_id', $this->resortId)->where('leave_type', 'Annual Leave')->value('id');
        $leaveId = DB::table('employees_leaves')->insertGetId(['resort_id' => $this->resortId, 'emp_id' => $a, 'leave_category_id' => $annual,
            'from_date' => now()->addDays(10)->toDateString(), 'to_date' => now()->addDays(11)->toDateString(), 'total_days' => 2, 'duration' => 'Full Day',
            'reason' => 'Family', 'status' => 'Pending', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('employees_leaves_status')->insert(['leave_request_id' => $leaveId, 'approver_id' => $hodId, 'approver_rank' => 'HOD', 'status' => 'Pending',
            'approved_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $otId = DB::table('employee_overtimes')->insertGetId(['resort_id' => $this->resortId, 'Emp_id' => $b, 'Shift_id' => $this->shiftId, 'roster_id' => $this->rosterId,
            'date' => now()->subDay()->toDateString(), 'start_time' => '16:00', 'end_time' => '18:00', 'total_time' => '02:00', 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now()]);
        $otherDeptOt = DB::table('employee_overtimes')->insertGetId(['resort_id' => $this->resortId, 'Emp_id' => $this->emp('hod.fnb')->id, 'Shift_id' => $this->shiftId,
            'roster_id' => $this->rosterId, 'date' => now()->subDay()->toDateString(), 'start_time' => '16:00', 'end_time' => '18:00', 'total_time' => '02:00',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);

        $requests = collect($this->forUser('hod.housekeeping')->leader()['requests']);
        $leave = $requests->firstWhere('id', $leaveId);
        $ot = $requests->firstWhere('id', $otId);
        $this->assertNotNull($leave);
        $this->assertNotNull($ot);
        $this->assertNull($requests->firstWhere('id', $otherDeptOt), 'another department\'s overtime is not listed');
        $this->assertArrayNotHasKey('undo', $leave, 'no fake undo');

        $this->as('hod.housekeeping')->postJson($leave['ok'], ['id' => $leaveId])->assertOk()->assertJson(['success' => true]);
        $this->assertSame('Approved', DB::table('employees_leaves_status')->where('leave_request_id', $leaveId)->where('approver_id', $hodId)->value('status'));
        $this->as('hod.housekeeping')->postJson($ot['no'], ['id' => $otId, 'reason' => 'Not needed'])->assertOk()->assertJson(['success' => true]);
        $this->assertSame('rejected', DB::table('employee_overtimes')->where('id', $otId)->value('status'));
        $this->as('hod.housekeeping')->postJson(route('resort.master.leader_request', ['type' => 'overtime', 'decision' => 'approve']), ['id' => $otherDeptOt])
            ->assertStatus(403)->assertJson(['success' => false]);
        $this->assertSame('pending', DB::table('employee_overtimes')->where('id', $otherDeptOt)->value('status'));
    }

    public function test_roster_returns_scoped_rows_with_in_miss_leave_and_not_started()
    {
        Carbon::setTestNow(Carbon::today()->setTime(10, 0));
        try {
            [$in, $miss, $lv, $due] = $this->housekeepers(4);
            $late = DB::table('shift_settings')->insertGetId(['resort_id' => $this->resortId, 'ShiftName' => 'Evening', 'StartTime' => '16:00',
                'EndTime' => '00:00', 'TotalHours' => '8:0', 'created_at' => now(), 'updated_at' => now()]);
            foreach ([$in => $this->shiftId, $miss => $this->shiftId, $lv => $this->shiftId, $due => $late] as $emp => $shift) {
                DB::table('duty_roster_entries')->insert(['roster_id' => $this->rosterId, 'resort_id' => $this->resortId, 'Shift_id' => $shift, 'Emp_id' => $emp,
                    'date' => Carbon::today()->toDateString(), 'Status' => 'Present', 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('parent_attendaces')->insert(['roster_id' => $this->rosterId, 'resort_id' => $this->resortId, 'Shift_id' => $this->shiftId, 'Emp_id' => $in,
                'date' => Carbon::today()->toDateString(), 'Status' => 'Present', 'CheckingTime' => '00:05:00', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('employees_leaves')->insert(['resort_id' => $this->resortId, 'emp_id' => $lv,
                'leave_category_id' => DB::table('leave_categories')->where('resort_id', $this->resortId)->value('id'), 'from_date' => Carbon::today()->toDateString(),
                'to_date' => Carbon::today()->toDateString(), 'total_days' => 1, 'duration' => 'Full Day', 'reason' => 'x', 'status' => 'Approved',
                'created_at' => now(), 'updated_at' => now()]);
            // Someone from another department rostered today: not in a housekeeping HOD's roster.
            DB::table('duty_roster_entries')->insert(['roster_id' => $this->rosterId, 'resort_id' => $this->resortId, 'Shift_id' => $this->shiftId,
                'Emp_id' => $this->emp('hod.fnb')->id, 'date' => Carbon::today()->toDateString(), 'Status' => 'Present', 'created_at' => now(), 'updated_at' => now()]);

            $rows = collect($this->as('hod.housekeeping')->getJson(route('resort.master.leader_roster', ['date' => Carbon::today()->toDateString()]))
                ->assertOk()->json('rows'));
            $name = fn ($id) => DB::table('employees as e')->join('resort_admins as a', 'a.id', '=', 'e.Admin_Parent_id')->where('e.id', $id)
                ->value(DB::raw("TRIM(CONCAT(a.first_name, ' ', a.last_name))"));
            $state = fn ($id) => $rows->firstWhere('name', $name($id))['state'] ?? null;
            $this->assertSame(['in', 'miss', 'lv', 'due'], [$state($in), $state($miss), $state($lv), $state($due)]);
            $this->assertNull($rows->firstWhere('name', $name($this->emp('hod.fnb')->id)), 'scoped to the department');
            $evening = $rows->firstWhere('name', $name($due));
            $this->assertSame([16.0, 24.0], [(float) $evening['start'], (float) $evening['end']], 'overnight end clamps to 24');
            $this->assertStringContainsString('Checked in 00:05', $rows->firstWhere('name', $name($in))['tip']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_wai_answer_carries_label_and_source_only_for_a_known_law_source()
    {
        config(['services.openrouter.key' => 'test-key', 'services.openrouter.base_url' => 'https://openrouter.test/api/v1']);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['openrouter.test/*' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => "Overtime is paid at 1.25x.\n[[topic: overtime | source: employment_act]]"]]], 'usage' => []])
            ->push(['choices' => [['message' => ['content' => "Some answer\n[[topic: leave | source: company_handbook]]"]]], 'usage' => []])]);

        $res = $this->as('hod.housekeeping')->postJson(route('resort.wisdom.chat'), ['message' => 'How is overtime paid?'])->assertOk();
        $res->assertJson(['reply' => 'Overtime is paid at 1.25x.', 'label' => 'Employment law · overtime',
            'source' => 'Maldives Employment Act (Law No. 2/2008, as amended)']);
        $this->assertStringNotContainsString('[[', json_encode(session('wisdom_ai_history')), 'the tag never reaches the stored conversation');

        $res = $this->as('hod.housekeeping')->postJson(route('resort.wisdom.chat'), ['message' => 'Leave?'])->assertOk();
        $this->assertSame('Some answer', $res->json('reply'));
        $this->assertNull($res->json('label'), 'a source the prompt does not contain is dropped');
    }
}
