<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use Closure;
use Illuminate\Http\Request;

/**
 * AC-03 write gate: Accommodation settings/bed-assign/inventory/maintenance-
 * forward actions restricted to HR only (Common::canWriteAccommodation) — no
 * separate Accommodation-manager role exists (product decision). Was
 * completely ungated. Engineering HOD's own existing app-side rank guard for
 * assigning/completing maintenance jobs is untouched by this middleware.
 */
class EnsureAccommodationHRAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (Common::canWriteAccommodation()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: HR access only.'], 403);
        }

        abort(403, 'You do not have permission to perform this action.');
    }
}
