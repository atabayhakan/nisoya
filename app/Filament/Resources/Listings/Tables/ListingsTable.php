<?php

namespace App\Filament\Resources\Listings\Tables;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Listing;
use App\Services\Ai\MarketplaceAiAssistant;
use App\Services\DolandiricilikTespiti;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

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
                TernaryFilter::make('fraud_reason')
                    ->label('Metin denetimi işaretli')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('fraud_reason'),
                        false: fn (Builder $q) => $q->whereNull('fraud_reason'),
                        blank: fn (Builder $q) => $q,
                    ),
            ])
            ->recordActions([
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
