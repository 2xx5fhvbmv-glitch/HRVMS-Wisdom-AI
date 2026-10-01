<?php

namespace Tests\Feature;

use App\Helpers\Totp;
use App\Models\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SuperAdminSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private string $email = 'mfa-test@wisdom.test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        DB::table('admins')->insert(['first_name' => 'T', 'last_name' => 'T', 'email' => $this->email, 'password' => Hash::make('Secret#123'),
            'status' => 'active', 'type' => 'super', 'allow_login' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function admin(): Admin { return Admin::where('email', $this->email)->first(); }
    private function login($pw = 'Secret#123') { return $this->postJson('/admin/do-login', ['email' => $this->email, 'password' => $pw]); }

    public function test_rfc6238_vector()
    {
        // RFC 6238 SHA-1 key "12345678901234567890", T=59 -> 94287082 (6-digit: 287082)
        $this->assertSame('287082', Totp::code('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 1));
        $s = Totp::generateSecret();
        $this->assertTrue(Totp::verify($s, Totp::code($s, intdiv(time(), 30))));
        $this->assertFalse(Totp::verify($s, '000000x'));
    }

    public function test_full_flow()
    {
        // 1. password login, not enrolled -> forced to enrolment, profile still reachable
        $this->login()->assertJson(['success' => true]);
        $this->assertAuthenticated('admin');
        $this->get('/admin/dashboard')->assertRedirect(route('admin.2fa.setup'));
        $this->getJson('/admin/dashboard')->assertStatus(403)->assertJson(['redirect_url' => route('admin.2fa.setup')]);
        $this->get('/admin/profile/edit')->assertStatus(200);
        Mail::assertQueued(\App\Mail\IncidentNotificationMail::class); // new-IP alert

        // 2. enrol
        $this->get('/admin/two-factor/setup')->assertStatus(200)->assertSee('Turn on two-factor');
        $secret = session('admin_2fa_setup_secret');
        $this->post('/admin/two-factor/setup', ['code' => '000000'])->assertStatus(422)->assertSee('match');
        $this->assertNull($this->admin()->two_factor_confirmed_at);
        $res = $this->post('/admin/two-factor/setup', ['code' => Totp::code($secret, intdiv(time(), 30))]);
        $res->assertStatus(200)->assertSee('recovery codes');
        $codes = $res->viewData('codes');
        $this->assertCount(8, $codes);
        $a = $this->admin();
        $this->assertNotNull($a->two_factor_confirmed_at);
        $this->assertSame($secret, $a->two_factor_secret);
        $this->assertNotSame($secret, DB::table('admins')->where('id', $a->id)->value('two_factor_secret'), 'secret must be encrypted at rest');
        $this->get('/admin/dashboard')->assertStatus(200)->assertSee('Last sign-in');

        // 3. logout, password alone no longer signs in
        $this->get('/admin/logout');
        $this->assertGuest('admin');
        $this->login()->assertJson(['success' => true, 'redirect_url' => route('admin.2fa.challenge')]);
        $this->assertGuest('admin');
        $this->get('/admin/dashboard')->assertRedirect(route('admin.loginindex'));
        $this->get('/admin/two-factor')->assertStatus(200);
        $this->post('/admin/two-factor', ['code' => '123456'])->assertStatus(422);
        $this->assertGuest('admin');
        $code = Totp::code($secret, intdiv(time(), 30));
        $this->post('/admin/two-factor', ['code' => $code])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated('admin');

        // 4. replay of the same TOTP is refused
        $this->get('/admin/logout');
        $this->login();
        $this->post('/admin/two-factor', ['code' => $code])->assertStatus(422);
        $this->assertGuest('admin');

        // 5. recovery code works exactly once
        $this->post('/admin/two-factor', ['code' => strtolower($codes[0])])->assertRedirect(route('admin.dashboard'));
        $this->assertCount(7, $this->admin()->two_factor_recovery_codes);
        $this->get('/admin/logout');
        $this->login();
        $this->post('/admin/two-factor', ['code' => $codes[0]])->assertStatus(422);
        $this->post('/admin/two-factor', ['code' => $codes[1]])->assertRedirect(route('admin.dashboard'));

        // 6. step-up re-auth on dangerous action + audit log
        session()->forget('admin_reauth_at');
        $this->postJson('/admin/admin-to-resort', ['resort_id' => 0])->assertStatus(403)->assertJson(['reauth_required' => true]);
        $this->post('/admin/confirm-identity', ['password' => 'wrong', 'code' => Totp::code($secret, intdiv(time(), 30) + 1)])->assertStatus(422);
        Cache::flush();
        $this->post('/admin/confirm-identity', ['password' => 'Secret#123', 'code' => Totp::code($secret, intdiv(time(), 30) + 1)])->assertRedirect();
        $this->postJson('/admin/admin-to-resort', ['resort_id' => 0, 'password' => 'leak'])->assertStatus(200)->assertJson(['success' => false]);
        $log = DB::table('admin_audit_logs')->where('admin_email', $this->email)->where('route_name', 'AdminToResort')->orderByDesc('id')->first();
        $this->assertNotNull($log);
        $this->assertSame(200, (int) $log->status_code);
        $this->assertStringNotContainsString('leak', $log->payload);

        // 7. idle timeout
        session(['admin_last_activity' => time() - 901]);
        $this->get('/admin/dashboard')->assertRedirect(route('admin.loginindex'));
        $this->assertGuest('admin');
    }

    public function test_deactivated_mid_session_is_kicked()
    {
        $this->login();
        DB::table('admins')->where('email', $this->email)->update(['status' => 'inactive']);
        app('auth')->forgetGuards(); // test app reuses the guard's cached user across requests; prod re-resolves per request
        $this->get('/admin/two-factor/setup')->assertRedirect(route('admin.loginindex'));
        $this->assertGuest('admin');
    }

    public function test_lockout_after_five_failures()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->login('bad')->assertJson(['success' => false]);
        }
        $this->assertNotNull($this->admin()->locked_until);
        $this->login()->assertJson(['success' => false, 'msg' => 'Invalid email or password.']);
        $this->assertGuest('admin');
        Mail::assertQueued(\App\Mail\IncidentNotificationMail::class, fn ($m) => $m->hasTo($this->email));
    }

    public function test_artisan_reset()
    {
        DB::table('admins')->where('email', $this->email)->update(['two_factor_confirmed_at' => now(), 'locked_until' => now()->addHour()]);
        $this->artisan('admin:2fa-reset', ['email' => $this->email])->assertExitCode(0);
        $this->assertNull($this->admin()->two_factor_confirmed_at);
        $this->assertNull($this->admin()->locked_until);
    }
}
