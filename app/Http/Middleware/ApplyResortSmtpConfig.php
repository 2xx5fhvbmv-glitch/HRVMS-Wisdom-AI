<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
use App\Helpers\Common;

/**
 * Web (resort-admin guard) and mobile (api guard) both resolve to the same
 * ResortAdmin model, so one middleware covers every outbound-mail path in
 * both. Runs before the controller, so every Mail::send()/Mail::to() call
 * later in this request inherits the resort's own SMTP config.
 */
class ApplyResortSmtpConfig
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('resort-admin')->user() ?? Auth::guard('api')->user();

        if ($user) {
            Common::applyResortSmtpConfig($user->resort_id);
        }

        return $next($request);
    }
}
