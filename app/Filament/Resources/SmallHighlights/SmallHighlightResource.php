<?php

namespace App\Filament\Resources\SmallHighlights;

use App\Filament\Resources\SmallHighlights\Pages\CreateSmallHighlight;
use App\Filament\Resources\SmallHighlights\Pages\EditSmallHighlight;
use App\Filament\Resources\SmallHighlights\Pages\ListSmallHighlights;
use App\Filament\Support\HomeHighlightResourceBase;
use App\Models\HomeHighlight;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Ana sayfadaki küçük (1x1) kartın dönen mesajları. */
class SmallHighlightResource extends HomeHighlightResourceBase
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Site Yönetimi';

    protected static ?string $navigationLabel = 'Ana Sayfa — Küçük Kart';

    protected static ?int $navigationSort = 6;

    protected static function slot(): string
    {
        return HomeHighlight::SLOT_SMALL;
    }

    public static function getModelLabel(): string
    {
        return 'küçük kart mesajı';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Ana Sayfa — Küçük Kart Mesajları';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmallHighlights::route('/'),
            'create' => CreateSmallHighlight::route('/create'),
            'edit' => EditSmallHighlight::route('/{record}/edit'),
        ];
    }
}
