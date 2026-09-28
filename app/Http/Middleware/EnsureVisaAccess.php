<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use Closure;
use Illuminate\Http\Request;

/**
 * V-02 read gate: the whole Visa module restricted to HR, Finance, or GM
 * (Common::canAccessVisa) — was open to every portal login. Write actions
 * are additionally restricted to HR/Finance by EnsureVisaWriteAccess.
 */
class EnsureVisaAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (Common::canAccessVisa()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: HR/Finance/GM access only.'], 403);
        }

        abort(403, 'You do not have permission to access this module.');
    }
}
