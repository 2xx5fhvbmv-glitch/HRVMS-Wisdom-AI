<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile API: older endpoints answer {status: bool}, newer ones {success: bool} (mobile QA #18 — 16 of 36 API
 * controllers mix the two). Every JSON object response gets both keys with the same value, so the app can read
 * `success` everywhere and builds still reading `status` keep working. Non-boolean `status` values (e.g. a leave's
 * "Pending") are left alone.
 */
class ApiResultKeys
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (!$response instanceof JsonResponse) {
            return $response;
        }
        $data = $response->getData(true);
        if (!is_array($data) || array_is_list($data)) {
            return $response;
        }
        $hasStatus = array_key_exists('status', $data) && is_bool($data['status']);
        $hasSuccess = array_key_exists('success', $data) && is_bool($data['success']);
        if ($hasStatus && !array_key_exists('success', $data)) {
            $data['success'] = $data['status'];
        } elseif ($hasSuccess && !array_key_exists('status', $data)) {
            $data['status'] = $data['success'];
        } else {
            return $response;
        }
        return $response->setData($data);
    }
}
