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

/** Security audit run-3, admin console: H5 (permission classified from the URL text, GET mutators without re-auth). */
class SecurityRound2AdminTest extends TestCase
{
    use DatabaseTransactions;

    private string $prefix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        $this->prefix = '/' . trim(config('settings.route_prefix.admin'), '/');
    }

    private function sub(array $grants): Admin
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'audit-r3', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        foreach ($grants as [$module, $permission]) {
            AdminRoleModulePermission::create(['role_id' => $roleId, 'module_permission_id' => AdminModulePermission::where('module_id', config("settings.admin_modules.$module"))
                ->where('permission_id', config("settings.permissions.$permission"))->value('id')]);
        }
        $id = DB::table('admins')->insertGetId(['first_name' => 'View', 'last_name' => 'Only', 'email' => uniqid('v') . '@wisdom.test', 'password' => Hash::make('Sub#Pass1'),
            'status' => 'active', 'type' => 'sub', 'role_id' => $roleId, 'allow_login' => 1, 'two_factor_confirmed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        return Admin::find($id);
    }

    private function as(Admin $admin, bool $reauthed = true)
    {
        // A fresh session per admin: AdminSecurity ends a session that changes hands.
        $this->flushSession();
        $this->app['auth']->forgetGuards();
        return $this->actingAs($admin, 'admin')->withSession($reauthed ? ['admin_reauth_at' => time()] : [])->withHeader('Sec-Fetch-Site', 'same-origin');
    }

    public function test_an_encoded_path_or_a_wordy_id_no_longer_turns_a_write_into_a_view()
    {
        $viewer = $this->sub([['resorts', 'view'], ['roles_permissions', 'view'], ['admin_users', 'view']]);
        $resort = DB::table('resorts')->value('id');
        $denied = $this->prefix . '/permission-denied';

        // %75 = "u", %6d = "m", %64 = "d": the router decodes them, the permission check used to read the raw text.
        $this->as($viewer)->post("{$this->prefix}/resorts/%75pdate/{$resort}", ['resort_name' => 'Hijacked'])->assertRedirectContains('permission');
        $this->as($viewer)->get("{$this->prefix}/roles/%6dassremove?ids=1")->assertRedirectContains('permission');
        $this->as($viewer)->delete("{$this->prefix}/admins/%64estroy/" . $viewer->id)->assertRedirectContains('permission');
        $this->assertNotSame('Hijacked', DB::table('resorts')->where('id', $resort)->value('resort_name'));
        $this->assertNull(DB::table('admins')->where('id', $viewer->id)->value('deleted_at'));

        // An id is a number; "2-create" no longer reaches (or re-classifies) the route.
        $this->as($viewer)->get("{$this->prefix}/resorts/block/{$resort}-create")->assertNotFound();
        // Viewing still works.
        $this->as($viewer)->get(route('admin.resorts.index'))->assertOk();
    }

    public function test_resort_and_role_status_changes_need_a_fresh_confirmation()
    {
        $editor = $this->sub([['resorts', 'view'], ['resorts', 'edit'], ['roles_permissions', 'view'], ['roles_permissions', 'edit'], ['roles_permissions', 'delete'], ['resorts', 'delete']]);
        $resort = DB::table('resorts')->value('id');
        $role = DB::table('roles')->where('name', 'audit-r3')->value('id');
        foreach ([route('admin.resorts.block', $resort), route('admin.resorts.active', $resort), route('admin.resorts.massremove'),
                  route('admin.role.block', $role), route('admin.role.active', $role), route('admin.role.massremove')] as $url) {
            $this->as($editor, false)->get($url)->assertRedirect(route('admin.reauth'));
        }
        $this->as($editor, false)->postJson(route('admin.resorts.update', $resort), [])->assertForbidden()->assertJson(['reauth_required' => true]);
    }

    public function test_m6_m7_admins_cannot_delete_super_admins_or_themselves_or_edit_a_broader_role()
    {
        $super = Admin::find(DB::table('admins')->insertGetId(['first_name' => 'Other', 'last_name' => 'Super', 'email' => uniqid('s') . '@wisdom.test',
            'password' => Hash::make('x'), 'status' => 'active', 'type' => 'super', 'allow_login' => 1, 'two_factor_confirmed_at' => now(), 'created_at' => now(), 'updated_at' => now()]));
        $broad = $this->sub([['admin_users', 'view'], ['admin_users', 'edit'], ['resorts', 'edit']]);   // has Resorts edit
        $editor = $this->sub([['admin_users', 'view'], ['admin_users', 'edit'], ['admin_users', 'delete']]);

        $this->as($editor)->deleteJson(route('admin.destroy', $super->id))->assertForbidden();
        $this->as($editor)->deleteJson(route('admin.destroy', $editor->id))->assertForbidden();
        $this->as($editor)->getJson(route('admin.massremove', ['id' => [$super->id]]))->assertForbidden();
        $this->assertNull($super->fresh()->deleted_at);
        $this->assertNull($editor->fresh()->deleted_at);

        // M7: can't reset the email / password of an admin whose role goes beyond the editor's own.
        $this->as($editor)->postJson(route('admin.update', $broad->id), ['first_name' => 'X', 'email' => 'taken@wisdom.test', 'password' => 'New#Pass123'])->assertForbidden();
        $this->assertNotSame('taken@wisdom.test', $broad->fresh()->email);

        // A super admin still can't delete their own account.
        $this->as($super)->deleteJson(route('admin.destroy', $super->id))->assertForbidden();
    }

    public function test_m8_temporary_passwords_need_resorts_edit()
    {
        $resort = DB::table('resorts')->value('id');
        $import = new \App\Models\ResortDataImport(['resort_id' => $resort, 'status' => 'done']);
        $import->credentials = [['emp_id' => 'X-1', 'name' => 'A', 'email' => 'a@x.test', 'password' => 'Temp#Secret99']];
        $import->save();
        $viewer = $this->sub([['resorts', 'view']]);
        $this->as($viewer)->get(route('admin.resort_data_setup.show', $resort))->assertOk()->assertDontSee('Temp#Secret99');
        $editor = $this->sub([['resorts', 'view'], ['resorts', 'edit']]);
        $this->as($editor)->get(route('admin.resort_data_setup.show', $resort))->assertOk()->assertSee('Temp#Secret99');
    }

    public function test_partial_items_notification_colours_head_requests_support_channel()
    {
        $editor = $this->sub([['notifications', 'view'], ['notifications', 'create'], ['resorts', 'view'], ['resorts', 'edit']]);
        $this->as($editor)->postJson(route('admin.notifications.store'), ['name' => 'n', 'content' => 'c', 'start_date' => now()->toDateString(), 'end_date' => now()->addDay()->toDateString(),
            'notice_color' => 'red;background:url(//evil)', 'font_color' => '#000000', 'status' => 'active'])->assertStatus(422);

        // HEAD runs the GET action: the same-origin guard applies to it too.
        $resort = DB::table('resorts')->value('id');
        $this->flushSession();
        $this->actingAs($editor, 'admin')->withSession(['admin_reauth_at' => time()])->withHeader('Sec-Fetch-Site', 'cross-site')
            ->call('HEAD', route('admin.resorts.block', $resort))->assertForbidden();

        // Support ticket live chat: an admin without Support access is not admitted.
        $auth = collect(\Illuminate\Support\Facades\Broadcast::driver()->getChannels())->first(fn ($cb, $pattern) => $pattern === 'support-ticket.{supportId}');
        $this->assertFalse((bool) $auth($editor, 1));
        $this->assertTrue((bool) $auth($this->sub([['supports', 'view']]), 1));
    }

    public function test_an_impersonated_resort_session_ends_with_the_admin_rules_and_can_be_ended()
    {
        $super = Admin::find(DB::table('admins')->insertGetId(['first_name' => 'S', 'last_name' => 'A', 'email' => uniqid('imp') . '@wisdom.test', 'password' => Hash::make('x'),
            'status' => 'active', 'type' => 'super', 'allow_login' => 1, 'two_factor_confirmed_at' => now(), 'created_at' => now(), 'updated_at' => now()]));
        $resortAdmin = \App\Models\ResortAdmin::whereHas('resort', fn ($q) => $q->where('status', 'Active'))->firstOrFail();
        $this->as($super)->postJson(route('AdminToResort'), ['resort_id' => $resortAdmin->resort_id])->assertJson(['success' => true]);
        $this->assertAuthenticated('resort-admin');

        $this->get(route('endImpersonation'), ['Sec-Fetch-Site' => 'same-origin'])->assertRedirect(route('admin.resorts.index'));
        $this->assertGuest('resort-admin');
        $this->assertAuthenticated('admin');

        // Idle past the admin limit: the next resort page ends the impersonation.
        $this->as($super)->postJson(route('AdminToResort'), ['resort_id' => $resortAdmin->resort_id])->assertJson(['success' => true]);
        $this->travel(\App\Http\Middleware\AdminSecurity::IDLE_SECONDS + 60)->seconds();
        $this->get('/resort/master/hr-dashboard')->assertRedirect(route('admin.loginindex'));
        $this->assertGuest('resort-admin');
    }

    public function test_an_empty_file_encryption_key_is_refused()
    {
        config(['app.file_encryption_key' => '']);
        $this->expectException(\RuntimeException::class);
        \App\Helpers\Common::fileEncryptionKey();
    }

    public function test_resort_permissions_save_exactly_the_ticked_pages()
    {
        $editor = $this->sub([['resorts', 'view'], ['resorts', 'edit']]);
        $resort = DB::table('resorts')->value('id');
        $T = 'resort_pagewise_permissions';
        $active = DB::table('module_pages')->where('status', 'Active')->whereIn('Module_Id', DB::table('modules')->where('status', 'Active')->select('id'));
        $low = (clone $active)->where('id', '<=', 19)->first(['id', 'Module_Id']);    // a page id that is also a module id
        $other = (clone $active)->where('id', '>', 19)->where('Module_Id', '!=', $low->Module_Id)->first(['id', 'Module_Id']);
        $hidden = DB::table('module_pages')->where('status', '!=', 'Active')->first(['id', 'Module_Id']);

        DB::table($T)->where('resort_id', $resort)->delete();
        $grant = fn ($p) => DB::table($T)->insert(['resort_id' => $resort, 'Module_id' => $p->Module_Id, 'page_permission_id' => $p->id]);
        $grant($low); $grant($low); $grant($other);                                    // incl. an old duplicate
        if ($hidden) { $grant($hidden); }
        $save = fn (array $perm) => $this->as($editor)->postJson(route('admin.resorts.update_permissions', $resort), ['module_permissions' => $perm]);

        // untick $low, keep $other (sent twice), and try a page under the wrong module
        $save([$other->Module_Id => [$other->id, $other->id], $low->Module_Id => [$other->id]])->assertOk()->assertJson(['success' => true]);
        $pages = DB::table($T)->where('resort_id', $resort)->pluck('page_permission_id')->all();
        $this->assertNotContains($low->id, $pages, 'a page whose id is 1-19 can be unticked');
        $this->assertSame(1, DB::table($T)->where('resort_id', $resort)->where('page_permission_id', $other->id)->count(), 'one row per grant');
        $this->assertSame(0, DB::table($T)->where('resort_id', $resort)->where('Module_id', $low->Module_Id)->where('page_permission_id', $other->id)->count());
        if ($hidden) {
            $this->assertContains($hidden->id, $pages, 'a grant on a page the screen does not show is kept');
        }

        // tick it again, and nothing at all is refused
        $save([$low->Module_Id => [$low->id], $other->Module_Id => [$other->id]])->assertJson(['success' => true]);
        $this->assertSame(1, DB::table($T)->where('resort_id', $resort)->where('page_permission_id', $low->id)->count());
        $this->as($editor)->postJson(route('admin.resorts.update_permissions', $resort), [])->assertJson(['success' => false]);
        $this->assertSame(1, DB::table($T)->where('resort_id', $resort)->where('page_permission_id', $low->id)->count());
    }
}
