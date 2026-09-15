<?php

namespace App\Filament\Concerns;

use App\Support\GlobalCommand\GeoContext;
use Illuminate\Database\Eloquent\Builder;

trait UsesAdminGeoContext
{
    public static function getEloquentQuery(): Builder
    {
        return app(GeoContext::class)->apply(parent::getEloquentQuery());
    }
}
