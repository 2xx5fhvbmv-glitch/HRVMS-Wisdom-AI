<?php

namespace Tests\Feature;

use App\Helpers\Common;
use App\Models\Employee;
use App\Models\ResortAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\Passport;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Fixes from the security gap check against the Vonzuu checklist (2026-11-19). */
class SecurityGapFixesTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
    }

    private function app_as(string $login): ResortAdmin
    {
        $this->app['auth']->forgetGuards();
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            $route->flushController();
        }
        $admin = ResortAdmin::where('email', "{$login}@demo.thewisdom.ai")->firstOrFail();
        Passport::actingAs($admin, [], 'api');
        return $admin;
    }

    public function test_a_shop_purchase_can_only_be_answered_while_waiting_for_consent()
    {
        $emp = $this->emp('employee');
        $id = DB::table('payments')->insertGetId(['shopkeeper_id' => 1, 'order_id' => 'ORD-T1', 'emp_id' => $emp->id, 'purchased_date' => now()->toDateString(),
            'product_id' => 1, 'quantity' => 1, 'price' => 50, 'status' => 'Consented', 'created_at' => now(), 'updated_at' => now()]);
        $this->app_as('employee');
        $this->postJson('api/shop/consent-request-handle', ['consent_request_id' => $id, 'status' => 'Rejected'])->assertStatus(422);
        $this->assertSame('Consented', DB::table('payments')->where('id', $id)->value('status'));

        DB::table('payments')->where('id', $id)->update(['status' => 'Pending Consent']);
        $this->postJson('api/shop/consent-request-handle', ['consent_request_id' => $id, 'status' => 'Approved'])->assertOk();
        $this->assertSame('Consented', DB::table('payments')->where('id', $id)->value('status'));
    }

    public function test_salary_advance_guarantor_and_approver_rules()
    {
        $emp = $this->emp('employee');
        $guarantor = $this->emp('supervisor');

        // not your own guarantor; amount must be a positive number
        $this->app_as('employee');
        $base = ['request_type' => 'Salary Advance', 'priority' => 'High', 'request_date' => now()->toDateString(), 'purpose' => 'x', 'currency' => 'MVR'];
        $this->postJson('api/request/request-store', $base + ['guarantor_id' => [$emp->id], 'request_amount' => 100])->assertStatus(400);
        $this->postJson('api/request/request-store', $base + ['guarantor_id' => [$guarantor->id], 'request_amount' => -5])->assertStatus(400);

        // a guarantor answers once
        $advanceId = DB::table('payroll_advance')->insertGetId(['employee_id' => $emp->id, 'resort_id' => $this->resortId, 'request_type' => 'Salary Advance',
            'request_date' => now()->toDateString(), 'pourpose' => 'x', 'request_amount' => 100, 'created_at' => now(), 'updated_at' => now()]);
        $gId = DB::table('payroll_advance_guarantor')->insertGetId(['payroll_advance_id' => $advanceId, 'guarantor_id' => $guarantor->id, 'status' => 'Pending',
            'created_at' => now(), 'updated_at' => now()]);
        $this->app_as('supervisor');
        $handle = fn ($st) => $this->postJson('api/request/guarantor-request-handle', ['payroll_advance_id' => $advanceId, 'guarantor_request_id' => $gId, 'status' => $st]);
        $handle('Approved')->assertOk();
        $handle('Rejected')->assertStatus(422);
        $this->assertSame('Approved', DB::table('payroll_advance_guarantor')->where('id', $gId)->value('status'));

        // HR never approves their own request
        $hr = $this->emp('hr');
        $own = DB::table('payroll_advance')->insertGetId(['employee_id' => $hr->id, 'resort_id' => $this->resortId, 'request_type' => 'Salary Advance',
            'request_date' => now()->toDateString(), 'pourpose' => 'x', 'request_amount' => 100, 'created_at' => now(), 'updated_at' => now()]);
        $this->as('hr')->postJson('/resort/people/advance-salary/update-status', ['advance_salary_id' => $own, 'action_by' => 'hr', 'status' => 'Approved'])
            ->assertStatus(403);
        $this->assertSame('Pending', DB::table('payroll_advance')->where('id', $own)->value('hr_status'));
    }

    public function test_the_web_profile_cannot_change_the_login_email()
    {
        $before = ResortAdmin::where('email', 'hr@demo.thewisdom.ai')->value('email');
        $this->as('hr')->post('/resort/update/user/profile', ['first_name' => 'Fatima', 'last_name' => 'N', 'email' => 'attacker@evil.test']);
        $this->assertTrue(ResortAdmin::where('email', $before)->exists());
        $this->assertFalse(ResortAdmin::where('email', 'attacker@evil.test')->exists());
    }

    public function test_web_change_password_counts_wrong_guesses()
    {
        $this->as('hr');
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/resort/change/password', ['old_password' => "wrong-$i", 'password' => 'NewPassw0rd!xyz', 'confirmpassword' => 'NewPassw0rd!xyz'])
                ->assertJson(['success' => false]);
        }
        $this->assertTrue(Common::isAccountLocked(ResortAdmin::where('email', 'hr@demo.thewisdom.ai')->first()));
    }

    public function test_super_admin_password_change_needs_a_strong_confirmed_password()
    {
        DB::table('admins')->insert(['first_name' => 'T', 'last_name' => 'T', 'email' => 'pw-test@wisdom.test', 'password' => Hash::make('Secret#123'),
            'status' => 'active', 'type' => 'super', 'allow_login' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $admin = \App\Models\Admin::where('email', 'pw-test@wisdom.test')->first();
        $this->actingAs($admin, 'admin');
        $url = route('admin.changePassword');

        $this->postJson($url, ['old_pass' => 'Secret#123'])->assertJson(['success' => false]);                                   // empty new password
        $this->postJson($url, ['old_pass' => 'Secret#123', 'password' => 'short', 'password_confirm' => 'short'])->assertJson(['success' => false]);
        $this->postJson($url, ['old_pass' => 'Secret#123', 'password' => 'Str0ngPassw0rdX', 'password_confirm' => 'Different1X'])->assertJson(['success' => false]);
        $this->assertTrue(Hash::check('Secret#123', $admin->fresh()->password));

        for ($i = 0; $i < 5; $i++) {
            $this->postJson($url, ['old_pass' => "wrong-$i", 'password' => 'Str0ngPassw0rdX', 'password_confirm' => 'Str0ngPassw0rdX']);
        }
        $this->assertTrue(Common::isAccountLocked($admin->fresh()));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.checkPassword'));   // the password oracle is gone
    }

    public function test_a_hod_only_handles_housekeeping_requests_of_their_own_department()
    {
        $hod = $this->emp('hod.kitchen');
        $other = Employee::where('resort_id', $this->resortId)->where('Dept_id', '!=', $hod->Dept_id)->whereNotNull('Dept_id')->firstOrFail();
        $this->assertFalse(Common::isHousekeepingHodXcom($hod));
        $service = DB::table('housekeeping_service_catalog')->insertGetId(['resort_id' => $this->resortId, 'name' => 'Linen', 'created_at' => now(), 'updated_at' => now()]);
        $mk = fn ($empId) => DB::table('housekeeping_requests')->insertGetId(['resort_id' => $this->resortId, 'request_id' => 'HK-' . $empId, 'batch_id' => 'B-' . $empId,
            'housekeeping_service_id' => $service, 'raised_by' => $empId, 'employee_id' => $empId, 'status' => 'Pending', 'created_at' => now(), 'updated_at' => now()]);
        $foreign = $mk($other->id);
        $mine = $mk(Common::getSubordinates($hod->id)[0]);

        $this->app_as('hod.kitchen');
        $this->postJson('api/accommodation/housekeeping-requests/update-status', ['id' => $foreign, 'status' => 'Completed'])->assertStatus(403);
        $this->assertSame('Pending', DB::table('housekeeping_requests')->where('id', $foreign)->value('status'));
        $this->getJson('api/accommodation/housekeeping-requests/view/' . base64_encode((string) $foreign))->assertStatus(403);
        $this->postJson('api/accommodation/housekeeping-requests/update-status', ['id' => $mine, 'status' => 'Accepted'])->assertOk();
    }

    public function test_an_overtime_decision_is_final()
    {
        $worker = Common::getSubordinates($this->emp('hod.kitchen')->id)[0];
        $id = DB::table('parent_attendaces')->insertGetId(['roster_id' => 0, 'resort_id' => $this->resortId, 'Shift_id' => 0, 'Emp_id' => $worker,
            'date' => now()->toDateString(), 'CheckingTime' => '08:00', 'CheckingOutTime' => '19:00', 'OverTime' => '02:00', 'Status' => 'present',
            'OTStatus' => 'Approved', 'created_at' => now(), 'updated_at' => now()]);
        $this->app_as('hr');
        $this->postJson('api/timeandattendance/approve-reject-ot', ['attendance_id' => $id, 'ot_status' => 'Rejected', 'note' => 'x'])->assertStatus(422);
        $this->assertSame('Approved', DB::table('parent_attendaces')->where('id', $id)->value('OTStatus'));
    }

    public function test_uploads_refuse_html_and_svg()
    {
        $this->app_as('employee');
        $this->post('api/resort/filemanagement/upload', ['file' => UploadedFile::fake()->createWithContent('x.html', '<script>alert(1)</script>')], ['Accept' => 'application/json'])
            ->assertStatus(422);
        $this->assertNotContains('svg', Common::UPLOAD_EXTENSIONS);
        $this->assertNotContains('html', Common::UPLOAD_EXTENSIONS);
    }

    public function test_hr_gets_an_email_when_signing_in_from_a_new_ip()
    {
        $admin = ResortAdmin::where('email', 'hr@demo.thewisdom.ai')->first();
        DB::table('login_attempts')->insert(['portal' => 'resort', 'identifier' => $admin->email, 'ip_address' => '10.0.0.1', 'successful' => true, 'created_at' => now()->subDay()]);
        $req = fn ($ip) => \Illuminate\Http\Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => $ip]);

        Common::alertNewLoginLocation($admin, 'resort', $admin->email, $req('10.0.0.1'));
        Mail::assertNothingQueued();
        Common::alertNewLoginLocation($admin, 'resort', $admin->email, $req('203.0.113.9'));
        Mail::assertQueued(\App\Mail\IncidentNotificationMail::class, 1);

        // an ordinary employee gets no alert
        $plain = ResortAdmin::where('email', 'employee@demo.thewisdom.ai')->first();
        DB::table('login_attempts')->insert(['portal' => 'resort', 'identifier' => $plain->email, 'ip_address' => '10.0.0.1', 'successful' => true, 'created_at' => now()->subDay()]);
        Common::alertNewLoginLocation($plain, 'resort', $plain->email, $req('203.0.113.9'));
        Mail::assertQueued(\App\Mail\IncidentNotificationMail::class, 1);
    }
}
