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

    public function test_when_the_lookup_rounds_run_out_it_still_answers_from_what_it_gathered()
    {
        config(['services.openrouter.key' => 'test-key', 'services.openrouter.base_url' => 'https://openrouter.test/api/v1']);
        $lookup = ['choices' => [['finish_reason' => 'tool_calls', 'message' => ['role' => 'assistant', 'content' => '',
            'tool_calls' => [['id' => 'c1', 'type' => 'function', 'function' => ['name' => 'search_policy_documents', 'arguments' => '{"question":"x"}']]]]]]];
        $seq = Http::sequence();
        for ($i = 0; $i < OpenRouterClient::MAX_TOOL_ROUNDS; $i++) {
            $seq->push($lookup);
        }
        $seq->push($this->reply('Here is what I found.'));
        Http::fake(['openrouter.test/*' => $seq, '*' => Http::response([])]);   // '*' = the policy search's own service

        $r = (new OpenRouterClient)->chat($this->ctx(), [['role' => 'user', 'content' => 'a long multi-part question']]);

        $this->assertSame('Here is what I found.', $r['reply']);
        // the final call offers no tools, so the model has to answer
        Http::assertSent(fn ($req) => str_contains((string) $req->url(), 'openrouter.test') && !isset($req->data()['tools'])
            && str_contains(json_encode($req->data()['messages']), 'Stop looking things up'));
    }

    public function test_a_reply_cut_off_by_the_budget_after_lookups_is_answered_again_without_tools()
    {
        config(['services.openrouter.key' => 'test-key', 'services.openrouter.base_url' => 'https://openrouter.test/api/v1']);
        $lookup = ['choices' => [['finish_reason' => 'tool_calls', 'message' => ['role' => 'assistant', 'content' => '',
            'tool_calls' => [['id' => 'c1', 'type' => 'function', 'function' => ['name' => 'search_policy_documents', 'arguments' => '{"question":"x"}']]]]]]];
        $cut = ['choices' => [['finish_reason' => 'length', 'message' => ['role' => 'assistant', 'content' => '']]]];
        Http::fake(['openrouter.test/*' => Http::sequence()->push($lookup)->push($cut)->push($this->reply('Answer from the data.')), '*' => Http::response([])]);

        $r = (new OpenRouterClient)->chat($this->ctx(), [['role' => 'user', 'content' => 'q']]);

        $this->assertSame('Answer from the data.', $r['reply']);
        Http::assertSent(function ($req) {
            if (!str_contains((string) $req->url(), 'openrouter.test') || isset($req->data()['tools'])) {
                return false;
            }
            $roles = array_column($req->data()['messages'], 'role');
            return !in_array('tool', $roles, true) && $req->data()['max_tokens'] >= 2400;   // flattened, bigger budget
        });
    }

    public function test_the_same_lookup_repeated_is_not_run_again()
    {
        config(['services.openrouter.key' => 'test-key', 'services.openrouter.base_url' => 'https://openrouter.test/api/v1']);
        $same = ['choices' => [['finish_reason' => 'tool_calls', 'message' => ['role' => 'assistant', 'content' => '',
            'tool_calls' => [['id' => 'c', 'type' => 'function', 'function' => ['name' => 'search_policy_documents', 'arguments' => '{"question":"x"}']]]]]]];
        Http::fake(['openrouter.test/*' => Http::sequence()->push($same)->push($same)->push($this->reply('Done.')), '*' => Http::response([])]);

        $client = new OpenRouterClient;
        $this->assertSame('Done.', $client->chat($this->ctx(), [['role' => 'user', 'content' => 'q']])['reply']);
        $this->assertCount(2, $client->trace);
        $this->assertStringContainsString('already called', $client->trace[1][2]);
        Http::assertSentCount(4);   // 3 model calls + the one policy lookup that actually ran
    }
}
