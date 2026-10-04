<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Admin\TwoFactorController;
use Closure;
use Illuminate\Http\Request;

/**
 * Decision D3: step-up confirmation (password + MFA code) before the most
 * dangerous super-admin actions, even inside a live session. A confirmation
 * stays valid for TwoFactorController::REAUTH_TTL seconds.
 */
class AdminReauth
{
    public function handle(Request $request, Closure $next)
    {
        $at = $request->session()->get('admin_reauth_at');
        if ($at && time() - $at <= TwoFactorController::REAUTH_TTL) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax() || !$request->isMethod('GET')) {
            // Return to the page the action was fired from, not the AJAX/POST endpoint.
            $request->session()->put('url.intended', url()->previous());
        }
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'reauth_required' => true,
                'msg' => 'Please confirm your password and authentication code to continue.',
                'redirect_url' => route('admin.reauth'),
            ], 403);
        }

        return $request->isMethod('GET') ? redirect()->guest(route('admin.reauth')) : redirect()->route('admin.reauth');
    }
}
