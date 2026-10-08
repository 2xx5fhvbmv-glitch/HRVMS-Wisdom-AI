<?php

namespace Tests\Feature;

use App\Helpers\StorageHelper;
use App\Models\Admin;
use App\Models\ResortAdmin;
use App\Support\Demo\Demo;
use App\Support\Demo\DemoMail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Demo ENV slice 1: the switch, the reset (button + command), email redirect, chat rate limit. */
class DemoEnvFoundationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'demo.enabled' => true, 'demo.password' => 'Demo#Pass2026', 'demo.resort_code' => 'DEMOTEST99',
            'demo.resort_email' => 'resort@demo.thewisdom.ai', 'demo.prefix' => 'DMT']);
        Demo::refresh();
        Storage::fake(config('settings.storage_driver'));
        (new \ReflectionClass(StorageHelper::class))->setStaticPropertyValue('cachedDisk', null);
        DB::table('admins')->insert(['first_name' => 'S', 'last_name' => 'A', 'email' => 'demo-sa@wisdom.test', 'password' => Hash::make('x'),
            'status' => 'active', 'type' => 'super', 'allow_login' => 1, 'two_factor_confirmed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs(Admin::where('email', 'demo-sa@wisdom.test')->first(), 'admin');
    }

    /** table => [resort_id => rows], for every resort-scoped table (the demo resort's key is dropped when comparing). */
    private function perResortCounts(): array
    {
        $snap = [];
        foreach (DB::select('SELECT TABLE_NAME t, COLUMN_NAME c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND LOWER(COLUMN_NAME) = "resort_id"
            AND DATA_TYPE IN ("int","bigint") AND TABLE_NAME != "resorts"') as $r) {
            $snap[$r->t] = DB::table($r->t)->selectRaw("COALESCE(`{$r->c}`, -1) k, COUNT(*) n")->groupBy('k')->pluck('n', 'k')->all();
        }
        return $snap;
    }

    private function withoutDemo(array $snap): array
    {
        $demo = Demo::resortId();
        return array_map(function ($byResort) use ($demo) {
            unset($byResort[$demo]);
            ksort($byResort);
            return $byResort;
        }, $snap);
    }

    public function test_page_exists_only_in_demo_mode_and_for_super_admins()
    {
        config(['demo.enabled' => false]);
        $this->get(route('admin.demo_env.index'))->assertNotFound();

        config(['demo.enabled' => true]);
        $this->get(route('admin.demo_env.index'))->assertOk()->assertSee('Reset Demo ENV')->assertSee('Not created yet');

        DB::table('admins')->where('email', 'demo-sa@wisdom.test')->update(['type' => 'sub']);
        $this->actingAs(Admin::where('email', 'demo-sa@wisdom.test')->first(), 'admin');
        $this->get(route('admin.demo_env.index'))->assertForbidden();
    }

    public function test_reset_button_builds_the_demo_and_a_second_reset_rebuilds_only_the_demo()
    {
        $others = $this->perResortCounts();
        $reauth = ['admin_reauth_at' => time()];

        // Needs a fresh identity check and the exact phrase.
        $this->post(route('admin.demo_env.reset'), ['confirm' => 'RESET DEMO ENV'])->assertRedirect(route('admin.reauth'));
        $this->withSession($reauth)->post(route('admin.demo_env.reset'), ['confirm' => 'reset'])->assertOk()->assertSee('exactly to confirm');
        $this->assertNull(Demo::resortId());

        // Queue runs inline in tests: the reset happens during this request.
        $this->withSession($reauth)->post(route('admin.demo_env.reset'), ['confirm' => 'RESET DEMO ENV'])->assertOk();
        Demo::refresh();
        $resortId = Demo::resortId();
        $this->assertNotNull($resortId);
        $this->assertSame('done', Cache::get('demo:reset_status')['state']);
        $last = Cache::get('demo:last_reset');
        $this->assertTrue($last['verify']['passed'], json_encode($last['verify']));
        $admin = DB::table('resort_admins')->where('resort_id', $resortId)->where('is_master_admin', 1)->first();
        $this->assertSame('admin@demo.thewisdom.ai', $admin->email);
        $this->assertTrue(Hash::check('Demo#Pass2026', $admin->password));
        $this->get(route('admin.demo_env.index'))->assertSee('every check passed')->assertSee((string) $last['seed']);

        // A client renames the resort and adds data during a demo…
        DB::table('resorts')->where('id', $resortId)->update(['resort_name' => 'Client Paradise']);
        DB::table('resort_divisions')->insert(['resort_id' => $resortId, 'name' => 'Client Division', 'code' => 'CD', 'short_name' => 'CD']);
        $menu = DB::table('resort_pagewise_permissions')->where('resort_id', $resortId)->count();

        // …a reset removes their data, keeps the branding, rebuilds the rest.
        $this->withSession($reauth)->post(route('admin.demo_env.reset'), ['confirm' => 'RESET DEMO ENV'])->assertOk();
        $this->assertSame(0, DB::table('resort_divisions')->where('resort_id', $resortId)->where('name', 'Client Division')->count());
        $this->assertSame('Client Paradise', DB::table('resorts')->where('id', $resortId)->value('resort_name'));
        $this->assertSame($menu, DB::table('resort_pagewise_permissions')->where('resort_id', $resortId)->count());
        $this->assertSame(1, DB::table('resort_admins')->where('resort_id', $resortId)->count());
        $this->assertGreaterThan(0, Cache::get('demo:last_reset')['deleted']);

        // No other resort lost or gained a single row.
        $this->assertSame($this->withoutDemo($others), $this->withoutDemo($this->perResortCounts()));
    }

    public function test_command_refuses_outside_demo_mode()
    {
        config(['demo.enabled' => false]);
        $this->artisan('demo:reset', ['--force' => true])->expectsOutputToContain('Demo mode is off')->assertFailed();
        $this->artisan('demo:reset', ['--dry-run' => true])->assertFailed();
    }

    public function test_demo_email_goes_only_to_the_demo_inbox_with_who_it_was_for()
    {
        config(['mail.default' => 'array']);
        $this->artisan('demo:reset', ['--force' => true])->assertSuccessful();
        Demo::refresh();
        $sent = fn () => Mail::mailer('array')->getSymfonyTransport()->messages();

        // In the demo resort's context: rerouted, banner added, cc/bcc dropped.
        \App\Helpers\Common::applyResortSmtpConfig(Demo::resortId());
        Mail::html('<p>Your leave was approved.</p>', fn ($m) => $m->to('staff.member@gmail.com')->cc('hod@gmail.com')->subject('Leave approved'));
        $msg = $sent()->last()->getOriginalMessage();
        $this->assertSame(['amey.tamshetti@gmail.com'], array_map(fn ($a) => $a->getAddress(), $msg->getTo()));
        $this->assertSame([], $msg->getCc());
        $this->assertSame('[Demo ENV] Leave approved', $msg->getSubject());
        $this->assertStringContainsString('meant for: staff.member@gmail.com, hod@gmail.com', $msg->getHtmlBody());

        // A real resort's mail is untouched.
        \App\Helpers\Common::applyResortSmtpConfig(26);
        Mail::html('<p>Hi</p>', fn ($m) => $m->to('real.person@gmail.com')->subject('Real'));
        $msg = $sent()->last()->getOriginalMessage();
        $this->assertSame(['real.person@gmail.com'], array_map(fn ($a) => $a->getAddress(), $msg->getTo()));
        $this->assertSame('Real', $msg->getSubject());

        // A demo login address is always rerouted, whatever the context; a queue worker forgets the last job's resort.
        Mail::html('<p>x</p>', fn ($m) => $m->to('hrd@demo.thewisdom.ai')->subject('To a demo login'));
        $this->assertSame(['amey.tamshetti@gmail.com'], array_map(fn ($a) => $a->getAddress(), $sent()->last()->getOriginalMessage()->getTo()));
        \App\Helpers\Common::applyResortSmtpConfig(Demo::resortId());
        DemoMail::reset();
        Mail::html('<p>x</p>', fn ($m) => $m->to('someone@gmail.com')->subject('After reset'));
        $this->assertSame(['someone@gmail.com'], array_map(fn ($a) => $a->getAddress(), $sent()->last()->getOriginalMessage()->getTo()));
    }

    public function test_wisdom_chat_is_rate_limited()
    {
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['choices' => [['message' => ['content' => 'ok']]]])]);
        $this->artisan('demo:reset', ['--force' => true])->assertSuccessful();
        Demo::refresh();
        $this->actingAs(ResortAdmin::where('resort_id', Demo::resortId())->first(), 'resort-admin');
        for ($i = 0; $i < 10; $i++) {
            $this->assertNotSame(429, $this->postJson(route('resort.wisdom.chat'), ['message' => 'hi'])->status());
        }
        $this->postJson(route('resort.wisdom.chat'), ['message' => 'hi'])->assertStatus(429)
            ->assertJson(['success' => false, 'message' => 'You are sending messages too quickly — please wait a minute and try again.']);
    }
}
