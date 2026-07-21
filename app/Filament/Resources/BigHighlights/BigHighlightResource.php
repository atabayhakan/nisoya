<?php

namespace App\Filament\Resources\BigHighlights;

use App\Filament\Resources\BigHighlights\Pages\CreateBigHighlight;
use App\Filament\Resources\BigHighlights\Pages\EditBigHighlight;
use App\Filament\Resources\BigHighlights\Pages\ListBigHighlights;
use App\Filament\Support\HomeHighlightResourceBase;
use App\Models\HomeHighlight;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Ana sayfadaki büyük (2x2) öne çıkan kartın dönen mesajları. */
class BigHighlightResource extends HomeHighlightResourceBase
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik & Tasarım (CMS)';

    protected static ?string $navigationLabel = 'Ana Sayfa — Büyük Kart';

    protected static ?int $navigationSort = 5;

    protected static function slot(): string
    {
        return HomeHighlight::SLOT_BIG;
    }

    public static function getModelLabel(): string
    {
        return 'büyük kart mesajı';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Ana Sayfa — Büyük Kart Mesajları';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBigHighlights::route('/'),
            'create' => CreateBigHighlight::route('/create'),
            'edit' => EditBigHighlight::route('/{record}/edit'),
        ];
    }
}
