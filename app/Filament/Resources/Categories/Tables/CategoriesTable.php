<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Enums\CategoryType;
use App\Filament\Resources\Listings\ListingResource;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('icon')
                    ->label('')
                    ->formatStateUsing(fn (?string $state): string => $state ?: '📁')
                    ->alignCenter(),

                TextColumn::make('name')
                    ->label('Kategori Adı')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(function (Category $record): string {
                        if ($record->parent instanceof Category) {
                            return '↳ '.$record->parent->name;
                        }
                        $subCount = $record->children()->count();

                        return $subCount > 0 ? "🌐 Ana Kategori ({$subCount} alt kategori)" : '🌐 Ana Kategori';
                    }),

                TextColumn::make('slug')
                    ->label('Kısa Ad (URL)')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('type')
                    ->label('Tür')
                    ->badge()
                    ->color(fn (CategoryType $state): string => match ($state) {
                        CategoryType::Hizmet => 'info',
                        CategoryType::Urun => 'success',
                        CategoryType::Ikisi => 'primary',
                        CategoryType::Emlak => 'warning',
                        CategoryType::Vasita => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('listings_count')
                    ->label('İlan Sayısı')
                    ->counts('listings')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn (int $state): string => "{$state} ilan")
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Üst Kategori')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('type')
                    ->label('Tür')
                    ->options(CategoryType::class),

                TernaryFilter::make('is_active')
                    ->label('Aktiflik'),

                TernaryFilter::make('has_listings')
                    ->label('İlan Varlığı')
                    ->placeholder('Tümü')
                    ->trueLabel('İlanı Olanlar')
                    ->falseLabel('Boş Olanlar (0 İlan)')
                    ->queries(
                        true: fn (Builder $q) => $q->has('listings'),
                        false: fn (Builder $q) => $q->doesntHave('listings'),
                        blank: fn (Builder $q) => $q,
                    ),
            ])
            ->recordActions([
                Action::make('ilanlariGor')
                    ->label('İlanları Gör')
                    ->icon(Heroicon::OutlinedShoppingBag)
                    ->color('gray')
                    ->url(fn (Category $record): string => ListingResource::getUrl('index', ['tableFilters[category_id][value]' => $record->id]))
                    ->visible(fn (Category $record): bool => $record->listings()->count() > 0),

                Action::make('durumDegistir')
                    ->label(fn (Category $record): string => $record->is_active ? 'Pasife Al' : 'Aktif Et')
                    ->icon(fn (Category $record): BackedEnum => $record->is_active ? Heroicon::OutlinedPauseCircle : Heroicon::OutlinedCheckCircle)
                    ->color(fn (Category $record): string => $record->is_active ? 'gray' : 'success')
                    ->action(function (Category $record): void {
                        $record->is_active = ! $record->is_active;
                        $record->save();
                        Notification::make()
                            ->title($record->is_active ? 'Kategori aktif edildi' : 'Kategori pasife alındı')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('topluAktifEt')
                        ->label('Seçilenleri Aktif Yap')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record instanceof Category) {
                                    $record->is_active = true;
                                    $record->save();
                                    $count++;
                                }
                            }
                            Notification::make()->title("{$count} kategori aktif edildi.")->success()->send();
                        }),

                    BulkAction::make('topluPasifEt')
                        ->label('Seçilenleri Pasife Al')
                        ->icon(Heroicon::OutlinedPauseCircle)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record instanceof Category) {
                                    $record->is_active = false;
                                    $record->save();
                                    $count++;
                                }
                            }
                            Notification::make()->title("{$count} kategori pasife alındı.")->info()->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Henüz kategori bulunamadı')
            ->emptyStateDescription('Arama veya filtre kriterlerinize uygun kategori bulunmuyor.')
            ->emptyStateIcon(Heroicon::OutlinedFolder);
    }
}
