<?php

namespace Tests\Feature;

use App\Services\Wisdom\WisdomTools;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Wisdom AI's company policy search: offered to every chat tier (the policy tier has no other tool), sent to the
 * AI service for this resort only with the internal key, and the passages come back with document and page.
 */
class WisdomPolicySearchTest extends TestCase
{
    private function toolNames(array $ctx): array
    {
        return array_map(fn ($t) => $t['function']['name'], WisdomTools::definitions($ctx));
    }

    public function test_every_tier_gets_policy_search_and_the_policy_tier_gets_only_that()
    {
        $this->assertSame(['search_policy_documents'], $this->toolNames(['can_db' => false, 'can_payroll' => false]));
        $this->assertContains('search_policy_documents', $this->toolNames(['can_db' => true, 'can_payroll' => false]));
        $this->assertContains('search_policy_documents', $this->toolNames(['can_db' => true, 'can_payroll' => true]));
    }

    public function test_search_asks_the_ai_service_for_this_resort_and_returns_cited_passages()
    {
        config(['services.ai_service.url' => 'http://ai.test', 'services.wisdom_ai.internal_key' => 'secret-key']);
        Http::fake(['ai.test/policies/search' => Http::response([
            'company_policy' => [['kind' => 'company_policy', 'document' => 'Staff Handbook.pdf', 'page' => 12, 'text' => 'Notice period is 30 days.', 'score' => 0.4]],
            'law' => [['kind' => 'law', 'document' => 'Employment-Act.pdf', 'page' => 9, 'text' => 'Section 21 ...', 'score' => 0.6]],
            'company_documents' => 1,
        ])]);

        // The policy tier has no database access but must still be able to search policies.
        $out = WisdomTools::execute('search_policy_documents', ['question' => 'notice period'], ['resort_id' => 29, 'can_db' => false, 'can_payroll' => false]);

        $this->assertSame([['document' => 'Staff Handbook.pdf', 'page' => 12, 'text' => 'Notice period is 30 days.']], $out['company_policy_passages']);
        $this->assertSame('Employment-Act.pdf', $out['law_passages'][0]['document']);
        $this->assertNull($out['note']);
        Http::assertSent(fn (Request $r) => $r->url() === 'http://ai.test/policies/search' && $r['resort_id'] === 29
            && $r['query'] === 'notice period' && $r->hasHeader('X-Internal-Key', 'secret-key'));
    }

    public function test_no_uploaded_policies_is_said_plainly()
    {
        config(['services.ai_service.url' => 'http://ai.test']);
        Http::fake(['ai.test/policies/search' => Http::response(['company_policy' => [], 'law' => [], 'company_documents' => 0])]);
        $out = WisdomTools::execute('search_policy_documents', ['question' => 'leave'], ['resort_id' => 29, 'can_db' => true, 'can_payroll' => true]);
        $this->assertStringContainsString('Company Policies', $out['note']);
    }

    public function test_an_unreachable_policy_library_is_reported_not_guessed()
    {
        config(['services.ai_service.url' => 'http://ai.test']);
        Http::fake(['ai.test/*' => Http::response('down', 500)]);
        $out = WisdomTools::execute('search_policy_documents', ['question' => 'leave'], ['resort_id' => 29, 'can_db' => true, 'can_payroll' => true]);
        $this->assertArrayHasKey('error', $out);
    }
}
