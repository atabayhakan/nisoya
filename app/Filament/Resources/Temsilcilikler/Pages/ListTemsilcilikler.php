<?php

declare(strict_types=1);

namespace App\Filament\Resources\Temsilcilikler\Pages;

use App\Filament\Resources\Temsilcilikler\TemsilcilikResource;
use App\Filament\Resources\Temsilcilikler\Widgets\TemsilciliklerStatsWidget;
use App\Models\Temsilcilik;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ListTemsilcilikler extends ListRecords
{
    protected static string $resource = TemsilcilikResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TemsilciliklerStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiKapsamRaporu')
                ->label('AI Ağ Denetimi')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('gray')
                ->modalHeading('Temsilcilik Ağı & Kapsam Denetim Raporu')
                ->modalDescription(function (): HtmlString {
                    $toplam = Temsilcilik::query()->count();
                    $aktif = Temsilcilik::query()->where('is_active', true)->count();
                    $koordinatsiz = Temsilcilik::query()->whereNull('latitude')->orWhereNull('longitude')->count();
                    $islemsiz = Temsilcilik::query()->doesntHave('islemler')->count();
                    $resmiAdresEksik = Temsilcilik::query()->whereNull('resmi_url')->orWhere('resmi_url', '')->count();

                    return new HtmlString(
                        "<div class='space-y-3 text-sm p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'>"
                        ."<div class='font-semibold text-base text-gray-900 dark:text-gray-100 flex items-center gap-2'>"
                        .'<span>Diplomatik Temsilcilik Ağı Sağlık Durumu</span>'
                        .'</div>'
                        ."<div class='grid grid-cols-2 gap-2 text-xs pt-1'>"
                        ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800'><strong>Toplam / Aktif:</strong> {$aktif} / {$toplam}</div>"
                        ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800'><strong>GPS Haritası Eksik:</strong> <span class='font-medium text-amber-700 dark:text-amber-400'>{$koordinatsiz} temsilcilik</span></div>"
                        ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800'><strong>İşlem Rehberi Olmayan:</strong> <span class='font-medium text-amber-700 dark:text-amber-400'>{$islemsiz} temsilcilik</span></div>"
                        ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800'><strong>Resmî URL Eksik:</strong> {$resmiAdresEksik} temsilcilik</div>"
                        .'</div>'
                        ."<div class='text-xs text-gray-600 dark:text-gray-300 pt-2 border-t border-gray-200 dark:border-gray-700 leading-relaxed'>"
                        .'<strong>AI Tavsiyesi:</strong> Harita butonlarının sitede aktif olması için koordinatları eksik temsilciliklere enlem/boylam giriniz. Özel işlem içeriği olmayan temsilciliklerde yönlendirme notunu dolu tutarak ziyaretçileri doğrudan konsolosluk.gov.tr veya resmi MFA portalına yönlendiriniz.'
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            CreateAction::make()
                ->label('Yeni Temsilcilik Ekle')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
