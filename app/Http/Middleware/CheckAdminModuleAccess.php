<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\Common;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CheckAdminModuleAccess
{
    public function handle($request, Closure $next)
    {
        
        if(Auth::guard('admin')->check() && Auth::guard('admin')->user()->role_id != 0 && request()->route()->getPrefix() == config('settings.route_prefix.admin'))
        {
            
            $url = $request->url();
            $dashboardRoute = Common::getDashboardLink();
        
            /*** Check role vise module access ***/
            if(Str::contains($url, 'admins')) {
                $moduleId = config('settings.admin_modules.admin_users');                
            } else if(Str::contains($url, 'settings')) {
                $moduleId = config('settings.admin_modules.settings');
            } else if(Str::contains($url, 'roles')) {
                $moduleId = config('settings.admin_modules.roles_permissions');     
            } else if(Str::contains($url, 'email-templates')) {
                $moduleId = config('settings.admin_modules.email_templates');
            } else if(Str::contains($url, 'clients')) {
                $moduleId = config('settings.admin_modules.clients');
            } else if(Str::contains($url, 'manufacturer')) {
                $moduleId = config('settings.admin_modules.manufacturers');
            } else if(Str::contains($url, 'oils')) {
                $moduleId = config('settings.admin_modules.oils');
            } else if(Str::contains($url, 'transformer_ratings')) {
                $moduleId = config('settings.admin_modules.transformer_ratings');
            } else if(Str::contains($url, 'tests_tobe_done')) {
                $moduleId = config('settings.admin_modules.test_tobe_done');
            } else if(Str::contains($url, 'sample_details')) {
                $moduleId = config('settings.admin_modules.sample_details');
            } else if(Str::contains($url, 'sample_vessel')) {
                $moduleId = config('settings.admin_modules.sample_vessel');
            } else if(Str::contains($url, 'employees')) {
                $moduleId = config('settings.admin_modules.employees');
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
