<?php

use App\Http\Middleware\EnsureUserCanAccessAdminPanel;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HoneypotMiddleware;
use App\Http\Middleware\PerformanceMetricsMiddleware;
use App\Http\Middleware\QueryLogMiddleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            EnsureUserIsActive::class,
            PerformanceMetricsMiddleware::class,
            QueryLogMiddleware::class,
        ]);

        $middleware->alias([
            'active.user' => EnsureUserIsActive::class,
            'honeypot' => HoneypotMiddleware::class,
            'admin.role' => EnsureUserCanAccessAdminPanel::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })
    ->booted(function () {
        // Hassas işlemler için rate limit policy'leri
        RateLimiter::for('listing-create', fn (Request $request) => Limit::perMinute(12)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('listing-feature', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('job-listing-feature', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('search-save', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('job-search-save', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('favorite-toggle', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('job-bookmark-toggle', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('portfolio-store', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('company-gallery-store', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('message-send', fn (Request $request) => Limit::perMinute(40)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('message-start', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('review-store', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('company-review-store', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('report-store', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('story-store', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('job-create', fn (Request $request) => Limit::perMinute(12)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('job-apply', fn (Request $request) => Limit::perMinute(15)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->input('email').$request->ip())
        );

        RateLimiter::for('verification', fn (Request $request) => Limit::perMinute(6)->by($request->user()?->id ?: $request->ip())
        );

        // Reverse geocoding (Nominatim 1 req/s rate limit'i):
        // Admin başına dakikada max 60 işlem — yeterli 1000+ görsel için.
        RateLimiter::for('reverse-geocode', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        );

        // === Admin API & health endpoint rate limit'leri (adım 9) ===
        // Public /health (uptime monitor'ler için) — IP başına 60/dk.
        // UptimeRobot her 5 dakikada bir ping atar → 12 istek/saat → limit rahat.
        RateLimiter::for('health-basic', fn (Request $request) => Limit::perMinute(60)->by($request->ip())
        );

        // Admin /health/detailed — hassas sistem bilgisi, admin başına 30/dk.
        RateLimiter::for('health-detailed', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip())
        );

        // Admin EXIF harita API'leri (gorseller/cluster/istatistik) — 60/dk.
        // Çok büyük dataset için rate limit gerekli (1000+ marker'ın scrape'i).
        RateLimiter::for('exif-map', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        );
    })
    ->create();
