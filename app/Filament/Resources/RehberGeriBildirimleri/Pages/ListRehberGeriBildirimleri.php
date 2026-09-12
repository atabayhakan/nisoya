<?php

namespace App\Filament\Resources\RehberGeriBildirimleri\Pages;

use App\Filament\Resources\RehberGeriBildirimleri\RehberGeriBildirimiResource;
use App\Filament\Resources\RehberGeriBildirimleri\Widgets\RehberGeriBildirimleriStatsWidget;
use App\Filament\Resources\TemsilcilikIslemleri\TemsilcilikIslemiResource;
use App\Models\RehberGeriBildirimi;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ListRehberGeriBildirimleri extends ListRecords
{
    protected static string $resource = RehberGeriBildirimiResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            RehberGeriBildirimleriStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiBildirimOzeti')
                ->label('AI Bildirim Analizi')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Geri Bildirimler — AI Kalite & Sinyal Analizi')
                ->modalDescription(function (): HtmlString {
                    $bekleyenler = RehberGeriBildirimi::with(['islem.temsilcilik', 'islem.islemTuru'])
                        ->where('incelendi', false)
                        ->latest('id')
                        ->get();

                    $toplamBekleyen = $bekleyenler->count();
                    $hataSayisi = $bekleyenler->where('tur', 'hata')->count();
                    $guncelDegilSayisi = $bekleyenler->where('tur', 'guncel_degil')->count();
                    $oneriSayisi = $bekleyenler->where('tur', 'oneri')->count();

                    if ($toplamBekleyen === 0) {
                        return new HtmlString(
                            "<div class='p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-800 dark:text-emerald-300 text-sm space-y-2'>"
                            ."<div class='font-bold flex items-center gap-1.5'><span>✓ Tüm Geri Bildirimler Temiz</span></div>"
                            ."<p class='text-xs'>Şu anda inceleme bekleyen veya işlem gerektiren hiçbir vatandaş bildirimi bulunmuyor. Rehber içerikleri en son teyit edilen bilgilerle yayında.</p>"
                            .'</div>'
                        );
                    }

                    return new HtmlString(
                        "<div class='space-y-4 text-sm'>"
                        ."<div class='grid grid-cols-3 gap-2 text-center'>"
                        ."<div class='p-2 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded'><div class='text-rose-700 dark:text-rose-400 font-bold text-lg'>{$hataSayisi}</div><div class='text-xs text-gray-500'>Hata Bildirimi</div></div>"
                        ."<div class='p-2 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded'><div class='text-amber-700 dark:text-amber-400 font-bold text-lg'>{$guncelDegilSayisi}</div><div class='text-xs text-gray-500'>Güncel Değil</div></div>"
                        ."<div class='p-2 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded'><div class='text-blue-700 dark:text-blue-400 font-bold text-lg'>{$oneriSayisi}</div><div class='text-xs text-gray-500'>Öneri</div></div>"
                        .'</div>'
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-300 space-y-1.5'>"
                        .'<strong>💡 AI Kalite Tavsiyesi:</strong>'
                        .'<p>Vatandaş bildirimleri, Dışişleri harç ve randevu yönetmeliklerindeki yerel değişikliklerin ilk habercisidir. Hata ve güncel değil sinyallerini ilgili temsilciliğin resmî mfa.gov.tr sayfasından teyit edip içeriği güncelleyiniz.</p>'
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            Action::make('tumunuIncelendiYap')
                ->label('Tümünü İncelendi İşaretle')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Bekleyen Tüm Bildirimleri İncelendi Yap')
                ->modalDescription('İncelenmemiş tüm kullanıcı geri bildirimleri incelendi olarak güncellenecek. Devam etmek istiyor musunuz?')
                ->visible(fn (): bool => RehberGeriBildirimi::query()->where('incelendi', false)->exists())
                ->action(function (): void {
                    $adet = RehberGeriBildirimi::query()->where('incelendi', false)->update(['incelendi' => true]);
                    Notification::make()
                        ->title("{$adet} bildirim incelendi olarak işaretlendi")
                        ->success()
                        ->send();
                }),

            Action::make('islemlereGit')
                ->label('İşlem İçerikleri')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->url(fn (): string => TemsilcilikIslemiResource::getUrl('index')),
        ];
    }
}
