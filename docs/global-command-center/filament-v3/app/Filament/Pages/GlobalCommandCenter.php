<?php

namespace App\Filament\Pages;

use App\Enums\UserStatus;
use App\Filament\Widgets\CountryLiquidityWidget;
use Filament\Pages\Page;

class GlobalCommandCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Pazarlama & Büyüme';

    protected static ?string $navigationLabel = 'Küresel Operasyon Merkezi';

    protected static ?string $title = 'Küresel Operasyon Merkezi';

    protected static ?string $slug = 'global-command-center';

    protected static string $view = 'filament.pages.global-command-center';

    public static function canAccess(): bool
    {
        return (bool) config('global-command.enabled') && (auth()->user()?->isAdmin() ?? false)
            && auth()->user()->status === UserStatus::Aktif;
    }

    protected function getHeaderWidgets(): array
    {
        return [CountryLiquidityWidget::class];
    }
}
