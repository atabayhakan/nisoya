<?php

namespace App\Filament\Resources\FootballVenues;

use App\Filament\Resources\FootballVenues\Pages\ListFootballVenues;
use App\Models\FootballVenue;
use BackedEnum;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FootballVenueResource extends Resource
{
    protected static ?string $model = FootballVenue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    public static function getNavigationGroup(): ?string
    {
        return '⚽ Halı Saha & Spor';
    }

    protected static ?string $navigationLabel = 'Halı Sahalar';

    protected static ?string $modelLabel = 'halı saha';

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
                TextColumn::make('name')
                    ->label('Saha Adı')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->label('Şehir')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pitch_type')
                    ->label('Saha Tipi')
                    ->formatStateUsing(fn ($state) => FootballVenue::PITCH_TYPES[$state] ?? $state),
                TextColumn::make('surface_type')
                    ->label('Zemin')
                    ->formatStateUsing(fn ($state) => FootballVenue::SURFACE_TYPES[$state] ?? $state),
                TextColumn::make('rating')
                    ->label('Puan')
                    ->formatStateUsing(fn ($state) => '⭐ '.number_format((float) $state, 1))
                    ->sortable(),
                TextColumn::make('reviews_count')
                    ->label('Yorum')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->defaultSort('rating', 'desc')
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFootballVenues::route('/'),
        ];
    }
}
