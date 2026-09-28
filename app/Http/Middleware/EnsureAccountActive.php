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
