<?php

namespace Tests\Feature;

use App\Services\Wisdom\OpenRouterClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Claude prompt caching: the cached system part must hold nothing user-, resort- or date-specific, or the cache never hits. */
class WisdomPromptCacheTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    private function send(string $model): array
    {
        $this->buildDemoOrganisation();
        config(['services.openrouter.key' => 'test', 'services.openrouter.model' => $model]);
        Http::swap(new Factory()); // drop the demo organisation's catch-all fake, which would answer first
        Http::fake(['*' => Http::response(['choices' => [['message' => ['role' => 'assistant', 'content' => 'Hello']]],
            'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 5, 'cost' => 0.001, 'prompt_tokens_details' => ['cached_tokens' => 80]]])]);
        $ctx = ['tier' => 'full', 'can_db' => true, 'can_payroll' => true, 'resort_id' => $this->resortId,
            'resort_name' => 'Cache Test Resort', 'user_name' => 'Zed Tester', 'role_label' => 'HOD', 'today' => '2031-01-02'];
        $client = new OpenRouterClient();
        $this->assertSame('Hello', $client->chat($ctx, [['role' => 'user', 'content' => 'hi']])['reply']);
        $payload = null;
        Http::assertSent(function ($request) use (&$payload) {
            $payload = $request->data();
            return true;
        });
        return [$payload, $client->usage];
    }

    public function test_claude_requests_cache_a_static_prompt_and_keep_user_details_out_of_it()
    {
        [$payload, $usage] = $this->send('anthropic/claude-haiku-5.5');
        [$static, $context] = $payload['messages'][0]['content'];

        $this->assertSame(['type' => 'ephemeral'], $static['cache_control']);
        $this->assertArrayNotHasKey('cache_control', $context);
        $this->assertSame(['type' => 'ephemeral'], $payload['cache_control']);
        $this->assertSame('wisdom-full', $payload['session_id']);

        $dept = DB::table('resort_departments')->where('resort_id', $this->resortId)->where('status', 'active')->value('name');
        foreach (['Cache Test Resort', 'Zed Tester', '2031-01-02', $dept] as $value) {
            $this->assertStringNotContainsString($value, $static['text']);
            $this->assertStringContainsString($value, $context['text']);
        }
        $this->assertSame(80, $usage['cached_tokens']);
    }

    public function test_other_models_get_one_plain_system_prompt_without_cache_markers()
    {
        [$payload] = $this->send('openai/gpt-oss-120b');

        $this->assertIsString($payload['messages'][0]['content']);
        $this->assertStringContainsString('Zed Tester', $payload['messages'][0]['content']);
        $this->assertArrayNotHasKey('cache_control', $payload);
        $this->assertArrayNotHasKey('session_id', $payload);
    }
}
