<?php

namespace Tests\Feature;

use App\Services\Wisdom\OpenRouterClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Wisdom AI: a reasoning model sometimes returns an empty message. The client
 * must nudge it once instead of showing a canned "not sure" line, and only fall
 * back when the retry is empty too. HTTP is faked — no OpenRouter call, no DB.
 */
class WisdomEmptyReplyTest extends TestCase
{
    private function ctx(): array
    {
        return ['tier' => 'policy', 'tier_label' => 'Policy', 'can_db' => false, 'can_payroll' => false, 'resort_id' => 1,
            'resort_name' => 'Test Resort', 'user_name' => 'Tester', 'role_label' => 'HR', 'dept_name' => null, 'today' => '2026-10-13'];
    }

    private function reply(string $content): array
    {
        return ['choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => $content]]]];
    }

    public function test_empty_reply_is_retried_once()
    {
        config(['services.openrouter.key' => 'test-key']);
        Http::fake(['*' => Http::sequence()->push($this->reply(''))->push($this->reply('Here is the answer.'))]);

        $r = (new OpenRouterClient)->chat($this->ctx(), [['role' => 'user', 'content' => 'hello']]);

        $this->assertTrue($r['ok']);
        $this->assertSame('Here is the answer.', $r['reply']);
        Http::assertSentCount(2);
    }

    public function test_second_empty_reply_falls_back_without_looping()
    {
        config(['services.openrouter.key' => 'test-key']);
        Http::fake(['*' => Http::response($this->reply(''))]);

        $r = (new OpenRouterClient)->chat($this->ctx(), [['role' => 'user', 'content' => 'hello']]);

        $this->assertTrue($r['ok']);
        $this->assertStringContainsString("couldn't put an answer together", $r['reply']);
        Http::assertSentCount(2);
    }
}
