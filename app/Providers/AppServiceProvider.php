<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\RazorpayService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton(RazorpayService::class, function ($app) {
            return new RazorpayService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();
        Schema::defaultStringLength(191);

        // Most timestamp models in this project (PublicHoliday,
        // ResortNotification, Incidents, Announcement, etc.) define
        // getCreatedAtAttribute / getUpdatedAtAttribute accessors that
        // FORMAT the value into the resort's display format
        // (e.g. "27/11/2025 21:49" — d/m/Y H:i). Carbon::parse can't read
        // d/m/Y, so blade code that does `Carbon::parse($x->created_at)`
        // crashes the entire view.
        //
        // `Carbon::flexible($value)` accepts:
        //   - an existing Carbon instance (returned as-is)
        //   - a raw DB timestamp ("Y-m-d H:i:s")
        //   - a formatted string in any of the common display formats
        // and falls back gracefully to "now()" rather than throwing.
        if (!Carbon::hasMacro('flexible')) {
            Carbon::macro('flexible', function ($value) {
                if ($value instanceof \DateTimeInterface) {
                    return Carbon::instance($value);
                }
                if ($value === null || $value === '') {
                    return null;
                }
                $value = (string) $value;

                // Cheap path: native Carbon::parse handles ISO/Y-m-d/etc.
                try {
                    return Carbon::parse($value);
                } catch (\Throwable $e) {
                    // fall through to format-by-format attempts below
                }

                // Formats observed in the codebase's accessor output.
                $formats = [
                    'd/m/Y H:i',
                    'd/m/Y H:i:s',
                    'd/m/Y h:i A',
                    'd/m/Y g:i A',
                    'd-m-Y H:i',
                    'd-m-Y h:i A',
                    'd/m/Y',
                    'd-m-Y',
                    'd M Y H:i',
                    'd M Y',
                    'm/d/Y H:i',
                    'm/d/Y',
                ];
                foreach ($formats as $fmt) {
                    try {
                        $c = Carbon::createFromFormat($fmt, $value);
                        if ($c) return $c;
                    } catch (\Throwable $e) {
                        // try next format
                    }
                }
                \Illuminate\Support\Facades\Log::warning("Carbon::flexible could not parse '{$value}'");
                return null;
            });
        }

    }
}
