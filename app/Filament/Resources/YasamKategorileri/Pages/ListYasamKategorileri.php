<?php

namespace App\Filament\Resources\YasamKategorileri\Pages;

use App\Filament\Resources\YasamKategorileri\Widgets\YasamKategorileriStatsWidget;
use App\Filament\Resources\YasamKategorileri\YasamKategorisiResource;
use App\Filament\Resources\YasamKonulari\YasamKonusuResource;
use App\Models\YasamKategorisi;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ListYasamKategorileri extends ListRecords
{
    protected static string $resource = YasamKategorisiResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            YasamKategorileriStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiRehberAnalizi')
                ->label('AI Kategori Analizi')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Yaşam Rehberi — AI Kapsam ve Sağlık Analizi')
                ->modalDescription(function (): HtmlString {
                    $kategoriler = YasamKategorisi::withCount('konular')->orderBy('sort_order')->get();
                    $bosKategoriler = $kategoriler->where('konular_count', 0);
                    $doluKategoriler = $kategoriler->where('konular_count', '>', 0);

                    $bosSayisi = $bosKategoriler->count();
                    $doluSayisi = $doluKategoriler->count();

                    return new HtmlString(
                        "<div class='space-y-4 text-sm'>"
                        ."<div class='grid grid-cols-2 gap-3 text-center'>"
                        ."<div class='p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg'>"
                        ."<div class='text-emerald-700 dark:text-emerald-400 font-bold text-xl'>{$doluSayisi}</div>"
                        ."<div class='text-xs text-gray-500 dark:text-gray-400'>İçerikli Kategori</div>"
                        .'</div>'
                        ."<div class='p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg'>"
                        ."<div class='text-amber-700 dark:text-amber-400 font-bold text-xl'>{$bosSayisi}</div>"
                        ."<div class='text-xs text-gray-500 dark:text-gray-400'>Konu Bekleyen Kategori</div>"
                        .'</div>'
                        .'</div>'
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-300 space-y-2'>"
                        ."<strong class='text-gray-900 dark:text-gray-100'>💡 AI İçerik Geliştirme Stratejisi:</strong>"
                        .'<p>Gurbetçilerin yurt dışında en çok ihtiyaç duyduğu Barınma, Sağlık, İş ve Bürokrasi kategorilerinde henüz konu başlığı bulunmuyor. Bu kategorilere soru/başlık ekleyerek rehber kapsamını genişletebilirsiniz.</p>'
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
                ->label('Yeni Kategori Ekle')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
