<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Log;

class CheckUserRankForAPI
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    
    public function handle(Request $request, Closure $next, ...$rankType)
    {
         // Ensure the request is for API
         if (!$request->is('api/*')) {
            return response()->json(['error' => 'Invalid request'], 403);
        }

        // Check if the user is authenticated
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $employee      = $user->GetEmployee;
        $employeeRank  = $employee->rank ?? null;
        $rankConfig    = config('settings.Position_Rank');
        $availableRank = $rankConfig[$employeeRank] ?? '';

        // Allow access if the rank matches any provided rank type
        if (in_array($availableRank, $rankType)) {
            return $next($request);
        }

        // If the user's rank doesn't match, return forbidden
        return response()->json(['error' => 'Forbidden: Insufficient rank'], 403);
    
    }
}
