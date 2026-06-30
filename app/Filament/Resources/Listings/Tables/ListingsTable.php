<?php

namespace App\Filament\Resources\Listings\Tables;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ListingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Başlık')
                    ->weight('medium')
                    ->description(fn ($record) => $record->user?->name)
                    ->searchable()
                    ->limit(40),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('type')
                    ->label('Tür')
                    ->badge(),
                TextColumn::make('price')
                    ->label('Fiyat')
                    ->formatStateUsing(fn ($state, $record) => $state !== null
                        ? number_format((float) $state, 2).' '.$record->currency
                        : 'Görüşülür')
                    ->sortable(),
                TextColumn::make('country_code')
                    ->label('Ülke')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge(),
                IconColumn::make('is_featured')
                    ->label('Öne çıkan')
                    ->boolean(),
                TextColumn::make('views_count')
                    ->label('Görüntülenme')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(ListingStatus::class),
                SelectFilter::make('type')
                    ->label('Tür')
                    ->options(ListingType::class),
                TernaryFilter::make('is_featured')
                    ->label('Öne çıkan'),
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
