<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Mobile clients don't send an Accept: application/json header, so
     * expectsJson() is false and Laravel's default exception rendering
     * (auth failures, validation failures, 404s, everything) falls back to
     * an HTML redirect — the app's HTTP client follows it and lands on the
     * login page with a 200 instead of a JSON error it can detect. Force
     * JSON for every api/* request regardless of Accept header.
     */
    protected function shouldReturnJson($request, Throwable $e)
    {
        return $request->is('api/*') || parent::shouldReturnJson($request, $e);
    }

    /**
     * Mobile clients don't send an Accept: application/json header, so
     * expectsJson() is false and the default handler redirects to the HTML
     * login page — the app's HTTP client follows the redirect and gets a
     * 200 full of login-page markup instead of a 401 it can detect.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest($exception->redirectTo() ?? route('resort.loginindex'));
    }
}
