<?php

declare(strict_types=1);

namespace App\Filament\Resources\IslemTurleri\Pages;

use App\Filament\Resources\IslemTurleri\IslemTuruResource;
use App\Filament\Resources\IslemTurleri\Widgets\IslemTurleriStatsWidget;
use App\Models\IslemTuru;
use App\Models\TemsilcilikIslemi;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ListIslemTurleri extends ListRecords
{
    protected static string $resource = IslemTuruResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            IslemTurleriStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiKapsamRaporu')
                ->label('AI Denge & Kapsam Analizi')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('gray')
                ->modalHeading('Konsolosluk İşlem Türleri — Küresel Kapsam Raporu')
                ->modalDescription(function (): HtmlString {
                    $toplamTur = IslemTuru::query()->count();
                    $aktifTur = IslemTuru::query()->where('is_active', true)->count();
                    $toplamIcerik = TemsilcilikIslemi::query()->count();
                    $yayindaIcerik = TemsilcilikIslemi::query()->where('status', TemsilcilikIslemi::STATUS_YAYIN)->count();
                    $taslakIcerik = TemsilcilikIslemi::query()->where('status', TemsilcilikIslemi::STATUS_TASLAK)->count();

                    $enCokGecen = IslemTuru::query()->withCount('islemler')->orderByDesc('islemler_count')->first();
                    $enAzGecen = IslemTuru::query()->withCount('islemler')->orderBy('islemler_count')->first();

                    $enCokMetin = $enCokGecen ? "{$enCokGecen->ad} ({$enCokGecen->islemler_count} temsilcilik)" : 'Tanımsız';
                    $enAzMetin = $enAzGecen ? "{$enAzGecen->ad} ({$enAzGecen->islemler_count} temsilcilik)" : 'Tanımsız';

                    return new HtmlString(
                        "<div class='space-y-3 text-sm p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'>"
                        ."<div class='font-semibold text-base text-gray-900 dark:text-gray-100 flex items-center gap-2'>"
                        .'<span>Standart İşlem Kategorileri Sağlık Raporu</span>'
                        .'</div>'
                        ."<div class='grid grid-cols-2 gap-2 text-xs pt-1'>"
                        ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800'><strong>Toplam Kategori:</strong> {$aktifTur} aktif / {$toplamTur} toplam</div>"
                        ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800'><strong>Toplam İçerik:</strong> {$toplamIcerik} ({$yayindaIcerik} yayında, {$taslakIcerik} taslak)</div>"
                        ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800'><strong>En Geniş Kapsam:</strong> {$enCokMetin}</div>"
                        ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800'><strong>En Dar Kapsam:</strong> {$enAzMetin}</div>"
                        .'</div>'
                        ."<div class='text-xs text-gray-600 dark:text-gray-300 pt-2 border-t border-gray-200 dark:border-gray-700 leading-relaxed'>"
                        .'<strong>AI Tavsiyesi:</strong> İşlem türleri ülke bağımsız evrensel şablonlardır. Temsilcilik sayfalarında ziyaretçilerin aradığı bilgiye kolay ulaşması için her işlem türünün kısa açıklamasını dolu tutunuz. Pasaport, Kimlik ve Noter gibi temel türlerin temsilcilik içeriklerini öncelikle yayına alınız.'
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            CreateAction::make()
                ->label('Yeni İşlem Türü Ekle')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
