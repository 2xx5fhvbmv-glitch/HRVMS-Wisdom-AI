<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // No Broadcast::routes(): it registers GET|HEAD|POST /broadcasting/auth
        // with no guard checks. routes/web.php defines the only (POST) web auth
        // route with the admin/resort-admin rules; routes/api.php the mobile one.
        require base_path('routes/channels.php');
    }
}
