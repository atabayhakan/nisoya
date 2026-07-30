<?php

namespace App\Filament\Resources\BekleyenHamleler;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\BekleyenHamleler\Pages\ListBekleyenHamleler;
use App\Filament\Resources\BekleyenHamleler\Tables\BekleyenHamlelerTable;
use App\Models\BekleyenHamle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Hamle kartları — dış-eylem onay kuyruğunun panel yüzü (F2, tasarım §2.2).
 *
 * Kart YALNIZ Kâhya tarafından açılır (hamle-oner eylemi); bu ekranda
 * oluşturma/düzenleme yok — sahibin işi OKUyup KARAR vermek: Onayla ya da
 * Reddet, istersen not düş. Karar bir kez verilir ve Kâhya sonraki turunda
 * görür; F5 ders-cikar bu kararlardan öğrenecek.
 *
 * Karar bekleyen kart sayısı menü rozetinde durur — onay kapısının kuyruğu
 * sessizce birikmemeli.
 */
class BekleyenHamlelerResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = BekleyenHamle::class;

    protected static ?string $slug = 'bekleyen-hamleler';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|UnitEnum|null $navigationGroup = 'Kâhya & Yapay Zekâ';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return 'Bekleyen Hamleler';
    }

    public static function getModelLabel(): string
    {
        return 'hamle';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Bekleyen Hamleler';
    }

    public static function getNavigationBadge(): ?string
    {
        $bekleyen = BekleyenHamle::query()->beklemede()->count();

        return $bekleyen > 0 ? (string) $bekleyen : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return BekleyenHamlelerTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBekleyenHamleler::route('/'),
        ];
    }
}
