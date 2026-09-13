<?php

namespace App\Filament\Resources\Deals;

use App\Enums\DealStatus;
use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\Deals\Pages\ListDeals;
use App\Filament\Resources\Deals\Tables\DealsTable;
use App\Filament\Resources\Deals\Widgets\DealStatsWidget;
use App\Models\Deal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Anlaşmalar (K-C) — salt-görüntüleme. Özellikle "Sorun bildirildi" (sorunlu)
 * anlaşmaları görüp taraflara aksiyon almak için. İşlem/tutar verisi içerdiği
 * için Admin'e kilitli (moderatöre kapalı, bkz. RestrictsToAdmins).
 */
class DealResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = Deal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'Pazaryeri & Ticaret';
    }

    public static function getNavigationLabel(): string
    {
        return 'Anlaşmalar';
    }

    public static function getModelLabel(): string
    {
        return 'Anlaşma';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Anlaşmalar';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Deal::query()->where('status', DealStatus::Sorunlu)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return DealsTable::configure($table);
    }

    public static function getWidgets(): array
    {
        return [
            DealStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeals::route('/'),
        ];
    }
}
