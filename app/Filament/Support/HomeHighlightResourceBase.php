<?php

namespace App\Filament\Support;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Models\HomeHighlight;
use App\Support\HighlightIcon;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * BigHighlightResource + SmallHighlightResource'ın paylaştığı form/tablo
 * mantığı (bkz. docs/plans/2026-07-17-anasayfa-vurgu-slider-design.md).
 * Kasıtlı olarak `app/Filament/Resources/` DIŞINDA — Filament panel
 * sağlayıcısı sadece o dizini keşfediyor (bkz. AdminPanelProvider), bu
 * soyut sınıf otomatik keşif tarafından bir kaynak olarak algılanmasın diye
 * burada duruyor (ContentBlocks.php ile aynı "paylaşılan Filament mantığı"
 * konumu).
 */
abstract class HomeHighlightResourceBase extends Resource
{
    // Ana sayfa vurgu slider'ları site konfigürasyonudur → yalnızca Admin.
    use RestrictsToAdmins;

    protected static ?string $model = HomeHighlight::class;

    abstract protected static function slot(): string;

    /** Alt sınıflar (Create sayfaları) yeni kayda hangi slot'u yazacağını buradan alır. */
    public static function slotValue(): string
    {
        return static::slot();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('slot', static::slot());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Başlık')
                ->required()
                ->maxLength(60),
            TextInput::make('text')
                ->label('Metin')
                ->required()
                ->maxLength(160),
            Select::make('icon')
                ->label('İkon')
                ->options(HighlightIcon::OPTIONS)
                ->native(false)
                ->required(),
            Toggle::make('is_active')
                ->label('Aktif (kartta göster)')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('Sıra')->sortable(),
                TextColumn::make('title')->label('Başlık')->searchable(),
                TextColumn::make('text')->label('Metin')->limit(50),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
