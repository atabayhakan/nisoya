<?php

namespace App\Filament\Resources\Deals\Tables;

use App\Enums\DealStatus;
use App\Models\Deal;
use App\Services\Ai\MarketplaceAiAssistant;
use App\Support\Para;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class DealsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#ID')
                    ->formatStateUsing(fn ($state): string => "#{$state}")
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('buyer.name')
                    ->label('Alıcı')
                    ->weight('bold')
                    ->icon('heroicon-m-user')
                    ->description(fn (Deal $record): ?string => $record->buyer ? $record->buyer->email : null)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('seller.name')
                    ->label('Satıcı')
                    ->weight('bold')
                    ->icon('heroicon-m-user-circle')
                    ->description(fn (Deal $record): ?string => $record->seller ? $record->seller->email : null)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('listing.title')
                    ->label('İlan')
                    ->icon('heroicon-m-shopping-bag')
                    ->description(function (Deal $record): string {
                        if ($record->listing && $record->listing->category) {
                            return $record->listing->category->name;
                        }

                        return $record->listing ? 'İlan' : 'Doğrudan Anlaşma';
                    })
                    ->placeholder('Doğrudan Anlaşma')
                    ->limit(35)
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('amount')
                    ->label('Tutar')
                    ->weight('bold')
                    ->formatStateUsing(function ($state, Deal $record): string {
                        if ($state === null) {
                            return 'Görüşülür';
                        }
                        $fmt = Para::bicimle($state);

                        return $fmt !== null ? "{$fmt} {$record->currency}" : 'Görüşülür';
                    })
                    ->badge()
                    ->color(fn ($state): string => $state !== null ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->icon(fn (DealStatus $state): string => match ($state) {
                        DealStatus::Teklif => 'heroicon-m-clock',
                        DealStatus::Kabul => 'heroicon-m-hand-thumb-up',
                        DealStatus::Tamamlandi => 'heroicon-m-check-circle',
                        DealStatus::Iptal => 'heroicon-m-x-circle',
                        DealStatus::Sorunlu => 'heroicon-m-exclamation-triangle',
                    })
                    ->sortable(),

                TextColumn::make('dispute_note')
                    ->label('Sorun Notu')
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'danger' : 'gray')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? Str::limit($state, 32) : '—')
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (Deal $record): string => $record->created_at ? $record->created_at->diffForHumans() : '')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(DealStatus::class),

                SelectFilter::make('currency')
                    ->label('Para Birimi')
                    ->options(fn (): array => Deal::query()->whereNotNull('currency')->distinct()->pluck('currency', 'currency')->toArray()),

                TernaryFilter::make('sorunlu')
                    ->label('İtiraz Durumu')
                    ->placeholder('Tümü')
                    ->trueLabel('Sorunlu / İtirazlı Olanlar')
                    ->falseLabel('Sorunsuz Anlaşmalar')
                    ->queries(
                        true: fn (Builder $q) => $q->where(fn (Builder $sq) => $sq->where('status', DealStatus::Sorunlu)->orWhereNotNull('dispute_note')),
                        false: fn (Builder $q) => $q->where('status', '!=', DealStatus::Sorunlu)->whereNull('dispute_note'),
                        blank: fn (Builder $q) => $q,
                    ),
            ])
            ->recordActions([
                Action::make('detayGoster')
                    ->label('Detay')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading(fn (Deal $record): string => "Anlaşma #{$record->id} Kayıt Detayı")
                    ->modalDescription(function (Deal $record): HtmlString {
                        $buyerName = e($record->buyer ? $record->buyer->name : 'Alıcı');
                        $buyerEmail = e($record->buyer ? $record->buyer->email : '—');
                        $sellerName = e($record->seller ? $record->seller->name : 'Satıcı');
                        $sellerEmail = e($record->seller ? $record->seller->email : '—');
                        $listingTitle = e($record->listing ? $record->listing->title : 'Doğrudan Anlaşma / Özel Talep');
                        $proposerName = e($record->proposer ? $record->proposer->name : 'Bilinmiyor');

                        $amountFmt = $record->amount !== null ? (Para::bicimle($record->amount)." {$record->currency}") : 'Görüşülür';
                        $statusLabel = $record->status->getLabel();
                        $statusColor = $record->status->getColor();

                        $tarihOlusturma = $record->created_at ? $record->created_at->format('d.m.Y H:i') : '—';
                        $tarihKabul = $record->accepted_at ? $record->accepted_at->format('d.m.Y H:i') : '—';
                        $tarihTamam = $record->completed_at ? $record->completed_at->format('d.m.Y H:i') : '—';
                        $tarihIptal = $record->cancelled_at ? $record->cancelled_at->format('d.m.Y H:i') : '—';
                        $tarihItiraz = $record->disputed_at ? $record->disputed_at->format('d.m.Y H:i') : '—';

                        $disputeHtml = '';
                        if (filled($record->dispute_note)) {
                            $disputeNote = e($record->dispute_note);
                            $disputeHtml = "<div class='p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl text-xs text-rose-800 dark:text-rose-300'><strong>⚠️ İtiraz / Sorun Notu ({$tarihItiraz}):</strong><p class='mt-1 text-xs'>{$disputeNote}</p></div>";
                        }

                        return new HtmlString(
                            "<div class='space-y-3.5 text-sm'>"
                            ."<div class='grid grid-cols-2 gap-3'>"
                            ."<div class='p-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-xl'>"
                            ."<div class='text-2xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider'>Alıcı Taraf</div>"
                            ."<div class='font-bold text-gray-900 dark:text-gray-100 text-sm mt-0.5'>{$buyerName}</div>"
                            ."<div class='text-xs text-gray-500'>{$buyerEmail}</div>"
                            .'</div>'
                            ."<div class='p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl'>"
                            ."<div class='text-2xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider'>Satıcı Taraf</div>"
                            ."<div class='font-bold text-gray-900 dark:text-gray-100 text-sm mt-0.5'>{$sellerName}</div>"
                            ."<div class='text-xs text-gray-500'>{$sellerEmail}</div>"
                            .'</div>'
                            .'</div>'
                            ."<div class='p-3 bg-gray-50 dark:bg-gray-800/80 rounded-xl border border-gray-200 dark:border-gray-700 text-xs space-y-2'>"
                            ."<div class='flex justify-between items-center'><span class='text-gray-500'>İşlem Tutarı:</span><span class='font-bold text-sm text-gray-900 dark:text-gray-100'>{$amountFmt}</span></div>"
                            ."<div class='flex justify-between items-center'><span class='text-gray-500'>Durum:</span><span class='font-bold text-{$statusColor}-600'>{$statusLabel}</span></div>"
                            ."<div class='flex justify-between items-center'><span class='text-gray-500'>Teklifi Başlatan:</span><span class='font-medium text-gray-800 dark:text-gray-200'>{$proposerName}</span></div>"
                            ."<div class='flex justify-between items-center'><span class='text-gray-500'>İlgili İlan:</span><span class='font-medium text-gray-800 dark:text-gray-200'>{$listingTitle}</span></div>"
                            .'</div>'
                            ."<div class='p-3 bg-gray-50 dark:bg-gray-800/80 rounded-xl border border-gray-200 dark:border-gray-700 text-2xs text-gray-600 dark:text-gray-300 space-y-1'>"
                            ."<div class='font-bold text-xs text-gray-800 dark:text-gray-200 mb-1'>⏱️ Süreç Zaman Çizelgesi</div>"
                            ."<div class='flex justify-between'><span>Oluşturulma:</span><span>{$tarihOlusturma}</span></div>"
                            ."<div class='flex justify-between'><span>Kabul Edilme:</span><span>{$tarihKabul}</span></div>"
                            ."<div class='flex justify-between'><span>Tamamlanma:</span><span>{$tarihTamam}</span></div>"
                            ."<div class='flex justify-between'><span>İptal Edilme:</span><span>{$tarihIptal}</span></div>"
                            .'</div>'
                            .$disputeHtml
                            .'</div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                Action::make('aiIhtilafAnalizi')
                    ->label('AI İhtilaf Analizi')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('warning')
                    ->visible(fn (Deal $record): bool => $record->status === DealStatus::Sorunlu || filled($record->dispute_note))
                    ->modalHeading(fn (Deal $record): string => "AI Arabuluculuk ve İhtilaf Analizi #{$record->id}")
                    ->modalDescription(function (Deal $record, MarketplaceAiAssistant $assistant): HtmlString {
                        $buyerName = $record->buyer ? $record->buyer->name : 'Alıcı';
                        $sellerName = $record->seller ? $record->seller->name : 'Satıcı';
                        $listingTitle = $record->listing ? $record->listing->title : 'İlan';
                        $amountStr = $record->amount ? ($record->amount.' '.$record->currency) : 'Belirtilmedi';
                        $note = $record->dispute_note ?? 'Sorun notu girilmemiş.';

                        $analiz = $assistant->analyzeDispute($note, $buyerName, $sellerName, $listingTitle, $amountStr);
                        $riskColor = match ($analiz['risk_level']) {
                            'yuksek' => 'text-rose-600 dark:text-rose-400',
                            'orta' => 'text-amber-600 dark:text-amber-400',
                            default => 'text-emerald-600 dark:text-emerald-400',
                        };

                        return new HtmlString(
                            "<div class='space-y-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-sm'>"
                            ."<div class='flex items-center justify-between font-bold'>"
                            ."<span>İhtilaf Düzeyi: <span class='{$riskColor} uppercase'>".e($analiz['risk_level']).'</span></span>'
                            ."<span class='text-xs text-gray-500'>Tutar: ".e($amountStr).'</span>'
                            .'</div>'
                            ."<div><strong class='text-xs text-gray-500'>Sorun Özeti:</strong><p class='text-xs mt-0.5 text-gray-800 dark:text-gray-200'>".e($analiz['summary']).'</p></div>'
                            ."<div class='pt-2 border-t border-gray-200 dark:border-gray-700'><strong class='text-xs text-gray-500'>Tavsiye Edilen Çözüm:</strong><p class='text-xs mt-0.5 text-primary-600 dark:text-primary-400 font-medium'>".e($analiz['recommendation']).'</p></div>'
                            .'</div>'
                        );
                    })
                    ->form([
                        Select::make('yeni_durum')
                            ->label('Anlaşma Durumunu Güncelle')
                            ->options([
                                DealStatus::Tamamlandi->value => '✅ Sorun Çözüldü / Tamamlandı',
                                DealStatus::Iptal->value => '❌ Anlaşmayı İptal Et',
                                DealStatus::Sorunlu->value => '⚠️ Sorunlu Olarak Bırak (İnceleme Sürüyor)',
                            ])
                            ->default(fn (Deal $record) => $record->status->value)
                            ->required(),
                    ])
                    ->action(function (Deal $record, array $data): void {
                        $yeni = DealStatus::tryFrom($data['yeni_durum']);
                        if ($yeni) {
                            $record->status = $yeni;
                            if ($yeni === DealStatus::Tamamlandi) {
                                $record->completed_at = now();
                            } elseif ($yeni === DealStatus::Iptal) {
                                $record->cancelled_at = now();
                            }
                            $record->save();
                            Notification::make()->title('Anlaşma durumu güncellendi')->success()->send();
                        }
                    }),

                Action::make('durumGuncelle')
                    ->label('Durum Değiştir')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->color('primary')
                    ->modalHeading(fn (Deal $record): string => "Anlaşma #{$record->id} Durumunu Güncelle")
                    ->form([
                        Select::make('status')
                            ->label('Yeni Durum')
                            ->options(DealStatus::class)
                            ->default(fn (Deal $record) => $record->status)
                            ->required(),
                        Textarea::make('dispute_note')
                            ->label('Sorun / İtiraz Açıklaması (Varsa)')
                            ->default(fn (Deal $record) => $record->dispute_note)
                            ->rows(3),
                    ])
                    ->action(function (Deal $record, array $data): void {
                        $record->status = $data['status'];
                        $record->dispute_note = filled($data['dispute_note']) ? $data['dispute_note'] : null;

                        if ($record->status === DealStatus::Tamamlandi && $record->completed_at === null) {
                            $record->completed_at = now();
                        } elseif ($record->status === DealStatus::Iptal && $record->cancelled_at === null) {
                            $record->cancelled_at = now();
                        } elseif ($record->status === DealStatus::Sorunlu && $record->disputed_at === null) {
                            $record->disputed_at = now();
                        }

                        $record->save();
                        Notification::make()->title('Anlaşma başarıyla güncellendi.')->success()->send();
                    }),

                Action::make('sitedeGor')
                    ->label('İlana Git')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->visible(fn (Deal $record): bool => $record->listing !== null)
                    ->url(fn (Deal $record): string => route('listings.show', [$record->listing->id, $record->listing->slug]), shouldOpenInNewTab: true),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Henüz anlaşma kaydı bulunmuyor')
            ->emptyStateDescription('Üyeler sohbet üzerinden ilanlar için teklif ilettiğinde veya el sıkıştığında işlem kayıtları, itirazlar ve tamamlanma durumları burada listelenir.')
            ->emptyStateIcon(Heroicon::OutlinedHandRaised);
    }
}
