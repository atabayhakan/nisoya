<?php

namespace App\Filament\Concerns;

use App\Support\GlobalCommand\GeoContext;
use Livewire\Attributes\Locked;

trait GuardsAdminGeoContext
{
    #[Locked]
    public string $adminGeoContextKey = '';

    public function mountGuardsAdminGeoContext(): void
    {
        $this->adminGeoContextKey = app(GeoContext::class)->key();
    }

    public function hydrateGuardsAdminGeoContext(): void
    {
        abort_unless($this->adminGeoContextKey === app(GeoContext::class)->key(), 409,
            'Coğrafi görünüm başka bir sekmede değişti. İşleme devam etmeden sayfayı yenileyin.');
    }
}
