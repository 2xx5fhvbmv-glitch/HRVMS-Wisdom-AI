<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gate for the Clinic Manager ("doctor") tier of routes/api.php's Clinic
 * endpoints. Two ways in:
 *   - a real employee with rank 12 (CLINIC_STAFF) — the existing in-house
 *     clinic staff login, same rule check.rank:CLINIC_STAFF used to enforce.
 *   - an active TemporaryClinicDoctor (third-party/agency doctor) — see
 *     EnsureClinicCapability for the further per-endpoint restriction HR
 *     configures on that account.
 * Deliberately not reusing CheckUserRankForAPI: that class 403s outright
 * when there's no linked Employee, which a TemporaryClinicDoctor never has.
 */
class EnsureClinicManagerAccess
{
    public function handle(Request $request, Closure $next)
    {
        $resortAdmin = Auth::guard('api')->user();
        if ($resortAdmin) {
            $employee = $resortAdmin->GetEmployee ?? null;
            if ((int) ($employee->rank ?? 0) === 12) {
                return $next($request);
            }
            return response()->json(['success' => false, 'message' => 'Forbidden: Insufficient rank'], 403);
        }

        $doctor = Auth::guard('temp-clinic-doctor')->user();
        if ($doctor && $doctor->isActive()) {
            return $next($request);
        }

        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}
