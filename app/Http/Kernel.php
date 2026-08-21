<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        // \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
           // 'throttle:api',
           \Illuminate\Session\Middleware\StartSession::class,
            // Single shared bucket across the ENTIRE mobile API surface —
            // every screen's calls count against the same counter, not
            // just the one that happens to trip it. A single data-dense
            // screen load (onboarding acknowledgement's several
            // view-files calls, a dashboard firing off multiple aggregate
            // endpoints at once, pull-to-refresh) burns through 60/min
            // fast. Raised to 180/min; still a real ceiling, just sized
            // for how this app's screens actually behave.
            'throttle:180,1',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'revalidate' => \App\Http\Middleware\RevalidateBackHistory::class,
        'hasModuleAccess' => \App\Http\Middleware\CheckAdminModuleAccess::class,
        'redirectIfNotCorrect.dashboard' => \App\Http\Middleware\RedirectIfNotCorrectDashboard::class,
        'checkResortPermission' => \App\Http\Middleware\CheckResortPermission::class,
        'applyResortSmtp' => \App\Http\Middleware\ApplyResortSmtpConfig::class,


        // Add rank-check middleware here
        'check.rank' => \App\Http\Middleware\CheckUserRankForAPI::class,
        'clinic.manager' => \App\Http\Middleware\EnsureClinicManagerAccess::class,
        'clinic.capability' => \App\Http\Middleware\EnsureClinicCapability::class,
        'ld.manager' => \App\Http\Middleware\EnsureLDManagerAccess::class,
        'sos.manager' => \App\Http\Middleware\EnsureSOSSecurityManagerAccess::class,
        'sos.security' => \App\Http\Middleware\EnsureSOSSecurityStaffAccess::class,



    ];
}


