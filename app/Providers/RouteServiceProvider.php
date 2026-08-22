<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        // Kernel.php's 'api' group throttle ran before any auth middleware,
        // so Laravel's bare 'throttle:180,1' always keyed on $request->ip()
        // — every employee on the same resort's shared/NAT'd WiFi drew from
        // ONE 180/min bucket collectively, not 180/min each. A handful of
        // staff refreshing dashboards on-site could 429 an unrelated
        // employee's single request. Key by the authenticated mobile user
        // when the Bearer token resolves one (works even before 'auth:api'
        // middleware runs — guard resolution from the token is on-demand,
        // not dependent on middleware order), falling back to IP only for
        // truly unauthenticated calls (login, forgot-password).
        RateLimiter::for('mobile-api', function ($request) {
            $key = optional($request->user('api'))->id ?: $request->ip();
            return Limit::perMinute(180)->by($key);
        });

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        $this->mapAdminRoutes();
        $this->mapResortRoutes();
        $this->mapShopkeeperRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    protected function mapAdminRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/admin_route.php'));
    }
    protected function mapResortRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/resort_route.php'));
    }


    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }
    protected function mapShopkeeperRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/shopkeeper_route.php'));
    }
}


