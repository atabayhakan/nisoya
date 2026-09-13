<?php

namespace App\Filament\Resources\FootballVenues;

use App\Filament\Resources\FootballVenues\Pages\ListFootballVenues;
use App\Filament\Resources\FootballVenues\Widgets\FootballVenueStatsWidget;
use App\Models\FootballVenue;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FootballVenueResource extends Resource
{
    protected static ?string $model = FootballVenue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    public static function getNavigationGroup(): ?string
    {
        return 'Topluluk & İletişim';
    }

    protected static ?string $navigationLabel = 'Halı Sahalar';

    protected static ?string $modelLabel = 'Halı Saha';

    protected static ?string $pluralModelLabel = 'Halı Sahalar';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Saha Adı')
                ->required(),
            TextInput::make('city')
                ->label('Şehir')
                ->required(),
            TextInput::make('country_code')
                ->label('Ülke Kodu')
                ->required()
                ->maxLength(2),
            TextInput::make('address')
                ->label('Adres')
                ->required(),
            TextInput::make('phone')
                ->label('Telefon'),
            TextInput::make('price_info')
                ->label('Fiyat Bilgisi'),
            TextInput::make('latitude')
                ->label('Enlem (Latitude)')
                ->numeric(),
            TextInput::make('longitude')
                ->label('Boylam (Longitude)')
                ->numeric(),
            Select::make('pitch_type')
                ->label('Saha Tipi')
                ->options(FootballVenue::PITCH_TYPES)
                ->required(),
            Select::make('surface_type')
                ->label('Zemin Türü')
                ->options(FootballVenue::SURFACE_TYPES)
                ->required(),
            FileUpload::make('cover_image_path')
                ->label('Fotoğraf')
                ->image()
                ->disk('public')
                ->directory('football/venues'),
            Toggle::make('is_active')
                ->label('Aktif'),
            Toggle::make('is_verified')
                ->label('Doğrulanmış Tesis'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image_path')
                    ->label('Görsel')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('name')
                    ->label('Saha Adı')
                    ->searchable()
                    ->sortable()
                    ->description(fn (FootballVenue $record): string => $record->address),
                TextColumn::make('city')
                    ->label('Şehir')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pitch_type')
                    ->label('Saha Tipi')
                    ->formatStateUsing(fn ($state) => FootballVenue::PITCH_TYPES[$state] ?? $state)
                    ->badge(),
                TextColumn::make('surface_type')
                    ->label('Zemin')
                    ->formatStateUsing(fn ($state) => FootballVenue::SURFACE_TYPES[$state] ?? $state)
                    ->badge(),
                TextColumn::make('rating')
                    ->label('Puan')
                    ->formatStateUsing(fn ($state) => '⭐ '.number_format((float) $state, 1))
                    ->sortable(),
                IconColumn::make('has_gps')
                    ->label('GPS')
                    ->state(fn (FootballVenue $record): bool => ! empty($record->latitude) && ! empty($record->longitude))
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('pitch_type')
                    ->label('Saha Tipi')
                    ->options(FootballVenue::PITCH_TYPES),
                SelectFilter::make('surface_type')
                    ->label('Zemin Türü')
                    ->options(FootballVenue::SURFACE_TYPES),
            ])
            ->recordActions([
                Action::make('googleMaps')
                    ->label('Google Rota')
                    ->icon('heroicon-o-map')
                    ->color('info')
                    ->url(fn (FootballVenue $record): ?string => $record->getGoogleMapsUrl())
                    ->openUrlInNewTab(),
                Action::make('yandexMaps')
                    ->label('Yandex')
                    ->icon('heroicon-o-map-pin')
                    ->color('warning')
                    ->url(fn (FootballVenue $record): ?string => $record->getYandexMapsUrl())
                    ->openUrlInNewTab(),
                Action::make('dogrulaToggle')
                    ->label(fn (FootballVenue $record): string => $record->is_verified ? 'Doğrulamayı Kaldır' : 'Doğrula')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->action(function (FootballVenue $record): void {
                        $record->update(['is_verified' => ! $record->is_verified]);
                    }),
            ])
            ->defaultSort('rating', 'desc')
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            FootballVenueStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFootballVenues::route('/'),
        ];
    }
}
