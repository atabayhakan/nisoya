<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        $middleware->alias([
            'active.user' => \App\Http\Middleware\EnsureUserIsActive::class,
            'honeypot' => \App\Http\Middleware\HoneypotMiddleware::class,
            'admin.role' => \App\Http\Middleware\EnsureUserCanAccessAdminPanel::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })
    ->booted(function () {
        // Hassas işlemler için rate limit policy'leri
        RateLimiter::for('listing-create', fn (Request $request) =>
            Limit::perMinute(12)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('listing-feature', fn (Request $request) =>
            Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('search-save', fn (Request $request) =>
            Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('favorite-toggle', fn (Request $request) =>
            Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('message-send', fn (Request $request) =>
            Limit::perMinute(40)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('message-start', fn (Request $request) =>
            Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('review-store', fn (Request $request) =>
            Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('report-store', fn (Request $request) =>
            Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('register', fn (Request $request) =>
            Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for('login', fn (Request $request) =>
            Limit::perMinute(5)->by($request->input('email').$request->ip())
        );

        RateLimiter::for('verification', fn (Request $request) =>
            Limit::perMinute(6)->by($request->user()?->id ?: $request->ip())
        );
    })
    ->create();
