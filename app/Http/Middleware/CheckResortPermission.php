<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
use App\Models\ModulePages;
use Illuminate\Support\Facades\Route;
use App\Helpers\Common;

class CheckResortPermission
{
    public function handle(Request $request, Closure $next)
    {
        $resortAdmin = Auth::guard('resort-admin')->user();
        $currentRoute = Route::currentRouteName();

        // Allow approvers to access payroll run page with viewonly/resume parameters
        // The controller handles its own access control for approvers
        if ($currentRoute === 'payroll.run' && ($request->has('viewonly') || $request->has('resume'))) {
            return $next($request);
        }

        $check = Common::checkRouteWisePermission($currentRoute, config('settings.resort_permissions.view'));

        // Check if the user is authenticated as a resort admin
        if ($check == false) {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}
