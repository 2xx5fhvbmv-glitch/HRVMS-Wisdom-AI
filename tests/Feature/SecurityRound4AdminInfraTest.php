<?php

namespace Tests\Feature;

use App\Helpers\Common;
use App\Models\Admin;
use App\Models\ResortAdmin;
use App\Models\Shopkeeper;
use App\Models\Support;
use App\Services\Wisdom\WisdomTools;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Security audit run-4: super-admin console, Wisdom AI, shared helpers, infrastructure. No demo org needed. */
class SecurityRound4AdminInfraTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function admin(array $extra = []): Admin
    {
        $id = DB::table('admins')->insertGetId($extra + ['first_name' => 'R4', 'last_name' => 'T', 'email' => uniqid('r4') . '@wisdom.test', 'password' => Hash::make('Secret#123'),
            'status' => 'active', 'type' => 'super', 'allow_login' => 1, 'two_factor_confirmed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        return Admin::find($id);
    }

    private function middlewareOf(string $name): array
    {
        return Route::getRoutes()->getByName($name)->gatherMiddleware();
    }

    // 1. H4: legacy SQL tools are refused for every tier, even full.
    public function test_legacy_sql_tools_are_refused_even_for_full_access()
    {
        $full = ['resort_id' => 1, 'can_db' => true, 'can_payroll' => true, 'tier' => 'full'];
        foreach (['run_read_query' => ['sql' => 'SELECT 1'], 'list_tables' => [], 'describe_table' => ['table' => 'employees']] as $tool => $args) {
            $r = WisdomTools::execute($tool, $args, $full);
            $this->assertArrayHasKey('error', $r, $tool);
            $this->assertStringContainsString('disabled', $r['error']);
        }
    }

    // 2. N3: a template of another resort is never used.
    public function test_send_template_email_refuses_another_resorts_template()
    {
        Queue::fake();
        $resorts = DB::table('resorts')->orderBy('id')->limit(2)->pluck('id');
        $this->assertCount(2, $resorts);
        $tpl = DB::table('ta_email_templates')->insertGetId(['Resort_id' => $resorts[1], 'TempleteName' => 'r4', 'MailTemplete' => 'Hi', 'MailSubject' => 'S', 'created_at' => now(), 'updated_at' => now()]);

        $this->assertSame('Template not found.', Common::sendTemplateEmail('TalentAcquisition', $tpl, 'x@wisdom.test', [], $resorts[0]));
        $this->assertSame('Template not found.', Common::sendTemplateEmail('TalentAcquisition', $tpl, 'x@wisdom.test', []));   // no resort at all
        Queue::assertNothingPushed();
        $this->assertTrue(Common::sendTemplateEmail('TalentAcquisition', $tpl, 'x@wisdom.test', [], $resorts[1]));
    }

    // 3. N13: admin notification content is purified.
    public function test_admin_notification_content_is_purified()
    {
        $html = view('resorts.renderfiles.resortnotification', ['getNotifications' => collect([(object) [
            'name' => 'N', 'content' => '<b>Hi</b><script>alert(1)</script><img src=x onerror=alert(2)>', 'start_date' => '', 'end_date' => '']])])->render();
        $this->assertStringContainsString('<b>Hi</b>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    // 4. N14: state-changing master-data routes need step-up; list/edit pages and change-password don't.
    public function test_master_data_writes_need_reauth()
    {
        foreach (['admin.ModulePages.update', 'admin.ModulePages.destroy', 'admin.ModulePages.massremove', 'admin.divisions.store', 'admin.department.import',
                  'admin.positions.update_permissions', 'admin.notifications.update', 'admin.public_holidays.block', 'admin.Modules.active',
                  'admin.ewt_brackets.destroy', 'admin.support_categories.massremove', 'admin.sections.update'] as $name) {
            $this->assertContains('admin.reauth', $this->middlewareOf($name), $name);
        }
        foreach (['admin.divisions.index', 'admin.ModulePages.edit', 'admin.changePassword'] as $name) {
            $this->assertNotContains('admin.reauth', $this->middlewareOf($name), $name);
        }
        $this->assertSame('[0-9]+', Route::getRoutes()->getByName('admin.ModulePages.update')->wheres['id'] ?? null);
    }

    // 5. N15: the data-setup page (salary/bank/passport diffs) needs Resorts Edit.
    public function test_resort_data_setup_show_needs_edit()
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'r4-view', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        \App\Models\AdminRoleModulePermission::create(['role_id' => $roleId, 'module_permission_id' => \App\Models\AdminModulePermission::where('module_id', config('settings.admin_modules.resorts'))
            ->where('permission_id', config('settings.permissions.view'))->value('id')]);
        $viewer = $this->admin(['type' => 'sub', 'role_id' => $roleId]);
        $resortId = DB::table('resorts')->value('id');
        $this->actingAs($viewer, 'admin')->withSession(['admin_last_activity' => time()])
            ->get(route('admin.resort_data_setup.show', $resortId))->assertForbidden();
    }

    // 6. /broadcasting/auth is POST only.
    public function test_broadcasting_auth_has_no_get_route()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class);
        Route::getRoutes()->match(Request::create('/broadcasting/auth', 'GET'));
    }

    // 7. admin:2fa-reset ends the admin's open sessions.
    public function test_2fa_reset_signs_out_live_sessions()
    {
        $admin = $this->admin();
        $session = ['admin_last_activity' => time(), 'admin_mfa_stamp' => \App\Http\Middleware\AdminSecurity::mfaStamp($admin)];
        $this->actingAs($admin, 'admin')->withSession($session)->get('/admin/profile/edit')->assertOk();

        $this->artisan('admin:2fa-reset', ['email' => $admin->email])->assertExitCode(0);
        $this->assertNotSame(null, $admin->fresh()->getRememberToken());

        $this->actingAs($admin->fresh(), 'admin')->get('/admin/profile/edit')->assertRedirect(route('admin.loginindex'));
        $this->assertGuest('admin');
    }

    // 8. Impersonation has an absolute lifetime; the end link is a real route.
    public function test_impersonation_ends_after_an_hour()
    {
        $admin = $this->admin();
        $ra = ResortAdmin::where('status', 'Active')->whereHas('resort', fn ($q) => $q->where('status', 'Active'))->first();
        $this->assertNotNull($ra);
        $this->actingAs($admin, 'admin')->actingAs($ra, 'resort-admin')
            ->withSession(['impersonated_by' => $admin->id, 'impersonation_last_activity' => time(), 'impersonation_started_at' => time() - 3700])
            ->get(route('support.index'))->assertRedirect(route('admin.loginindex'));
        $this->assertGuest('resort-admin');
        $this->assertNotNull(Route::getRoutes()->getByName('endImpersonation'));
    }

    // 9. Support tickets: own tickets (or HR) on the resort side, assigned ones on the console side.
    public function test_support_ticket_scopes()
    {
        $user = new ResortAdmin(['resort_id' => 5]);
        $user->id = 77;
        $user->setRelation('GetEmployee', null);
        $sql = Support::visibleTo($user)->toSql();
        $this->assertStringContainsString('"resort_id" = ?', str_replace('`', '"', $sql));
        $this->assertStringContainsString('"created_by" = ?', str_replace('`', '"', $sql));

        $sub = new Admin(['type' => 'sub']);
        $sub->id = 9;
        $this->assertStringContainsString('assigned_to', Support::visibleToAdmin($sub)->toSql());
        $this->assertStringNotContainsString('assigned_to', Support::visibleToAdmin(new Admin(['type' => 'super']))->toSql());
    }

    // 11. Shopkeeper password change follows the password policy.
    public function test_shopkeeper_password_policy()
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $shop = Shopkeeper::first();
        if (!$shop) {
            $this->markTestSkipped('no shopkeeper row');
        }
        $before = DB::table('shopkeepers')->where('id', $shop->id)->value('password');
        $this->actingAs($shop, 'shopkeeper')->postJson(route('shopkeeper.update.profile'), ['name' => $shop->name, 'contact_no' => $shop->contact_no, 'password' => 'short1'])
            ->assertStatus(422)->assertJson(['success' => false]);
        $this->assertSame($before, DB::table('shopkeepers')->where('id', $shop->id)->value('password'));
    }

    // 12. Generated passwords: CSPRNG, >= 16 chars, alphanumeric.
    public function test_generate_unique_password()
    {
        $a = Common::generateUniquePassword(8);
        $this->assertSame(16, strlen($a));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $a);
        $this->assertNotSame($a, Common::generateUniquePassword(8));
        $this->assertSame(20, strlen(Common::generateUniquePassword(20)));
    }

    // 13. Public token routes are throttled.
    public function test_public_token_routes_are_throttled()
    {
        foreach (['resort.offer.letter.accept', 'resort.contract.accept', 'resort.interview.invitation.show', 'resort.consent.approve', 'resort.availability.available'] as $name) {
            $this->assertContains('throttle:30,1', $this->middlewareOf($name), $name);
        }
    }

    // 14. A confidential grievance never shows its complainant's department in the catalog.
    public function test_catalog_withholds_confidential_complainant_department()
    {
        $field = config('wisdom_catalog.datasets.grievance_cases.fields.department');
        $this->assertStringContainsString("Request_Identity_Disclosure = 'No' THEN NULL", $field['expr']);
    }
}
