<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        RateLimiter::for('auth', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('uploads', function ($request) {
            return Limit::perMinute(30)->by(optional($request->user())->id ?: $request->ip());
        });

        Route::fallback(function () {
            $candidates = [
                public_path('build/index.html'),
                base_path('frontend/index.html'),
            ];

            foreach ($candidates as $index) {
                if (file_exists($index)) {
                    return response()->file($index);
                }
            }

            abort(404);
        });
    }
}
