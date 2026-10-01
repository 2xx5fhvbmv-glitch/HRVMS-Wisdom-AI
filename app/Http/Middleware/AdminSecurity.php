<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Super-admin console guard rails (advisory 2026-09-29), run on every
 * authenticated /admin request after auth:admin:
 *  - F4: deactivated account is cut off mid-session (login check alone left
 *        a live session working until it expired);
 *  - D4: 15-minute idle timeout, tighter than the resort app's session;
 *  - A1: MFA enrolment is mandatory before anything else is reachable;
 *  - F3: every state-changing request is written to admin_audit_logs.
 */
class AdminSecurity
{
    private const IDLE_SECONDS = 900;

    /** Reachable before MFA enrolment: enrolment itself, logout, and the must_change_password flow (ForcePasswordChange runs first). */
    private const ALLOWED_BEFORE_MFA = ['admin.2fa.setup', 'admin.2fa.confirm', 'admin.logout', 'admin.profile', 'admin.changePassword', 'admin.checkPassword'];

    /** GET routes that change state (legacy) — audited like POST/DELETE. */
    private const STATE_CHANGING_GETS = ['admin.inactive', 'admin.active', 'admin.block', 'admin.massremove', 'endImpersonation', 'admin.logout'];

    private const REDACT = ['password', 'password_confirmation', 'current_password', 'new_password', 'confirm_password', 'code', '_token'];

    public function handle(Request $request, Closure $next)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return $next($request);
        }

        $now = time();
        $last = $request->session()->get('admin_last_activity');
        if ($admin->status !== 'active' || ($last && $now - $last > self::IDLE_SECONDS)) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $msg = $admin->status !== 'active' ? 'Your account is deactivated.' : 'Signed out after 15 minutes of inactivity.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'msg' => $msg], 401)
                : redirect()->route('admin.loginindex')->withErrors([$msg]);
        }
        $request->session()->put('admin_last_activity', $now);

        $routeName = $request->route()?->getName();
        if (!$admin->two_factor_confirmed_at && !in_array($routeName, self::ALLOWED_BEFORE_MFA, true)) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'msg' => 'Set up two-factor authentication first.', 'redirect_url' => route('admin.2fa.setup')], 403)
                : redirect()->route('admin.2fa.setup');
        }

        $response = $next($request);

        if (!$request->isMethod('GET') || in_array($routeName, self::STATE_CHANGING_GETS, true)) {
            try {
                DB::table('admin_audit_logs')->insert([
                    'admin_id'    => $admin->id,
                    'admin_email' => $admin->email,
                    'method'      => $request->method(),
                    'route_name'  => $routeName,
                    'url'         => substr($request->fullUrl(), 0, 2048),
                    'payload'     => substr(json_encode($request->except(self::REDACT)) ?: '{}', 0, 60000),
                    'status_code' => $response->getStatusCode(),
                    'ip_address'  => $request->ip(),
                    'user_agent'  => substr((string) $request->userAgent(), 0, 255),
                    'created_at'  => now(),
                ]);
            } catch (\Throwable $e) {
                \Log::warning('admin audit log failed: ' . $e->getMessage());
            }
        }

        return $response;
    }
}
