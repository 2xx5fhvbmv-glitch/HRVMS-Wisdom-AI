<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * Security hardening review (S9): report-only CSP. This app loads a
     * mix of inline scripts/styles and third-party CDN assets across
     * dozens of Blade views, so an enforcing policy written without
     * auditing every one of them would break pages instead of securing
     * them. Report-Only never blocks a request — browsers just log
     * violations to devtools console — so this is safe to ship now and
     * gives real violation data to shape an eventual enforcing policy.
     * Flip to 'Content-Security-Policy' only after that data confirms
     * nothing legitimate is flagged.
     */
    private const CSP = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; font-src 'self' data: https:; connect-src 'self' https:; frame-ancestors 'none'";

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy-Report-Only', self::CSP);

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
