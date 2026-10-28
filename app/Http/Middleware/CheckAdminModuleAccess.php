<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\Common;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CheckAdminModuleAccess
{
    /** First path segment (inside the admin prefix) => key in config('settings.admin_modules'). */
    private const MODULES = [
        'admins' => 'admin_users', 'settings' => 'settings', 'roles' => 'roles_permissions', 'email-templates' => 'email_templates',
        'resorts' => 'resorts', 'resort' => 'resorts', 'get-positions-by-department' => 'resorts',
        'divisions' => 'divisions', 'department' => 'departments', 'sections' => 'sections', 'positions' => 'positions',
        'notifications' => 'notifications', 'modules' => 'modules', 'module-pages' => 'Pages', 'public_holidays' => 'public_holidays',
        'ewt_brackets' => 'ewt_brackets', 'support-categories' => 'support_categories', 'supports' => 'supports',
    ];

    /** The caller's own account (AdminController scopes these to the signed-in admin), and controllers with their own checks. */
    private const OPEN = ['profile', 'change-password', 'check-password', 'resort-data-setup', 'demo-env'];

    public function handle($request, Closure $next)
    {
        $admin = Auth::guard('admin')->user();
        if ($admin && $admin->type !== 'super' && request()->route()->getPrefix() == config('settings.route_prefix.admin'))
        {
            // Match on the path inside the admin prefix, never the full URL (host or prefix could contain a module word).
            $path = Str::after($request->path(), trim(config('settings.route_prefix.admin'), '/') . '/');
            $seg = array_values(array_diff(explode('/', $path), ['admin'])); // "/admin/supports/view/1" -> supports
            $key = ($seg[0] ?? '') === 'admins' && in_array($seg[1] ?? '', ['modules', 'module-pages'], true) ? $seg[1] : ($seg[0] ?? '');

            if (in_array($key, self::OPEN, true) || $request->routeIs('admin.supports.counts')) {
                return $next($request);
            }
            $moduleId = isset(self::MODULES[$key]) ? config('settings.admin_modules.' . self::MODULES[$key]) : null;

            // Whole words only: "restore" is not "store", "massremove" is a delete.
            $is = fn ($words) => preg_match('#(^|[/_-])(' . $words . ')([/_-]|$)#', $path);
            if ($is('create|save|store|import')) {
                $permissionId = config('settings.permissions.create');
            } else if ($is('delete|destroy|remove|massremove')) {
                $permissionId = config('settings.permissions.delete');
            } else if ($is('edit|update|active|inactive|block|restore|assign|send|reply')) {
                $permissionId = config('settings.permissions.edit');
            } else {
                $permissionId = config('settings.permissions.view');
            }

            // Unknown module = denied, not allowed.
            if (!$moduleId || !Common::hasPermission($moduleId, $permissionId)) {
                return redirect(route('admin.permission.denied'))->withErrors([__('messages.nopermission')]);
            }
        }
        return $next($request);
    }
}
