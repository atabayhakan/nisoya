<?php

namespace App\Filament\Resources\Tags\Pages;

use App\Filament\Resources\Listings\ListingResource;
use App\Filament\Resources\Tags\TagResource;
use App\Filament\Resources\Tags\Widgets\TagStatsWidget;
use App\Models\Tag;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ListTags extends ListRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TagStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $toplam = Tag::query()->count();
        $ilanli = Tag::query()->has('listings')->count();
        $bosta = Tag::query()->doesntHave('listings')->count();

        return [
            'hepsi' => Tab::make('Tüm Etiketler')
                ->badge((string) $toplam),

            'ilanli' => Tab::make('İlanı Olanlar')
                ->badge((string) $ilanli)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('listings')),

            'bosta' => Tab::make('Boşta Kalanlar (0 İlan)')
                ->badge($bosta > 0 ? (string) $bosta : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('listings')),

            'populer' => Tab::make('En Çok Kullanılanlar')
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('listings')->withCount('listings')->orderByDesc('listings_count')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiTaksonomiRaporu')
                ->label('AI Taksonomi Raporu')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Etiket Ekosistemi — AI Taksonomi & Trend Analizi')
                ->modalDescription(function (): HtmlString {
                    $toplam = Tag::query()->count();
                    $ilanli = Tag::query()->has('listings')->count();
                    $bosta = Tag::query()->doesntHave('listings')->count();

                    $topEtiketler = Tag::query()
                        ->withCount('listings')
                        ->orderByDesc('listings_count')
                        ->limit(5)
                        ->get();

                    $etiketlerHtml = '';
                    if ($topEtiketler->isEmpty()) {
                        $etiketlerHtml = "<div class='text-xs text-gray-500 dark:text-gray-400 py-2'>Sistemde henüz etiket kaydı bulunmuyor.</div>";
                    } else {
                        foreach ($topEtiketler as $tag) {
                            $etiketlerHtml .= "<div class='flex items-center justify-between text-xs py-1 border-b border-gray-100 dark:border-gray-800'><span>#{$tag->name}</span><span class='font-bold text-gray-700 dark:text-gray-300'>{$tag->listings_count} ilan</span></div>";
                        }
                    }

                    $bosHtml = $bosta > 0
                        ? "<div class='p-2.5 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl text-xs text-amber-800 dark:text-amber-300'><strong>⚠️ Henüz İlanı Olmayan Etiketler ({$bosta}):</strong><p class='mt-1 text-2xs text-amber-700 dark:text-amber-400'>İçeriği olmayan etiketleri silebilir veya ilgili ilanlara bağlayarak arama görünürlüğünü artırabilirsiniz.</p></div>"
                        : "<div class='p-2.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs text-emerald-800 dark:text-emerald-300'><strong>✓ Sağlıklı Taksonomi:</strong> Tanımlı tüm etiketler aktif ilanlara bağlı durumda.</div>";

                    return new HtmlString(
                        "<div class='space-y-3.5 text-sm'>"
                        ."<div class='grid grid-cols-3 gap-2.5 text-center'>"
                        ."<div class='p-3 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-xl'>"
                        ."<div class='text-primary-700 dark:text-primary-400 font-bold text-lg'>{$toplam}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Toplam Etiket</div>"
                        .'</div>'
                        ."<div class='p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl'>"
                        ."<div class='text-emerald-700 dark:text-emerald-400 font-bold text-lg'>{$ilanli}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>İlanlı Etiket</div>"
                        .'</div>'
                        ."<div class='p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl'>"
                        ."<div class='text-amber-700 dark:text-amber-400 font-bold text-lg'>{$bosta}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Boşta Kalan</div>"
                        .'</div>'
                        .'</div>'
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700'>"
                        ."<div class='text-xs font-bold text-gray-900 dark:text-gray-100 mb-1.5'>🔥 En Popüler Etiketler</div>"
                        .$etiketlerHtml
                        .'</div>'
                        .$bosHtml
                        ."<div class='p-3 bg-stone-50 dark:bg-stone-800/80 rounded-xl border border-stone-200 dark:border-stone-700 text-xs text-stone-600 dark:text-stone-300 space-y-1'>"
                        ."<strong class='text-stone-900 dark:text-stone-100'>💡 AI Taksonomi ve Arama Optimizasyonu:</strong>"
                        .'<p>Gurbetçi pazar yerinde en yüksek organik dönüşüm sağlayan etiketler: <code>#usta</code>, <code>#nakliye</code>, <code>#ikinciel</code>, <code>#tercume</code>, <code>#avukat</code>, <code>#doktor</code>, <code>#tamirat</code> ve <code>#temizlik</code>. İlan sahiplerinin bu etiketleri kullanması arama motoru (SEO) trafiğini doğrudan artırır.</p>'
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            Action::make('populerEtiketleriYukle')
                ->label('Popüler Etiketleri Başlat')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Popüler Pazar Yeri Etiketlerini Başlat')
                ->modalDescription('Diaspora pazar yerinde en çok kullanılan 15 temel etiket (Usta, Nakliye, Tercüme, İkinci El, Doktor, vb.) eksikse otomatik eklenecektir. Onaylıyor musunuz?')
                ->action(function (): void {
                    $hazirEtiketler = [
                        'Usta & Tamirat',
                        'Evden Eve Nakliye',
                        'İkinci El Eşya',
                        'Yeminli Tercüme',
                        'Doktor & Sağlık',
                        'Avukat & Hukuk',
                        'Muhasebe & Vergi',
                        'Temizlik Hizmetleri',
                        'Oto Bakım & Onarım',
                        'Kiralık Daire',
                        'Bebek & Çocuk Bakımı',
                        'Tadilat & Boya',
                        'Türkçe Özel Ders',
                        'Düğün & Organizasyon',
                        'Bilişim & Web Hizmetleri',
                    ];

                    $eklenen = 0;
                    foreach ($hazirEtiketler as $etiketAdi) {
                        $slug = Str::slug($etiketAdi);
                        $varMi = Tag::query()->where('slug', $slug)->exists();
                        if (! $varMi) {
                            Tag::query()->create([
                                'name' => $etiketAdi,
                                'slug' => $slug,
                            ]);
                            $eklenen++;
                        }
                    }

                    Notification::make()
                        ->title("{$eklenen} adet popüler etiket başarıyla eklendi.")
                        ->success()
                        ->send();
                }),

            Action::make('ilanlar')
                ->label('Tüm İlanlar')
                ->icon(Heroicon::OutlinedShoppingBag)
                ->color('gray')
                ->url(fn (): string => ListingResource::getUrl('index')),

            CreateAction::make()
                ->label('Yeni Etiket Oluştur')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
