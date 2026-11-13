<?php

namespace Tests\Feature;

use App\Models\ResortAdmin;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Demo ENV: the named demo logins keep the shared DEMO_PASSWORD, whatever path tries to change it. */
class DemoLoginPasswordTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function hr(): ResortAdmin
    {
        return ResortAdmin::where('email', 'hr@demo.thewisdom.ai')->firstOrFail();
    }

    public function test_a_demo_login_keeps_the_shared_password()
    {
        $shared = config('demo.password');
        $new = 'An0ther-Strong-Passw0rd!';

        // web and mobile change-password screens refuse with a clear message
        $this->as('hr')->postJson(route('resort.profile.changePassword'), ['old_password' => $shared, 'password' => $new, 'confirmpassword' => $new])
            ->assertJson(['success' => false, 'msg' => 'Demo logins share one password, so it cannot be changed.']);
        $this->assertTrue(Hash::check($shared, $this->hr()->password));

        // any other path (forgot password, HR send-credentials…) is undone by the model
        $hr = $this->hr();
        $hr->password = Hash::make($new);
        $hr->save();
        $this->assertTrue(Hash::check($shared, $this->hr()->password));

        // demo:reset setting DEMO_PASSWORD is allowed; an ordinary login is unaffected
        $hr->password = Hash::make($shared);
        $hr->save();
        $this->assertTrue(Hash::check($shared, $this->hr()->password));
        $other = ResortAdmin::where('resort_id', '!=', $this->resortId)->whereNull('deleted_at')->firstOrFail();
        $other->password = Hash::make($new);
        $other->save();
        $this->assertTrue(Hash::check($new, $other->fresh()->password));
    }

    public function test_the_migration_puts_a_changed_demo_login_back_on_the_shared_password()
    {
        DB::table('resort_admins')->where('id', $this->hr()->id)->update(['password' => Hash::make('Changed-From-The-App-1')]);

        (include database_path('migrations/2026_11_13_100000_restore_demo_login_passwords.php'))->up();

        $this->assertTrue(Hash::check(config('demo.password'), $this->hr()->password));
    }
}
