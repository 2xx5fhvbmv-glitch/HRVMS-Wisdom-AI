<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
       
        if (! $request->expectsJson()) {
            if (request()->route()->getPrefix() == "/admin") {
                return route('admin.loginindex');
            } else if (request()->route()->getPrefix() == "/resort") {
                return route('resort.loginindex');
            }
            else if (request()->route()->getPrefix() == "/shopkeeper") {
                return route('shopkeeper.loginindex');
            }
             else if (request()->route()->getPrefix() == "api") {
                throw new HttpResponseException(response()->json(['status'=>false,'message' => 'Unauthenticated.'], 401));
            }
        }
    }
}
