<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
use App\Models\Employee;
use App\Helpers\Common;

/**
 * Per-request status re-check (S4-01 / S4-02).
 *
 * Revoking tokens / logging out at the moment status changes (see
 * Common::revokeAllApiTokens() call sites) only covers the paths that call
 * it today. This middleware is the catch-all: it re-checks the resort admin
 * account, its linked employee, and the resort itself on every request, so
 * a deactivated account is cut off immediately even from a path added later
 * that forgets to call the revoke helper.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next)
    {
        $isApi = $request->is('api/*');
        $guard = $isApi ? 'api' : 'resort-admin';
        $user  = Auth::guard($guard)->user();

        if (!$isApi && $user && $request->hasSession() && $request->session()->has('impersonated_by')) {
            if ($ended = $this->impersonationEnded($request)) {
                return $ended;
            }
        }

        if (!$user || $this->isAllowed($user)) {
            return $next($request);
        }

        if ($isApi) {
            try {
                Common::revokeAllApiTokens($user);
            } catch (\Exception $e) {
                \Log::warning('EnsureAccountActive: token revoke failed: ' . $e->getMessage());
            }
            return response()->json(['success' => false, 'message' => 'Account is deactivated'], 401);
        }

        Auth::guard('resort-admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('resort.loginindex')->with('error', 'Your account is deactivated.');
    }

    /**
     * Audit run-3 §4 (impersonation): a super-admin's "log in as resort" session lives by the admin's rules — it ends
     * when the admin is signed out or deactivated, or after the admin console's 15-minute idle limit — and every
     * change made in it is written to admin_audit_logs under the impersonating admin.
     */
    private function impersonationEnded(Request $request)
    {
        $session = $request->session();
        $admin = Auth::guard('admin')->user();
        $last = $session->get('impersonation_last_activity', now()->getTimestamp());
        if (!$admin || (int) $admin->id !== (int) $session->get('impersonated_by') || $admin->status !== 'active'
            || now()->getTimestamp() - $last > \App\Http\Middleware\AdminSecurity::IDLE_SECONDS) {
            Auth::guard('resort-admin')->logout();
            $session->forget(['impersonated_by', 'impersonation_last_activity']);
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'The admin session for this resort has ended.'], 401)
                : redirect()->route('admin.loginindex');
        }
        $session->put('impersonation_last_activity', now()->getTimestamp());
        if (!$request->isMethod('GET')) {
            try {
                \DB::table('admin_audit_logs')->insert([
                    'admin_id' => $admin->id, 'admin_email' => $admin->email, 'method' => $request->method(),
                    'route_name' => 'impersonating:' . ($request->route()?->getName() ?? ''), 'url' => substr($request->fullUrl(), 0, 2048),
                    'payload' => '{}', 'status_code' => null, 'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255), 'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                \Log::warning('impersonation audit log failed: ' . $e->getMessage());
            }
        }
        return null;
    }

    private function isAllowed($user): bool
    {
        if (strtolower($user->status) !== 'active') {
            return false;
        }

        $employee = $user->getEmployee ?? null;
        if ($employee && !in_array($employee->status, Employee::LOGIN_ALLOWED_STATUSES, true)) {
            return false;
        }

        $resort = $user->resort;
        if ($resort && strtolower($resort->status) !== 'active') {
            return false;
        }

        return true;
    }
}
