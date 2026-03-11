<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Log;
use App\Models\ResortDepartment;

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

        $employee = $user->GetEmployee ?? $user->getEmployee ?? null;
        if (!$employee) {
            return response()->json(['error' => 'Forbidden: No employee record linked'], 403);
        }

        $rankConfig    = config('settings.Position_Rank', []);
        $availableRank = (string) ($rankConfig[$employee->rank ?? ''] ?? $rankConfig[$employee->main_rank ?? ''] ?? '');

        // Middleware params like "HR,GM,HOD,EXCOM" come as one string; split into allowed ranks
        $allowedRanks  = $rankType;
        if (count($rankType) === 1 && is_string($rankType[0]) && strpos($rankType[0], ',') !== false) {
            $allowedRanks = array_map('trim', explode(',', $rankType[0]));
        }
        $allowedRanks = array_map('strtoupper', array_map('trim', $allowedRanks));

        // Allow access if the rank matches any provided rank type (case-insensitive)
        if ($availableRank !== '' && in_array(strtoupper($availableRank), $allowedRanks)) {
            return $next($request);
        }

        // Fallback: when this route group allows HR,GM,HOD,EXCOM, also allow if user is in HR department (for HR EXCOM / HR HOD access)
        $allowsHRGroup = count(array_intersect($allowedRanks, ['HR', 'GM', 'HOD', 'EXCOM'])) >= 1;
        if ($allowsHRGroup) {
            $hrDeptId = ResortDepartment::where('resort_id', $user->resort_id)
                ->where(function ($q) {
                    $q->where('name', 'Human Resources')->orWhere('name', 'like', '%Human Resources%');
                })
                ->value('id');
            if ($hrDeptId !== null && (int) $employee->Dept_id === (int) $hrDeptId) {
                return $next($request);
            }
        }

        // If the user's rank doesn't match, return forbidden
        return response()->json(['error' => 'Forbidden: Insufficient rank'], 403);
    }
}
