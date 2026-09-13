<?php

namespace App\Filament\Resources\FootballTeams;

use App\Enums\FootballLevel;
use App\Filament\Resources\FootballTeams\Pages\ListFootballTeams;
use App\Filament\Resources\FootballTeams\Widgets\FootballTeamStatsWidget;
use App\Models\FootballTeam;
use App\Services\Football\FootballCrestGeneratorService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
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

    protected static ?string $modelLabel = 'Futbol Takımı';

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
            TextInput::make('secondary_kit_color')
                ->label('İkincil Forma Rengi'),
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
                ImageColumn::make('logo_path')
                    ->label('Arma')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&background=090d16&color=f59e0b'),
                TextColumn::make('name')
                    ->label('Takım Adı')
                    ->searchable()
                    ->sortable()
                    ->description(fn (FootballTeam $record): ?string => $record->primary_kit_color ? "Kit: {$record->primary_kit_color} / {$record->secondary_kit_color}" : null),
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
            ->recordActions([
                Action::make('aiLogoUret')
                    ->label('AI Arma Üret')
                    ->icon('heroicon-o-sparkles')
                    ->color('warning')
                    ->form([
                        Select::make('symbol')
                            ->label('Maskot / Sembol')
                            ->options(FootballCrestGeneratorService::getAvailableSymbols())
                            ->default('kartal')
                            ->required(),
                        Select::make('style')
                            ->label('Kalkan Stili')
                            ->options(FootballCrestGeneratorService::getAvailableStyles())
                            ->default('klasik_kalkan')
                            ->required(),
                    ])
                    ->action(function (FootballTeam $record, array $data): void {
                        $generator = app(FootballCrestGeneratorService::class);
                        $res = $generator->generateAndStore(
                            teamName: $record->name,
                            city: $record->city,
                            primaryColor: $record->primary_kit_color,
                            secondaryColor: $record->secondary_kit_color,
                            symbol: $data['symbol'] ?? 'kartal',
                            style: $data['style'] ?? 'klasik_kalkan',
                        );

                        $record->update(['logo_path' => $res['path']]);

                        Notification::make()
                            ->title('AI Kulüp Arması Üretildi')
                            ->body("{$record->name} için EA FC tarzı kalkan arması oluşturuldu.")
                            ->success()
                            ->send();
                    }),
                Action::make('dogrulaToggle')
                    ->label(fn (FootballTeam $record): string => $record->is_verified ? 'Doğrulamayı Kaldır' : 'Doğrula')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->action(function (FootballTeam $record): void {
                        $record->update(['is_verified' => ! $record->is_verified]);
                    }),
            ])
            ->defaultSort('points', 'desc')
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            FootballTeamStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFootballTeams::route('/'),
        ];
    }
}
