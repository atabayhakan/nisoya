<?php

namespace App\Filament\Resources\YasamKonuIcerikleri\Pages;

use App\Filament\Resources\YasamKonuIcerikleri\Widgets\YasamKonuIcerikleriStatsWidget;
use App\Filament\Resources\YasamKonuIcerikleri\YasamKonuIcerigiResource;
use App\Filament\Resources\YasamKonulari\YasamKonusuResource;
use App\Models\YasamKonuIcerigi;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ListYasamKonuIcerikleri extends ListRecords
{
    protected static string $resource = YasamKonuIcerigiResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            YasamKonuIcerikleriStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiDogrulamaRaporu')
                ->label('AI Tazelik Raporu')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Yaşam Rehberi — AI Doğruluk & Tazelik Raporu')
                ->modalDescription(function (): HtmlString {
                    $toplam = YasamKonuIcerigi::count();
                    $yayinda = YasamKonuIcerigi::where('status', YasamKonuIcerigi::STATUS_YAYIN)->count();
                    $taslak = YasamKonuIcerigi::where('status', YasamKonuIcerigi::STATUS_TASLAK)->count();
                    $bayat = YasamKonuIcerigi::bayat()->count();
                    $kaynaksiz = YasamKonuIcerigi::whereNull('kaynak_url')->orWhere('kaynak_url', '')->count();

                    return new HtmlString(
                        "<div class='space-y-4 text-sm'>"
                        ."<div class='grid grid-cols-3 gap-2 text-center'>"
                        ."<div class='p-2 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded'><div class='text-emerald-700 dark:text-emerald-400 font-bold text-lg'>{$yayinda}</div><div class='text-xs text-gray-500 dark:text-gray-400'>Yayında</div></div>"
                        ."<div class='p-2 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded'><div class='text-amber-700 dark:text-amber-400 font-bold text-lg'>{$taslak}</div><div class='text-xs text-gray-500 dark:text-gray-400'>Taslak</div></div>"
                        ."<div class='p-2 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded'><div class='text-rose-700 dark:text-rose-400 font-bold text-lg'>{$bayat}</div><div class='text-xs text-gray-500 dark:text-gray-400'>Bayat (>90 gün)</div></div>"
                        .'</div>'
                        .($kaynaksiz > 0 ? "<div class='p-2 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded text-xs text-rose-800 dark:text-rose-300'><strong>⚠️ Kaynaksız İçerik Uyarısı:</strong> {$kaynaksiz} adet içerikte resmî kaynak linki bulunmuyor.</div>" : '')
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-300 space-y-1.5'>"
                        ."<strong class='text-gray-900 dark:text-gray-100'>💡 AI Doğrulama Kuralı (K7):</strong>"
                        ."<p>Yaşam rehberi içerikleri resmi kaynak adresi olmadan yayına alınamaz. Doğrulama tarihi 90 günü geçen içerikler Kâhya paneline 'bayat rehber' uyarısı olarak yansır.</p>"
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            Action::make('konularaGit')
                ->label('Tüm Konular')
                ->icon(Heroicon::OutlinedQuestionMarkCircle)
                ->color('gray')
                ->url(fn (): string => YasamKonusuResource::getUrl('index')),

            CreateAction::make()
                ->label('Yeni Konu İçeriği Ekle')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
