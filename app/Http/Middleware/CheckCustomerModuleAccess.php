<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\Common;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CheckCustomerModuleAccess
{
    public function handle($request, Closure $next)
    {
        
        if(Auth::guard('customer')->check() && Auth::guard('customer')->user()->role_id != 0 && request()->route()->getPrefix() == config('settings.route_prefix.customer'))
        {
            
            $url = $request->url();
            $dashboardRoute = Common::getDashboardLink();
        
            /*** Check role vise module access ***/
            if(Str::contains($url, 'profile')) {
                $moduleId = config('settings.admin_modules.customer');                
            } else {
                return $next($request);
            }
         
            if(Str::contains($url, 'create') || Str::contains($url, 'save') || Str::contains($url, 'store')) {
                $permissionId = config('settings.permissions.create');
            } else if(Str::contains($url, 'edit') || Str::contains($url, 'update') || Str::contains($url, 'active') || Str::contains($url, 'inactive')  || Str::contains($url, 'block') ) {
                $permissionId = config('settings.permissions.edit');  
            } else if(Str::contains($url, 'delete') || Str::contains($url, 'destroy')) {
                $permissionId = config('settings.permissions.delete');   
            } else {                
                $permissionId = config('settings.permissions.view');  
            }
            
            // dd($moduleId,$permissionId);

            $accessible = Common::hasPermission($moduleId,$permissionId);
            
            if(!$accessible) {
                return redirect(route('admin.permission.denied'))->withErrors([__('messages.nopermission')]);
            }
        }
        return $next($request);
    }
}
