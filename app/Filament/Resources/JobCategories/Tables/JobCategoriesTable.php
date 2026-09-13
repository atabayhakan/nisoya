<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobCategories\Tables;

use App\Filament\Resources\JobCategories\JobCategoryResource;
use App\Filament\Resources\JobListings\JobListingResource;
use App\Models\JobCategory;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class JobCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                IconColumn::make('icon')
                    ->label('Simge')
                    ->icon(fn (?string $state): string => filled($state) ? "heroicon-o-{$state}" : 'heroicon-o-rectangle-group')
                    ->color('primary'),

                TextColumn::make('name')
                    ->label('Kategori / Sektör')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (JobCategory $record): string => "nisoya.com/isler?kategori={$record->slug}"),

                TextColumn::make('job_listings_count')
                    ->label('İş İlanı')
                    ->counts('jobListings')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-m-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Kayıt')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktiflik Durumu')
                    ->trueLabel('Yalnızca Aktifler')
                    ->falseLabel('Pasif / Gizli'),

                TernaryFilter::make('has_jobs')
                    ->label('İlan Durumu')
                    ->queries(
                        true: fn (Builder $q) => $q->has('jobListings'),
                        false: fn (Builder $q) => $q->doesntHave('jobListings'),
                    ),
            ])
            ->actions([
                Action::make('ilanlariGor')
                    ->label('İlanlar')
                    ->icon(Heroicon::OutlinedBriefcase)
                    ->color('info')
                    ->url(fn (JobCategory $record): string => JobListingResource::getUrl('index', [
                        'tableFilters' => [
                            'job_category_id' => [
                                'value' => $record->id,
                            ],
                        ],
                    ])),

                Action::make('toggleActive')
                    ->label(fn (JobCategory $record): string => $record->is_active ? 'Pasife Al' : 'Aktife Al')
                    ->icon(fn (JobCategory $record): string => $record->is_active ? 'heroicon-m-x-circle' : 'heroicon-m-check-circle')
                    ->color(fn (JobCategory $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (JobCategory $record): string => $record->is_active ? "'{$record->name}' Kategorisini Gizle" : "'{$record->name}' Kategorisini Yayına Al")
                    ->modalDescription(fn (JobCategory $record): string => $record->is_active
                        ? 'Kategori sitedeki arama ve ilan verme filtrelerinden gizlenecektir. Devam edilsin mi?'
                        : 'Kategori sitedeki iş arama ve filtreleme listelerine eklenecektir.')
                    ->action(function (JobCategory $record): void {
                        $record->is_active = ! $record->is_active;
                        $record->save();

                        $msg = $record->is_active
                            ? "'{$record->name}' kategorisi yayına alındı."
                            : "'{$record->name}' kategorisi pasife alındı.";

                        Notification::make()->title($msg)->success()->send();
                    }),

                EditAction::make(),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('topluAktif')
                    ->label('Seçilenleri Yayına Al')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record instanceof JobCategory) {
                                $record->is_active = true;
                                $record->save();
                                $count++;
                            }
                        }
                        Notification::make()->title("{$count} kategori başarıyla yayına alındı.")->success()->send();
                    }),

                BulkAction::make('topluPasif')
                    ->label('Seçilenleri Pasife Al')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record instanceof JobCategory) {
                                $record->is_active = false;
                                $record->save();
                                $count++;
                            }
                        }
                        Notification::make()->title("{$count} kategori pasife alındı.")->warning()->send();
                    }),

                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Henüz İş Kategorisi Bulunmuyor')
            ->emptyStateDescription('İş ilanlarının sektörel olarak sınıflandırılması ve adayların aradıkları pozisyonlara hızla ulaşması için yeni bir iş kategorisi oluşturabilirsiniz.')
            ->emptyStateIcon(Heroicon::OutlinedRectangleGroup)
            ->emptyStateActions([
                Action::make('yeniKategori')
                    ->label('Yeni İş Kategorisi Oluştur')
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => JobCategoryResource::getUrl('create')),
            ]);
    }
}
