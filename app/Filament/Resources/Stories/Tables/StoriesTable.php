<?php

namespace App\Filament\Resources\Stories\Tables;

use App\Enums\StoryStatus;
use App\Models\Story;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Yazan (sadece moderasyon için)')
                    ->searchable(),
                TextColumn::make('body')
                    ->label('Hikaye')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(StoryStatus::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('onayla')
                    ->label('Onayla')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Story $record) => $record->status !== StoryStatus::Onaylandi)
                    ->action(fn (Story $record) => $record->update(['status' => StoryStatus::Onaylandi])),
                Action::make('reddet')
                    ->label('Reddet')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Story $record) => $record->status !== StoryStatus::Reddedildi)
                    ->action(fn (Story $record) => $record->update(['status' => StoryStatus::Reddedildi])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
