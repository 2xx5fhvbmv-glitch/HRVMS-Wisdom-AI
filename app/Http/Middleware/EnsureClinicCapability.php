<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Per-endpoint capability gate for TemporaryClinicDoctor accounts — a real
 * rank-12 employee (in-house clinic staff) is unrestricted and always
 * passes, since HR doesn't configure capabilities for their own staff.
 * Usage: ->middleware('clinic.capability:can_manage_treatment')
 */
class EnsureClinicCapability
{
    public function handle(Request $request, Closure $next, string $capability)
    {
        $doctor = Auth::guard('temp-clinic-doctor')->user();
        if (!$doctor) {
            return $next($request); // real employee (rank-12) path — unrestricted
        }

        if (!$doctor->{$capability}) {
            return response()->json([
                'success' => false,
                'message' => 'Your account does not have access to this feature.',
            ], 403);
        }

        return $next($request);
    }
}
