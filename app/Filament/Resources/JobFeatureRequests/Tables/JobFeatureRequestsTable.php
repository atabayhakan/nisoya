<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobFeatureRequests\Tables;

use App\Enums\FeatureRequestStatus;
use App\Filament\Resources\JobFeatureRequests\JobFeatureRequestResource;
use App\Filament\Resources\JobListings\JobListingResource;
use App\Models\JobFeatureRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class JobFeatureRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('jobListing.title')
                    ->label('İş İlanı')
                    ->weight('bold')
                    ->icon('heroicon-m-briefcase')
                    ->description(function (JobFeatureRequest $record): string {
                        $company = $record->jobListing && $record->jobListing->company ? $record->jobListing->company->name : null;
                        $until = $record->jobListing && $record->jobListing->featured_until ? ('Vitrin: '.$record->jobListing->featured_until->format('d.m.Y')) : null;
                        if ($company && $until) {
                            return "{$company} • {$until}";
                        }
                        if ($company) {
                            return $company;
                        }
                        if ($until) {
                            return $until;
                        }

                        return 'İş İlanı Detayı';
                    })
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Talep Eden')
                    ->weight('bold')
                    ->icon('heroicon-m-user')
                    ->description(fn (JobFeatureRequest $record): ?string => $record->user ? $record->user->email : null)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('days')
                    ->label('Süre')
                    ->formatStateUsing(fn ($state): string => "{$state} Gün")
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-calendar-days')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->icon(fn (FeatureRequestStatus $state): string => match ($state) {
                        FeatureRequestStatus::Beklemede => 'heroicon-m-clock',
                        FeatureRequestStatus::Onaylandi => 'heroicon-m-check-circle',
                        FeatureRequestStatus::Reddedildi => 'heroicon-m-x-circle',
                    })
                    ->sortable(),

                TextColumn::make('processed_at')
                    ->label('İşlem Tarihi')
                    ->formatStateUsing(fn ($state) => $state ? $state->format('d.m.Y H:i') : 'İnceleme Bekliyor')
                    ->badge(fn ($state): bool => $state === null)
                    ->color(fn ($state): string => $state === null ? 'warning' : 'gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Talep Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (JobFeatureRequest $record): string => $record->created_at ? $record->created_at->diffForHumans() : '')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Son Güncelleme')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(FeatureRequestStatus::class),

                SelectFilter::make('days')
                    ->label('Süre (Gün)')
                    ->options([
                        7 => '7 Gün (1 Hafta)',
                        14 => '14 Gün (2 Hafta)',
                        30 => '30 Gün (1 Ay)',
                        60 => '60 Gün (2 Ay)',
                    ]),
            ])
            ->recordActions([
                Action::make('onayla')
                    ->label('Onayla')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Öne Çıkarma Talebini Onayla')
                    ->modalDescription(fn (JobFeatureRequest $record): string => "Bu iş ilanını {$record->days} gün boyunca vitrinde öne çıkarmak istiyor musunuz?")
                    ->visible(fn (JobFeatureRequest $record): bool => $record->status === FeatureRequestStatus::Beklemede)
                    ->action(function (JobFeatureRequest $record): void {
                        $record->status = FeatureRequestStatus::Onaylandi;
                        $record->save();
                        Notification::make()->title('Talep onaylandı ve iş ilanı vitrine alındı')->success()->send();
                    }),

                Action::make('reddet')
                    ->label('Reddet')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Talebi Reddet')
                    ->modalDescription('Bu iş ilanı öne çıkarma talebini reddetmek istediğinize emin misiniz?')
                    ->visible(fn (JobFeatureRequest $record): bool => $record->status === FeatureRequestStatus::Beklemede)
                    ->action(function (JobFeatureRequest $record): void {
                        $record->status = FeatureRequestStatus::Reddedildi;
                        $record->save();
                        Notification::make()->title('Talep reddedildi')->warning()->send();
                    }),

                Action::make('sitedeGor')
                    ->label('İlanı Gör')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->visible(fn (JobFeatureRequest $record): bool => $record->jobListing !== null)
                    ->url(fn (JobFeatureRequest $record): string => route('jobs.show', [$record->jobListing->id, $record->jobListing->slug]), shouldOpenInNewTab: true),

                Action::make('detay')
                    ->label('İncele')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading('Öne Çıkarma Talebi Detayı')
                    ->modalDescription(function (JobFeatureRequest $record): HtmlString {
                        $jobTitle = $record->jobListing ? e($record->jobListing->title) : '—';
                        $companyName = ($record->jobListing && $record->jobListing->company) ? e($record->jobListing->company->name) : 'Belirtilmedi';
                        $userName = $record->user ? e($record->user->name) : '—';
                        $userEmail = $record->user ? e($record->user->email) : '—';
                        $statusLabel = e($record->status->getLabel());
                        $days = $record->days;
                        $created = $record->created_at ? $record->created_at->format('d.m.Y H:i') : '—';
                        $processed = $record->processed_at ? $record->processed_at->format('d.m.Y H:i') : 'İşlem bekliyor';
                        $activeUntil = ($record->jobListing && $record->jobListing->featured_until) ? $record->jobListing->featured_until->format('d.m.Y H:i') : 'Yok';

                        return new HtmlString(
                            "<div class='space-y-3.5 text-sm'>"
                            ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 space-y-1.5'>"
                            ."<div><strong>İş İlanı:</strong> {$jobTitle}</div>"
                            ."<div><strong>Şirket:</strong> {$companyName}</div>"
                            ."<div><strong>Talep Eden:</strong> {$userName} ({$userEmail})</div>"
                            .'</div>'
                            ."<div class='grid grid-cols-2 gap-2 text-xs'>"
                            ."<div class='p-2.5 bg-primary-50 dark:bg-primary-950/30 rounded-lg border border-primary-200 dark:border-primary-800'><strong>Talep Edilen Süre:</strong> {$days} Gün</div>"
                            ."<div class='p-2.5 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'><strong>Mevcut Vitrin Bitiş:</strong> {$activeUntil}</div>"
                            ."<div class='p-2.5 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'><strong>Talep Tarihi:</strong> {$created}</div>"
                            ."<div class='p-2.5 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'><strong>İşlem Durumu:</strong> {$statusLabel} ({$processed})</div>"
                            .'</div>'
                            .'</div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('topluOnayla')
                        ->label('Seçilenleri Onayla')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record instanceof JobFeatureRequest && $record->status === FeatureRequestStatus::Beklemede) {
                                    $record->status = FeatureRequestStatus::Onaylandi;
                                    $record->save();
                                    $count++;
                                }
                            }
                            Notification::make()->title("{$count} talep onaylandı ve vitrine alındı.")->success()->send();
                        }),

                    BulkAction::make('topluReddet')
                        ->label('Seçilenleri Reddet')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record instanceof JobFeatureRequest && $record->status === FeatureRequestStatus::Beklemede) {
                                    $record->status = FeatureRequestStatus::Reddedildi;
                                    $record->save();
                                    $count++;
                                }
                            }
                            Notification::make()->title("{$count} talep reddedildi.")->info()->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Henüz İş İlanı Öne Çıkarma Talebi Bulunmuyor')
            ->emptyStateDescription('İşverenler iş ilanlarını vitrinde öne çıkarmak istediklerinde talepleri buraya düşer. Bekleyen talepleri onaylayarak ilanı hemen öne çıkarabilirsiniz.')
            ->emptyStateIcon(Heroicon::OutlinedRocketLaunch)
            ->emptyStateActions([
                Action::make('yeniTalep')
                    ->label('Yeni Talep Ekle')
                    ->icon(Heroicon::OutlinedPlus)
                    ->color('primary')
                    ->url(fn (): string => JobFeatureRequestResource::getUrl('create')),

                Action::make('isIlanlari')
                    ->label('Tüm İş İlanları')
                    ->icon(Heroicon::OutlinedBriefcase)
                    ->color('gray')
                    ->url(fn (): string => JobListingResource::getUrl('index')),
            ]);
    }
}
