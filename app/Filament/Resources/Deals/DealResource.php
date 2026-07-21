<?php

namespace App\Filament\Resources\Deals;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\Deals\Pages\ListDeals;
use App\Filament\Resources\Deals\Tables\DealsTable;
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

    public static function getNavigationGroup(): ?string
    {
        return 'Pazaryeri & Ticaret';
    }

    protected static ?string $navigationLabel = 'Anlaşmalar';

    protected static ?string $modelLabel = 'anlaşma';

    protected static ?string $pluralModelLabel = 'Anlaşmalar';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return DealsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeals::route('/'),
        ];
    }
}
