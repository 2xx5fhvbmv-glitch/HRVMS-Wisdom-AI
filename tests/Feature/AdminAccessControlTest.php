<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminModulePermission;
use App\Models\AdminRoleModulePermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Security audit wave 1, admin console: W1-01/02/03/08/10/21/22. */
class AdminAccessControlTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
    }

    /** MFA-enrolled sub-admin whose role holds only $grants, e.g. [['admin_users', 'edit']]. */
    private function sub(array $grants = []): Admin
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'audit-test', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        foreach ($grants as [$module, $permission]) {
            AdminRoleModulePermission::create(['role_id' => $roleId, 'module_permission_id' => AdminModulePermission::where('module_id', config("settings.admin_modules.$module"))
                ->where('permission_id', config("settings.permissions.$permission"))->value('id')]);
        }
        $id = DB::table('admins')->insertGetId(['first_name' => 'Sub', 'last_name' => 'Admin', 'email' => uniqid('sub') . '@wisdom.test', 'password' => Hash::make('Sub#Pass1'),
            'status' => 'active', 'type' => 'sub', 'role_id' => $roleId, 'allow_login' => 1, 'two_factor_confirmed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        return Admin::find($id);
    }

    private function as(Admin $admin)
    {
        return $this->actingAs($admin, 'admin')->withSession(['admin_reauth_at' => time()])->withHeader('Sec-Fetch-Site', 'same-origin');
    }

    public function test_profile_update_only_changes_own_profile_fields()
    {
        $me = $this->sub();
        $other = $this->sub();
        $this->as($me)->post(route('admin.updateProfile'), ['id' => $other->id, 'first_name' => 'Mallory', 'email' => $me->email, 'type' => 'super', 'role_id' => 0, 'status' => 'inactive']);

        $me->refresh();
        $this->assertSame(['Mallory', 'sub', 'active'], [$me->first_name, $me->type, $me->status]);
        $this->assertNotEquals(0, $me->role_id);
        $this->assertSame('Sub', $other->fresh()->first_name);
    }

    public function test_unmapped_modules_and_impersonation_need_the_permission()
    {
        $me = $this->sub([['email_templates', 'view']]);
        $this->as($me)->get(route('admin.emailTemplate.index'))->assertOk();                                  // granted module still works
        $this->as($me)->get(route('admin.resorts.index'))->assertRedirect(route('admin.permission.denied'));  // used to fall through
        $this->as($me)->post(route('AdminToResort'), ['resort_id' => DB::table('resorts')->value('id')])->assertForbidden();
        $this->assertGuest('resort-admin');
    }

    public function test_users_editor_cannot_touch_primary_admin_or_promote_self()
    {
        DB::table('admins')->insertOrIgnore(['id' => 1, 'first_name' => 'Primary', 'last_name' => 'Admin', 'email' => 'primary@wisdom.test', 'password' => Hash::make('x'),
            'status' => 'active', 'type' => 'super', 'allow_login' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $me = $this->sub([['admin_users', 'view'], ['admin_users', 'edit']]);
        $this->as($me)->post(route('admin.update', 1), ['first_name' => 'X', 'email' => 'x@wisdom.test'])->assertForbidden();
        $this->as($me)->get(route('admin.inactive', 1))->assertForbidden();
        $this->as($me)->get(route('admin.inactive', $me->id))->assertForbidden();

        $this->as($me)->post(route('admin.update', $me->id), ['first_name' => 'Self', 'email' => $me->email, 'role_id' => $me->role_id, 'type' => 'super'])->assertOk();
        $this->assertSame(['Self', 'sub'], [$me->fresh()->first_name, $me->fresh()->type]);
    }

    public function test_check_password_only_tests_the_callers_own_password()
    {
        $me = $this->sub();
        $victim = $this->sub();
        DB::table('admins')->where('id', $victim->id)->update(['password' => Hash::make('Victim#1')]);
        $this->assertSame('false', $this->as($me)->post(route('admin.checkPassword'), ['id' => $victim->id, 'old_pass' => 'Victim#1'])->getContent());
        $this->assertSame('true', $this->as($me)->post(route('admin.checkPassword'), ['old_pass' => 'Sub#Pass1'])->getContent());
    }

    public function test_audit_log_never_stores_passwords()
    {
        $me = $this->sub();
        $this->as($me)->post(route('admin.checkPassword'), ['old_pass' => 'Sub#Pass1', 'password_confirm' => 'New#Pass1']);
        $payload = DB::table('admin_audit_logs')->where('admin_id', $me->id)->latest('id')->value('payload');
        $this->assertNotNull($payload);
        $this->assertStringNotContainsString('Sub#Pass1', $payload);
        $this->assertStringNotContainsString('New#Pass1', $payload);
    }

    public function test_state_changing_get_needs_same_site_and_massremove_needs_delete()
    {
        $me = $this->sub([['admin_users', 'view'], ['admin_users', 'edit']]);
        $target = $this->sub();

        $this->as($me)->withHeader('Sec-Fetch-Site', 'cross-site')->get(route('admin.inactive', $target->id))->assertForbidden();
        $this->assertSame('active', $target->fresh()->status);
        $this->as($me)->get(route('admin.inactive', $target->id))->assertOk();
        $this->assertSame('inactive', $target->fresh()->status);

        $this->as($me)->get(route('admin.massremove', ['id' => [$target->id]]))->assertRedirect(route('admin.permission.denied'));
        $this->assertNotNull(Admin::find($target->id));
    }
}
