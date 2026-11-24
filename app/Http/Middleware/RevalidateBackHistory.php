<?php

namespace App\Http\Middleware;

use Closure;

class RevalidateBackHistory
{
  public function handle($request, Closure $next)
  {
    $response = $next($request);
    if(method_exists($response, 'header')){
      return $response->header('Cache-Control','nocache, no-store, max-age=0, must-revalidate')
      ->header('Pragma','no-cache')
      ->header('Expires','Fri, 01 Jan 1990 00:00:00 GMT');
    }
    // Downloads/streams have no header(); was `return $next($request)`, which ran the whole request a second time.
    return $response;
  }
}
