<?php

namespace App\Filament\Resources\Deals\Tables;

use App\Enums\DealStatus;
use App\Models\Deal;
use App\Services\Ai\MarketplaceAiAssistant;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class DealsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('buyer.name')
                    ->label('Alıcı')
                    ->searchable(),
                TextColumn::make('seller.name')
                    ->label('Satıcı')
                    ->searchable(),
                TextColumn::make('listing.title')
                    ->label('İlan')
                    ->placeholder('—')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('amount')
                    ->label('Tutar')
                    ->formatStateUsing(fn ($state, $record): string => $state !== null ? $state.' '.$record->currency : 'Görüşülür'),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge(),
                TextColumn::make('dispute_note')
                    ->label('Sorun notu')
                    ->placeholder('—')
                    ->wrap()
                    ->limit(80)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(DealStatus::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('aiIhtilafAnalizi')
                    ->label('AI İhtilaf Analizi')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('warning')
                    ->visible(fn (Deal $record): bool => $record->status === DealStatus::Sorunlu || filled($record->dispute_note))
                    ->modalHeading(fn (Deal $record): string => 'AI Arabuluculuk ve İhtilaf Analizi #'.$record->id)
                    ->modalDescription(function (Deal $record, MarketplaceAiAssistant $assistant): HtmlString {
                        $buyerName = $record->buyer ? $record->buyer->name : 'Alıcı';
                        $sellerName = $record->seller ? $record->seller->name : 'Satıcı';
                        $listingTitle = $record->listing ? $record->listing->title : 'İlan';
                        $amountStr = $record->amount ? "{$record->amount} {$record->currency}" : 'Belirtilmedi';
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
            ])
            ->toolbarActions([]);
    }
}
