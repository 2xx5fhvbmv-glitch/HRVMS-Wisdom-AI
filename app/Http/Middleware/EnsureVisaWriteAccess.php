<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use Closure;
use Illuminate\Http\Request;

/**
 * V-02 write gate: HR or Finance only — GM has read access
 * (EnsureVisaAccess) but never this, per the decided "GM read-only" split.
 */
class EnsureVisaWriteAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (Common::canWriteVisa()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: HR/Finance access only.'], 403);
        }

        abort(403, 'You do not have permission to perform this action.');
    }
}
