<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\GlobalCommandServiceProvider;

return [
    AppServiceProvider::class,
    GlobalCommandServiceProvider::class,
    AdminPanelProvider::class,
];
