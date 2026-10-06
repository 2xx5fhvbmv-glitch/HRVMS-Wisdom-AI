<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ResortAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Admin "Add Resort": all-or-nothing creation, and the emailed
 * set-password link leaving the new admin free to use the portal.
 * (Uses gmail.com addresses because the form validates email DNS.)
 */
class ResortCreationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        Notification::fake();
        // Never touch the real bucket: fake the configured disk and drop StorageHelper's cached instance.
        Storage::fake(config('settings.storage_driver'));
        (new \ReflectionClass(\App\Helpers\StorageHelper::class))->setStaticPropertyValue('cachedDisk', null);
        DB::table('admins')->insert(['first_name' => 'T', 'last_name' => 'T', 'email' => 'rc-test@wisdom.test', 'password' => Hash::make('x'),
            'status' => 'active', 'type' => 'super', 'allow_login' => 1, 'two_factor_confirmed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs(Admin::where('email', 'rc-test@wisdom.test')->first(), 'admin');
    }

    private function form(): array
    {
        return [
            'resort_name' => 'RC Test Resort', 'resort_prefix' => 'RCT9', 'resort_id' => 'rct9000001', 'resort_status' => 'active',
            'resort_email' => 'rc.resort.test9@gmail.com', 'resort_it_email' => 'rc.it.test9@gmail.com', 'invoice_email' => 'rc.invoice.test9@gmail.com',
            'email' => 'rc.admin.test9@gmail.com', 'first_name' => 'Rc', 'last_name' => 'Admin', 'gender' => 'male', 'status' => 'active',
            'support_preference' => ['Email'], 'Support_SLA' => '24/7 support', 'no_of_users' => 10, 'same_billing_address' => 'yes',
        ];
    }

    public function test_resort_and_admin_are_created_together_or_not_at_all()
    {
        if (!checkdnsrr('gmail.com', 'MX')) {
            $this->markTestSkipped('No DNS — the form validates email domains.');
        }

        // Admin save fails half-way → no orphan resort row.
        ResortAdmin::saving(function () { throw new \RuntimeException('simulated admin save failure'); });
        $this->post(route('admin.resorts.store'), $this->form())->assertJson(['success' => false, 'msg' => 'simulated admin save failure']);
        $this->assertFalse(DB::table('resorts')->where('resort_prefix', 'RCT9')->exists());
        $this->assertFalse(DB::table('resort_admins')->where('email', 'rc.admin.test9@gmail.com')->exists());

        // Retry with the same email/prefix now works.
        ResortAdmin::flushEventListeners();
        ResortAdmin::boot();
        $this->post(route('admin.resorts.store'), $this->form())->assertJson(['success' => true]);
        $resort = DB::table('resorts')->where('resort_prefix', 'RCT9')->first();
        $admin = ResortAdmin::where('email', 'rc.admin.test9@gmail.com')->first();
        // Password comes from the emailed set-password link, so no forced change.
        $this->assertSame([$resort->id, 'super', 1, 0], [(int) $admin->resort_id, $admin->type, (int) $admin->is_master_admin, (int) $admin->must_change_password]);
        $this->assertTrue(DB::table('leave_categories')->where('resort_id', $resort->id)->where('leave_type', 'Day Off')->exists());
        Notification::assertSentTo($admin, \App\Notifications\ResortRegistrationEmail::class);
        // createFolderByResort writes S3-style "dir/" objects, which the faked
        // local disk rejects — that failure is logged after commit and must
        // not undo or fail the creation.
        $this->assertTrue(DB::table('resorts')->where('id', $resort->id)->exists());
    }

    public function test_set_password_link_clears_the_forced_change()
    {
        $resortId = DB::table('resorts')->value('id');
        $admin = new ResortAdmin;
        $admin->forceFill(['resort_id' => $resortId, 'first_name' => 'R', 'last_name' => 'P', 'email' => 'rc.reset.test9@gmail.com', 'gender' => 'male',
            'status' => 'active', 'type' => 'super', 'role_id' => 0, 'is_master_admin' => 1, 'password' => Hash::make('old-temporary-1')]);
        $admin->must_change_password = true;
        $admin->save();
        $token = Password::broker('resort-admin')->createToken($admin);

        $this->post(route('resort.password.reset-submit'), ['token' => $token, 'email' => $admin->email,
            'password' => 'NewPassw0rd!Zq', 'password_confirmation' => 'NewPassw0rd!Zq'])->assertOk();

        $admin->refresh();
        $this->assertTrue(Hash::check('NewPassw0rd!Zq', $admin->password));
        $this->assertSame(0, (int) $admin->must_change_password);
    }
}
