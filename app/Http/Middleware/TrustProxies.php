<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application: Cloudflare's published ranges only
     * (https://www.cloudflare.com/ips/, fetched 2026-11-03). app.thewisdom.ai is proxied by
     * Cloudflare, so without this every user looks like a handful of Cloudflare IPs and one
     * resort's failed logins throttle everyone. Was null (trust nothing) — before that '*',
     * which let any client spoof X-Forwarded-For and skip the login rate limit; trusting only
     * these ranges keeps that closed: a header is believed only when the connection itself
     * comes from Cloudflare, and Cloudflare appends the real client IP last.
     * thewisdom.io (not proxied) is unaffected — its peers are the real clients.
     *
     * ponytail: static list, Cloudflare changes it rarely; refresh from the URL above if they announce new ranges.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
        '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
        '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
        '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    /**
     * The headers that should be used to detect proxies: the client IP and the scheme only.
     * Not X-Forwarded-Host/Port — Cloudflare passes a client's own copies through untouched,
     * and a forged host would end up in password-reset and email links.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO;
}
