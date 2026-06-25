<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Older MySQL/MariaDB versions cap index keys at 1000 bytes; utf8mb4 uses 4 bytes
        // per character, so VARCHAR(255) = 1020 bytes overflows it. 191 × 4 = 764 bytes.
        Schema::defaultStringLength(191);

        RateLimiter::for('chat', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
