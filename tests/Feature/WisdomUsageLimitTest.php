<?php

namespace Tests\Feature;

use App\Models\ResortAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Wisdom AI usage limits: only successful calls draw from the daily allowance,
 * the 429 body carries code/retry_after/usage, and the resort budget stops calls.
 * OpenRouter is faked; runs against the real DB inside a rolled-back transaction.
 */
class WisdomUsageLimitTest extends TestCase
{
    use DatabaseTransactions;

    private ResortAdmin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = ResortAdmin::where('is_master_admin', 1)->whereNotNull('resort_id')->firstOrFail();
        config(['services.openrouter.key' => 'test-key', 'services.wisdom_ai.resort_monthly_budget' => null]);
        DB::table('resorts')->where('id', $this->admin->resort_id)->update(['wisdom_daily_limit' => 2, 'wisdom_monthly_budget' => null]);
        DB::table('wisdom_usage')->where('user_id', $this->admin->id)->delete();
        Cache::flush();
        Mail::fake();
        $this->actingAs($this->admin, 'resort-admin');
    }

    private function ok(): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Answer.']]],
            'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 20, 'cost' => 0.0045]];
    }

    private function send()
    {
        return $this->postJson(route('resort.wisdom.chat'), ['message' => 'How many staff?']);
    }

    public function test_only_successful_calls_count_and_limit_returns_429()
    {
        Http::fake(['*' => Http::sequence()->push([], 500)->push([], 500)->push([], 500)->push($this->ok())->push($this->ok())]);

        $this->send()->assertStatus(502)->assertJsonPath('usage.remaining', 2); // provider error: not counted
        $this->send()->assertOk()->assertJsonPath('usage.remaining', 1);
        $this->send()->assertOk()->assertJsonPath('usage.remaining', 0);

        $res = $this->send()->assertStatus(429)->assertJsonPath('code', 'daily_limit')->assertJsonPath('usage.used', 2);
        $this->assertGreaterThan(0, $res->json('retry_after'));
        $this->assertSame(now()->addDay()->startOfDay()->toIso8601String(), $res->json('usage.resets_at'));

        $row = DB::table('wisdom_usage')->where('user_id', $this->admin->id)->where('ok', true)->first();
        $this->assertSame(100, (int) $row->prompt_tokens);
        $this->assertEquals(0.0045, (float) $row->cost);

        $this->getJson(route('resort.wisdom.history'))->assertOk()->assertJsonPath('usage.remaining', 0);
    }

    public function test_resort_budget_stops_calls_and_alerts_once_at_80_percent()
    {
        Http::fake(['*' => Http::response($this->ok())]);
        DB::table('resorts')->where('id', $this->admin->resort_id)->update(['wisdom_daily_limit' => 10, 'wisdom_monthly_budget' => 0.01]);
        DB::table('wisdom_usage')->where('resort_id', $this->admin->resort_id)->where('created_at', '>=', now()->startOfMonth())->delete();
        config(['services.wisdom_ai.budget_alert_email' => 'owner@example.com']);

        $alertKey = "wisdom-budget-alert:{$this->admin->resort_id}:" . now()->format('Y-m');
        $this->send()->assertOk(); // USD 0.0045 = 45%
        $this->assertFalse(Cache::has($alertKey));
        $this->send()->assertOk(); // 0.009 = 90% → alert sent (once per month)
        $this->assertTrue(Cache::has($alertKey));
        $this->send()->assertOk(); // 0.0135 — the call that crossed 100% still answered
        $this->send()->assertStatus(429)->assertJsonPath('code', 'resort_budget');
    }
}
