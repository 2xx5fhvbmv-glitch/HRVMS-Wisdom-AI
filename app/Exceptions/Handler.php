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
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });

        // A resort's master admin is not an employee (no employees row), but
        // many portal pages read $user->GetEmployee->... (or $employees[0])
        // unguarded. For that account only, turn the resulting error into a
        // redirect to the Permission page (its setup home) instead of an
        // error page. Still reported above.
        $this->renderable(function (Throwable $e, $request) {
            if (!preg_match('/^((Attempt to read property "\w+"|Call to a member function \w+\(\)) on null|Undefined array key 0)$/', $e->getMessage())) {
                return null;
            }
            $user = \Illuminate\Support\Facades\Auth::guard('resort-admin')->user();
            if (!$user || !$user->is_master_admin || $user->GetEmployee) {
                return null;
            }
            $message = 'This page works for employees only. As the resort admin, set up the structure and page access — your HR Director then works in the modules.';
            return $request->expectsJson() || $request->ajax()
                ? response()->json(['success' => false, 'message' => $message, 'msg' => $message], 403)
                : redirect()->route('resort.Page.Permission', ['notice' => 'employee-only']);
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

        return redirect()->guest($exception->redirectTo($request) ?? route('resort.loginindex'));
    }
}
