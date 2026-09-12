<?php

declare(strict_types=1);

namespace App\Filament\Resources\TemsilcilikIslemleri\Pages;

use App\Filament\Resources\TemsilcilikIslemleri\TemsilcilikIslemiResource;
use App\Filament\Resources\TemsilcilikIslemleri\Widgets\TemsilcilikIslemleriStatsWidget;
use App\Models\TemsilcilikIslemi;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ListTemsilcilikIslemleri extends ListRecords
{
    protected static string $resource = TemsilcilikIslemiResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TemsilcilikIslemleriStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiDogrulamaRaporu')
                ->label('AI Tazelik & Kalite Raporu')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('gray')
                ->modalHeading('Konsolosluk Rehberi — Tazelik ve Doğrulama Durumu')
                ->modalDescription(function (): HtmlString {
                    $toplam = TemsilcilikIslemi::query()->count();
                    $yayinda = TemsilcilikIslemi::query()->where('status', TemsilcilikIslemi::STATUS_YAYIN)->count();
                    $taslak = TemsilcilikIslemi::query()->where('status', TemsilcilikIslemi::STATUS_TASLAK)->count();

                    $bayat = TemsilcilikIslemi::query()
                        ->where('status', TemsilcilikIslemi::STATUS_YAYIN)
                        ->where(fn ($q) => $q->whereNull('dogrulanma_tarihi')->orWhere('dogrulanma_tarihi', '<', now()->subDays(TemsilcilikIslemi::BAYATLIK_GUN)))
                        ->count();

                    $jenerikKaynak = TemsilcilikIslemi::query()
                        ->where('resmi_kaynak_url', 'https://www.konsolosluk.gov.tr')
                        ->count();

                    return new HtmlString(
                        "<div class='space-y-3 text-sm p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'>"
                        ."<div class='font-semibold text-base text-gray-900 dark:text-gray-100 flex items-center gap-2'>"
                        .'<span>Rehber İçerikleri Kalite Metrikleri</span>'
                        .'</div>'
                        ."<div class='grid grid-cols-2 gap-2 text-xs pt-1'>"
                        ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800'><strong>Toplam Rehber:</strong> {$toplam} ({$yayinda} yayında, {$taslak} taslak)</div>"
                        ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800'><strong>Bayat İçerikler:</strong> <span class='font-medium text-amber-700 dark:text-amber-400'>{$bayat} adet (>90 gün)</span></div>"
                        ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800'><strong>Jenerik Portal Bağlantılı:</strong> {$jenerikKaynak} adet</div>"
                        ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 text-emerald-700 dark:text-emerald-400'><strong>Aktif Güncel:</strong> ".max(0, $yayinda - $bayat).' adet</div>'
                        .'</div>'
                        ."<div class='text-xs text-gray-600 dark:text-gray-300 pt-2 border-t border-gray-200 dark:border-gray-700 leading-relaxed'>"
                        .'<strong>AI Tavsiyesi:</strong> Yayındaki içeriklerin son doğrulama tarihlerini periyodik olarak güncel tutunuz. Jenerik konsolosluk.gov.tr adresi taşıyan taslakları doğrudan yayına almak yerine, ilgili temsilciliğin kendi konsolosluk sayfasından evrak ve harçları teyit ediniz.'
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            CreateAction::make()
                ->label('Yeni İşlem İçeriği Ekle')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
