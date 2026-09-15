<?php

namespace App\Providers;

use App\Http\Middleware\ResolveAdminGeoContext;
use App\Support\GlobalCommand\GeoContext;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class GlobalCommandServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(GeoContext::class, fn () => GeoContext::global());
    }

    public function boot(): void
    {
        // Re-applies only when the original panel route had this middleware.
        Livewire::addPersistentMiddleware([ResolveAdminGeoContext::class]);
    }
}
