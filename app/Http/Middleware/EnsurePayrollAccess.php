<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use Closure;
use Illuminate\Http\Request;

/**
 * P-01 gate: payroll browsing/editing restricted to HR, Finance, or master
 * admin (Common::canAccessPayroll). Approval endpoints and the run-payroll
 * page are excluded controller-side (->except(...)) because they already
 * carry their own, more specific per-step approver checks that the GM must
 * pass through — this middleware never runs in front of those.
 */
class EnsurePayrollAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (Common::canAccessPayroll()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: HR/Finance access only.'], 403);
        }

        abort(403, 'You do not have permission to access payroll.');
    }
}
