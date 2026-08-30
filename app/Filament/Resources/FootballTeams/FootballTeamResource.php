<?php

namespace App\Filament\Resources\FootballTeams;

use App\Enums\FootballLevel;
use App\Filament\Resources\FootballTeams\Pages\ListFootballTeams;
use App\Models\FootballTeam;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FootballTeamResource extends Resource
{
    protected static ?string $model = FootballTeam::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    public static function getNavigationGroup(): ?string
    {
        return 'Topluluk & İletişim';
    }

    protected static ?string $navigationLabel = 'Takımlar';

    protected static ?string $modelLabel = 'futbol takımı';

    protected static ?string $pluralModelLabel = 'Futbol Takımları';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Takım Adı')
                ->required()
                ->maxLength(60),
            Select::make('user_id')
                ->label('Kaptan')
                ->relationship('captain', 'name')
                ->searchable()
                ->required(),
            TextInput::make('city')
                ->label('Şehir')
                ->required(),
            TextInput::make('country_code')
                ->label('Ülke Kodu')
                ->required()
                ->maxLength(2),
            Select::make('level')
                ->label('Seviye')
                ->options(FootballLevel::class)
                ->required(),
            TextInput::make('primary_kit_color')
                ->label('Forma Rengi'),
            FileUpload::make('logo_path')
                ->label('Logo')
                ->image()
                ->disk('public')
                ->directory('football/teams'),
            Textarea::make('description')
                ->label('Açıklama')
                ->columnSpanFull(),
            Toggle::make('is_verified')
                ->label('Doğrulanmış Takım Rozeti'),
            Toggle::make('is_active')
                ->label('Aktif'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Takım Adı')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->label('Şehir')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('captain.name')
                    ->label('Kaptan')
                    ->searchable(),
                TextColumn::make('level')
                    ->label('Seviye')
                    ->badge(),
                TextColumn::make('points')
                    ->label('Puan')
                    ->sortable(),
                TextColumn::make('matches_count')
                    ->label('Maç')
                    ->sortable(),
                IconColumn::make('is_verified')
                    ->label('Doğrulandı')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label('Seviye')
                    ->options(FootballLevel::class),
            ])
            ->defaultSort('points', 'desc')
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFootballTeams::route('/'),
        ];
    }
}
