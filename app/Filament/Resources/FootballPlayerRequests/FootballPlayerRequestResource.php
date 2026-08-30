<?php

namespace App\Filament\Resources\FootballPlayerRequests;

use App\Enums\FootballRequestType;
use App\Filament\Resources\FootballPlayerRequests\Pages\ListFootballPlayerRequests;
use App\Models\FootballPlayerRequest;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
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

class FootballPlayerRequestResource extends Resource
{
    protected static ?string $model = FootballPlayerRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function getNavigationGroup(): ?string
    {
        return 'Topluluk & İletişim';
    }

    protected static ?string $navigationLabel = 'Oyuncu / Maç İlanları';

    protected static ?string $modelLabel = 'futbol ilanı';

    protected static ?string $pluralModelLabel = 'Futbol İlanları';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('user_id')
                ->label('İlan Sahibi')
                ->relationship('user', 'name')
                ->required(),
            Select::make('type')
                ->label('İlan Türü')
                ->options(FootballRequestType::class)
                ->required(),
            TextInput::make('city')
                ->label('Şehir')
                ->required(),
            TextInput::make('needed_count')
                ->label('Eksik Sayısı')
                ->numeric(),
            DateTimePicker::make('match_time')
                ->label('Maç Saati'),
            TextInput::make('venue_name')
                ->label('Saha / Konum'),
            Textarea::make('description')
                ->label('Açıklama')
                ->required()
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->label('Yayında'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Kullanıcı')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tür')
                    ->badge(),
                TextColumn::make('city')
                    ->label('Şehir')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('needed_count')
                    ->label('Eksik')
                    ->sortable(),
                TextColumn::make('match_time')
                    ->label('Maç Saati')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Yayında')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('İlan Türü')
                    ->options(FootballRequestType::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFootballPlayerRequests::route('/'),
        ];
    }
}
