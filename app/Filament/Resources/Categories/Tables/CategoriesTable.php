<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('icon')
                    ->label(''),
                TextColumn::make('name')
                    ->label('Ad')
                    ->weight('medium')
                    ->description(fn ($record) => $record->parent?->name)
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tür')
                    ->badge(),
                TextColumn::make('listings_count')
                    ->label('İlan')
                    ->counts('listings')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktiflik'),
                SelectFilter::make('parent_id')
                    ->label('Üst kategori')
                    ->relationship('parent', 'name'),
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
