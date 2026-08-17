<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gate for the SOS approver/dispatcher actions (approve/reject an SOS,
 * dispatch response teams, mark drill vs real, complete an incident, the
 * manager dashboard). Gated on position_title == 'Security Manager' only —
 * SOSStore() originally also required rank==4 (MGR) when routing the
 * trigger notification, but no real Security Manager record anywhere in
 * the DB actually has rank 4 (the one seeded example is rank 2/HOD), so
 * that condition could never match and silently dropped the notification.
 * Fixed here and in SOSStore() to match on title alone. These endpoints
 * previously had no role check at all beyond "is logged in", so any
 * employee could approve/reject/dispatch someone else's SOS.
 */
class EnsureSOSSecurityManagerAccess
{
    public function handle(Request $request, Closure $next)
    {
        $resortAdmin = Auth::guard('api')->user();
        if (!$resortAdmin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if ((int) ($resortAdmin->is_master_admin ?? 0) === 1) {
            return $next($request);
        }

        $employee = $resortAdmin->GetEmployee ?? null;
        $positionTitle = optional(optional($employee)->position)->position_title;

        if ($employee && $positionTitle === 'Security Manager') {
            return $next($request);
        }

        return response()->json(['success' => false, 'message' => 'Forbidden: Security Manager access only'], 403);
    }
}
