<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Tests\TestCase;

/** app.thewisdom.ai sits behind Cloudflare: believe forwarded headers from Cloudflare's ranges only, and never the host. */
class TrustProxiesTest extends TestCase
{
    protected function tearDown(): void
    {
        Request::setTrustedProxies([], -1);   // the middleware sets them process-wide
        parent::tearDown();
    }

    private function seen(string $peer, array $headers): Request
    {
        $server = ['REMOTE_ADDR' => $peer, 'HTTP_HOST' => 'app.thewisdom.ai'];
        foreach ($headers as $name => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }
        $request = Request::create('http://app.thewisdom.ai/resort', 'GET', [], [], [], $server);
        return app(TrustProxies::class)->handle($request, fn ($r) => $r);
    }

    public function test_through_cloudflare_the_real_client_ip_and_scheme_are_used()
    {
        // Cloudflare appends the client it saw last; anything the client put in front is ignored.
        $r = $this->seen('172.68.10.20', ['X-Forwarded-For' => '6.6.6.6, 103.110.1.5', 'X-Forwarded-Proto' => 'https']);
        $this->assertSame('103.110.1.5', $r->ip());
        $this->assertTrue($r->isSecure());

        $this->assertSame('203.0.113.9', $this->seen('2606:4700::1', ['X-Forwarded-For' => '203.0.113.9'])->ip(), 'Cloudflare over IPv6');
    }

    public function test_a_direct_client_cannot_spoof_its_ip()
    {
        // thewisdom.io is not proxied: the peer is the client, and its own header must not move the rate limiter.
        $this->assertSame('103.110.1.5', $this->seen('103.110.1.5', ['X-Forwarded-For' => '1.2.3.4'])->ip());
    }

    public function test_a_forwarded_host_is_never_believed()
    {
        // Would otherwise poison password-reset and email links.
        $r = $this->seen('172.68.10.20', ['X-Forwarded-Host' => 'evil.example', 'X-Forwarded-For' => '103.110.1.5']);
        $this->assertSame('app.thewisdom.ai', $r->getHost());
    }
}
