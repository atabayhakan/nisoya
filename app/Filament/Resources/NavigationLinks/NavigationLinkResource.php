<?php

namespace App\Filament\Resources\NavigationLinks;

use App\Filament\Resources\NavigationLinks\Pages\CreateNavigationLink;
use App\Filament\Resources\NavigationLinks\Pages\EditNavigationLink;
use App\Filament\Resources\NavigationLinks\Pages\ListNavigationLinks;
use App\Models\NavigationLink;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Header'daki gezinme menüsü (bkz. App\Models\NavigationLink). Admin serbestçe
 * yeni link ekleyip/silebilir, sürükle-bırak ile sıralayabilir — Zone'un
 * aksine sabit bir kod-anchor'ına bağlı değildir.
 */
class NavigationLinkResource extends Resource
{
    protected static ?string $model = NavigationLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|UnitEnum|null $navigationGroup = 'Site Yönetimi';

    protected static ?string $navigationLabel = 'Menü (Header)';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'menü linki';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Menü Linkleri';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')
                ->label('Etiket')
                ->required()
                ->maxLength(50),
            TextInput::make('url')
                ->label('Bağlantı')
                ->required()
                ->maxLength(255)
                ->helperText('Site içi göreli yol (ör. /ilanlar) ya da tam URL (ör. https://...).'),
            Toggle::make('is_active')
                ->label('Aktif (menüde göster)')
                ->default(true),
            Toggle::make('opens_new_tab')
                ->label('Yeni sekmede aç')
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('Sıra')->sortable(),
                TextColumn::make('label')->label('Etiket')->searchable(),
                TextColumn::make('url')->label('Bağlantı')->limit(40),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                IconColumn::make('opens_new_tab')->label('Yeni sekme')->boolean(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNavigationLinks::route('/'),
            'create' => CreateNavigationLink::route('/create'),
            'edit' => EditNavigationLink::route('/{record}/edit'),
        ];
    }
}
