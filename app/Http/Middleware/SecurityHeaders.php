<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Security hardening review (S9): the app sent none of these on any
 * response. Deliberately NOT setting a Content-Security-Policy here —
 * this app loads a mix of inline scripts/styles and third-party CDN
 * assets across dozens of Blade views, and an enforcing CSP written
 * without auditing every one of them would break pages rather than
 * secure them. That needs its own dedicated pass (start in
 * report-only mode, verify against every view, then enforce) — not
 * bundled into this hardening fix.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
