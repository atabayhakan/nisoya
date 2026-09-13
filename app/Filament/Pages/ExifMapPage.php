<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\ListingImages\ListingImageResource;
use App\Filament\Widgets\ExifMapStatsWidget;
use App\Models\ListingImage;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Admin için EXIF Haritası.
 * GPS koordinatı içeren tüm görselleri Leaflet haritasında gösterir.
 * Marker'lar üzerinde görsel önizleme + ilan + kullanıcı bilgisi.
 * Cluster view ile duplicate tespiti (aynı yerde çok sayıda görsel).
 */
class ExifMapPage extends Page
{
    use RestrictsToAdmins;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|\UnitEnum|null $navigationGroup = 'Pazaryeri & Ticaret';

    protected static ?string $navigationLabel = 'EXIF Haritası';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.exif-map';

    public function getTitle(): string
    {
        return 'EXIF Coğrafi İstihbarat Haritası';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ListingImage::query()->withGps()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public function getHeading(): string|Htmlable
    {
        return 'EXIF Coğrafi İstihbarat Haritası';
    }

    public function getSubheading(): ?string
    {
        $count = ListingImage::query()->withGps()->count();
        $clusters = ListingImage::query()->withGps()
            ->selectRaw('COUNT(*) as cnt, ROUND(gps_lat, 2) as lat, ROUND(gps_lng, 2) as lng')
            ->groupBy('lat', 'lng')
            ->havingRaw('cnt > 1')
            ->get();

        return sprintf(
            '%d GPS koordinatlı görsel tespit edildi · %d şüpheli noktada kümelenme analizi yapıldı',
            $count,
            $clusters->count()
        );
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ExifMapStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiCografiAnaliz')
                ->label('AI Coğrafi İstihbarat')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Coğrafi İstihbarat & Kopya İlan Analizi')
                ->modalDescription(function (): HtmlString {
                    $count = ListingImage::query()->withGps()->count();
                    $sensitive = ListingImage::query()->where('has_sensitive_exif', true)->count();
                    $resolved = ListingImage::query()->whereNotNull('reverse_city')->count();

                    $clusters = DB::table('listing_images')
                        ->whereNotNull('gps_lat')
                        ->whereNotNull('gps_lng')
                        ->selectRaw('COUNT(*) as cnt, ROUND(gps_lat, 2) as lat, ROUND(gps_lng, 2) as lng')
                        ->groupBy('lat', 'lng')
                        ->havingRaw('cnt > 1')
                        ->orderByDesc('cnt')
                        ->limit(5)
                        ->get();

                    $clusterRows = '';
                    if ($clusters->isEmpty()) {
                        $clusterRows = "<div class='text-xs text-gray-500 py-1'>Şu an tespit edilen yoğunlaşmış şüpheli küme bulunmuyor.</div>";
                    } else {
                        foreach ($clusters as $c) {
                            $clusterRows .= "<div class='flex justify-between items-center text-xs py-1 border-b border-gray-100 dark:border-gray-800'><span>📍 Koordinat: {$c->lat}, {$c->lng}</span><span class='font-bold text-amber-600'>{$c->cnt} görsel</span></div>";
                        }
                    }

                    return new HtmlString(
                        "<div class='space-y-3.5 text-sm'>"
                        ."<div class='grid grid-cols-3 gap-2.5 text-center'>"
                        ."<div class='p-3 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-xl'>"
                        ."<div class='text-primary-700 dark:text-primary-400 font-bold text-lg'>{$count}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>GPS'li Görsel</div>"
                        .'</div>'
                        ."<div class='p-3 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded-xl'>"
                        ."<div class='text-rose-700 dark:text-rose-400 font-bold text-lg'>{$sensitive}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Hassas EXIF</div>"
                        .'</div>'
                        ."<div class='p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl'>"
                        ."<div class='text-emerald-700 dark:text-emerald-400 font-bold text-lg'>{$resolved}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Çözümlenen Şehir</div>"
                        .'</div>'
                        .'</div>'
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700'>"
                        ."<div class='text-xs font-bold text-gray-900 dark:text-gray-100 mb-1.5'>🔍 En Yoğun 5 Coğrafi Küme (Spam/Bot Tespiti)</div>"
                        .$clusterRows
                        .'</div>'
                        ."<div class='p-3 bg-stone-50 dark:bg-stone-800/80 rounded-xl border border-stone-200 dark:border-stone-700 text-xs text-stone-600 dark:text-stone-300 space-y-1'>"
                        ."<strong class='text-stone-900 dark:text-stone-100'>🛡️ Pazar Yeri Güvenlik Kriteri:</strong>"
                        .'<p>Farklı kullanıcıların aynı bina/koordinat kümesinden ilan açması organize dolandırıcılık veya sahte kiralık emlak çetelerine işaret edebilir. Şüpheli durumlarda ilan sahibinin profili ve diğer görselleri incelenmelidir.</p>'
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            Action::make('topluGeocode')
                ->label('Konumları Çözümle')
                ->icon('heroicon-o-map-pin')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('GPS Koordinatlarını Şehirlere Çözümle')
                ->modalDescription('Henüz şehir ve ülke bilgisi çıkarılmamış GPS koordinatları için arka planda Nominatim reverse geocode işlemi başlatılsın mı?')
                ->action(function (): void {
                    Artisan::call('images:reverse-geocode');
                    Notification::make()
                        ->title('Ters kodlama işlemi arka planda başlatıldı.')
                        ->info()
                        ->send();
                }),

            Action::make('gorseller')
                ->label('Tüm Görseller')
                ->icon(Heroicon::OutlinedPhoto)
                ->color('gray')
                ->url(fn (): string => ListingImageResource::getUrl('index')),
        ];
    }
}
