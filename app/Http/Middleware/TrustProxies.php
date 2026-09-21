<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * Was '*' — trusts ANY client-supplied X-Forwarded-For/-Host/-Port/-Proto
     * header unconditionally, so an attacker could spoof their apparent IP
     * on every request (e.g. `X-Forwarded-For: <random>` on each login
     * attempt) and bypass the per-IP rate limiters entirely — confirmed via
     * a live test: rotating the header produced unlimited login attempts
     * that never throttled. Set to null (trust nothing) so $request->ip()
     * always resolves to the real TCP connection's peer IP.
     *
     * If this app ever sits behind a real reverse proxy/CDN/load balancer
     * that appends its own X-Forwarded-For (Cloudflare, an nginx upstream,
     * etc.), this needs to become that proxy's actual IP range instead of
     * null — null is only correct when requests reach this app's web
     * server directly from the internet. Confirm the real deploy topology
     * before assuming null is final.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = null;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
