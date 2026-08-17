<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gate for the Security-staff dashboard (broader than the Security Manager
 * approver role — any employee in the Security department can view open
 * incidents, only the Security Manager can act on them; see
 * EnsureSOSSecurityManagerAccess). A Security Manager is themselves
 * Security-department staff, so this passes for them too.
 */
class EnsureSOSSecurityStaffAccess
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

        if ($employee && Common::isSecurityDepartment($employee->Dept_id ?? null)) {
            return $next($request);
        }

        return response()->json(['success' => false, 'message' => 'Forbidden: Security staff access only'], 403);
    }
}
