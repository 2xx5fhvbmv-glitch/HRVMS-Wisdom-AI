<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\ResortAdmin;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Security audit run-3, People: H1 contact email takeover, H2 probation / exit-clearance status changes, H3 employee creation. */
class SecurityRound2PeopleTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Mail::fake();
    }

    public function test_h1_only_hr_changes_a_login_email_and_it_must_be_unique()
    {
        $target = $this->emp('gm');
        $post = fn (array $data) => $this->postJson(route('employee.update.contacts'), ['employee_id' => $target->id, 'personal_phone' => '7771234'] + $data);

        $this->as('manager');
        $post(['email' => 'attacker@example.com'])->assertForbidden();
        $this->assertNotSame('attacker@example.com', $target->resortAdmin->fresh()->email);

        $this->as('hr');
        $post(['email' => 'not-an-email'])->assertStatus(422);
        $post(['email' => $this->emp('hr')->resortAdmin->email])->assertStatus(422);   // someone else's login
        $old = $target->resortAdmin->email;
        $post(['email' => 'gm.new@demo.thewisdom.ai'])->assertOk()->assertJson(['success' => true]);
        $this->assertSame('gm.new@demo.thewisdom.ai', $target->resortAdmin->fresh()->email);
        Mail::assertSentCount(0);   // Mail::raw isn't a Mailable; check the message went to the old address instead
    }

    public function test_h2_only_hr_changes_probation_or_completes_exit_clearance()
    {
        $line = $this->emp('employee');
        $this->as('manager');
        $this->postJson(route('confirm.probation', $line->id))->assertForbidden();
        $this->postJson(route('fail.probation', $line->id))->assertForbidden();
        $this->postJson(route('extend.probation', $line->id), ['extension_date' => '01/02/2027'])->assertForbidden();
        $this->postJson(route('people.exit-clearance.markAsComplete', base64_encode(1)))->assertForbidden();
        $this->assertSame($line->status, $line->fresh()->status);

        $this->as('hr');
        $line->forceFill(['status' => 'Terminated'])->save();
        $this->postJson(route('confirm.probation', $line->id))->assertStatus(422);
        $this->assertSame('Terminated', $line->fresh()->status, 'a leaver is never set back to Active');
    }

    public function test_h3_only_hr_creates_employees_and_rank_department_position_come_from_the_vacancy()
    {
        $dept = DB::table('resort_departments')->where('resort_id', $this->resortId)->where('name', 'Kitchen')->value('id');
        $position = DB::table('resort_positions')->where('resort_id', $this->resortId)->where('dept_id', $dept)->first();
        $hrDept = DB::table('resort_departments')->where('resort_id', $this->resortId)->where('name', 'like', '%Human%')->value('id');
        $vacancy = DB::table('vacancies')->insertGetId(['Resort_id' => $this->resortId, 'department' => $dept, 'position' => $position->id,
            'division' => DB::table('resort_departments')->where('id', $dept)->value('division_id'), 'rank' => 6, 'reporting_to' => 0,
            'employee_type' => 'Full-Time', 'budgeted' => 'yes', 'required_starting_date' => now()->toDateString(), 'Total_position_required' => 3,
            'salary' => 500, 'budgeted_salary' => 500, 'propsed_salary' => 500, 'created_at' => now(), 'updated_at' => now()]);
        $form = [
            'vacancy_id' => $vacancy, 'employeeF_name' => 'Test', 'employeeL_name' => 'Hire', 'email_address' => 'test.hire@demo.thewisdom.ai',
            'gender' => 'male', 'date_birth' => '01/01/1995', 'joining_date' => now()->format('d/m/Y'), 'employment_status' => 'Full-Time',
            'basic_salary' => 500, 'basic_salary_currency' => 'USD', 'reporting_person' => $this->emp('hod.kitchen')->id,
            'marital_status' => 'Single', 'nationality' => 'Indian', 'blood_group' => 'O+', 'religion' => 'Hindu', 'passport_numb' => 'P123456',
            'nid' => 'N1', 'tin' => 'T1', 'contract_type' => 'Permanent', 'payment_mode' => 'Bank', 'emg_contact_fname' => 'E', 'emg_contact_lname' => 'C',
            'emg_contact_email' => 'e@example.com', 'emg_contact_number' => '7770000', 'emg_contact_relation' => 'Brother', 'emg_contact_nationalitys' => 'Indian',
            // tampered: try to make the hire an HR head
            'position_rank' => 3, 'department' => $hrDept, 'position' => 1, 'division' => 1,
        ];

        $this->as('manager');
        $this->post(route('people.employees.store'), $form, ['Accept' => 'application/json'])->assertForbidden();
        $this->assertNull(ResortAdmin::where('email', 'test.hire@demo.thewisdom.ai')->first());

        $this->as('hr');
        $this->post(route('people.employees.store'), $form, ['Accept' => 'application/json'])->assertOk()->assertJson(['success' => true]);
        $admin = ResortAdmin::where('email', 'test.hire@demo.thewisdom.ai')->firstOrFail();
        $employee = Employee::where('Admin_Parent_id', $admin->id)->firstOrFail();
        $this->assertSame('sub', $admin->type, 'never a full-access super account');
        $this->assertSame(1, (int) $admin->is_employee);
        $this->assertSame([6, (int) $dept, (int) $position->id], [(int) $employee->rank, (int) $employee->Dept_id, (int) $employee->Position_id]);
    }

    public function test_h3_a_login_created_without_a_type_is_an_ordinary_account()
    {
        $admin = ResortAdmin::create(['resort_id' => $this->resortId, 'first_name' => 'No', 'last_name' => 'Type', 'email' => 'no.type@demo.thewisdom.ai']);
        $this->assertSame('sub', $admin->fresh()->type);
        $this->assertSame('sub', DB::table('information_schema.columns')->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'resort_admins')->where('column_name', 'type')->value('column_default') === "'sub'" ? 'sub' : (string) DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())->where('table_name', 'resort_admins')->where('column_name', 'type')->value('column_default'));
    }

    public function test_m9_the_hr_break_alert_lists_only_this_resorts_staff()
    {
        $today = now()->toDateString();
        $punch = function (Employee $e) use ($today) {
            $pa = DB::table('parent_attendaces')->insertGetId(['resort_id' => $e->resort_id, 'Emp_id' => $e->id, 'date' => $today, 'Status' => 'Present',
                'CheckInCheckOut_Type' => 'Geofencing', 'CheckingTime' => now()->subHours(3)->format('H:i'), 'CheckingOutTime' => null, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('child_attendaces')->insert(['Parent_attd_id' => $pa, 'InTime_out' => now()->subHours(3)->format('H:i:s'), 'created_at' => now(), 'updated_at' => now()]);
        };
        $mine = $this->emp('employee');
        $elsewhere = Employee::where('resort_id', '!=', $this->resortId)->whereHas('resortAdmin')->firstOrFail();
        $punch($mine);
        $punch($elsewhere);

        \Laravel\Passport\Passport::actingAs($this->emp('hr')->resortAdmin, [], 'api');
        $json = json_encode($this->getJson('/api/timeandattendance/time-attendance-hr-dashboard')->json());
        $this->assertStringNotContainsString('"admin_id":' . $elsewhere->Admin_Parent_id . ',', $json, 'another resort\'s employee is not listed');
    }

    public function test_m2_m3_only_hr_decides_appeals_deletes_grievances_or_changes_grievance_settings()
    {
        $case = DB::table('grivance_submission_models')->insertGetId(['resort_id' => $this->resortId, 'Grivance_id' => 'GR-T1', 'Employee_id' => $this->emp('employee')->id,
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        $this->as('manager');
        $this->postJson(route('GrievanceAndDisciplinery.Appeals.Decide', base64_encode(1)), ['decision' => 'Upheld', 'final_status' => 'Resolved'])->assertForbidden();
        $this->deleteJson(route('GrievanceAndDisciplinery.grivance.DeleteGrivance', base64_encode($case)))->assertForbidden();
        $this->postJson(route('GrievanceAndDisciplinery.grivance.RequestForStatement'), ['id' => 'GR-T1'])->assertForbidden();
        $this->postJson(route('GrievanceAndDisciplinery.config.GrievanceCategoryStore'), ['Category_Name' => 'x'])->assertForbidden();
        $this->assertNull(DB::table('grivance_submission_models')->where('id', $case)->value('deleted_at'));

        $this->as('hr');
        $this->deleteJson(route('GrievanceAndDisciplinery.grivance.DeleteGrivance', base64_encode($case)))->assertOk();
        $this->assertNotNull(DB::table('grivance_submission_models')->where('id', $case)->value('deleted_at'), 'kept, marked deleted');
        $this->assertNull(\App\Models\GrivanceSubmissionModel::find($case), 'and gone from every list');
    }

    public function test_m4_m5_people_settings_and_employee_records_are_hr_only_and_nobody_approves_their_own_change()
    {
        $line = $this->emp('employee');
        $this->as('manager');
        $this->postJson(route('people.increment-types.store'), ['name' => 'x'])->assertForbidden();
        $this->postJson(route('employee.update.personal'), ['employee_id' => $line->id, 'first_name' => 'Hacked'])->assertForbidden();
        $this->assertNotSame('Hacked', $line->resortAdmin->fresh()->first_name);

        // An HR employee's own info-update request: HR can't approve their own; a decided request can't be re-decided.
        $hr = $this->emp('hr');
        $own = DB::table('employee_info_update_request')->insertGetId(['resort_id' => $this->resortId, 'employee_id' => $hr->id, 'status' => 'Pending',
            'info_payload' => json_encode(['first_name' => 'Self']), 'created_at' => now(), 'updated_at' => now()]);
        $done = DB::table('employee_info_update_request')->insertGetId(['resort_id' => $this->resortId, 'employee_id' => $line->id, 'status' => 'Rejected',
            'info_payload' => json_encode(['first_name' => 'Late']), 'created_at' => now(), 'updated_at' => now()]);
        $this->as('hr');
        $this->postJson(route('people.info-update.status-change'), ['id' => $own, 'status' => 'approve'])->assertForbidden();
        $this->postJson(route('people.info-update.status-change'), ['id' => $done, 'status' => 'approve'])->assertStatus(422);
        $this->assertSame('Pending', DB::table('employee_info_update_request')->where('id', $own)->value('status'));
        $this->assertNotSame('Late', $line->resortAdmin->fresh()->first_name);
    }

    public function test_m1_a_notification_for_one_person_goes_on_their_own_channel_only()
    {
        $me = $this->emp('employee');
        $personal = new \App\Events\ResortNotificationEvent(['resortid' => $this->resortId, 'type' => 10, 'sendto' => $me->id, 'html' => 'x']);
        $this->assertSame(["private-resort-notifications.{$this->resortId}.employee.{$me->id}"], array_map(fn ($c) => $c->name, $personal->broadcastOn()));
        $shared = new \App\Events\ResortNotificationEvent(['resortid' => $this->resortId, 'type' => 2, 'html' => 'x']);
        $this->assertSame(["private-resort-notifications.{$this->resortId}"], array_map(fn ($c) => $c->name, $shared->broadcastOn()));

        $auth = collect(\Illuminate\Support\Facades\Broadcast::driver()->getChannels())->first(fn ($cb, $pattern) => $pattern === 'resort-notifications.{resortId}.employee.{employeeId}');
        $this->assertNotNull($auth);
        $this->assertTrue((bool) $auth($me->resortAdmin, $this->resortId, $me->id));
        $this->assertFalse((bool) $auth($this->emp('hr')->resortAdmin, $this->resortId, $me->id), 'a colleague cannot listen in');
    }

    public function test_mobile_staff_see_only_their_own_island_passes_and_cannot_rehouse_people()
    {
        $line = $this->emp('employee');
        $other = $this->emp('supervisor');
        foreach ([$line, $other] as $e) {
            DB::table('employee_travel_passes')->insert(['resort_id' => $this->resortId, 'employee_id' => $e->id, 'status' => 'Approved',
                'departure_date' => now()->toDateString(), 'arrival_date' => now()->addDay()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
        }
        \Laravel\Passport\Passport::actingAs($line->resortAdmin, [], 'api');
        $rows = collect($this->postJson('/api/boarding/emp-leaving-arriving', ['filter' => 'today'])->assertOk()->json('data') ?? [])->flatten(1);
        $json = json_encode($this->postJson('/api/boarding/emp-leaving-arriving', ['filter' => 'today'])->json());
        $this->assertStringNotContainsString('"employee_id":' . $other->id . ',', $json, "a line worker doesn't see a colleague's travel");

        $this->postJson('/api/accommodation/assign-accommodation-to-emp', ['emp_id' => $other->id])->assertForbidden();
        $this->postJson('/api/accommodation/move-accommodation-for-emp', ['emp_id' => $other->id])->assertForbidden();
    }

    public function test_section5_writes_are_refused_for_a_non_hr_manager()
    {
        $this->as('manager');
        $routes = [
            ['people.salary-increment.update', ['id' => 1]], ['people.salary-increment.bulk-update-status', []], ['resort.benefitgradelevel.store', []],
            ['incident.assign', []], ['incident.investigation.approve', []], ['promotion.confirm', []], ['promotion.send-letter', []],
            ['people.announcements.store', []], ['manning.divisions.store', []], ['people.onboarding.convertApplicant', []],
            ['people.exit-clearance.assignmentSubmitDepartment', []], ['resort.vacancies.markRecruited', ['id' => 1]],
            ['people.advance-salary.payment-reschedule-store', []],
        ];
        foreach ($routes as [$name, $params]) {
            $this->assertSame(403, $this->postJson(route($name, $params), ['data' => [['payrollAdvanceId' => 1]], 'ids' => [1]])->getStatusCode(), $name);
        }

        // Duty-roster geofence: HOD / HR only for their own people — a colleague's roster in another department is refused.
        $other = $this->emp('hod.kitchen');
        $roster = DB::table('duty_rosters')->insertGetId(['resort_id' => $this->resortId, 'Emp_id' => $other->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->as('employee');
        $this->postJson(route('resort.timeandattendance.UpdateDutyRosterGeofence'), ['roster_id' => $roster, 'geofence_zone_ids' => []])->assertForbidden();
    }

    public function test_a_browser_session_login_reaches_controllers_that_read_the_user_in_their_constructor()
    {
        // Signed in through the session cookie only, like a browser (actingAs() hides this): the session has to be
        // started before Laravel builds the controller, or Auth::guard()->user() in __construct is null and the page 500s.
        $hr = ResortAdmin::where('email', 'hr@demo.thewisdom.ai')->firstOrFail();
        $this->app['auth']->forgetGuards();
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            $route->flushController();
        }
        // A real stored session + encrypted cookie (withSession() fills the session before any middleware, which hides it).
        config(['session.driver' => 'file']);
        $this->app['session']->forgetDrivers();
        $store = $this->app['session']->driver();
        $store->start();
        $store->put(\Illuminate\Support\Facades\Auth::guard('resort-admin')->getName(), $hr->id);
        $store->save();
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        $this->app['auth']->forgetGuards();
        try {
            $this->withCookie(config('session.cookie'), $store->getId())
                ->get('/resort/master/hr-dashboard')
                ->assertOk();
        } finally {
            $store->getHandler()->destroy($store->getId());
        }
    }
}
