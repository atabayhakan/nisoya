<?php

namespace App\Filament\Resources\Listings\Tables;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Services\Ai\MarketplaceAiAssistant;
use App\Services\DolandiricilikTespiti;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class ListingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('coverImage.path_thumb')
                    ->label('Görsel')
                    ->disk('public')
                    ->square()
                    ->size(46)
                    ->extraImgAttributes([
                        'class' => 'rounded-xl object-cover shadow-2xs border border-stone-200 dark:border-stone-700',
                    ])
                    ->defaultImageUrl('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="%239ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>'),

                TextColumn::make('title')
                    ->label('Başlık & Satıcı')
                    ->weight('bold')
                    ->description(fn (Listing $record): string => ($record->user->name ?? 'Anonim').($record->city ? ' • '.$record->city : ''))
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->description(fn (Listing $record): ?string => $record->category?->parent instanceof Category ? $record->category->parent->name : null)
                    ->sortable()
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Tür')
                    ->badge()
                    ->color(fn (ListingType $state): string => match ($state) {
                        ListingType::Hizmet => 'info',
                        ListingType::Urun => 'success',
                        ListingType::Emlak => 'warning',
                        ListingType::Vasita => 'primary',
                    })
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Fiyat')
                    ->formatStateUsing(function ($state, Listing $record): string {
                        if ($state === null || (float) $state <= 0.0) {
                            return 'Görüşülür';
                        }
                        $unit = $record->price_unit ? ' '.$record->price_unit->suffix() : '';

                        return number_format((float) $state, 2).' '.$record->currency.$unit;
                    })
                    ->weight('semibold')
                    ->sortable(),

                TextColumn::make('country_code')
                    ->label('Ülke')
                    ->formatStateUsing(fn (?string $state, Listing $record): string => $record->country ? ($record->country->emoji.' '.$state) : ($state ?? '—'))
                    ->description(fn (Listing $record): ?string => $record->country?->name_tr)
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->icon(fn (ListingStatus $state): string => match ($state) {
                        ListingStatus::Aktif => 'heroicon-m-check-circle',
                        ListingStatus::Beklemede => 'heroicon-m-clock',
                        ListingStatus::Reddedildi => 'heroicon-m-x-circle',
                        ListingStatus::Pasif => 'heroicon-m-pause-circle',
                        ListingStatus::Taslak => 'heroicon-m-document',
                    })
                    ->sortable(),

                /*
                 * METİN DENETİMİ İŞARETİ.
                 *
                 * Hafif kategoride ilan yayında KALIYOR; işaret yalnız burada
                 * görünüyor. Bu sütun olmasaydı hafif tespitin hiçbir
                 * karşılığı olmazdı — kimse görmediği bir işaret, tespit
                 * sayılmaz.
                 */
                TextColumn::make('fraud_reason')
                    ->label('Metin denetimi')
                    ->badge()
                    ->color(fn (?string $state): string => $state === null
                        ? 'gray'
                        : (app(DolandiricilikTespiti::class)->agirMi($state) ? 'danger' : 'warning'))
                    ->formatStateUsing(fn (string $state): string => app(DolandiricilikTespiti::class)->kategoriAdi($state))
                    ->placeholder('—')
                    ->wrap(),

                IconColumn::make('is_featured')
                    ->label('Öne çıkan')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable(),

                TextColumn::make('views_count')
                    ->label('Görüntülenme')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->date('d.m.Y')
                    ->description(fn (Listing $record): ?string => $record->created_at?->diffForHumans())
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('country_code')
                    ->label('Ülke')
                    ->options(fn (): array => Country::query()->pluck('name_tr', 'code')->toArray())
                    ->searchable(),
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(ListingStatus::class),
                SelectFilter::make('type')
                    ->label('Tür')
                    ->options(ListingType::class),
                TernaryFilter::make('is_featured')
                    ->label('Öne çıkan'),
                TernaryFilter::make('fraud_reason')
                    ->label('Metin denetimi işaretli')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('fraud_reason'),
                        false: fn (Builder $q) => $q->whereNull('fraud_reason'),
                        blank: fn (Builder $q) => $q,
                    ),
            ])
            ->recordActions([
                Action::make('sitedeGor')
                    ->label('Sitede Gör')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Listing $record): string => route('listings.show', [$record->id, $record->slug]), shouldOpenInNewTab: true)
                    ->visible(fn (Listing $record): bool => $record->status === ListingStatus::Aktif),

                Action::make('hizliOnayla')
                    ->label('Onayla')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('İlanı Onayla ve Yayına Al')
                    ->modalDescription('Bu ilan hemen aktif duruma getirilerek yayına alınacak.')
                    ->visible(fn (Listing $record): bool => $record->status === ListingStatus::Beklemede)
                    ->action(function (Listing $record): void {
                        $record->status = ListingStatus::Aktif;
                        $record->save();
                        Notification::make()->title('İlan onaylandı ve yayına alındı')->success()->send();
                    }),

                Action::make('oneCikarToggle')
                    ->label(fn (Listing $record): string => $record->is_featured ? 'Vitrinden Kaldır' : 'Öne Çıkar')
                    ->icon(Heroicon::OutlinedStar)
                    ->color('warning')
                    ->action(function (Listing $record): void {
                        $record->is_featured = ! $record->is_featured;
                        $record->featured_until = $record->is_featured ? now()->addDays(30) : null;
                        $record->save();
                        Notification::make()
                            ->title($record->is_featured ? 'İlan vitrine alındı' : 'İlan vitrinden kaldırıldı')
                            ->success()
                            ->send();
                    }),

                /*
                 * İŞARETİ KALDIR — yanlış alarmın çıkış yolu.
                 *
                 * Olmasaydı yanlış işaretlenen bir ilan panelde temelli
                 * kırmızı kalırdı ve gerçek işaretler bu gürültünün içinde
                 * kaybolurdu.
                 *
                 * `forceFill`: `fraud_reason` bilerek `$fillable` dışında
                 * (hiçbir form set edemesin diye), o yüzden `update()` bu
                 * alanı SESSİZCE yok sayardı.
                 */
                Action::make('isareti-kaldir')
                    ->label('İşareti kaldır')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Bu ilanın metin denetimi işareti kaldırılacak. İlanın durumu değişmez.')
                    ->visible(fn (Listing $record): bool => $record->fraud_reason !== null)
                    ->action(fn (Listing $record) => $record->forceFill(['fraud_reason' => null])->save()),

                Action::make('aiAnaliz')
                    ->label('AI İncele')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('primary')
                    ->modalHeading(fn (Listing $record): string => 'AI Güvenlik & Kalite Analizi — '.$record->title)
                    ->modalDescription(function (Listing $record, MarketplaceAiAssistant $assistant): HtmlString {
                        $analiz = $assistant->evaluateListing(
                            $record->title,
                            (string) $record->description,
                            $record->price ? (float) $record->price : null,
                            $record->city
                        );
                        $skor = $analiz['score'];
                        $tavsiye = strtoupper($analiz['recommendation']);
                        $ozet = e($analiz['summary']);

                        $riskHtml = '';
                        if (! empty($analiz['risks'])) {
                            $riskHtml = '<div class="mt-2 text-rose-600 dark:text-rose-400 font-semibold text-xs">⚠️ '.e(implode(', ', $analiz['risks'])).'</div>';
                        }

                        $oneriHtml = '';
                        if (! empty($analiz['suggestions'])) {
                            $oneriHtml = '<div class="mt-1 text-gray-600 dark:text-gray-300 text-xs">💡 '.e(implode(', ', $analiz['suggestions'])).'</div>';
                        }

                        $badgeColorClass = $analiz['recommendation'] === 'onayla'
                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400'
                            : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400';

                        return new HtmlString(
                            "<div class='space-y-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-sm'>"
                            ."<div class='flex items-center justify-between font-bold'>"
                            ."<span>Kalite Skoru: <span class='text-primary-600'>{$skor}/100</span></span>"
                            ."<span class='px-2 py-0.5 rounded text-xs {$badgeColorClass}'>Tavsiye: {$tavsiye}</span>"
                            .'</div>'
                            ."<p class='text-xs text-gray-700 dark:text-gray-200 mt-1'>{$ozet}</p>"
                            .$riskHtml
                            .$oneriHtml
                            .'</div>'
                        );
                    })
                    ->form([
                        Select::make('aksiyon')
                            ->label('Moderasyon Kararı')
                            ->options([
                                'aktif' => '✅ Onayla ve Yayına Al (Aktif)',
                                'reddedildi' => '❌ Reddet (Yayından Kaldır)',
                                'isareti_kaldir' => '🛡️ Yalnızca Risk İşaretini Temizle',
                            ])
                            ->default('aktif')
                            ->required(),
                        TextInput::make('gerekce')
                            ->label('Not / Gerekçe (opsiyonel)')
                            ->placeholder('Gerekçe belirtin...'),
                    ])
                    ->action(function (Listing $record, array $data): void {
                        if ($data['aksiyon'] === 'aktif') {
                            $record->status = ListingStatus::Aktif;
                            $record->fraud_reason = null;
                            $record->save();
                            Notification::make()->title('İlan onaylandı ve yayına alındı')->success()->send();
                        } elseif ($data['aksiyon'] === 'reddedildi') {
                            $record->status = ListingStatus::Reddedildi;
                            if (! empty($data['gerekce'])) {
                                $record->fraud_reason = (string) $data['gerekce'];
                            }
                            $record->save();
                            Notification::make()->title('İlan reddedildi')->warning()->send();
                        } elseif ($data['aksiyon'] === 'isareti_kaldir') {
                            $record->forceFill(['fraud_reason' => null])->save();
                            Notification::make()->title('Güvenlik işareti kaldırıldı')->success()->send();
                        }
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('topluOnayla')
                        ->label('Seçilenleri Onayla (Aktif)')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record instanceof Listing) {
                                    $record->status = ListingStatus::Aktif;
                                    $record->save();
                                    $count++;
                                }
                            }
                            Notification::make()->title("{$count} ilan onaylandı ve yayına alındı.")->success()->send();
                        }),

                    BulkAction::make('topluPasifeAl')
                        ->label('Seçilenleri Pasife Al')
                        ->icon(Heroicon::OutlinedPauseCircle)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record instanceof Listing) {
                                    $record->status = ListingStatus::Pasif;
                                    $record->unpublished_at = now();
                                    $record->save();
                                    $count++;
                                }
                            }
                            Notification::make()->title("{$count} ilan pasife alındı.")->info()->send();
                        }),

                    BulkAction::make('topluOneCikar')
                        ->label('Seçilenleri Öne Çıkar')
                        ->icon(Heroicon::OutlinedStar)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record instanceof Listing) {
                                    $record->is_featured = true;
                                    $record->featured_until = now()->addDays(30);
                                    $record->save();
                                    $count++;
                                }
                            }
                            Notification::make()->title("{$count} ilan vitrine eklendi.")->success()->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Henüz ilan bulunamadı')
            ->emptyStateDescription('Arama kriterlerinize uygun ilan bulunmuyor veya sisteme henüz kayıt eklenmedi.')
            ->emptyStateIcon(Heroicon::OutlinedShoppingBag);
    }
}
