<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ResortAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Security audit wave 1, sessions: W1-26/35/39. */
class SessionHardeningTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
    }

    private function master(): ResortAdmin
    {
        return ResortAdmin::where('type', 'super')->where('status', 'Active')->firstOrFail();
    }

    public function test_resort_logout_drops_the_users_session_data()
    {
        $this->actingAs($this->master(), 'resort-admin')->withSession(['wisdom_ai_history' => [['role' => 'user', 'content' => 'private']]])
            ->get(route('resort.logout'));
        $this->assertFalse(session()->has('wisdom_ai_history'));
        $this->assertGuest('resort-admin');
    }

    public function test_admin_logout_also_ends_an_impersonated_resort_login()
    {
        $admin = Admin::where('type', 'super')->firstOrFail();
        $resortKey = app('auth')->guard('resort-admin')->getName(); // where AdminToResort's login lives in the shared session
        $this->actingAs($admin, 'admin')->withSession([$resortKey => $this->master()->id])->get(route('admin.logout'));
        $this->assertGuest('admin');
        $this->assertFalse(session()->has($resortKey));
    }

    public function test_a_new_login_on_the_same_session_is_not_compared_with_the_previous_users_hash()
    {
        $this->withSession(['password_hash_resort-admin' => 'previous-user-hash'])->get(route('resort.loginindex'));
        app('auth')->guard('resort-admin')->login($this->master()); // e.g. switching the impersonated resort
        $this->assertFalse(session()->has('password_hash_resort-admin'));
    }

    public function test_a_password_change_signs_out_older_sessions()
    {
        $user = $this->master();
        DB::table('resort_admins')->where('id', $user->id)->update(['password' => Hash::make('Old#Pass1'), 'must_change_password' => 0]);
        $this->postJson(route('resort.login'), ['email' => $user->email, 'password' => 'Old#Pass1'])->assertJson(['success' => true]);
        $this->getJson(route('resort.wisdom.history'))->assertOk();

        DB::table('resort_admins')->where('id', $user->id)->update(['password' => Hash::make('New#Pass1')]); // changed from another browser
        app('auth')->forgetGuards();
        $this->getJson(route('resort.wisdom.history'))->assertUnauthorized();
    }
}
