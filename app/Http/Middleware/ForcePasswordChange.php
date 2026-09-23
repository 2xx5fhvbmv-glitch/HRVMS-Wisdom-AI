<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Enforces must_change_password: until the account sets a new password,
 * only the profile / change-password / logout routes are reachable.
 * Usage: forcePasswordChange:<guard>
 */
class ForcePasswordChange
{
    private const ALLOWED = [
        'resort' => ['resort.logout', 'resort.user.profile', 'resort.profile.changePassword'],
        'admin' => ['admin.logout', 'admin.profile', 'admin.changePassword', 'admin.checkPassword'],
        'shopkeeper' => ['shopkeeper.logout', 'shopkeeper.profile', 'shopkeeper.update.profile'],
    ];

    private const PROFILE = [
        'resort' => 'resort.user.profile',
        'admin' => 'admin.profile',
        'shopkeeper' => 'shopkeeper.profile',
    ];

    public function handle($request, Closure $next, $guard)
    {
        $user = Auth::guard($guard === 'resort' ? 'resort-admin' : $guard)->user();

        if (!$user || !$user->must_change_password || in_array($request->route()?->getName(), self::ALLOWED[$guard], true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'must_change_password' => true,
                'msg' => 'Please set a new password before continuing.',
            ], 403);
        }

        return redirect()->route(self::PROFILE[$guard]);
    }
}
