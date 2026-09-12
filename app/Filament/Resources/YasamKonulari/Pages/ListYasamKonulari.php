<?php

namespace App\Filament\Resources\YasamKonulari\Pages;

use App\Filament\Resources\YasamKategorileri\YasamKategorisiResource;
use App\Filament\Resources\YasamKonuIcerikleri\YasamKonuIcerigiResource;
use App\Filament\Resources\YasamKonulari\Widgets\YasamKonulariStatsWidget;
use App\Filament\Resources\YasamKonulari\YasamKonusuResource;
use App\Models\YasamKategorisi;
use App\Models\YasamKonusu;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ListYasamKonulari extends ListRecords
{
    protected static string $resource = YasamKonusuResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            YasamKonulariStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiKonuAnalizi')
                ->label('AI Kapsam Raporu')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Yaşam Konuları — AI Kapsam ve Dağılım Raporu')
                ->modalDescription(function (): HtmlString {
                    $toplam = YasamKonusu::count();
                    $iceriksiz = YasamKonusu::doesntHave('icerikler')->count();
                    $icerikli = $toplam - $iceriksiz;

                    $kategoriler = YasamKategorisi::withCount('konular')->orderBy('sort_order')->get();
                    $bosKat = $kategoriler->where('konular_count', 0)->pluck('ad')->implode(', ');

                    return new HtmlString(
                        "<div class='space-y-4 text-sm'>"
                        ."<div class='grid grid-cols-2 gap-3 text-center'>"
                        ."<div class='p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg'>"
                        ."<div class='text-emerald-700 dark:text-emerald-400 font-bold text-xl'>{$icerikli}</div>"
                        ."<div class='text-xs text-gray-500 dark:text-gray-400'>Ülke İçeriği Olan Konu</div>"
                        .'</div>'
                        ."<div class='p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg'>"
                        ."<div class='text-amber-700 dark:text-amber-400 font-bold text-xl'>{$iceriksiz}</div>"
                        ."<div class='text-xs text-gray-500 dark:text-gray-400'>İçerik Bekleyen Konu</div>"
                        .'</div>'
                        .'</div>'
                        .($bosKat ? "<div class='p-3 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded text-xs text-rose-800 dark:text-rose-300'><strong>⚠️ Konusu Olmayan Kategoriler:</strong> {$bosKat}</div>" : '')
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-300 space-y-1.5'>"
                        ."<strong class='text-gray-900 dark:text-gray-100'>💡 AI Tavsiyesi:</strong>"
                        ."<p>Her konu başlığı, farklı ülkelerdeki vatandaşlar için (Almanya, Hollanda, Avusturya vb.) yerelleştirilmiş 'Yaşam Konu İçeriği' dökümanlarına bağlanır. Konu tanımlandıktan sonra ilgili ülkelere özel başvuru adımlarını eklemeyi unutmayınız.</p>"
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            Action::make('iceriklereGit')
                ->label('Ülke İçerikleri')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->url(fn (): string => YasamKonuIcerigiResource::getUrl('index')),

            Action::make('kategorilereGit')
                ->label('Kategoriler')
                ->icon(Heroicon::OutlinedSquares2x2)
                ->color('gray')
                ->url(fn (): string => YasamKategorisiResource::getUrl('index')),

            CreateAction::make()
                ->label('Yeni Yaşam Konusu Ekle')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
