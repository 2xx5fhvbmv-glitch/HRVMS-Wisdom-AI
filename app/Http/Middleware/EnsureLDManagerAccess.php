<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gate for the mobile L&D Manager module (routes/api.php's ld-manager/* and
 * on-boarding/hr-dashboard endpoints). Passes for:
 *   - an employee whose position title matches a known L&D Manager title, or
 *     who sits in the L&D department (Common::isLDDepartment), OR
 *   - a resort master admin (is_master_admin == 1) — same bypass convention
 *     LeaveController uses elsewhere for master-admin shortcuts.
 * Deliberately position/department based, not rank based: HOD/EXCOM/HR/GM
 * rank alone must NOT unlock this module for anyone who isn't actually L&D.
 */
class EnsureLDManagerAccess
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
        $ldManagerTitles = ['Training Director', 'L&D Manager', 'Learning & Development Head'];
        $positionTitle = optional(optional($employee)->position)->position_title;

        $isLdManager = in_array($positionTitle, $ldManagerTitles, true)
            || Common::isLDDepartment($employee->Dept_id ?? null);

        if ($isLdManager) {
            return $next($request);
        }

        return response()->json(['success' => false, 'message' => 'Forbidden: L&D Manager access only'], 403);
    }
}
