<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Enums\PageStatus;
use App\Models\Page;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label('Başlık')
                    ->weight('medium')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Adres')
                    ->formatStateUsing(fn (string $state) => '/'.$state)
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge(),
                TextColumn::make('publish_at')
                    ->label('Zamanlanan yayın')
                    ->badge()
                    ->color('warning')
                    ->placeholder('—')
                    ->state(fn (Page $record): ?string => $record->isScheduled()
                        ? $record->publish_at?->format('d.m.Y H:i')
                        : null),
                IconColumn::make('show_in_footer')
                    ->label('Footer')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Güncelleme')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(PageStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
