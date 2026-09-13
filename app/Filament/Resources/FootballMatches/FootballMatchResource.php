<?php

namespace App\Filament\Resources\FootballMatches;

use App\Enums\FootballMatchStatus;
use App\Enums\FootballResultStatus;
use App\Filament\Resources\FootballMatches\Pages\ListFootballMatches;
use App\Filament\Resources\FootballMatches\Widgets\FootballMatchStatsWidget;
use App\Models\FootballMatch;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FootballMatchResource extends Resource
{
    protected static ?string $model = FootballMatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function getNavigationGroup(): ?string
    {
        return 'Topluluk & İletişim';
    }

    protected static ?string $navigationLabel = 'Maçlar';

    protected static ?string $modelLabel = 'Halı Saha Maçı';

    protected static ?string $pluralModelLabel = 'Halı Saha Maçları';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('home_team_id')
                ->label('Ev Sahibi')
                ->relationship('homeTeam', 'name')
                ->required(),
            Select::make('away_team_id')
                ->label('Deplasman')
                ->relationship('awayTeam', 'name'),
            TextInput::make('city')
                ->label('Şehir')
                ->required(),
            DateTimePicker::make('match_date')
                ->label('Maç Tarihi')
                ->required(),
            TextInput::make('home_score')
                ->label('Ev Sahibi Skoru')
                ->numeric(),
            TextInput::make('away_score')
                ->label('Deplasman Skoru')
                ->numeric(),
            Select::make('result_status')
                ->label('Sonuç Durumu')
                ->options(FootballResultStatus::class)
                ->required(),
            Select::make('status')
                ->label('Maç Durumu')
                ->options(FootballMatchStatus::class)
                ->required(),
            Textarea::make('dispute_reason')
                ->label('İtiraz Sebebi')
                ->columnSpanFull(),
            Toggle::make('is_featured')
                ->label('Haftanın Maçı'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('homeTeam.name')
                    ->label('Ev Sahibi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('awayTeam.name')
                    ->label('Deplasman')
                    ->searchable()
                    ->placeholder('Açık Maç'),
                TextColumn::make('home_score')
                    ->label('Skor')
                    ->formatStateUsing(fn ($record) => $record->home_score !== null ? $record->home_score.' - '.$record->away_score : '—'),
                TextColumn::make('city')
                    ->label('Şehir')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('match_date')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('result_status')
                    ->label('Sonuç Durumu')
                    ->badge(),
                IconColumn::make('is_featured')
                    ->label('Öne Çıkan')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('result_status')
                    ->label('Sonuç Durumu')
                    ->options(FootballResultStatus::class),
                SelectFilter::make('status')
                    ->label('Maç Durumu')
                    ->options(FootballMatchStatus::class),
            ])
            ->recordActions([
                Action::make('skorOnayla')
                    ->label('Skoru Doğrula')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (FootballMatch $record): bool => in_array($record->result_status, [FootballResultStatus::Girildi, FootballResultStatus::Itiraz], true))
                    ->requiresConfirmation()
                    ->action(function (FootballMatch $record): void {
                        $record->update([
                            'result_status' => FootballResultStatus::Dogrulandi->value,
                            'status' => FootballMatchStatus::Oynandi->value,
                        ]);
                        Notification::make()
                            ->title('Skor Başarıyla Onaylandı')
                            ->success()
                            ->send();
                    }),
                Action::make('oneCikarToggle')
                    ->label(fn (FootballMatch $record): string => $record->is_featured ? 'Öne Çıkarmayı Kaldır' : 'Öne Çıkar')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->action(function (FootballMatch $record): void {
                        $record->update(['is_featured' => ! $record->is_featured]);
                    }),
            ])
            ->defaultSort('match_date', 'desc')
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            FootballMatchStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFootballMatches::route('/'),
        ];
    }
}
