<?php

namespace App\Filament\Resources\YasamKonuOnerileri\Pages;

use App\Filament\Resources\YasamKonuIcerikleri\YasamKonuIcerigiResource;
use App\Filament\Resources\YasamKonulari\YasamKonusuResource;
use App\Filament\Resources\YasamKonuOnerileri\Widgets\YasamKonuOnerileriStatsWidget;
use App\Filament\Resources\YasamKonuOnerileri\YasamKonuOnerisiResource;
use App\Models\YasamKonuOnerisi;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ListYasamKonuOnerileri extends ListRecords
{
    protected static string $resource = YasamKonuOnerisiResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            YasamKonuOnerileriStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiOneriRaporu')
                ->label('AI Öneri Raporu')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Topluluk Önerileri — AI Analiz & Kalite Raporu')
                ->modalDescription(function (): HtmlString {
                    $bekleyenler = YasamKonuOnerisi::with(['icerik.konu.kategori', 'icerik.country', 'kullanici'])
                        ->bekleyen()
                        ->latest('id')
                        ->get();

                    $toplamBekleyen = $bekleyenler->count();
                    $kaynakliSayisi = $bekleyenler->filter(fn (YasamKonuOnerisi $o) => filled($o->kaynak_url))->count();
                    $kaynaksizSayisi = $toplamBekleyen - $kaynakliSayisi;

                    if ($toplamBekleyen === 0) {
                        return new HtmlString(
                            "<div class='p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-800 dark:text-emerald-300 text-sm space-y-2'>"
                            ."<div class='font-bold flex items-center gap-1.5'><span>✓ Tüm Topluluk Önerileri İncelendi</span></div>"
                            ."<p class='text-xs'>Şu anda inceleme bekleyen veya işlem gerektiren hiçbir kullanıcı önerisi bulunmuyor. Yaşam rehberi güncel doğrulanmış verilerle yayında.</p>"
                            .'</div>'
                        );
                    }

                    return new HtmlString(
                        "<div class='space-y-4 text-sm'>"
                        ."<div class='grid grid-cols-3 gap-2 text-center'>"
                        ."<div class='p-2 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded'><div class='text-amber-700 dark:text-amber-400 font-bold text-lg'>{$toplamBekleyen}</div><div class='text-xs text-gray-500'>Bekleyen Öneri</div></div>"
                        ."<div class='p-2 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded'><div class='text-emerald-700 dark:text-emerald-400 font-bold text-lg'>{$kaynakliSayisi}</div><div class='text-xs text-gray-500'>Resmî Kaynaklı</div></div>"
                        ."<div class='p-2 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded'><div class='text-blue-700 dark:text-blue-400 font-bold text-lg'>{$kaynaksizSayisi}</div><div class='text-xs text-gray-500'>Teyit Bekleyen</div></div>"
                        .'</div>'
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-300 space-y-1.5'>"
                        .'<strong>💡 AI Moderasyon Tavsiyesi:</strong>'
                        .'<p>Gurbetçilerimizden gelen düzeltme ve ekleme önerileri mevzuat değişikliklerini erkenden yakalar. Resmî kaynak URL içeren önerileri öncelikle değerlendirip doğrulanmış içeriklere dahil ediniz.</p>'
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            Action::make('tumunuOnayla')
                ->label('Bekleyenleri Toplu Onayla')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Tüm Bekleyen Önerileri Onayla')
                ->modalDescription('İncelenmemiş tüm kullanıcı önerileri onaylandı durumuna getirilecektir. Devam etmek istiyor musunuz?')
                ->visible(fn (): bool => YasamKonuOnerisi::query()->bekleyen()->exists())
                ->action(function (): void {
                    $adet = YasamKonuOnerisi::query()->bekleyen()->update(['durum' => YasamKonuOnerisi::DURUM_ONAYLANDI]);
                    Notification::make()
                        ->title("{$adet} öneri onaylandı olarak işaretlendi")
                        ->success()
                        ->send();
                }),

            Action::make('iceriklereGit')
                ->label('Konu İçerikleri')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->url(fn (): string => YasamKonuIcerigiResource::getUrl('index')),

            Action::make('konularaGit')
                ->label('Yaşam Konuları')
                ->icon(Heroicon::OutlinedQuestionMarkCircle)
                ->color('gray')
                ->url(fn (): string => YasamKonusuResource::getUrl('index')),
        ];
    }
}
